'use strict';

const test = require('node:test');
const assert = require('node:assert/strict');
const { buildTopologyEdges } = require('../../src/topology/engine');

test('gera arestas de dependencia a partir de services', () => {
  const edges = buildTopologyEdges('run-1', [{ id: 'a1', services: [{ dependency_target: '10.10.2.99:3306', protocol: 'tcp' }] }]);
  assert.equal(edges.length, 2);
  assert.equal(edges[0].to_asset_ref, '10.10.2.99:3306');
});
