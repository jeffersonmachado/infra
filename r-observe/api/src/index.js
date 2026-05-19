'use strict';

const path = require('path');
const express = require('express');
const helmet = require('helmet');
const cors = require('cors');
const rateLimit = require('express-rate-limit');
const { Pool } = require('pg');
const Redis = require('ioredis');
const client = require('prom-client');
const { v4: uuidv4 } = require('uuid');
const icinga = require('./icinga');

// ─── Config ──────────────────────────────────────────────────────────────────
const PORT = parseInt(process.env.PORT || '3000', 10);
const BASE = process.env.BASE_PATH || '/observe/api';
const LOG_LEVEL = process.env.LOG_LEVEL || 'info';
const INTERNAL_TOKEN = process.env.INTERNAL_TOKEN || '';

// ─── Logger ───────────────────────────────────────────────────────────────────
function log(level, msg, extra = {}) {
  if (level === 'debug' && LOG_LEVEL !== 'debug') return;
  process.stdout.write(JSON.stringify({ level, service: 'r-observe-api', msg, ts: new Date().toISOString(), ...extra }) + '\n');
}

// ─── Metrics ─────────────────────────────────────────────────────────────────
const register = new client.Registry();
client.collectDefaultMetrics({ register });
const httpRequestsTotal = new client.Counter({
  name: 'r_observe_api_http_requests_total',
  help: 'Total HTTP requests',
  labelNames: ['method', 'route', 'status'],
  registers: [register],
});
const eventsReceivedTotal = new client.Counter({
  name: 'r_observe_events_received_total',
  help: 'Total events received',
  labelNames: ['source'],
  registers: [register],
});

// ─── Database ────────────────────────────────────────────────────────────────
const db = new Pool({
  host: process.env.DB_HOST,
  port: parseInt(process.env.DB_PORT || '5432', 10),
  database: process.env.DB_NAME,
  user: process.env.DB_USER,
  password: process.env.DB_PASSWORD,
  max: 10,
  idleTimeoutMillis: 30000,
  connectionTimeoutMillis: 5000,
});

db.on('error', (err) => log('error', 'Database pool error', { err: err.message }));

// ─── Redis ────────────────────────────────────────────────────────────────────
const redis = process.env.REDIS_URL
  ? new Redis(process.env.REDIS_URL, { lazyConnect: true, maxRetriesPerRequest: 3, enableReadyCheck: true })
  : null;

if (redis) {
  redis.on('error', (err) => log('error', 'Redis error', { err: err.message }));
  redis.connect().catch((e) => log('warn', 'Redis initial connect failed', { err: e.message }));
}

// ─── Express ──────────────────────────────────────────────────────────────────
const app = express();
app.set('trust proxy', 1);
app.use('/observe/api/ui', express.static(path.join(__dirname, '../public')));
app.use(helmet({ contentSecurityPolicy: false }));
app.use(cors({
  origin: process.env.CORS_ORIGINS ? process.env.CORS_ORIGINS.split(',').map(s => s.trim()) : false,
  methods: ['GET', 'POST'],
  allowedHeaders: ['Content-Type', 'Authorization', 'x-internal-token'],
}));
app.use(express.json({ limit: '512kb' }));

// ─── Middleware: request logging + metrics ────────────────────────────────────
app.use((req, res, next) => {
  res.on('finish', () => {
    httpRequestsTotal.inc({ method: req.method, route: req.path, status: String(res.statusCode) });
    if (LOG_LEVEL === 'debug') log('debug', 'request', { method: req.method, path: req.path, status: res.statusCode });
  });
  next();
});

// ─── Auth middleware ──────────────────────────────────────────────────────────
const requireAuth = (req, res, next) => {
  if (!INTERNAL_TOKEN) return next(); // sem token configurado = dev mode
  const provided = req.headers['x-internal-token'] ||
    (req.headers['authorization'] || '').replace(/^Bearer\s+/i, '');
  if (provided !== INTERNAL_TOKEN) {
    return res.status(401).json({ error: 'Unauthorized' });
  }
  next();
};

// ─── Rate limiters ────────────────────────────────────────────────────────────
const eventLimiter = rateLimit({ windowMs: 60_000, max: 100, standardHeaders: true, legacyHeaders: false });
const apiLimiter  = rateLimit({ windowMs: 60_000, max: 300, standardHeaders: true, legacyHeaders: false });

// ─── Helper: proxy para o serviço AI ─────────────────────────────────────────
async function proxyToAI(path, options = {}) {
  const aiUrl = process.env.AI_SERVICE_URL || 'http://observe-ai:3000';
  const timeout = parseInt(process.env.AI_TIMEOUT_MS || '30000', 10);
  const ctrl = new AbortController();
  const timer = setTimeout(() => ctrl.abort(), timeout);
  try {
    const resp = await fetch(`${aiUrl}${path}`, {
      ...options,
      headers: { 'Content-Type': 'application/json', 'x-internal-token': INTERNAL_TOKEN, ...(options.headers || {}) },
      signal: ctrl.signal,
    });
    return { ok: true, status: resp.status, data: await resp.json() };
  } catch (e) {
    if (e.name === 'AbortError') return { ok: false, status: 504, data: { error: 'AI service timeout' } };
    return { ok: false, status: 502, data: { error: 'AI service unavailable', detail: e.message } };
  } finally {
    clearTimeout(timer);
  }
}

async function proxyToDiscovery(path, options = {}) {
  const svc = process.env.DISCOVERY_SERVICE_URL || 'http://observe-discovery:3010';
  const timeout = parseInt(process.env.DISCOVERY_TIMEOUT_MS || '30000', 10);
  const ctrl = new AbortController();
  const timer = setTimeout(() => ctrl.abort(), timeout);
  try {
    const resp = await fetch(`${svc}${path}`, {
      ...options,
      headers: { 'Content-Type': 'application/json', 'x-internal-token': INTERNAL_TOKEN, ...(options.headers || {}) },
      signal: ctrl.signal,
    });
    const text = await resp.text();
    let data;
    try { data = JSON.parse(text); } catch { data = { raw: text }; }
    return { ok: true, status: resp.status, data };
  } catch (e) {
    if (e.name === 'AbortError') return { ok: false, status: 504, data: { error: 'Discovery timeout' } };
    return { ok: false, status: 502, data: { error: 'Discovery service unavailable', detail: e.message } };
  } finally {
    clearTimeout(timer);
  }
}

// ─── Routes ───────────────────────────────────────────────────────────────────

// Health
app.get(`${BASE}/health`, (_req, res) => {
  res.json({ status: 'ok', service: 'r-observe-api', version: '0.1.0' });
});

// Prometheus metrics
app.get(`${BASE}/metrics`, async (_req, res) => {
  res.set('Content-Type', register.contentType);
  res.end(await register.metrics());
});

