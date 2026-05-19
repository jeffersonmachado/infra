'use strict';

const { log } = require('../utils/logger');
const { activeScanTarget } = require('../scanners/active');
const { discoverLocalDocker } = require('../scanners/docker-local');
const { fingerprintAsset } = require('../fingerprint/engine');
const { buildTopologyEdges } = require('../topology/engine');
const { writeFileSd } = require('../exporters/prometheus-sd');
const { registerApprovedAsset } = require('../integrations/icinga');
const { emitEvent } = require('../queues/events');
const { createRun, completeRun, upsertAsset, listTargets, getPolicy } = require('./repository');
const { normalizePolicy, validateTarget } = require('../security/guardrails');

function sleep(ms) {
  return new Promise((resolve) => setTimeout(resolve, ms));
}

function chunk(items, size) {
  const out = [];
  for (let i = 0; i < items.length; i += size) out.push(items.slice(i, i + size));
  return out;
}

function keyFromTarget(t) {
  return `${t.discovery_type || 'ip'}:${t.address}`;
}

async function saveFindings(db, runId, tenant, finding) {
  await db.query(
    `INSERT INTO observe_discovery_findings
      (id, run_id, tenant_id, site_id, edge_id, finding_type, severity, source, asset_key, payload)
     VALUES (gen_random_uuid(),$1,$2,$3,$4,$5,$6,$7,$8,$9)`,
    [runId, tenant.tenant_id, tenant.site_id, tenant.edge_id, finding.type, finding.severity || 'info', finding.source, finding.asset_key, JSON.stringify(finding.payload || {})]
  );
}

async function storeAssetServices(db, asset, scan) {
  const services = [];
  for (const port of scan.open_ports || []) {
    const proto = [53, 67, 68, 123, 161, 5060].includes(port) ? 'udp' : 'tcp';
    const exporter = [9100, 9104, 9108, 9115, 9117, 9121, 9187, 9256, 9419, 9090, 9091].includes(port);
    const job = port === 9100 ? 'node-exporter' : port === 9187 ? 'postgres-exporter' : port === 9090 ? 'prometheus' : 'generic-metrics';
    await db.query(
      `INSERT INTO observe_asset_services
        (id, tenant_id, site_id, edge_id, asset_id, service_key, service_name, protocol, port, status, fingerprint, first_seen_at, last_seen_at)
       VALUES (gen_random_uuid(),$1,$2,$3,$4,$5,$6,$7,$8,'open',$9,NOW(),NOW())
       ON CONFLICT (tenant_id, site_id, edge_id, asset_id, service_key)
       DO UPDATE SET status = 'open', fingerprint = EXCLUDED.fingerprint, last_seen_at = NOW(), updated_at = NOW()`,
      [
        asset.tenant_id, asset.site_id, asset.edge_id, asset.id,
        `${proto}:${port}`,
        `port-${port}`,
        proto,
        port,
        JSON.stringify({ protocol: proto, port, http_server: scan.http_server || null }),
      ]
    );
    services.push({
      protocol: proto,
      port,
      exporter_target: exporter ? `${asset.primary_ip}:${port}` : null,
      job: exporter ? job : null,
      dependency_target: [5432, 3306, 53].includes(port) ? `${asset.primary_ip}:${port}` : null,
    });
  }
  return services;
}

async function persistDependencies(db, tenant, fromAssetId, services) {
  for (const svc of services) {
    let type = null;
    if ([3306, 5432].includes(svc.port)) type = 'service_database';
    if (svc.port === 53) type = 'service_dns';
    if ([9100, 9104, 9108, 9115, 9117, 9121, 9187, 9256, 9419, 9090, 9091].includes(svc.port)) type = 'service_exporter';
    if (!type) continue;
    await db.query(
      `INSERT INTO observe_dependencies
        (id, tenant_id, site_id, edge_id, upstream_asset_id, downstream_asset_id, dependency_type, confidence, metadata, first_seen_at, last_seen_at)
       VALUES (gen_random_uuid(),$1,$2,$3,$4,NULL,$5,$6,$7,NOW(),NOW())`,
      [tenant.tenant_id, tenant.site_id, tenant.edge_id, fromAssetId, type, 0.7, JSON.stringify({ port: svc.port, protocol: svc.protocol })]
    );
  }
}

