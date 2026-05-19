'use strict';

const test = require('node:test');
const assert = require('node:assert/strict');
const { fingerprintAsset } = require('../../src/fingerprint/engine');
const { buildTopologyEdges } = require('../../src/topology/engine');

const fingerprintFixtures = [
  { name: 'Apache', input: { http_server: 'Apache/2.4.58', open_ports: [80] }, product: 'Apache', category: 'web' },
  { name: 'Nginx', input: { http_server: 'nginx/1.25.3', open_ports: [443] }, product: 'Nginx', category: 'web' },
  { name: 'PostgreSQL', input: { open_ports: [5432], hostname: 'pg-core' }, product: 'PostgreSQL', category: 'database' },
  { name: 'MariaDB/MySQL', input: { open_ports: [3306], hostname: 'mysql-core' }, product: 'MariaDB', category: 'database' },
  { name: 'Redis', input: { open_ports: [6379] }, product: 'Redis', category: 'cache' },
  { name: 'Prometheus', input: { open_ports: [9090] }, product: 'Prometheus', category: 'observability' },
  { name: 'Grafana', input: { open_ports: [3000] }, product: 'Grafana', category: 'observability' },
  { name: 'Asterisk', input: { open_ports: [5060, 8088] }, product: 'Asterisk', category: 'voice' },
  { name: 'PowerDNS', input: { open_ports: [53] }, product: 'PowerDNS', category: 'dns' },
  { name: 'Docker Registry', input: { open_ports: [5000] }, product: 'Docker Registry', category: 'registry' },
  { name: 'ControlID FaceID', input: { onvif_model: 'ControlID FaceID iDProx', open_ports: [80] }, product: 'ControlID FaceID', category: 'iot' },
  { name: 'Grandstream', input: { snmp_sysdescr: 'Grandstream GXV3275', open_ports: [5060] }, product: 'Grandstream', category: 'voice' },
  { name: 'Mikrotik', input: { snmp_sysdescr: 'Mikrotik RouterOS', open_ports: [80] }, product: 'Mikrotik', category: 'unknown' },
  { name: 'Hikvision', input: { snmp_sysdescr: 'Hikvision Camera', open_ports: [554] }, product: 'Hikvision', category: 'iot' },
  { name: 'Intelbras', input: { snmp_sysdescr: 'Intelbras DVR', open_ports: [80] }, product: 'Intelbras', category: 'iot' },
];

for (const fx of fingerprintFixtures) {
  test(`fingerprint fixture: ${fx.name}`, () => {
    const fp = fingerprintAsset(fx.input);
    assert.equal(fp.product, fx.product);
    assert.equal(fp.category, fx.category);
    assert.equal(typeof fp.vendor, 'string');
    assert.equal(typeof fp.confidence, 'number');
    assert.ok(Array.isArray(fp.evidence));
    assert.ok(fp.evidence.length > 0);
  });
}

test('topology: host -> service, service -> db/dns/exporter e container -> service', () => {
  const edges = buildTopologyEdges('run-xyz', [
    {
      id: 'asset-1',
      primary_ip: '10.10.2.55',
      services: [
        { port: 5432, protocol: 'tcp', dependency_target: '10.10.2.99:5432', container_id: 'abc123' },
        { port: 53, protocol: 'udp' },
        { port: 9100, protocol: 'tcp' },
      ],
    },
  ]);

  assert.ok(edges.find((e) => e.edge_type === 'host_service' && e.to_asset_ref === '10.10.2.55:5432'));
  assert.ok(edges.find((e) => e.edge_type === 'service_database'));
  assert.ok(edges.find((e) => e.edge_type === 'service_dns'));
  assert.ok(edges.find((e) => e.edge_type === 'service_exporter'));
  assert.ok(edges.find((e) => e.edge_type === 'container_service' && e.to_asset_ref === 'container:abc123'));
});

test('topology: deduplicação de edges', () => {
  const edges = buildTopologyEdges('run-dup', [
    {
      id: 'asset-dup',
      primary_ip: '10.10.2.77',
      services: [
        { port: 3306, protocol: 'tcp', dependency_target: '10.10.2.99:3306' },
        { port: 3306, protocol: 'tcp', dependency_target: '10.10.2.99:3306' },
      ],
    },
  ]);

  const uniqueKeys = new Set(edges.map((e) => `${e.from_asset_id}|${e.to_asset_ref}|${e.edge_type}|${e.protocol}`));
  assert.equal(uniqueKeys.size, edges.length);
});