// Status — verifica conectividade com DB e Redis
app.get(`${BASE}/status`, requireAuth, async (_req, res) => {
  const components = { api: 'ok', db: 'unknown', redis: 'unknown' };
  try { await db.query('SELECT 1'); components.db = 'ok'; }
  catch (e) { components.db = 'error'; log('warn', 'DB status check failed', { err: e.message }); }
  try {
    if (redis) { await redis.ping(); components.redis = 'ok'; }
    else { components.redis = 'disabled'; }
  } catch (e) { components.redis = 'error'; log('warn', 'Redis status check failed', { err: e.message }); }
  const healthy = components.db === 'ok';
  res.status(healthy ? 200 : 503).json({ status: healthy ? 'ok' : 'degraded', components });
});

// Hosts — listagem
app.get(`${BASE}/hosts`, requireAuth, apiLimiter, async (_req, res) => {
  try {
    const r = await db.query('SELECT * FROM observe_hosts ORDER BY updated_at DESC LIMIT 200');
    res.json({ hosts: r.rows, total: r.rowCount });
  } catch (e) { res.status(500).json({ error: e.message }); }
});

// Hosts — host individual
app.get(`${BASE}/hosts/:name`, requireAuth, async (req, res) => {
  try {
    const r = await db.query('SELECT * FROM observe_hosts WHERE name = $1', [req.params.name]);
    if (!r.rowCount) return res.status(404).json({ error: 'Host não encontrado' });
    res.json({ host: r.rows[0] });
  } catch (e) { res.status(500).json({ error: e.message }); }
});

// Hosts — criação/upsert manual (registra em DB + Icinga2)
app.post(`${BASE}/hosts`, requireAuth, async (req, res) => {
  const { name, address, display_name, vars } = req.body;
  if (!name || !address) return res.status(400).json({ error: '"name" e "address" são obrigatórios' });
  if (!/^[a-zA-Z0-9][a-zA-Z0-9_.-]{0,127}$/.test(name))
    return res.status(400).json({ error: 'name inválido (use apenas letras, números, hífens, pontos)' });
  try {
    // Upsert no banco
    const r = await db.query(
      `INSERT INTO observe_hosts (id, name, address, metadata, updated_at)
       VALUES ($1, $2, $3, $4, NOW())
       ON CONFLICT (name)
       DO UPDATE SET address = EXCLUDED.address, metadata = EXCLUDED.metadata, updated_at = NOW()
       RETURNING *`,
      [uuidv4(), name, address, JSON.stringify({ source: 'manual', display_name, vars })]
    );
    // Sincroniza com Icinga2 (não-fatal: falha no Icinga não impede resposta)
    let icingaResult = { ok: false, existed: false, error: 'unreachable' };
    try {
      icingaResult = await icinga.registerHost(name, address, display_name || name, vars || {});
    } catch (e) {
      icingaResult.error = e.message;
    }
    log('info', 'Host registered', { name, address, icinga_ok: icingaResult.ok });
    res.status(201).json({ host: r.rows[0], icinga: { ok: icingaResult.ok, existed: icingaResult.existed, error: icingaResult.error } });
  } catch (e) { res.status(500).json({ error: e.message }); }
});

// Inventory — Docker local agent bulk upsert
app.post(`${BASE}/inventory/docker`, requireAuth, async (req, res) => {
  const { agent_id, host, address, containers } = req.body || {};
  const hostName = host || agent_id;
  if (!hostName) return res.status(400).json({ error: '"agent_id" ou "host" é obrigatório' });
  if (!Array.isArray(containers)) return res.status(400).json({ error: '"containers" deve ser array' });
  if (!/^[a-zA-Z0-9][a-zA-Z0-9_.-]{0,127}$/.test(hostName))
    return res.status(400).json({ error: 'host inválido (use apenas letras, números, hífens, pontos)' });

  const client = await db.connect();
  try {
    await client.query('BEGIN');
    const hostResult = await client.query(
      `INSERT INTO observe_hosts (id, name, address, status, last_check, metadata, updated_at)
       VALUES ($1, $2, $3, 'up', NOW(), $4, NOW())
       ON CONFLICT (name)
       DO UPDATE SET address = EXCLUDED.address,
                     status = 'up',
                     last_check = NOW(),
                     metadata = EXCLUDED.metadata,
                     updated_at = NOW()
       RETURNING id`,
      [uuidv4(), hostName, address || null, JSON.stringify({
        source: 'docker-agent',
        agent_id: agent_id || hostName,
        container_count: containers.length,
      })]
    );
    const hostId = hostResult.rows[0].id;

    let upserted = 0;
    for (const c of containers) {
      if (!c || !c.name) continue;
      const status = c.ignored
        ? 'up'
        : c.health === 'unhealthy'
        ? 'critical'
        : (c.state === 'running' ? 'up' : 'warning');
      await client.query(
        `INSERT INTO observe_services (id, host_id, name, status, last_check, output, metadata, updated_at)
         VALUES ($1, $2, $3, $4, NOW(), $5, $6, NOW())
         ON CONFLICT (host_id, name)
         DO UPDATE SET status = EXCLUDED.status,
                       last_check = NOW(),
                       output = EXCLUDED.output,
                       metadata = EXCLUDED.metadata,
                       updated_at = NOW()`,
        [
          uuidv4(),
          hostId,
          c.name,
          status,
          c.status || c.state || '',
          JSON.stringify({
            source: 'docker-agent',
            container_id: c.id,
            image: c.image,
            state: c.state,
            status: c.status,
            health: c.health,
            ignored: !!c.ignored,
            compose_project: c.compose_project,
            compose_service: c.compose_service,
            ports: c.ports || [],
            networks: c.networks || {},
          }),
        ]
      );
      upserted++;
    }

    await client.query('COMMIT');
    log('info', 'Docker inventory synced', { host: hostName, services: upserted });
    res.status(202).json({ accepted: true, host: hostName, services: upserted });
  } catch (e) {
    await client.query('ROLLBACK').catch(() => {});
    res.status(500).json({ error: e.message });
  } finally {
    client.release();
  }
});

// Hosts — remoção (remove de DB + Icinga2)
app.delete(`${BASE}/hosts/:name`, requireAuth, async (req, res) => {
  const { name } = req.params;
  try {
    const r = await db.query('DELETE FROM observe_hosts WHERE name = $1 RETURNING id', [name]);
    if (!r.rowCount) return res.status(404).json({ error: 'Host não encontrado' });
    let icingaResult = { ok: false, error: 'unreachable' };
    try {
      icingaResult = await icinga.removeHost(name);
    } catch (e) {
      icingaResult.error = e.message;
    }
    log('info', 'Host removed', { name, icinga_ok: icingaResult.ok });
    res.json({ removed: true, name, icinga: { ok: icingaResult.ok, error: icingaResult.error } });
  } catch (e) { res.status(500).json({ error: e.message }); }
});

// Hosts — dispara scan de rede (enfileira para o agente/script externo processar)
app.post(`${BASE}/hosts/scan`, requireAuth, async (req, res) => {
  const { subnet } = req.body;
  const task = JSON.stringify({ type: 'scan:requested', subnet: subnet || null, requested_at: new Date().toISOString() });
  try {
    if (redis) await redis.rpush('observe:scan:network', task);
    log('info', 'Network scan requested', { subnet: subnet || 'auto' });
    res.status(202).json({
      accepted: true,
      subnet:   subnet || 'auto-detect',
      message:  'Scan enfileirado. Execute: npm run observe:discover -- --mode r-observe' + (subnet ? ` --subnet ${subnet}` : ''),
    });
  } catch (e) { res.status(500).json({ error: e.message }); }
});

