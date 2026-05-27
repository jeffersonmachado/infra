'use strict';

const test = require('node:test');
const assert = require('node:assert');

const { syncDiscoveredToIcinga } = require('../../src/integrations/icinga');

test('syncDiscoveredToIcinga com lista vazia nao depende de rede', async () => {
  const out = await syncDiscoveredToIcinga({
    tenant: { tenant_id: 'default', site_id: 'default-site', edge_id: 'central' },
    assets: [],
  });

  assert.ok(out && typeof out === 'object');
  assert.strictEqual(out.synced, 0);
  assert.strictEqual(out.staged, false);
  assert.strictEqual(out.deployed, false);
});
