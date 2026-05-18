'use strict';

const { Umzug, SequelizeStorage } = require('umzug');
const { Pool }  = require('pg');
const path      = require('path');
const fs        = require('fs');

// ─── Custom storage: grava estado no PostgreSQL ───────────────────────────────
class PgStorage {
  constructor(pool, table = 'schema_migrations') {
    this.pool  = pool;
    this.table = table;
  }

  async _ensureTable() {
    await this.pool.query(`
      CREATE TABLE IF NOT EXISTS ${this.table} (
        name       TEXT        PRIMARY KEY,
        run_at     TIMESTAMPTZ NOT NULL DEFAULT NOW()
      )
    `);
  }

  async logMigration({ name }) {
    await this._ensureTable();
    await this.pool.query(`INSERT INTO ${this.table} (name) VALUES ($1) ON CONFLICT DO NOTHING`, [name]);
  }

  async unlogMigration({ name }) {
    await this._ensureTable();
    await this.pool.query(`DELETE FROM ${this.table} WHERE name = $1`, [name]);
  }

  async executed() {
    await this._ensureTable();
    const r = await this.pool.query(`SELECT name FROM ${this.table} ORDER BY name`);
    return r.rows.map(row => row.name);
  }
}

// ─── Resolve caminho das migrações ───────────────────────────────────────────
// Tenta: r-observe/migrations (monorepo) → ../migrations (relativo ao src/)
function findMigrationsDir() {
  const candidates = [
    path.resolve(__dirname, '../migrations'),         // container: /app/migrations
    path.resolve(__dirname, '../../../migrations'),   // monorepo: r-observe/migrations
    path.resolve(__dirname, '../../migrations'),      // alternativo
  ];
  for (const dir of candidates) {
    if (fs.existsSync(dir)) return dir;
  }
  throw new Error('Diretório de migrações não encontrado (buscado em: ' + candidates.join(', ') + ')');
}

// ─── Cria e executa o Umzug ───────────────────────────────────────────────────
async function runMigrations(pool) {
  const migrationsDir = findMigrationsDir();

  const umzug = new Umzug({
    migrations: {
      glob:    path.join(migrationsDir, '*.sql'),
      resolve: ({ name, path: filePath }) => ({
        name,
        up: async () => {
          const sql = fs.readFileSync(filePath, 'utf8');
          await pool.query(sql);
        },
        down: async () => {
          // Down migrations não são suportadas neste stack (SQL DDL idempotente)
          console.warn(`[umzug] down não implementado para: ${name}`);
        },
      }),
    },
    storage:  new PgStorage(pool),
    logger:   {
      info:  ({ event, name }) => console.log(JSON.stringify({ level: 'info',  service: 'umzug', msg: event, migration: name, ts: new Date().toISOString() })),
      warn:  ({ event, name }) => console.log(JSON.stringify({ level: 'warn',  service: 'umzug', msg: event, migration: name, ts: new Date().toISOString() })),
      error: ({ event, name }) => console.log(JSON.stringify({ level: 'error', service: 'umzug', msg: event, migration: name, ts: new Date().toISOString() })),
      debug: () => {},
    },
  });

  const pending = await umzug.pending();
  if (pending.length === 0) {
    console.log(JSON.stringify({ level: 'info', service: 'umzug', msg: 'No pending migrations', ts: new Date().toISOString() }));
    return;
  }

  console.log(JSON.stringify({ level: 'info', service: 'umzug', msg: `Running ${pending.length} migration(s)`, ts: new Date().toISOString() }));
  await umzug.up();
}

// ─── CLI: node migrate.js [up|pending|list] ───────────────────────────────────
if (require.main === module) {
  const pool = new Pool({
    host:     process.env.DB_HOST,
    port:     parseInt(process.env.DB_PORT || '5432', 10),
    database: process.env.DB_NAME,
    user:     process.env.DB_USER,
    password: process.env.DB_PASSWORD,
  });

  const cmd = process.argv[2] || 'up';

  (async () => {
    try {
      await runMigrations(pool);
    } finally {
      await pool.end();
    }
  })().catch(e => { console.error(e.message); process.exit(1); });
}

module.exports = { runMigrations };