// Discovery compatibility endpoints
function discoveryQuery(req) {
  const p = new URLSearchParams();
  if (req.query.tenant_id) p.set('tenant_id', String(req.query.tenant_id));
  if (req.query.site_id) p.set('site_id', String(req.query.site_id));
  if (req.query.edge_id) p.set('edge_id', String(req.query.edge_id));
  if (req.query.run_id) p.set('run_id', String(req.query.run_id));
  if (req.query.asset_id) p.set('asset_id', String(req.query.asset_id));
  const q = p.toString();
  return q ? `?${q}` : '';
}

app.get(`${BASE}/discovery/runs`, requireAuth, async (_req, res) => {
  const out = await proxyToDiscovery(`/api/discovery/runs${discoveryQuery(_req)}`);
  res.status(out.status).json(out.data);
});

app.get(`${BASE}/discovery/assets`, requireAuth, async (req, res) => {
  const out = await proxyToDiscovery(`/api/discovery/assets${discoveryQuery(req)}`);
  res.status(out.status).json(out.data);
});

app.get(`${BASE}/discovery/findings`, requireAuth, async (req, res) => {
  const out = await proxyToDiscovery(`/api/discovery/findings${discoveryQuery(req)}`);
  res.status(out.status).json(out.data);
});

app.get(`${BASE}/discovery/topology`, requireAuth, async (req, res) => {
  const out = await proxyToDiscovery(`/api/discovery/topology${discoveryQuery(req)}`);
  res.status(out.status).json(out.data);
});

app.get(`${BASE}/discovery/fingerprints`, requireAuth, async (req, res) => {
  const out = await proxyToDiscovery(`/api/discovery/fingerprints${discoveryQuery(req)}`);
  res.status(out.status).json(out.data);
});

app.get(`${BASE}/discovery/policies`, requireAuth, async (req, res) => {
  const out = await proxyToDiscovery(`/api/discovery/policies${discoveryQuery(req)}`);
  res.status(out.status).json(out.data);
});

app.post(`${BASE}/discovery/policies`, requireAuth, async (req, res) => {
  const out = await proxyToDiscovery('/api/discovery/policies', { method: 'POST', body: JSON.stringify(req.body || {}) });
  res.status(out.status).json(out.data);
});

app.post(`${BASE}/discovery/scan`, requireAuth, async (req, res) => {
  const out = await proxyToDiscovery('/api/discovery/scan', { method: 'POST', body: JSON.stringify(req.body || {}) });
  res.status(out.status).json(out.data);
});

app.get(`${BASE}/discovery/history`, requireAuth, async (req, res) => {
  const out = await proxyToDiscovery(`/api/discovery/history${discoveryQuery(req)}`);
  res.status(out.status).json(out.data);
});

// Hosts — lista hosts do Icinga2 diretamente
app.get(`${BASE}/hosts/icinga/list`, requireAuth, async (_req, res) => {
  try {
    const hosts = await icinga.listHosts();
    res.json({ hosts, total: hosts.length });
  } catch (e) { res.status(500).json({ error: e.message }); }
});

// Services
app.get(`${BASE}/services`, requireAuth, apiLimiter, async (_req, res) => {
  try {
    const r = await db.query('SELECT * FROM observe_services ORDER BY updated_at DESC LIMIT 200');
    res.json({ services: r.rows, total: r.rowCount });
  } catch (e) { res.status(500).json({ error: e.message }); }
});

// Incidents
app.get(`${BASE}/incidents`, requireAuth, apiLimiter, async (_req, res) => {
  try {
    const r = await db.query('SELECT * FROM observe_incidents ORDER BY created_at DESC LIMIT 50');
    res.json({ incidents: r.rows, total: r.rowCount });
  } catch (e) { res.status(500).json({ error: e.message }); }
});

app.get(`${BASE}/incidents/:id`, requireAuth, async (req, res) => {
  try {
    const r = await db.query('SELECT * FROM observe_incidents WHERE id = $1', [req.params.id]);
    if (!r.rowCount) return res.status(404).json({ error: 'Not found' });
    const incident = r.rows[0];
    const events = await db.query(
      'SELECT * FROM observe_incident_events WHERE incident_id = $1 ORDER BY occurred_at ASC',
      [req.params.id]
    );
    res.json({ incident, timeline: events.rows });
  } catch (e) { res.status(500).json({ error: e.message }); }
});

// Events (generic)
app.post(`${BASE}/events`, requireAuth, eventLimiter, async (req, res) => {
  const event = req.body;
  if (!event || !event.type) return res.status(400).json({ error: 'event.type is required' });
  try {
    eventsReceivedTotal.inc({ source: event.source || 'generic' });
    if (redis) await redis.rpush('observe:events', JSON.stringify({ ...event, receivedAt: new Date().toISOString() }));
    res.status(202).json({ accepted: true, id: uuidv4() });
  } catch (e) { res.status(500).json({ error: e.message }); }
});

// Icinga events
app.post(`${BASE}/icinga/events`, requireAuth, eventLimiter, async (req, res) => {
  const event = req.body;
  if (!event) return res.status(400).json({ error: 'Body obrigatório' });
  try {
    eventsReceivedTotal.inc({ source: 'icinga' });
    const payload = JSON.stringify({ ...event, source: 'icinga', receivedAt: new Date().toISOString() });
    if (redis) await redis.rpush('observe:events:icinga', payload);
    res.status(202).json({ accepted: true, id: uuidv4() });
  } catch (e) { res.status(500).json({ error: e.message }); }
});

// AI explain (proxy para observe-ai)
app.post(`${BASE}/ai/explain`, requireAuth, async (req, res) => {
  if (process.env.AI_ENABLED !== 'true') {
    return res.status(503).json({ error: 'AI service disabled' });
  }
  const { incident, context } = req.body;
  if (!incident) return res.status(400).json({ error: 'incident é obrigatório' });
  const result = await proxyToAI('/ai/explain', { method: 'POST', body: JSON.stringify({ incident, context }) });
  res.status(result.status).json(result.data);
});

// AI models — lista modelos do provider (real ou fallback estático)
app.get(`${BASE}/ai/models`, requireAuth, async (req, res) => {
  const qs = req.query.provider ? `?provider=${encodeURIComponent(req.query.provider)}` : '';
  const result = await proxyToAI(`/ai/models${qs}`);
  res.status(result.status).json(result.data);
});

// AI settings — lê configuração atual do provider
app.get(`${BASE}/ai/settings`, requireAuth, async (_req, res) => {
  const result = await proxyToAI('/ai/settings');
  res.status(result.status).json(result.data);
});

