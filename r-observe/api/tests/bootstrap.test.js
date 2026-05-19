'use strict';

const assert = require('node:assert/strict');
const test = require('node:test');
const { bootstrapInitialUsers, parseInitialUsers } = require('../src/bootstrap');

test('parseInitialUsers includes configured IcingaWeb admin first', () => {
  const users = parseInitialUsers({
    ICINGAWEB_ADMIN_USER: 'admin',
    ICINGAWEB_ADMIN_PASS: 'secret',
    OBSERVE_INITIAL_USERS_JSON: JSON.stringify([{ name: 'ops', password: 'ops-secret', admin: false }]),
  });

  assert.deepEqual(users.map((user) => user.name), ['admin', 'ops']);
  assert.equal(users[0].admin, true);
  assert.equal(users[1].admin, false);
});

test('bootstrapInitialUsers is idempotent by default and never logs passwords', async () => {
  const queries = [];
  const logs = [];
  const pool = {
    async query(sql, params = []) {
      queries.push({ sql, params });
      return { rowCount: 1, rows: [] };
    },
  };

  const result = await bootstrapInitialUsers(pool, {
    ICINGAWEB_ADMIN_USER: 'admin',
    ICINGAWEB_ADMIN_PASS: 'secret',
  }, { log: (line) => logs.push(line) });

  assert.equal(result.users, 1);
  assert.equal(queries.length, 3);
  assert.match(queries[1].sql, /ON CONFLICT \("name"\) DO NOTHING/);
  assert.match(queries[2].sql, /ON CONFLICT \("group_name", "username"\) DO NOTHING/);
  assert.deepEqual(queries[1].params, ['admin', 1, 'secret']);
  assert.doesNotMatch(logs.join('\n'), /secret/);
});

test('bootstrapInitialUsers can force password updates explicitly', async () => {
  const queries = [];
  const pool = {
    async query(sql, params = []) {
      queries.push({ sql, params });
      return { rowCount: 1, rows: [] };
    },
  };

  await bootstrapInitialUsers(pool, {
    ICINGAWEB_ADMIN_USER: 'admin',
    ICINGAWEB_ADMIN_PASS: 'secret',
    OBSERVE_BOOTSTRAP_FORCE_PASSWORD_UPDATE: 'true',
  }, { log: () => {} });

  assert.match(queries[1].sql, /ON CONFLICT \("name"\) DO UPDATE/);
});