async function detectDrift(db, runId, asset) {
  const prev = await db.query(
    `SELECT id, primary_ip, hostname, product, vendor, confidence FROM observe_assets
     WHERE tenant_id = $1 AND site_id = $2 AND edge_id = $3 AND asset_key = $4 LIMIT 1`,
    [asset.tenant_id, asset.site_id, asset.edge_id, asset.asset_key]
  );
  if (!prev.rowCount) return [];
  const p = prev.rows[0];
  const changes = [];
  const maybe = (field, oldVal, newVal) => {
    if ((oldVal || null) !== (newVal || null)) changes.push({ field, old_value: oldVal, new_value: newVal });
  };
  maybe('primary_ip', p.primary_ip, asset.primary_ip);
  maybe('hostname', p.hostname, asset.hostname);
  maybe('product', p.product, asset.product);
  maybe('vendor', p.vendor, asset.vendor);

  for (const c of changes) {
    await db.query(
      `INSERT INTO observe_asset_changes
        (id, tenant_id, site_id, edge_id, asset_id, run_id, change_type, field_name, old_value, new_value, changed_at)
       VALUES (gen_random_uuid(),$1,$2,$3,$4,$5,'attribute_changed',$6,$7,$8,NOW())`,
      [asset.tenant_id, asset.site_id, asset.edge_id, p.id, runId, c.field, String(c.old_value || ''), String(c.new_value || '')]
    );
  }
  return changes;
}