// AI settings — atualiza provider/model/api_key em runtime
app.post(`${BASE}/ai/settings`, requireAuth, async (req, res) => {
  const result = await proxyToAI('/ai/settings', { method: 'POST', body: JSON.stringify(req.body) });
  res.status(result.status).json(result.data);
});

// Remediation request (manual)
app.post(`${BASE}/remediation/request`, requireAuth, async (req, res) => {
  const { incident_id, action, params, reason } = req.body;
  if (!incident_id || !action) return res.status(400).json({ error: 'incident_id e action são obrigatórios' });
  try {
    const r = await db.query(
      `INSERT INTO observe_remediations
         (incident_id, action, params, reason, status, confidence_score, requested_at, updated_at)
       VALUES ($1, $2, $3, $4, 'pending_approval', NULL, NOW(), NOW()) RETURNING *`,
      [incident_id, action, JSON.stringify(params || {}), reason || '']
    );
    log('info', 'Remediation requested', { incident_id, action });
    res.status(202).json({ remediation: r.rows[0] });
  } catch (e) { res.status(500).json({ error: e.message }); }
});

// Lista remediações pendentes de aprovação
app.get(`${BASE}/remediation/pending`, requireAuth, apiLimiter, async (_req, res) => {
  try {
    const r = await db.query(`
      SELECT rem.*, inc.title AS incident_title, inc.severity AS incident_severity
      FROM observe_remediations rem
      LEFT JOIN observe_incidents inc ON rem.incident_id = inc.id
      WHERE rem.status = 'pending_approval'
      ORDER BY rem.requested_at DESC
      LIMIT 50
    `);
    res.json({ remediations: r.rows, total: r.rowCount });
  } catch (e) { res.status(500).json({ error: e.message }); }
});

// Aprova e enfileira remediação para execução pelo worker
app.post(`${BASE}/remediation/:id/approve`, requireAuth, async (req, res) => {
  const { id } = req.params;
  const { approved_by } = req.body;
  try {
    const r = await db.query(
      `UPDATE observe_remediations
       SET status = 'approved', approved_by = $1, updated_at = NOW()
       WHERE id = $2 AND status = 'pending_approval'
       RETURNING *`,
      [approved_by || 'api', id]
    );
    if (!r.rowCount) return res.status(404).json({ error: 'Remediação não encontrada ou já processada' });
    const rem = r.rows[0];
    if (redis) {
      await redis.rpush('observe:remediation:execute', JSON.stringify({
        remediation_id: rem.id,
        incident_id:    rem.incident_id,
        action:         rem.action,
        params:         rem.params,
      }));
    }
    log('info', 'Remediation approved', { id, action: rem.action, approved_by });
    res.json({ remediation: rem, queued: !!redis });
  } catch (e) { res.status(500).json({ error: e.message }); }
});

// Rejeita remediação
app.post(`${BASE}/remediation/:id/reject`, requireAuth, async (req, res) => {
  const { id } = req.params;
  const { rejected_by, reason } = req.body;
  try {
    const r = await db.query(
      `UPDATE observe_remediations
       SET status = 'rejected', approved_by = $1,
           execution_output = $2, updated_at = NOW()
       WHERE id = $3 AND status = 'pending_approval'
       RETURNING *`,
      [rejected_by || 'api', reason ? `Rejeitado: ${reason}` : 'Rejeitado manualmente', id]
    );
    if (!r.rowCount) return res.status(404).json({ error: 'Remediação não encontrada ou já processada' });
    log('info', 'Remediation rejected', { id, rejected_by });
    res.json({ remediation: r.rows[0] });
  } catch (e) { res.status(500).json({ error: e.message }); }
});

// ─── Prometheus HTTP SD — /observe/api/sd/targets ────────────────────────────
// Retorna targets no formato Prometheus HTTP Service Discovery (RFC).
// O Prometheus consulta este endpoint a cada 30s (configurado em prometheus.yml).
const EXPORTER_PORTS = [
  { port: 9100, job: 'node-exporter' },
  { port: 9104, job: 'mysqld-exporter' },
  { port: 9108, job: 'generic-metrics' },
  { port: 9115, job: 'blackbox-exporter' },
  { port: 9117, job: 'apache-exporter' },
  { port: 9121, job: 'redis-exporter' },
  { port: 9187, job: 'postgres-exporter' },
  { port: 9256, job: 'process-exporter' },
  { port: 9419, job: 'rabbitmq-exporter' },
  { port: 9090, job: 'prometheus' },
  { port: 9091, job: 'pushgateway' },
];

async function probeMetricsEndpoint(address, port) {
  try {
    const resp = await fetch(`http://${address}:${port}/metrics`, {
      signal: AbortSignal.timeout(3000),
    });
    if (!resp.ok) return false;
    const text = await resp.text();
    return /^#\s*(HELP|TYPE)/m.test(text);
  } catch {
    return false;
  }
}

app.get(`${BASE}/sd/targets`, async (_req, res) => {
  try {
    const hosts = await db.query(
      `SELECT name, address, metadata FROM observe_hosts
       WHERE address IS NOT NULL ORDER BY updated_at DESC LIMIT 500`
    );

    const groups = [];

    // Serviços Docker internos conhecidos (targets estáticos enriquecidos)
    const staticServices = [
      { target: 'observe-api:3000',          job: 'r-observe-api',   path: '/observe/api/metrics' },
      { target: 'observe-worker:3000',        job: 'r-observe-worker',path: '/metrics' },
      { target: 'observe-ai:3000',            job: 'r-observe-ai',    path: '/metrics' },
      { target: 'observe-agent:3000',         job: 'r-observe-agent', path: '/metrics' },
      { target: 'observe-otel-collector:8888',job: 'otel-collector',  path: '/metrics' },
    ];
    for (const s of staticServices) {
      groups.push({
        targets: [s.target],
        labels: { job: s.job, __metrics_path__: s.path, source: 'r-observe-static' },
      });
    }

    // Hosts descobertos — testa portas de exporters em paralelo
    const probePromises = [];
    for (const host of hosts.rows) {
      if (!host.address) continue;
      for (const { port, job } of EXPORTER_PORTS) {
        probePromises.push(
          probeMetricsEndpoint(host.address, port).then(ok => ok ? {
            target: `${host.address}:${port}`,
            labels: {
              job,
              host:       host.name,
              host_ip:    host.address,
              discovered: 'true',
              source:     'r-observe-db',
            },
          } : null)
        );
      }
    }

    const results = await Promise.all(probePromises);
    for (const r of results) {
      if (r) groups.push({ targets: [r.target], labels: r.labels });
    }

    // Prometheus HTTP SD exige array de { targets, labels }
    res.set('Content-Type', 'application/json');
    res.json(groups);
  } catch (e) {
    res.status(500).json([]);
  }
});

// ─── AI Feedback ─────────────────────────────────────────────────────────────
app.post(`${BASE}/ai/feedback`, requireAuth, async (req, res) => {
  const { incident_id, rating, comment } = req.body;
  if (!incident_id || ![1, -1].includes(Number(rating)))
    return res.status(400).json({ error: 'incident_id e rating (1 ou -1) são obrigatórios' });
  try {
    const r = await db.query(
      `INSERT INTO observe_ai_feedback (incident_id, rating, comment)
       VALUES ($1, $2, $3) RETURNING *`,
      [incident_id, Number(rating), comment || null]
    );
    res.status(201).json({ feedback: r.rows[0] });
  } catch (e) { res.status(500).json({ error: e.message }); }
});

