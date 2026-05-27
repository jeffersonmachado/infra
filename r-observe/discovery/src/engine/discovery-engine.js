'use strict';

const { log } = require('../utils/logger');
const { activeScanTarget } = require('../scanners/active');
const { discoverArpTable, enrichArpAssets } = require('../scanners/arp-discovery');
const { discoverLocalDocker } = require('../scanners/docker-local');
const { fingerprintAsset } = require('../fingerprint/engine');
const { buildTopologyEdges } = require('../topology/engine');
const { writeGraph: writeNeo4jGraph, enabled: neo4jEnabled } = require('../topology/graph-store');
const { writeFileSd } = require('../exporters/prometheus-sd');
const { registerApprovedAsset, syncDiscoveredToIcinga } = require('../integrations/icinga');
const { emitEvent } = require('../queues/events');
const { createRun, completeRun, upsertAsset, listTargets, getPolicy } = require('./repository');
const { normalizePolicy, validateTarget } = require('../security/guardrails');
const { expandTargets, chunkTargets } = require('../scanners/target-expansion');

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

async function getHistoricalContext(db, tenant, assetKey, scan) {
  const cert = scan?.tls_sha256 || null;
  const prev = await db.query(
    `SELECT asset_key, asset_type, vendor, product, confidence, metadata, last_seen_at
     FROM observe_assets
     WHERE tenant_id = $1 AND site_id = $2 AND edge_id = $3
       AND (asset_key = $4 OR ($5::text IS NOT NULL AND metadata->'scan'->>'tls_sha256' = $5))
     ORDER BY updated_at DESC
     LIMIT 1`,
    [tenant.tenant_id, tenant.site_id, tenant.edge_id, assetKey, cert]
  );
  if (!prev.rowCount) return null;
  const row = prev.rows[0];
  const lastSeen = row.last_seen_at ? new Date(row.last_seen_at).getTime() : Date.now();
  const ageDays = Math.max(0, (Date.now() - lastSeen) / 86400000);
  const previousFp = row.metadata?.fingerprint || {};
  return {
    asset_key_match: row.asset_key === assetKey,
    cert_match: !!cert && row.metadata?.scan?.tls_sha256 === cert,
    previous_asset_type: row.asset_type || previousFp.asset_type || null,
    previous_technology: previousFp.technology || null,
    previous_product: row.product || previousFp.product || null,
    previous_vendor: row.vendor || previousFp.vendor || null,
    previous_confidence: row.confidence || previousFp.confidence || null,
    age_days: Number(ageDays.toFixed(2)),
  };
}

function lifecycleFromGovernance(fp) {
  if (fp.governance?.auto_approve) return 'approved';
  return 'discovered';
}

