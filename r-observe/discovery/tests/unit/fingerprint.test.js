'use strict';

const test = require('node:test');
const assert = require('node:assert/strict');
const { fingerprintAsset } = require('../../src/fingerprint/engine');

test('fingerprint detecta PostgreSQL pela porta', () => {
  const fp = fingerprintAsset({ open_ports: [22, 5432], hostname: 'db-core' });
  assert.equal(fp.product, 'PostgreSQL');
  assert.equal(fp.technology, 'postgres');
  assert.equal(fp.criticality, 'high');
});

test('fingerprint detecta vendor via OUI', () => {
  const fp = fingerprintAsset({ open_ports: [80], mac_oui: '3C:5A:B4' });
  assert.equal(fp.vendor, 'Hikvision');
});