// ─── AI Activity ─────────────────────────────────────────────────────────────
app.get(`${BASE}/ai/activity`, requireAuth, apiLimiter, async (_req, res) => {
  try {
    const [incidents, remediations, feedback, catalog] = await Promise.all([
      db.query(`
        SELECT i.id, i.title, i.severity, i.status, i.source,
               i.ai_summary, i.ai_cause, i.ai_suggestion, i.ai_pattern,
               h.name AS host_name, i.created_at, i.updated_at
        FROM observe_incidents i
        LEFT JOIN observe_hosts h ON i.host_id = h.id
        WHERE i.ai_summary IS NOT NULL
        ORDER BY i.updated_at DESC LIMIT 20`),
      db.query(`
        SELECT action, status, confidence_score, auto_executed, requested_at
        FROM observe_remediations
        ORDER BY requested_at DESC LIMIT 20`),
      db.query(`
        SELECT rating, COUNT(*) AS count
        FROM observe_ai_feedback GROUP BY rating`),
      db.query(`
        SELECT action, description, risk, enabled, auto_ok, max_severity
        FROM observe_ai_catalog ORDER BY risk, action`),
    ]);

    const totalAnalyzed  = incidents.rowCount;
    const remRows        = remediations.rows;
    const autoExecuted   = remRows.filter(r => r.auto_executed).length;
    const pendingApproval= remRows.filter(r => r.status === 'pending_approval').length;
    const succeeded      = remRows.filter(r => r.status === 'executed').length;
    const failed         = remRows.filter(r => r.status === 'failed').length;
    const fbPos = feedback.rows.find(r => r.rating == 1)?.count || 0;
    const fbNeg = feedback.rows.find(r => r.rating == -1)?.count || 0;

    res.json({
      metrics: { total_analyzed: totalAnalyzed, auto_executed: autoExecuted,
                 pending_approval: pendingApproval, succeeded, failed,
                 feedback_positive: Number(fbPos), feedback_negative: Number(fbNeg) },
      recent_analyses:    incidents.rows,
      recent_remediations:remRows,
      catalog:            catalog.rows,
    });
  } catch (e) { res.status(500).json({ error: e.message }); }
});

// ─── AI Catalog CRUD ──────────────────────────────────────────────────────────
app.get(`${BASE}/ai/catalog`, requireAuth, async (_req, res) => {
  try {
    const r = await db.query('SELECT * FROM observe_ai_catalog ORDER BY risk, action');
    res.json({ catalog: r.rows });
  } catch (e) { res.status(500).json({ error: e.message }); }
});

app.post(`${BASE}/ai/catalog`, requireAuth, async (req, res) => {
  const { action, description, risk, params, auto_ok, max_severity } = req.body;
  if (!action || !description) return res.status(400).json({ error: 'action e description são obrigatórios' });
  try {
    const r = await db.query(
      `INSERT INTO observe_ai_catalog (action, description, risk, params, auto_ok, max_severity)
       VALUES ($1, $2, $3, $4, $5, $6) RETURNING *`,
      [action, description, risk || 'medium', JSON.stringify(params || []),
       auto_ok || false, max_severity || 'warning']
    );
    res.status(201).json({ action: r.rows[0] });
  } catch (e) { res.status(e.message.includes('unique') ? 409 : 500).json({ error: e.message }); }
});

app.patch(`${BASE}/ai/catalog/:action`, requireAuth, async (req, res) => {
  const { action } = req.params;
  const { description, risk, params, auto_ok, max_severity, enabled } = req.body;
  try {
    const r = await db.query(
      `UPDATE observe_ai_catalog
       SET description  = COALESCE($1, description),
           risk         = COALESCE($2, risk),
           params       = COALESCE($3, params),
           auto_ok      = COALESCE($4, auto_ok),
           max_severity = COALESCE($5, max_severity),
           enabled      = COALESCE($6, enabled),
           updated_at   = NOW()
       WHERE action = $7 RETURNING *`,
      [description, risk, params ? JSON.stringify(params) : null,
       auto_ok, max_severity, enabled, action]
    );
    if (!r.rowCount) return res.status(404).json({ error: 'Ação não encontrada' });
    res.json({ action: r.rows[0] });
  } catch (e) { res.status(500).json({ error: e.message }); }
});

app.delete(`${BASE}/ai/catalog/:action`, requireAuth, async (req, res) => {
  try {
    const r = await db.query(
      `UPDATE observe_ai_catalog SET enabled = false, updated_at = NOW()
       WHERE action = $1 RETURNING action`,
      [req.params.action]
    );
    if (!r.rowCount) return res.status(404).json({ error: 'Ação não encontrada' });
    res.json({ disabled: true, action: req.params.action });
  } catch (e) { res.status(500).json({ error: e.message }); }
});

// ─── UI: página de configuração do provider de IA ─────────────────────────────
app.get('/observe/settings', (_req, res) => {
  res.set('Content-Type', 'text/html; charset=utf-8');
  res.end(SETTINGS_HTML);
});

// ─── UI: dashboard de atividade da IA ─────────────────────────────────────────
app.get('/observe/ai', (_req, res) => {
  res.set('Content-Type', 'text/html; charset=utf-8');
  res.end(AI_DASHBOARD_HTML);
});

// ─── 404 handler ─────────────────────────────────────────────────────────────
app.use((_req, res) => res.status(404).json({ error: 'Not found' }));

// ─── Error handler ────────────────────────────────────────────────────────────
app.use((err, _req, res, _next) => {
  log('error', 'Unhandled error', { err: err.message });
  res.status(500).json({ error: 'Internal server error' });
});

// ─── Start ────────────────────────────────────────────────────────────────────
const { runMigrations } = require('./migrate');
const { bootstrapInitialUsers } = require('./bootstrap');

(async () => {
  try {
    await runMigrations(db);
    await bootstrapInitialUsers(db, process.env, { log: (line) => process.stdout.write(line + '\n') });
  } catch (e) {
    log('error', 'Startup bootstrap failed', { err: e.message });
    await db.end().catch(() => {});
    if (redis) await redis.quit().catch(() => {});
    process.exit(1);
  }
  app.listen(PORT, '0.0.0.0', () => {
    log('info', `Listening on :${PORT}`, { basePath: BASE });
  });
})();

module.exports = app;