async function runDiscovery({ db, redis, input, onProgress }) {
  const report = (update) => { try { if (onProgress) onProgress(update); } catch (_) {} };
  let run = null;

  const tenant = {
    tenant_id: input.tenant_id || 'default',
    site_id: input.site_id || 'default-site',
    edge_id: input.edge_id || 'central',
  };

  try {
	  report({ stage: 'policy', status: 'running' });
	  const policyRow = await getPolicy(db, input.policy_id || null, tenant);
	  if (input.policy_id && !policyRow) throw new Error('Policy not found in discovery scope');
	  const policy = normalizePolicy(policyRow || { scan_profile: input.profile || 'safe' });
  report({ stage: 'policy', status: 'done' });

  report({ stage: 'run_create', status: 'running' });
  run = await createRun(db, { ...tenant, policy_id: policyRow?.id || null, metadata: { trigger: input.trigger || 'manual' } });
  report({ stage: 'run_create', status: 'done', run_id: run.id });

  await emitEvent(redis, 'observe.discovery.started', { run_id: run.id, ...tenant, profile: policy.profile });

  report({ stage: 'targets', status: 'running' });
  const targets = input.targets?.length ? input.targets : await listTargets(db, tenant);
  report({ stage: 'targets', status: 'done', total_targets: targets.length });
  
  // Expand targets (CIDR, ranges, hostnames) → individual IPs
  const expandOptions = {
    maxHosts: policy.max_hosts || 65536,
    maxScanTargets: policy.max_scan_targets || 512,
    excludeRanges: policy.blocked_ranges || [],
    includeRanges: policy.allowed_ranges || [],
  };
  
	  let expandedTargets = targets;
	  let blockedTargets = 0;
	  try {
	    const targetSpecs = targets.map(t => t.address || t);
	    const expansion = await expandTargets(targetSpecs, expandOptions);
	    expandedTargets = expansion.targets;
	    const filteredOut = expansion.filteredOut || [];
	    blockedTargets += filteredOut.length;
	    for (const filtered of filteredOut.slice(0, 500)) {
	      await saveFindings(db, run.id, tenant, {
	        type: 'target_blocked',
	        severity: 'warning',
	        source: 'policy',
	        asset_key: keyFromTarget(filtered),
	        payload: { reason: filtered.reason, target: { address: filtered.address, discovery_type: filtered.discovery_type || 'ip' } },
	      });
	    }
	    if (filteredOut.length > 500) {
	      await saveFindings(db, run.id, tenant, {
	        type: 'target_blocked_summary',
	        severity: 'warning',
	        source: 'policy',
	        asset_key: 'targets',
	        payload: {
	          total_blocked: filteredOut.length,
	          persisted_sample: 500,
	          reasons: filteredOut.reduce((acc, item) => {
	            acc[item.reason] = (acc[item.reason] || 0) + 1;
	            return acc;
	          }, {}),
	        },
	      });
	    }
	    
	    report({ stage: 'targets', status: 'done', 
	      input_targets: targets.length,
      expanded_targets: expansion.totalExpanded,
      unique_targets: expansion.totalUnique,
      filtered_targets: expansion.totalFiltered,
    });
  } catch (e) {
    await saveFindings(db, run.id, tenant, {
      type: 'target_expansion_error',
      severity: 'error',
      source: 'target-expansion',
      asset_key: 'system',
      payload: { message: e.message },
    });
    throw new Error(`Target expansion failed: ${e.message}`);
  }

	  const assets = [];
	  let scanned = 0;

  report({ stage: 'scanning', status: 'running', scanned: 0, discovered: 0, total_targets: expandedTargets.length });

  const scanResults = [];
  const maxConc = Math.max(1, Math.min(50, policy.max_concurrency || 5));
  const throttlePerTargetMs = Math.floor(60000 / Math.max(1, policy.max_rate_per_minute || 300));

  // Use chunked processing for large target sets
  const targetChunks = chunkTargets(expandedTargets, 256);
  let totalProcessed = 0;
  const processTarget = async (t) => {
    const target = { address: t.address, discovery_type: t.discovery_type || 'ip' };
    const guard = validateTarget(target, policy);
    if (!guard.ok) {
      blockedTargets++;
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
    report({ stage: 'scanning', status: 'running', scanned, discovered: assets.length, total_targets: expandedTargets.length });
    scanResults.push({
      name: scan.hostname || `host-${target.address.replace(/\./g, '-')}`,
      address: target.address,
      ports: scan.open_ports || [],
      display_name: scan.hostname || target.address,
    });
    const assetKey = keyFromTarget(target);
    const history = await getHistoricalContext(db, tenant, assetKey, scan);
    const fp = fingerprintAsset({ ...scan, history });

    const row = {
      ...tenant,
      asset_key: assetKey,
      asset_name: scan.hostname || `asset-${target.address.replace(/\./g, '-')}`,
      display_name: scan.hostname || target.address,
      primary_ip: target.address,
      hostname: scan.hostname,
      asset_type: fp.asset_type || 'host',
      vendor: fp.vendor,
      product: fp.product,
      os_hint: fp.os_hint,
      criticality: fp.criticality,
      confidence: fp.confidence,
      lifecycle_state: lifecycleFromGovernance(fp),
      metadata: {
        scan,
        fingerprint: fp,
        inference: fp.inference,
        governance: fp.governance,
        historical_context: history,
        lifecycle: lifecycleFromGovernance(fp),
      },
    };

    const drift = await detectDrift(db, run.id, row);
    const asset = await upsertAsset(db, row);
    asset.services = await storeAssetServices(db, asset, scan);

    await db.query(
      `INSERT INTO observe_service_fingerprints
       (id, tenant_id, site_id, edge_id, asset_id, service_key, fingerprint, confidence, observed_at)
       VALUES (gen_random_uuid(),$1,$2,$3,$4,$5,$6,$7,NOW())
       ON CONFLICT (tenant_id, site_id, edge_id, asset_id, service_key)
       DO UPDATE SET fingerprint = EXCLUDED.fingerprint, confidence = EXCLUDED.confidence, observed_at = NOW()`,
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

  for (const chunk_data of targetChunks) {
    for (const group of chunk(chunk_data, maxConc)) {
      await Promise.all(group.map(processTarget));
    }
    totalProcessed += chunk_data.length;
    report({ stage: 'scanning', status: 'running', scanned, discovered: assets.length, total_targets: expandedTargets.length, processed_chunks: Math.ceil(totalProcessed / 256) });
  }
  report({ stage: 'scanning', status: 'done', scanned, discovered: assets.length, total_targets: expandedTargets.length });

  const arpEnabled = input.arp_discovery_enabled === true || process.env.DISCOVERY_ARP_ENABLED === 'true';
  report({ stage: 'arp', status: arpEnabled ? 'running' : 'skipped' });
  if (arpEnabled) {
    const arpAssets = enrichArpAssets(await discoverArpTable());
    for (const a of arpAssets) {
      const arpRow = {
        ...tenant,
        asset_key: a.mac_address ? `mac:${a.mac_address}` : `ip:${a.primary_ip}`,
        asset_name: a.device_name || a.primary_ip,
        display_name: a.device_name || a.primary_ip,
        primary_ip: a.primary_ip,
        hostname: null,
        asset_type: a.device_type || 'network_device',
        vendor: a.vendor || null,
        product: a.device_name || null,
        os_hint: null,
        criticality: 'medium',
        confidence: a.confidence || 0.9,
        metadata: { arp: a, lifecycle: 'discovered' },
      };
      const saved = await upsertAsset(db, arpRow);
      assets.push({ ...saved, tenant_id: tenant.tenant_id, site_id: tenant.site_id, edge_id: tenant.edge_id, asset_name: arpRow.asset_name, primary_ip: arpRow.primary_ip, services: [] });
      await saveFindings(db, run.id, tenant, {
        type: 'arp_asset_discovered',
        source: 'arp',
        asset_key: arpRow.asset_key,
        payload: a,
      });
    }
    report({ stage: 'arp', status: 'done', arp_discovered: arpAssets.length });
  }

  const dockerEnabled = input.docker_discovery_enabled === true || process.env.DISCOVERY_DOCKER_ENABLED === 'true';
  report({ stage: 'docker', status: dockerEnabled ? 'running' : 'skipped' });
  const dockerAssets = dockerEnabled ? await discoverLocalDocker() : [];
  for (const c of dockerAssets) {
    await saveFindings(db, run.id, tenant, {
      type: 'docker_container_discovered',
      source: 'docker-local',
      asset_key: `docker:${c.container_id}`,
      payload: c,
    });
  }
  report({ stage: 'docker', status: 'done', docker_found: dockerAssets.length });

  report({ stage: 'topology', status: 'running' });
  const edges = buildTopologyEdges(run.id, assets);
  for (const e of edges) {
    await db.query(
      `INSERT INTO observe_topology_edges
        (id, tenant_id, site_id, edge_id, run_id, from_asset_id, to_asset_ref, edge_type, protocol, source, confidence, evidence, last_seen, observed_at)
       VALUES (gen_random_uuid(),$1,$2,$3,$4,$5,$6,$7,$8,$9,$10,$11,NOW(),NOW())
       ON CONFLICT (tenant_id, site_id, edge_id, from_asset_id, to_asset_ref, edge_type, protocol)
       DO UPDATE SET observed_at = NOW(), source = EXCLUDED.source,
                     confidence = EXCLUDED.confidence, evidence = EXCLUDED.evidence, last_seen = NOW()`,
      [tenant.tenant_id, tenant.site_id, tenant.edge_id, run.id,
       e.from_asset_id, e.to_asset_ref, e.edge_type, e.protocol, e.source,
       e.confidence ?? 0.5, JSON.stringify(e.evidence ?? [])]
    );
  }

  report({ stage: 'topology', status: 'done', topology_edges: edges.length });

  if (neo4jEnabled()) {
    try {
      await writeNeo4jGraph({ tenant, runId: run.id, assets, edges });
      await emitEvent(redis, 'observe.discovery.graph.updated', { run_id: run.id, ...tenant, nodes: assets.length, edges: edges.length });
    } catch (e) {
      await saveFindings(db, run.id, tenant, {
        type: 'neo4j_write_error',
        severity: 'warning',
        source: 'neo4j',
        asset_key: 'neo4j:graph',
        payload: { message: e.message },
      });
    }
  }

  report({ stage: 'prometheus_sd', status: policy.auto_prometheus_sd ? 'running' : 'skipped' });
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

  report({ stage: 'prometheus_sd', status: 'done' });

  report({ stage: 'icinga', status: policy.auto_icinga_sync ? 'running' : 'skipped' });
  if (policy.auto_icinga_sync) {
    try {
      const sync = await syncDiscoveredToIcinga({ tenant, assets });
      await emitEvent(redis, 'observe.discovery.icinga_sync_completed', {
        run_id: run.id,
        ...tenant,
        synced: sync.synced,
        staged: sync.staged,
        deployed: sync.deployed,
        stale: sync.reconcile?.stale || 0,
        removed: sync.reconcile?.removed || 0,
      });
    } catch (e) {
      await saveFindings(db, run.id, tenant, {
        type: 'icinga_sync_error',
        severity: 'warning',
        source: 'icinga',
        asset_key: 'icinga:sync',
        payload: { message: e.message },
      });
      await emitEvent(redis, 'observe.discovery.icinga_error', {
        run_id: run.id,
        ...tenant,
        message: e.message,
      });
    }
  }

  report({ stage: 'icinga', status: 'done' });

	  const summary = { scanned_targets: scanned, blocked_targets: blockedTargets, discovered_assets: assets.length, topology_edges: edges.length, file_sd_targets: sd.total };
  await completeRun(db, run.id, 'completed', summary);
  report({ stage: 'done', status: 'done', summary });

  if (targets.length === 0) {
    log('warn', 'Discovery run completed without targets', { run_id: run.id, tenant_id: tenant.tenant_id, site_id: tenant.site_id, edge_id: tenant.edge_id });
	  } else if (blockedTargets > 0 && expandedTargets.length === 0) {
	    log('warn', 'Discovery run completed with all targets blocked by policy', {
	      run_id: run.id,
	      tenant_id: tenant.tenant_id,
	      site_id: tenant.site_id,
	      edge_id: tenant.edge_id,
	      total_targets: targets.length,
	      blocked_targets: blockedTargets,
	    });
  }

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

  log('info', 'Discovery run completed', { run_id: run.id, total_targets: targets.length, blocked_targets: blockedTargets, ...summary });
  return { run_id: run.id, summary };
  } catch (e) {
    const summary = { error: e.message, stage: _safeStage(onProgress) };
    if (run?.id) {
      try {
        await completeRun(db, run.id, 'failed', summary);
        await saveFindings(db, run.id, tenant, {
          type: 'discovery_run_failed',
          severity: 'error',
          source: 'discovery-engine',
          asset_key: 'run',
          payload: summary,
        });
      } catch (persistErr) {
        log('error', 'Failed to persist discovery failure state', { run_id: run.id, err: persistErr.message });
      }
      await emitEvent(redis, 'observe.discovery.failed', { run_id: run.id, ...tenant, summary });
    }
    report({ stage: 'done', status: 'error', summary });
    log('error', 'Discovery run failed', { run_id: run?.id || null, err: e.message });
    throw e;
  }
}

function _safeStage() {
  return 'unknown';
}

module.exports = { runDiscovery };
