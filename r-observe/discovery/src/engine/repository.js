'use strict';

const { v4: uuidv4 } = require('uuid');

const WEAK_VENDORS  = new Set(['Não identificado', 'Nao identificado', 'Unknown', null, undefined, '']);
const WEAK_PRODUCTS = new Set(['Sem sinal de serviço', 'Sem sinal de servico', null, undefined, '']);

function isWeakVendor(v)  { return WEAK_VENDORS.has(v ?? null); }
function isWeakProduct(v) { return WEAK_PRODUCTS.has(v ?? null); }

async function createRun(db, ctx) {
  const r = await db.query(
    `INSERT INTO observe_discovery_runs (id, tenant_id, site_id, edge_id, policy_id, status, started_at, metadata)
     VALUES ($1,$2,$3,$4,$5,'running',NOW(),$6) RETURNING *`,
    [uuidv4(), ctx.tenant_id, ctx.site_id, ctx.edge_id, ctx.policy_id, JSON.stringify(ctx.metadata || {})]
  );
  return r.rows[0];
}

async function completeRun(db, runId, status, summary) {
  await db.query(
    `UPDATE observe_discovery_runs SET status = $2, completed_at = NOW(), summary = $3, updated_at = NOW() WHERE id = $1`,
    [runId, status, JSON.stringify(summary || {})]
  );
}

async function upsertAsset(db, row) {
  // Buscar registro existente para aplicar merge de valores conhecidos
  const existing = await db.query(
    `SELECT vendor, product, os_hint, hostname, confidence FROM observe_assets
     WHERE tenant_id = $1 AND site_id = $2 AND edge_id = $3 AND asset_key = $4 LIMIT 1`,
    [row.tenant_id, row.site_id, row.edge_id, row.asset_key]
  );
  const prev = existing.rows[0] || {};

  // Preservar o melhor valor conhecido: não sobrescrever dados ricos com fallbacks genéricos
  const vendor     = (!isWeakVendor(row.vendor) ? row.vendor : null) ?? (!isWeakVendor(prev.vendor) ? prev.vendor : null) ?? row.vendor ?? null;
  const product    = (!isWeakProduct(row.product) ? row.product : null) ?? (!isWeakProduct(prev.product) ? prev.product : null) ?? row.product ?? null;
  const os_hint    = row.os_hint ?? prev.os_hint ?? null;
  const hostname   = row.hostname ?? prev.hostname ?? null;
  const confidence = Math.max(Number(row.confidence || 0.5), Number(prev.confidence || 0));

  const r = await db.query(
    `INSERT INTO observe_assets
      (id, tenant_id, site_id, edge_id, asset_key, asset_name, display_name, asset_type, vendor, product, os_hint,
       primary_ip, hostname, lifecycle_state, criticality, confidence, metadata, first_seen_at, last_seen_at)
     VALUES
      ($1,$2,$3,$4,$5,$6,$7,$8,$9,$10,$11,$12,$13,COALESCE($14,'discovered'),$15,$16,$17,NOW(),NOW())
     ON CONFLICT (tenant_id, site_id, edge_id, asset_key)
     DO UPDATE SET
      asset_name   = EXCLUDED.asset_name,
      display_name = EXCLUDED.display_name,
      asset_type   = EXCLUDED.asset_type,
      vendor       = EXCLUDED.vendor,
      product      = EXCLUDED.product,
      os_hint      = EXCLUDED.os_hint,
      primary_ip   = EXCLUDED.primary_ip,
      hostname     = EXCLUDED.hostname,
      criticality  = EXCLUDED.criticality,
      confidence   = EXCLUDED.confidence,
      metadata     = EXCLUDED.metadata,
      last_seen_at = NOW(),
      updated_at   = NOW()
     RETURNING *`,
    [
      uuidv4(), row.tenant_id, row.site_id, row.edge_id, row.asset_key, row.asset_name, row.display_name,
      row.asset_type || 'host', vendor, product, os_hint,
      row.primary_ip || null, hostname, row.lifecycle_state || 'discovered', row.criticality || 'medium',
      confidence, JSON.stringify(row.metadata || {}),
    ]
  );
  return r.rows[0];
}

async function listTargets(db, ctx) {
  const r = await db.query(
    `SELECT * FROM observe_discovery_targets
     WHERE tenant_id = $1 AND site_id = $2 AND edge_id = $3 AND enabled = true`,
    [ctx.tenant_id, ctx.site_id, ctx.edge_id]
  );
  return r.rows;
}

async function getPolicy(db, policyId, ctx) {
  if (policyId) {
    const p = await db.query(
      `SELECT * FROM observe_discovery_policies
       WHERE id = $1 AND tenant_id = $2 AND site_id = $3 AND edge_id = $4`,
      [policyId, ctx.tenant_id, ctx.site_id, ctx.edge_id]
    );
    return p.rows[0] || null;
  }
  const d = await db.query(
    `SELECT * FROM observe_discovery_policies
     WHERE tenant_id = $1 AND site_id = $2 AND edge_id = $3 AND is_default = true
     ORDER BY updated_at DESC LIMIT 1`,
    [ctx.tenant_id, ctx.site_id, ctx.edge_id]
  );
  return d.rows[0] || null;
}

module.exports = { createRun, completeRun, upsertAsset, listTargets, getPolicy };