async function runDiscovery({ db, redis, input }) {
  const tenant = {
    tenant_id: input.tenant_id || 'default',
    site_id: input.site_id || 'default-site',
    edge_id: input.edge_id || 'central',
  };

  const policyRow = await getPolicy(db, input.policy_id || null, tenant);
  const policy = normalizePolicy(policyRow || { scan_profile: input.profile || 'safe' });
  const run = await createRun(db, { ...tenant, policy_id: policyRow?.id || null, metadata: { trigger: input.trigger || 'manual' } });

  await emitEvent(redis, 'observe.discovery.started', { run_id: run.id, ...tenant, profile: policy.profile });

  const targets = input.targets?.length ? input.targets : await listTargets(db, tenant);
  const assets = [];
  let scanned = 0;

  const scanResults = [];
  const maxConc = Math.max(1, Math.min(50, policy.max_concurrency || 5));
  const throttlePerTargetMs = Math.floor(60000 / Math.max(1, policy.max_rate_per_minute || 300));

  const processTarget = async (t) => {
    const target = { address: t.address, discovery_type: t.discovery_type || 'ip' };
    const guard = validateTarget(target, policy);
    if (!guard.ok) {
      await saveFindings(db, run.id, tenant, {
        type: 'target_blocked',
        severity: 'warning',
        source: 'policy',
        asset_key: keyFromTarget(target),
        payload: { reason: guard.reason, target },
      });
      return null;
    }

    scanned++;
    const scan = policy.active_enabled ? await activeScanTarget(target, policy) : { address: target.address, open_ports: [] };
    scanResults.push({
      name: scan.hostname || `host-${target.address.replace(/\./g, '-')}`,
      address: target.address,
      ports: scan.open_ports || [],
      display_name: scan.hostname || target.address,
    });
    const fp = fingerprintAsset(scan);

    const row = {
      ...tenant,
      asset_key: keyFromTarget(target),
      asset_name: scan.hostname || `asset-${target.address.replace(/\./g, '-')}`,
      display_name: scan.hostname || target.address,
      primary_ip: target.address,
      hostname: scan.hostname,
      asset_type: 'host',
      vendor: fp.vendor,
      product: fp.product,
      os_hint: fp.os_hint,
      criticality: fp.criticality,
      confidence: fp.confidence,
      metadata: { scan, fingerprint: fp, lifecycle: 'discovered' },
    };

    const drift = await detectDrift(db, run.id, row);
    const asset = await upsertAsset(db, row);
    asset.services = await storeAssetServices(db, asset, scan);

    await db.query(
      `INSERT INTO observe_service_fingerprints
       (id, tenant_id, site_id, edge_id, asset_id, service_key, fingerprint, confidence, observed_at)
       VALUES (gen_random_uuid(),$1,$2,$3,$4,$5,$6,$7,NOW())`,
      [asset.tenant_id, asset.site_id, asset.edge_id, asset.id, 'asset', JSON.stringify(fp), fp.confidence]
    );

    assets.push({ ...asset, tenant_id: tenant.tenant_id, site_id: tenant.site_id, edge_id: tenant.edge_id, asset_name: row.asset_name, primary_ip: row.primary_ip, services: asset.services });

    await persistDependencies(db, tenant, asset.id, asset.services);
    await db.query(
      `INSERT INTO observe_asset_history
        (id, tenant_id, site_id, edge_id, asset_id, snapshot, captured_at, snapshot_type)
       VALUES (gen_random_uuid(),$1,$2,$3,$4,$5,NOW(),'discovery-run')`,
      [tenant.tenant_id, tenant.site_id, tenant.edge_id, asset.id, JSON.stringify({ asset: row, fingerprint: fp, services: asset.services })]
    );

    await emitEvent(redis, drift.length ? 'observe.discovery.asset_changed' : 'observe.discovery.asset_found', {
      run_id: run.id,
      ...tenant,
      asset_id: asset.id,
      asset_key: asset.asset_key,
      changes: drift,
    });
    if (throttlePerTargetMs > 0) await sleep(throttlePerTargetMs);
    return asset;
  };

  for (const group of chunk(targets, maxConc)) {
    await Promise.all(group.map(processTarget));
  }

  const dockerEnabled = input.docker_discovery_enabled === true || process.env.DISCOVERY_DOCKER_ENABLED === 'true';
  const dockerAssets = dockerEnabled ? await discoverLocalDocker() : [];
  for (const c of dockerAssets) {
    await saveFindings(db, run.id, tenant, {
      type: 'docker_container_discovered',
      source: 'docker-local',
      asset_key: `docker:${c.container_id}`,
      payload: c,
    });
  }

  const edges = buildTopologyEdges(run.id, assets);
  for (const e of edges) {
    await db.query(
      `INSERT INTO observe_topology_edges
        (id, tenant_id, site_id, edge_id, run_id, from_asset_id, to_asset_ref, edge_type, protocol, source, observed_at)
       VALUES (gen_random_uuid(),$1,$2,$3,$4,$5,$6,$7,$8,$9,NOW())
       ON CONFLICT ON CONSTRAINT uq_observe_topology_edges_dedupe
       DO UPDATE SET observed_at = NOW(), source = EXCLUDED.source`,
      [tenant.tenant_id, tenant.site_id, tenant.edge_id, run.id, e.from_asset_id, e.to_asset_ref, e.edge_type, e.protocol, e.source]
    );
  }

  let sd = { path: null, total: 0 };
  if (policy.auto_prometheus_sd) {
    try {
      sd = await writeFileSd(assets);
    } catch (e) {
      await saveFindings(db, run.id, tenant, {
        type: 'prometheus_sd_write_error',
        severity: 'warning',
        source: 'prometheus-sd',
        asset_key: 'prometheus:http-sd',
        payload: { message: e.message },
      });
      await emitEvent(redis, 'observe.discovery.prometheus_sd_error', {
        run_id: run.id,
        ...tenant,
        message: e.message,
      });
    }
  }

  if (policy.auto_icinga_sync) {
    for (const a of assets.filter((x) => x.lifecycle_state === 'approved' || x.lifecycle_state === 'monitored')) {
      try {
        await registerApprovedAsset(a);
      } catch (e) {
        await saveFindings(db, run.id, tenant, {
          type: 'icinga_sync_error',
          severity: 'warning',
          source: 'icinga',
          asset_key: a.asset_key,
          payload: { message: e.message },
        });
        await emitEvent(redis, 'observe.discovery.icinga_error', {
          run_id: run.id,
          ...tenant,
          asset_id: a.id,
          asset_key: a.asset_key,
          message: e.message,
        });
      }
    }
  }

  const summary = { scanned_targets: scanned, discovered_assets: assets.length, topology_edges: edges.length, file_sd_targets: sd.total };
  await completeRun(db, run.id, 'completed', summary);

  await emitEvent(redis, 'observe.discovery.topology.updated', { run_id: run.id, ...tenant, edges: edges.length });
  await emitEvent(redis, 'observe.discovery.completed', { run_id: run.id, ...tenant, summary });

  if (redis) {
    const resultEvt = JSON.stringify({
      type: 'scan:results',
      run_id: run.id,
      tenant_id: tenant.tenant_id,
      site_id: tenant.site_id,
      edge_id: tenant.edge_id,
      hosts: scanResults,
      created_at: new Date().toISOString(),
    });
    await redis.rpush('observe:scan:results', resultEvt);
    await redis.publish('observe:scan:results', resultEvt);
  }

  log('info', 'Discovery run completed', { run_id: run.id, ...summary });
  return { run_id: run.id, summary };
}

module.exports = { runDiscovery };