// ─── HTML da UI de configuração ───────────────────────────────────────────────
const AI_DASHBOARD_HTML = /* html */`<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Results · R-Observe IA</title>
  <link rel="stylesheet" href="/observe/api/ui/observe-ai.css">
</head>
<body>
<div class="header">
  <svg class="logo" viewBox="0 0 36 36" xmlns="http://www.w3.org/2000/svg"><rect width="36" height="36" rx="8" fill="#CC1212"/><text x="18" y="26" font-family="Arial Black,Arial,sans-serif" font-weight="900" font-size="21" fill="white" text-anchor="middle">R</text></svg>
  <div>
    <div class="brand-name">Results · Sistemas de Informática</div>
    <h1>R-Observe · IA Dashboard</h1>
    <div class="sub">Atividade, catálogo de remediações e feedback</div>
  </div>
  <div style="margin-left:auto;display:flex;gap:.5rem;align-items:center;">
    <span style="color:var(--muted);font-size:.8rem;">TOKEN</span>
    <input type="password" id="token" class="token-field" style="max-width:200px" placeholder="OBSERVE_INTERNAL_TOKEN">
    <button class="btn btn-primary btn-sm" onclick="loadAll()">↺ Atualizar</button>
  </div>
</div>

<nav>
  <div class="tab active" onclick="showTab('overview')">Visão Geral</div>
  <div class="tab" onclick="showTab('analyses')">Análises</div>
  <div class="tab" onclick="showTab('catalog')">Catálogo</div>
  <div class="tab" onclick="showTab('remediations')">Remediações</div>
</nav>

<div id="overview" class="page active">
  <div class="metrics" id="metrics-grid">
    <div class="metric"><div class="val">—</div><div class="lbl">Analisados</div></div>
  </div>
  <div class="section-title">Análises Recentes com IA</div>
  <table id="overview-table">
    <thead><tr><th>Incidente</th><th>Severidade</th><th>Resumo IA</th><th>Status</th><th>Data</th></tr></thead>
    <tbody><tr><td colspan="5" class="empty">Carregando…</td></tr></tbody>
  </table>
</div>

<div id="analyses" class="page">
  <div class="section-title">Todas as Análises</div>
  <table id="analyses-table">
    <thead><tr><th>Título</th><th>Host</th><th>Causa</th><th>Sugestão</th><th>Feedback</th></tr></thead>
    <tbody><tr><td colspan="5" class="empty">Carregando…</td></tr></tbody>
  </table>
</div>

<div id="catalog" class="page">
  <div class="section-title">Catálogo de Remediações</div>
  <table id="catalog-table">
    <thead><tr><th>Ação</th><th>Descrição</th><th>Risco</th><th>Auto</th><th>Max Sev.</th><th>Status</th><th></th></tr></thead>
    <tbody><tr><td colspan="7" class="empty">Carregando…</td></tr></tbody>
  </table>
</div>

<div id="remediations" class="page">
  <div class="section-title">Remediações Recentes</div>
  <table id="rem-table">
    <thead><tr><th>Ação</th><th>Status</th><th>Score</th><th>Auto</th><th>Output</th><th>Data</th></tr></thead>
    <tbody><tr><td colspan="6" class="empty">Carregando…</td></tr></tbody>
  </table>
</div>

<div id="toast"></div>

<script>
const API = '/observe/api';
let _data = {};

const tokenEl = document.getElementById('token');
tokenEl.value = sessionStorage.getItem('observe_token') || '';
tokenEl.addEventListener('input', () => {
  const v = tokenEl.value.trim();
  if (v) sessionStorage.setItem('observe_token', v);
  else   sessionStorage.removeItem('observe_token');
});

function getHeaders() {
  const h = { 'Content-Type': 'application/json' };
  const t = tokenEl.value.trim();
  if (t) h['x-internal-token'] = t;
  return h;
}

function showTab(id) {
  document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
  document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
  document.getElementById(id).classList.add('active');
  document.querySelector('[onclick="showTab(\\''+id+'\\')"]').classList.add('active');
}

function toast(msg, ok) {
  const el = document.getElementById('toast');
  el.textContent = msg;
  el.className = ok ? 'ok' : 'err';
  el.style.display = 'block';
  setTimeout(() => { el.style.display = 'none'; }, 3000);
}

function badge(cls, text) { return '<span class="badge ' + cls + '">' + text + '</span>'; }
function esc(s) { return String(s||'').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
function dt(s) { return s ? new Date(s).toLocaleString('pt-BR', {dateStyle:'short',timeStyle:'short'}) : '—'; }

async function loadAll() {
  if (!tokenEl.value.trim()) {
    document.getElementById('metrics-grid').innerHTML =
      '<div style="color:var(--muted);grid-column:1/-1;padding:1rem">Cole o OBSERVE_INTERNAL_TOKEN no campo acima e clique em ↺ Atualizar.</div>';
    return;
  }
  try {
    const r = await fetch(API + '/ai/activity', { headers: getHeaders() });
    if (!r.ok) { toast('Erro ' + r.status + ' — verifique o token', false); return; }
    _data = await r.json();
    renderMetrics(_data.metrics);
    renderOverview(_data.recent_analyses);
    renderAnalyses(_data.recent_analyses);
    renderCatalog(_data.catalog);
    renderRemediations(_data.recent_remediations);
  } catch(e) { toast('Falha: ' + e.message, false); }
}

function renderMetrics(m) {
  document.getElementById('metrics-grid').innerHTML = [
    ['green',  m.total_analyzed,   'Analisados'],
    ['yellow', m.auto_executed,    'Auto-executados'],
    ['orange', m.pending_approval, 'Aguardando aprovação'],
    ['ok',     m.succeeded,        'Sucesso'],
    ['red',    m.failed,           'Falhas'],
    ['purple', m.feedback_positive + '👍 / ' + m.feedback_negative + '👎', 'Feedback'],
  ].map(([cls, val, lbl]) =>
    '<div class="metric ' + cls + '"><div class="val">' + val + '</div><div class="lbl">' + lbl + '</div></div>'
  ).join('');
}

function renderOverview(rows) {
  const tb = document.querySelector('#overview-table tbody');
  if (!rows.length) { tb.innerHTML = '<tr><td colspan="5" class="empty">Nenhuma análise ainda.</td></tr>'; return; }
  tb.innerHTML = rows.map(r => '<tr>' +
    '<td>' + esc(r.title) + '</td>' +
    '<td>' + badge(r.severity, r.severity) + '</td>' +
    '<td><div class="ai-text">' + esc(r.ai_summary || '—') + '</div></td>' +
    '<td>' + badge(r.status, r.status) + '</td>' +
    '<td style="white-space:nowrap">' + dt(r.created_at) + '</td>' +
  '</tr>').join('');
}

function renderAnalyses(rows) {
  const tb = document.querySelector('#analyses-table tbody');
  if (!rows.length) { tb.innerHTML = '<tr><td colspan="5" class="empty">Nenhuma análise ainda.</td></tr>'; return; }
  tb.innerHTML = rows.map(r => '<tr>' +
    '<td><b>' + esc(r.title) + '</b><br><span style="color:var(--muted);font-size:.75rem">' + esc(r.host_name || '') + '</span></td>' +
    '<td><div class="ai-text">' + esc(r.ai_cause || '—') + '</div></td>' +
    '<td><div class="ai-text">' + esc(r.ai_suggestion || '—') + '</div></td>' +
    '<td><div class="feedback-bar">' +
      '<button class="btn btn-sm" style="background:#1a7f3733;color:var(--green)" onclick="sendFeedback(\\''+r.id+'\\',1)">👍</button>' +
      '<button class="btn btn-sm" style="background:#da363333;color:var(--red)"   onclick="sendFeedback(\\''+r.id+'\\',-1)">👎</button>' +
    '</div></td>' +
  '</tr>').join('');
}

function renderCatalog(rows) {
  const tb = document.querySelector('#catalog-table tbody');
  if (!rows.length) { tb.innerHTML = '<tr><td colspan="7" class="empty">Catálogo vazio.</td></tr>'; return; }
  tb.innerHTML = rows.map(r => '<tr>' +
    '<td><code style="color:var(--accent)">' + esc(r.action) + '</code></td>' +
    '<td>' + esc(r.description) + '</td>' +
    '<td>' + badge(r.risk, r.risk) + '</td>' +
    '<td>' + (r.auto_ok ? badge('low','sim') : badge('disabled','não')) + '</td>' +
    '<td>' + badge(r.max_severity || 'warning', r.max_severity || 'warning') + '</td>' +
    '<td>' + (r.enabled ? badge('low','ativo') : badge('disabled','desativado')) + '</td>' +
    '<td>' + (r.enabled
      ? '<button class="btn btn-danger btn-sm" onclick="disableAction(\\''+r.action+'\\')">Desativar</button>'
      : '<button class="btn btn-sm" style="background:var(--green);color:#fff" onclick="enableAction(\\''+r.action+'\\')">Ativar</button>') +
    '</td>' +
  '</tr>').join('');
}

function renderRemediations(rows) {
  const tb = document.querySelector('#rem-table tbody');
  if (!rows.length) { tb.innerHTML = '<tr><td colspan="6" class="empty">Nenhuma remediação ainda.</td></tr>'; return; }
  const colors = { executed:'low', failed:'high', pending_approval:'medium', executing:'ok', rejected:'disabled' };
  tb.innerHTML = rows.map(r => '<tr>' +
    '<td><code style="color:var(--accent)">' + esc(r.action) + '</code></td>' +
    '<td>' + badge(colors[r.status] || 'medium', r.status.replace('_',' ')) + '</td>' +
    '<td>' + (r.confidence_score ? (parseFloat(r.confidence_score)*100).toFixed(0)+'%' : '—') + '</td>' +
    '<td>' + (r.auto_executed ? '✅' : '👤') + '</td>' +
    '<td><div class="ai-text">' + esc(r.execution_output || '—') + '</div></td>' +
    '<td style="white-space:nowrap">' + dt(r.requested_at) + '</td>' +
  '</tr>').join('');
}

async function sendFeedback(incidentId, rating) {
  const r = await fetch(API + '/ai/feedback', {
    method: 'POST', headers: getHeaders(),
    body: JSON.stringify({ incident_id: incidentId, rating }),
  });
  toast(r.ok ? (rating > 0 ? '👍 Feedback positivo enviado' : '👎 Feedback negativo enviado') : 'Erro ao enviar feedback', r.ok);
  if (r.ok) loadAll();
}

async function disableAction(action) {
  const r = await fetch(API + '/ai/catalog/' + action, { method: 'DELETE', headers: getHeaders() });
  toast(r.ok ? 'Ação "' + action + '" desativada' : 'Erro', r.ok);
  if (r.ok) loadAll();
}

async function enableAction(action) {
  const r = await fetch(API + '/ai/catalog/' + action, {
    method: 'PATCH', headers: getHeaders(), body: JSON.stringify({ enabled: true }),
  });
  toast(r.ok ? 'Ação "' + action + '" ativada' : 'Erro', r.ok);
  if (r.ok) loadAll();
}

loadAll();
// Auto-refresh apenas quando há token
let _refreshTimer = null;
function scheduleRefresh() {
  clearInterval(_refreshTimer);
  if (tokenEl.value.trim()) _refreshTimer = setInterval(loadAll, 30000);
}
tokenEl.addEventListener('change', scheduleRefresh);
scheduleRefresh();
</script>
</body>
</html>`;

const SETTINGS_HTML = /* html */`<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Results · R-Observe — Configuração IA</title>
  <link rel="stylesheet" href="/observe/api/ui/observe-settings.css">
</head>
<body>
  <div class="card">
    <div class="header">
      <svg class="logo" viewBox="0 0 36 36" xmlns="http://www.w3.org/2000/svg"><rect width="36" height="36" rx="8" fill="#CC1212"/><text x="18" y="26" font-family="Arial Black,Arial,sans-serif" font-weight="900" font-size="21" fill="white" text-anchor="middle">R</text></svg>
      <div>
        <div class="brand-name">Results · Sistemas de Informática</div>
        <h1>R-Observe · Configuração IA</h1>
        <div class="subtitle">Altere o provider de IA sem reiniciar o serviço</div>
      </div>
    </div>

    <div class="status-bar">
      <span class="dot warn" id="status-dot"></span>
      <span class="status-text" id="status-text">Carregando…</span>
      <span class="status-value" id="status-value"></span>
    </div>

    <div class="field">
      <label>Provider</label>
      <div class="providers">
        <button class="provider-btn" data-provider="openai"     onclick="selectProvider('openai')">
          <span class="provider-icon">🤖</span>OpenAI
        </button>
        <button class="provider-btn" data-provider="anthropic"  onclick="selectProvider('anthropic')">
          <span class="provider-icon">🟠</span>Anthropic
        </button>
        <button class="provider-btn" data-provider="deepseek"   onclick="selectProvider('deepseek')">
          <span class="provider-icon">🔍</span>DeepSeek
        </button>
        <button class="provider-btn" data-provider="mock"       onclick="selectProvider('mock')">
          <span class="provider-icon">🧪</span>Mock
        </button>
      </div>
    </div>

    <div class="field" id="model-section" style="display:none">
      <label>Modelo</label>
      <select id="model" onchange="onModelChange()"></select>
      <div class="effective-model" id="effective-model"></div>
    </div>

    <div id="api-key-section" class="field">
      <label>API Key</label>
      <div class="key-row">
        <input type="password" id="api-key" placeholder="Cole a chave aqui" autocomplete="new-password">
        <button class="toggle-btn" onclick="toggleKey()">👁</button>
      </div>
      <div class="hint" id="key-hint"></div>
    </div>

    <div class="token-section">
      <div class="field">
        <label>Token de Autenticação (OBSERVE_INTERNAL_TOKEN)</label>
        <div class="key-row">
          <input type="password" id="token" placeholder="Cole o token interno aqui" autocomplete="off">
          <button class="toggle-btn" onclick="toggleToken()">👁</button>
        </div>
      </div>
    </div>

    <div class="actions">
      <button class="btn btn-secondary" onclick="loadStatus()">↺ Atualizar</button>
      <button class="btn btn-primary" id="save-btn" onclick="saveSettings()" disabled>Salvar configuração</button>
    </div>

    <div id="feedback"></div>
  </div>

  <script>
    const API_BASE = '/observe/api';
    let selectedProvider = 'mock';

    // Fallback estático — usado enquanto a API carrega ou quando não há chave
    const AUTO_LABELS = {
      openai:    'gpt-4o-mini',
      anthropic: 'claude-haiku-4-5-20251001',
      deepseek:  'deepseek-chat',
    };

    function populateSelect(sel, models, autoLabel, currentModel) {
      sel.innerHTML = '';
      const auto = document.createElement('option');
      auto.value = 'auto';
      auto.textContent = 'Automático' + (autoLabel ? '  (' + autoLabel + ')' : '');
      sel.appendChild(auto);
      for (const id of models) {
        const opt = document.createElement('option');
        opt.value = id;
        opt.textContent = id;
        sel.appendChild(opt);
      }
      sel.value = currentModel || 'auto';
      if (!sel.value) sel.selectedIndex = 0;
    }

    async function loadModels(provider, currentModel) {
      const sel = document.getElementById('model');
      sel.innerHTML = '<option value="auto">Carregando modelos…</option>';
      sel.disabled = true;
      try {
        const resp = await fetch(API_BASE + '/ai/models?provider=' + provider, { headers: getHeaders() });
        if (resp.ok) {
          const d = await resp.json();
          populateSelect(sel, d.models, d.auto, currentModel);
          const src = d.source === 'api' ? '  ·  lista real da API' : '  ·  lista estática (sem chave)';
          document.getElementById('effective-model').innerHTML =
            'Modelo efetivo: <span>' + (currentModel && currentModel !== 'auto' ? currentModel : (d.auto || '')) + '</span>' + src;
        } else {
          populateSelect(sel, [], AUTO_LABELS[provider], currentModel);
        }
      } catch {
        populateSelect(sel, [], AUTO_LABELS[provider], currentModel);
      } finally {
        sel.disabled = false;
      }
    }

    function updateEffectiveModel(rawModel, provider) {
      const el = document.getElementById('effective-model');
      const eff = (!rawModel || rawModel === 'auto') ? (AUTO_LABELS[provider] || '') : rawModel;
      el.innerHTML = eff ? 'Modelo efetivo: <span>' + eff + '</span>' : '';
    }

    function onModelChange() {
      const sel = document.getElementById('model');
      sel.disabled = false;
      updateEffectiveModel(sel.value, selectedProvider);
    }

    function selectProvider(p, currentModel) {
      selectedProvider = p;
      document.querySelectorAll('.provider-btn').forEach(b =>
        b.classList.toggle('active', b.dataset.provider === p)
      );

      const keySection   = document.getElementById('api-key-section');
      const modelSection = document.getElementById('model-section');

      if (p === 'mock') {
        keySection.classList.remove('visible');
        modelSection.style.display = 'none';
      } else {
        keySection.classList.add('visible');
        modelSection.style.display = '';
        document.getElementById('key-hint').textContent =
          'Deixe em branco para manter a chave já configurada.';
        loadModels(p, currentModel);
      }
    }

    function toggleKey()   { const e = document.getElementById('api-key'); e.type = e.type === 'password' ? 'text' : 'password'; }
    function toggleToken() { const e = document.getElementById('token');   e.type = e.type === 'password' ? 'text' : 'password'; }

    // Token guardado em variável JS — não depende do DOM (browsers limpam
    // input[type=password] no refresh antes do script conseguir ler o valor).
    let _authToken = sessionStorage.getItem('observe_token') || '';

    const tokenEl = document.getElementById('token');
    if (_authToken) tokenEl.value = _authToken;

    tokenEl.addEventListener('input', () => {
      _authToken = tokenEl.value.trim();
      if (_authToken) sessionStorage.setItem('observe_token', _authToken);
      else            sessionStorage.removeItem('observe_token');
    });

    function getHeaders() {
      const h = { 'Content-Type': 'application/json' };
      if (_authToken) h['x-internal-token'] = _authToken;
      return h;
    }

    let _statusReady = false; // true após primeiro loadStatus bem-sucedido

    function setStatus(cls, text, value = '') {
      document.getElementById('status-dot').className  = 'dot ' + cls;
      document.getElementById('status-text').textContent = text;
      document.getElementById('status-value').textContent = value;
      // Habilita salvar quando o serviço responde (_statusReady).
      // Erros reais (401, rede) deixam _statusReady=false e já desabilitam o botão.
      // Não bloquear por cls==='err': usuário precisa salvar quando não há chave.
      document.getElementById('save-btn').disabled = !_statusReady;
    }

    function showFeedback(msg, isOk) {
      const el = document.getElementById('feedback');
      el.textContent = msg;
      el.className = isOk ? 'ok' : 'err';
    }

    async function loadStatus() {
      _statusReady = false;
      setStatus('warn', 'Consultando serviço de IA…', '');
      try {
        const resp = await fetch(API_BASE + '/ai/settings', { headers: getHeaders() });
        if (resp.status === 401) { setStatus('err', 'Cole o OBSERVE_INTERNAL_TOKEN abaixo e clique em ↺', ''); return; }
        if (!resp.ok)            { setStatus('err', 'Serviço AI indisponível.', resp.status); return; }

        const d = await resp.json();
        const hasKey = d.has_api_key;
        const effModel = d.effective_model || d.model || '';

        const label = d.provider === 'mock'
          ? 'Mock ativo — sem custo, sem IA real'
          : hasKey
            ? d.provider + ' · ' + effModel
            : d.provider + ' — chave não configurada';

        _statusReady = true;
        setStatus(d.provider === 'mock' ? 'warn' : hasKey ? 'ok' : 'err', 'Provider atual:', label);

        selectProvider(d.provider, d.model || 'auto');
        updateEffectiveModel(d.model, d.provider);

        if (hasKey && d.provider !== 'mock') {
          document.getElementById('key-hint').textContent = 'Chave já configurada. Deixe em branco para mantê-la.';
        }
      } catch (e) {
        setStatus('err', 'Erro ao consultar API.', e.message);
      }
    }

    async function saveSettings() {
      const btn = document.getElementById('save-btn');
      btn.disabled = true;
      btn.textContent = 'Salvando…';

      const apiKey = document.getElementById('api-key').value.trim();
      const body = { provider: selectedProvider, model: document.getElementById('model').value || 'auto' };
      if (apiKey) body.api_key = apiKey;

      try {
        const resp = await fetch(API_BASE + '/ai/settings', {
          method: 'POST', headers: getHeaders(), body: JSON.stringify(body),
        });
        const d = await resp.json();
        if (!resp.ok) {
          showFeedback('Erro: ' + (d.error || resp.status), false);
        } else {
          const eff = d.effective_model || d.model || '';
          showFeedback('Salvo! Provider: ' + d.provider + (eff ? ' · ' + eff : ''), true);
          document.getElementById('api-key').value = '';
          await loadStatus();
        }
      } catch (e) {
        showFeedback('Falha na requisição: ' + e.message, false);
      } finally {
        btn.disabled = false;
        btn.textContent = 'Salvar configuração';
      }
    }

    loadStatus();
  </script>
</body>
</html>`;
