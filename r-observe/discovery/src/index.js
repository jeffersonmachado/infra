'use strict';

const path = require('path');
const express = require('express');
const helmet = require('helmet');
const rateLimit = require('express-rate-limit');
const Redis = require('ioredis');
const client = require('prom-client');

const { log } = require('./utils/logger');
const { runDiscovery } = require('./engine/discovery-engine');
const { normalizePassiveEvent } = require('./passive/parser');
const { startPassiveReceivers } = require('./passive/receivers');
const { shortestPath, blastRadius, writeGraph: writeNeo4jGraph, enabled: neo4jEnabled, closeDriver: closeNeo4jDriver } = require('./topology/graph-store');
const { buildTopologyEdges } = require('./topology/engine');
const { fingerprintAsset } = require('./fingerprint/engine');
const { aiFingerprint, shouldEnrichWithAI } = require('./fingerprint/ai-enrichment');
const { toPromSdGroups } = require('./exporters/prometheus-sd');
const { emitEvent } = require('./queues/events');
const { upsertAsset } = require('./engine/repository');
const { createDbClient } = require('./db');

const PORT = parseInt(process.env.PORT || '3010', 10);
const INTERNAL_TOKEN = process.env.INTERNAL_TOKEN || '';
const SCAN_QUEUE_ENABLED = process.env.DISCOVERY_SCAN_QUEUE_ENABLED !== 'false';
const DOCKER_DISCOVERY_ENABLED = process.env.DISCOVERY_DOCKER_ENABLED === 'true';
const PASSIVE_LISTENERS_ENABLED = process.env.DISCOVERY_PASSIVE_LISTENERS_ENABLED !== 'false';

// ── Progresso em memória da varredura atual ────────────────────────────────
const PIPELINE_STAGES = ['policy', 'run_create', 'targets', 'scanning', 'docker', 'topology', 'prometheus_sd', 'icinga', 'done'];

// ── Dedup de findings passivos (janela de 5 minutos por chave) ─────────────
const FINDING_DEDUP_TTL_MS = 5 * 60 * 1000;
const _findingDedupCache = new Map();
function isDuplicateFinding(tenantId, siteId, edgeId, source, assetKey) {
  const key = `${tenantId}|${siteId}|${edgeId}|${source}|${assetKey}`;
  const now = Date.now();
  const last = _findingDedupCache.get(key);
  if (last && now - last < FINDING_DEDUP_TTL_MS) return true;
  _findingDedupCache.set(key, now);
  if (_findingDedupCache.size > 10000) {
    for (const [k, ts] of _findingDedupCache) {
      if (now - ts > FINDING_DEDUP_TTL_MS) _findingDedupCache.delete(k);
    }
  }
  return false;
}

let _progress = null;

function makeOnProgress() {
  const startedAt = new Date().toISOString();
  const state = {
    active: true,
    run_id: null,
    started_at: startedAt,
    stage: 'policy',
    stages: Object.fromEntries(PIPELINE_STAGES.map((s) => [s, 'pending'])),
    scanned: 0,
    total_targets: 0,
    discovered: 0,
    docker_found: 0,
    topology_edges: 0,
    summary: null,
  };
  _progress = state;
  return (update) => {
    if (update.run_id) state.run_id = update.run_id;
    if (update.stage) state.stage = update.stage;
    if (update.status && update.stage) state.stages[update.stage] = update.status;
    if (update.scanned != null) state.scanned = update.scanned;
    if (update.total_targets != null) state.total_targets = update.total_targets;
    if (update.discovered != null) state.discovered = update.discovered;
    if (update.docker_found != null) state.docker_found = update.docker_found;
    if (update.topology_edges != null) state.topology_edges = update.topology_edges;
    if (update.summary) state.summary = update.summary;
    if (update.stage === 'done' && update.status === 'done') state.active = false;
    _progress = { ...state };
  };
}

const db = createDbClient(process.env);

const redis = process.env.REDIS_URL ? new Redis(process.env.REDIS_URL, { lazyConnect: true, maxRetriesPerRequest: 3 }) : null;
if (redis) redis.connect().catch((e) => log('warn', 'Redis connect failed', { err: e.message }));

const register = new client.Registry();
client.collectDefaultMetrics({ register });
const runsTotal = new client.Counter({ name: 'r_observe_discovery_runs_total', help: 'Total discovery runs', labelNames: ['status'], registers: [register] });

function incRunMetric(status) {
  try { runsTotal.inc({ status }); } catch (_) {}
}

const app = express();

let _vite = null;
if (process.env.NODE_ENV === 'development') {
  app.use((req, res, next) => {
    if (_vite) return _vite.middlewares.handle(req, res, next);
    next();
  });
} else {
  app.use('/observe/discovery/data', express.static(path.join(__dirname, '../public')));
}
app.use(helmet({ contentSecurityPolicy: false }));
app.use(express.json({ limit: '1mb' }));
app.use(rateLimit({ windowMs: 60_000, max: 120 }));

const requireAuth = (req, res, next) => {
  if (!INTERNAL_TOKEN) return next();
  const provided = req.headers['x-internal-token'] || (req.headers.authorization || '').replace(/^Bearer\s+/i, '');
  if (provided !== INTERNAL_TOKEN) return res.status(401).json({ error: 'Unauthorized' });
  next();
};

function parseScope(input = {}) {
  return {
    tenant_id: String(input.tenant_id || 'default'),
    site_id: String(input.site_id || 'default-site'),
    edge_id: String(input.edge_id || 'central'),
  };
}

function validateScanInput(body = {}) {
  const validProfiles = ['safe', 'balanced', 'aggressive'];
  if (body.profile && !validProfiles.includes(body.profile)) {
    return 'profile inválido';
  }
  if (body.targets && !Array.isArray(body.targets)) {
    return 'targets deve ser array';
  }
  return null;
}

function asyncRoute(fn) {
  return (req, res, next) => Promise.resolve(fn(req, res, next)).catch(next);
}

async function ensurePolicyInScope(policyId, scope) {
  if (!policyId) return true;
  const r = await db.query(
    `SELECT 1 FROM observe_discovery_policies
     WHERE id = $1 AND tenant_id = $2 AND site_id = $3 AND edge_id = $4`,
    [policyId, scope.tenant_id, scope.site_id, scope.edge_id]
  );
  return r.rowCount > 0;
}

function passiveAssetKey(evt) {
  if (evt.mac) return `mac:${evt.mac}`;
  if (evt.source_ip) return `ip:${evt.source_ip}`;
  if (evt.hostname) return `host:${evt.hostname}`;
  return `passive:${evt.type}:${Date.now()}`;
}

function passiveAssetName(evt) {
  return evt.hostname || evt.source_ip || evt.mac || `passive-${evt.type}`;
}

function passiveAssetType(evt) {
  const payloadText = JSON.stringify(evt?.payload || {}).toLowerCase();
  if (payloadText.includes('smarttv') || payloadText.includes('_airplay') || payloadText.includes('mediarenderer') || payloadText.includes('mediaserver') || payloadText.includes('dlna')) return 'media_device';
  if (payloadText.includes('_googlecast') || payloadText.includes('_androidtvremote') || payloadText.includes('_companion-link') || payloadText.includes('_airdrop') || payloadText.includes('android')) return 'mobile';
  if (payloadText.includes('linux') || payloadText.includes('avahi') || payloadText.includes('debian') || payloadText.includes('ubuntu')) return 'host';
  if (evt.type === 'snmp_trap') return 'network_device';
  if (evt.type === 'lldp' || evt.type === 'cdp') return 'network_device';
  if (evt.type === 'ssdp' || evt.type === 'mdns') return 'iot';
  if (evt.type === 'dhcp' || evt.type === 'arp_change') return 'host';
  return 'host';
}

function protocolForPassiveType(type) {
  if (['mdns', 'ssdp', 'snmp_trap', 'dhcp'].includes(type)) return 'udp';
  return 'tcp';
}

function inferPassivePorts(evt) {
  const ports = new Set();
  const addIfValid = (port) => {
    const n = Number(port);
    if (Number.isInteger(n) && n > 0 && n <= 65535) ports.add(n);
  };

  addIfValid(evt?.payload?.port);

  const byType = {
    mdns: [5353],
    ssdp: [1900],
    snmp_trap: [162],
    dhcp: [67],
  };
  for (const p of byType[evt?.type] || []) addIfValid(p);
  if (!byType[evt?.type] && evt?.payload?.source_port) addIfValid(evt.payload.source_port);

  return Array.from(ports).sort((a, b) => a - b);
}

async function storePassiveServices({ dbConn, asset, tenant, parsed, ports, fingerprint }) {
  const protocol = protocolForPassiveType(parsed.type);
  const services = [];

  for (const port of ports) {
    const serviceKey = `${protocol}:${port}`;
    await dbConn.query(
      `INSERT INTO observe_asset_services
        (id, tenant_id, site_id, edge_id, asset_id, service_key, service_name, protocol, port, status, fingerprint, first_seen_at, last_seen_at)
       VALUES (gen_random_uuid(),$1,$2,$3,$4,$5,$6,$7,$8,'open',$9,NOW(),NOW())
       ON CONFLICT (tenant_id, site_id, edge_id, asset_id, service_key)
       DO UPDATE SET
         service_name = EXCLUDED.service_name,
         protocol = EXCLUDED.protocol,
         port = EXCLUDED.port,
         status = 'open',
         fingerprint = EXCLUDED.fingerprint,
         last_seen_at = NOW(),
         updated_at = NOW()`,
      [
        tenant.tenant_id,
        tenant.site_id,
        tenant.edge_id,
        asset.id,
        serviceKey,
        `passive-${parsed.type}-${port}`,
        protocol,
        port,
        JSON.stringify({ passive_type: parsed.type, fingerprint }),
      ]
    );

    services.push({
      protocol,
      port,
      dependency_target: [53, 3306, 5432].includes(port) ? `${asset.primary_ip || 'unknown'}:${port}` : null,
    });
  }

  return services;
}

async function persistPassiveTopology({ dbConn, tenant, asset, services }) {
  if (!services.length) return 0;
  const edges = buildTopologyEdges(null, [{ ...asset, services }]);
  for (const edge of edges) {
    await dbConn.query(
      `INSERT INTO observe_topology_edges
        (id, tenant_id, site_id, edge_id, run_id, from_asset_id, to_asset_ref, edge_type, protocol, source, observed_at, metadata)
       VALUES (gen_random_uuid(),$1,$2,$3,NULL,$4,$5,$6,$7,'passive-discovery',NOW(),$8)
       ON CONFLICT (tenant_id, site_id, edge_id, from_asset_id, to_asset_ref, edge_type, protocol)
       DO UPDATE SET
         observed_at = NOW(),
         source = EXCLUDED.source,
         metadata = EXCLUDED.metadata`,
      [
        tenant.tenant_id,
        tenant.site_id,
        tenant.edge_id,
        asset.id,
        edge.to_asset_ref,
        edge.edge_type,
        edge.protocol || null,
        JSON.stringify({ passive: true }),
      ]
    );
  }
  return edges.length;
}

async function ingestPassiveEvents(events) {
  const list = Array.isArray(events) ? events : [events];
  let accepted = 0;

  for (const evt of list) {
    const parsed = normalizePassiveEvent(evt);
    if (!parsed) continue;
    const tenant = parseScope(evt || {});

    const _assetKey = passiveAssetKey(parsed);
    const _source = `passive:${parsed.type}`;
    if (!isDuplicateFinding(tenant.tenant_id, tenant.site_id, tenant.edge_id, _source, _assetKey)) {
      await db.query(
        `INSERT INTO observe_discovery_findings
          (id, run_id, tenant_id, site_id, edge_id, finding_type, severity, source, asset_key, payload, observed_at)
         VALUES (gen_random_uuid(),NULL,$1,$2,$3,'passive_signal','info',$4,$5,$6,NOW())`,
        [
          tenant.tenant_id,
          tenant.site_id,
          tenant.edge_id,
          _source,
          _assetKey,
          JSON.stringify(parsed),
        ]
      );
    }

    const passivePorts = inferPassivePorts(parsed);
    const mdnsServices    = parsed.payload?.mdns_services || [];
    const txtManufacturer = parsed.payload?.txt_manufacturer || null;
    const txtModel        = parsed.payload?.txt_model || null;

    // Banner de texto: concatena raw + serviços mDNS para detecção por string
    const smtpBanner = [
      parsed.payload?.message,
      parsed.payload?.raw,
      mdnsServices.join(' '),
      txtManufacturer,
      txtModel,
    ].filter(Boolean).join(' ') || null;

    const fp = fingerprintAsset({
      mac:           parsed.mac || null,
      hostname:      parsed.hostname || null,
      open_ports:    passivePorts,
      mdns_services: mdnsServices,
      txt_manufacturer: txtManufacturer,
      txt_model:     txtModel,
      snmp_sysdescr: parsed.type === 'snmp_trap' ? (parsed.payload?.raw_text || null) : null,
      http_server:   parsed.payload?.server || null,
      http_title:    parsed.payload?.st || null,
      smtp_banner:   smtpBanner,
      os_hint:       parsed.payload?.os_hint || null,
    });

    const row = {
      ...tenant,
      asset_key: _assetKey,
      asset_name: passiveAssetName(parsed),
      display_name: passiveAssetName(parsed),
      primary_ip: parsed.source_ip || null,
      hostname: parsed.hostname || null,
      asset_type: passiveAssetType(parsed),
      vendor: fp.vendor || null,
      product: fp.product || parsed.type,
      os_hint: null,
      criticality: 'medium',
      confidence: Math.max(0.6, Number(fp.confidence || 0.7)),
      metadata: { passive: parsed, fingerprint: fp, lifecycle: 'discovered' },
    };
    const asset = await upsertAsset(db, row);
    const services = await storePassiveServices({ dbConn: db, asset, tenant, parsed, ports: passivePorts, fingerprint: fp });

    // Enriquecimento AI assíncrono: não bloqueia o pipeline principal
    if (shouldEnrichWithAI(fp)) {
      aiFingerprint({
        mac:              parsed.mac || null,
        mac_vendor:       fp.vendor !== 'Não identificado' ? fp.vendor : null,
        hostname:         parsed.hostname || null,
        ip:               parsed.source_ip || null,
        open_ports:       passivePorts,
        mdns_services:    mdnsServices,
        ssdp_server:      parsed.payload?.server || null,
        txt_manufacturer: txtManufacturer,
        txt_model:        txtModel,
        txt_friendly_name: parsed.payload?.txt_friendly_name || null,
        raw_mdns:         parsed.type === 'mdns' ? (parsed.payload?.raw || null) : null,
        raw_ssdp:         parsed.type === 'ssdp' ? (parsed.payload?.message || null) : null,
      }).then(async (aiResult) => {
        if (!aiResult || aiResult.confidence < 0.6) return;
        const aiVendor  = aiResult.vendor  || fp.vendor;
        const aiProduct = aiResult.product || fp.product;
        if (aiVendor === fp.vendor && aiProduct === fp.product) return;
        await upsertAsset(db, {
          ...row,
          vendor:     aiVendor,
          product:    aiProduct,
          confidence: Math.max(row.confidence, aiResult.confidence),
          metadata:   { ...row.metadata, fingerprint: { ...fp, ...aiResult, source: 'ai' } },
        });
        log('info', 'AI enriqueceu ativo', { asset_key: asset.asset_key, vendor: aiVendor, product: aiProduct });
      }).catch(e => log('warn', 'AI enrichment async error', { err: e.message }));
    }

    await db.query(
      `INSERT INTO observe_service_fingerprints
       (id, tenant_id, site_id, edge_id, asset_id, service_key, fingerprint, confidence, observed_at)
       VALUES (gen_random_uuid(),$1,$2,$3,$4,$5,$6,$7,NOW())
       ON CONFLICT (tenant_id, site_id, edge_id, asset_id, service_key)
       DO UPDATE SET fingerprint = EXCLUDED.fingerprint, confidence = EXCLUDED.confidence, observed_at = NOW()`,
      [tenant.tenant_id, tenant.site_id, tenant.edge_id, asset.id, `passive:${parsed.type}`, JSON.stringify(fp), fp.confidence || 0.7]
    );

    await db.query(
      `INSERT INTO observe_asset_history
        (id, tenant_id, site_id, edge_id, asset_id, snapshot, captured_at, snapshot_type)
       VALUES (gen_random_uuid(),$1,$2,$3,$4,$5,NOW(),'passive-event')`,
      [tenant.tenant_id, tenant.site_id, tenant.edge_id, asset.id, JSON.stringify({ asset: row, fingerprint: fp, services, passive: parsed })]
    );

    const writtenEdges = await persistPassiveTopology({ dbConn: db, tenant, asset: { ...asset, primary_ip: row.primary_ip }, services });

    if (neo4jEnabled() && writtenEdges > 0) {
      try {
        const neoEdges = buildTopologyEdges(null, [{ ...asset, primary_ip: row.primary_ip, services }]);
        await writeNeo4jGraph({ tenant, runId: null, assets: [{ ...asset, asset_name: row.asset_name, primary_ip: row.primary_ip }], edges: neoEdges });
      } catch (e) {
        log('warn', 'Passive Neo4j write failed', { err: e.message, asset_key: asset.asset_key });
      }
    }

    await emitEvent(redis, 'observe.discovery.asset_found', {
      passive: true,
      tenant_id: tenant.tenant_id,
      site_id: tenant.site_id,
      edge_id: tenant.edge_id,
      asset_id: asset.id,
      asset_key: asset.asset_key,
      type: parsed.type,
      topology_edges: writtenEdges,
      confidence: fp.confidence || 0.7,
    });

    accepted++;
  }

  return accepted;
}

app.get('/health', async (_req, res) => {
  let dbOk = true;
  try { await db.query('SELECT 1'); } catch { dbOk = false; }
  res.status(dbOk ? 200 : 503).json({ status: dbOk ? 'ok' : 'degraded', service: 'r-observe-discovery' });
});

app.get('/api/discovery/health', async (_req, res) => {
  let dbOk = true;
  try { await db.query('SELECT 1'); } catch { dbOk = false; }
  res.status(dbOk ? 200 : 503).json({ status: dbOk ? 'ok' : 'degraded', service: 'r-observe-discovery' });
});

app.get('/metrics', async (_req, res) => {
  res.set('Content-Type', register.contentType);
  res.end(await register.metrics());
});

app.get('/api/discovery/progress', (_req, res) => {
  if (!_progress) return res.json({ active: false });
  const elapsed_ms = Date.now() - new Date(_progress.started_at).getTime();
  res.json({ ..._progress, elapsed_ms });
});

app.post('/api/discovery/scan', requireAuth, asyncRoute(async (req, res) => {
  const err = validateScanInput(req.body || {});
  if (err) return res.status(400).json({ error: err });
  const payload = { ...(req.body || {}), docker_discovery_enabled: DOCKER_DISCOVERY_ENABLED };

  if (SCAN_QUEUE_ENABLED && redis) {
    await redis.rpush('observe:scan:network', JSON.stringify(payload));
    await redis.publish('observe:scan:network', JSON.stringify({ event: 'scan_enqueued', ts: new Date().toISOString() }));
    _progress = { active: true, queued: true, started_at: new Date().toISOString(), stage: 'queued',
      stages: Object.fromEntries(PIPELINE_STAGES.map((s) => [s, 'pending'])),
      scanned: 0, total_targets: 0, discovered: 0 };
    return res.status(202).json({ queued: true, queue: 'observe:scan:network' });
  }

  const onProgress = makeOnProgress();
  let out;
  try {
    out = await runDiscovery({ db, redis, input: payload, onProgress });
    incRunMetric('completed');
  } catch (e) {
    incRunMetric('failed');
    throw e;
  }
  res.status(202).json({ ...out, queued: false });
}));

app.get('/api/discovery/runs', requireAuth, asyncRoute(async (req, res) => {
  const scope = parseScope(req.query);
  const r = await db.query(
    `SELECT * FROM observe_discovery_runs WHERE tenant_id = $1 AND site_id = $2 AND edge_id = $3 ORDER BY started_at DESC LIMIT 100`,
    [scope.tenant_id, scope.site_id, scope.edge_id]
  );
  res.json({ runs: r.rows, total: r.rowCount });
}));

app.get('/api/discovery/assets', requireAuth, asyncRoute(async (req, res) => {
  const scope = parseScope(req.query);
  const r = await db.query(
    `SELECT * FROM observe_assets WHERE tenant_id = $1 AND site_id = $2 AND edge_id = $3 ORDER BY updated_at DESC LIMIT 500`,
    [scope.tenant_id, scope.site_id, scope.edge_id]
  );
  res.json({ assets: r.rows, total: r.rowCount });
}));

app.get('/api/discovery/findings', requireAuth, asyncRoute(async (req, res) => {
  const runId = req.query.run_id;
  const scope = parseScope(req.query);
  const sql = runId
    ? 'SELECT * FROM observe_discovery_findings WHERE run_id = $1 AND tenant_id = $2 AND site_id = $3 AND edge_id = $4 ORDER BY observed_at DESC LIMIT 500'
    : 'SELECT * FROM observe_discovery_findings WHERE tenant_id = $1 AND site_id = $2 AND edge_id = $3 ORDER BY observed_at DESC LIMIT 500';
  const vals = runId ? [runId, scope.tenant_id, scope.site_id, scope.edge_id] : [scope.tenant_id, scope.site_id, scope.edge_id];
  const r = await db.query(sql, vals);
  res.json({ findings: r.rows, total: r.rowCount });
}));

app.get('/api/discovery/topology', requireAuth, asyncRoute(async (req, res) => {
  const scope = parseScope(req.query);
  const r = await db.query(
    'SELECT * FROM observe_topology_edges WHERE tenant_id = $1 AND site_id = $2 AND edge_id = $3 ORDER BY observed_at DESC LIMIT 500',
    [scope.tenant_id, scope.site_id, scope.edge_id]
  );
  res.json({ edges: r.rows, total: r.rowCount });
}));

app.get('/api/discovery/fingerprints', requireAuth, asyncRoute(async (req, res) => {
  const scope = parseScope(req.query);
  const r = await db.query(
    'SELECT * FROM observe_service_fingerprints WHERE tenant_id = $1 AND site_id = $2 AND edge_id = $3 ORDER BY observed_at DESC LIMIT 500',
    [scope.tenant_id, scope.site_id, scope.edge_id]
  );
  res.json({ fingerprints: r.rows, total: r.rowCount });
}));

app.get('/api/discovery/policies', requireAuth, asyncRoute(async (req, res) => {
  const scope = parseScope(req.query);
  const r = await db.query(
    'SELECT * FROM observe_discovery_policies WHERE tenant_id = $1 AND site_id = $2 AND edge_id = $3 ORDER BY updated_at DESC LIMIT 100',
    [scope.tenant_id, scope.site_id, scope.edge_id]
  );
  res.json({ policies: r.rows, total: r.rowCount });
}));

app.post('/api/discovery/policies', requireAuth, asyncRoute(async (req, res) => {
  const b = req.body || {};
  if (!b.name || typeof b.name !== 'string') return res.status(400).json({ error: 'name é obrigatório' });
  if (b.scan_profile && !['safe', 'balanced', 'aggressive'].includes(b.scan_profile)) {
    return res.status(400).json({ error: 'scan_profile inválido' });
  }
  const scope = parseScope(b);
  if (b.policy_id && !(await ensurePolicyInScope(b.policy_id, scope))) {
    return res.status(400).json({ error: 'policy_id fora do escopo' });
  }
  const r = await db.query(
    `INSERT INTO observe_discovery_policies
      (id, tenant_id, site_id, edge_id, name, scan_profile, active_enabled, passive_enabled, allowed_ranges, blocked_ranges,
       max_rate_per_minute, auto_prometheus_sd, auto_icinga_sync, is_default, metadata)
     VALUES (gen_random_uuid(),$1,$2,$3,$4,$5,$6,$7,$8,$9,$10,$11,$12,$13,$14)
     RETURNING *`,
    [
      scope.tenant_id, scope.site_id, scope.edge_id, b.name || 'policy', b.scan_profile || 'safe',
      b.active_enabled !== false, b.passive_enabled !== false, JSON.stringify(b.allowed_ranges || []), JSON.stringify(b.blocked_ranges || []),
      b.max_rate_per_minute || 300, b.auto_prometheus_sd !== false, b.auto_icinga_sync === true, b.is_default === true,
      JSON.stringify(b.metadata || {}),
    ]
  );
  res.status(201).json({ policy: r.rows[0] });
}));

app.patch('/api/discovery/policies/:id', requireAuth, asyncRoute(async (req, res) => {
  const { id } = req.params;
  const b = req.body || {};
  const scope = parseScope(b);
  if (b.scan_profile && !['safe', 'balanced', 'aggressive'].includes(b.scan_profile)) {
    return res.status(400).json({ error: 'scan_profile inválido' });
  }
  const sets = [];
  const vals = [];
  let i = 1;
  const maybe = (col, val) => { if (val !== undefined) { sets.push(`${col} = $${i++}`); vals.push(val); } };
  maybe('name', b.name);
  maybe('scan_profile', b.scan_profile);
  maybe('active_enabled', b.active_enabled);
  maybe('passive_enabled', b.passive_enabled);
  maybe('allowed_ranges', b.allowed_ranges != null ? JSON.stringify(b.allowed_ranges) : undefined);
  maybe('blocked_ranges', b.blocked_ranges != null ? JSON.stringify(b.blocked_ranges) : undefined);
  maybe('max_rate_per_minute', b.max_rate_per_minute);
  maybe('host_timeout_ms', b.host_timeout_ms);
  maybe('max_concurrency', b.max_concurrency);
  maybe('allow_udp', b.allow_udp);
  maybe('auto_prometheus_sd', b.auto_prometheus_sd);
  maybe('auto_icinga_sync', b.auto_icinga_sync);
  maybe('is_default', b.is_default);
  if (!sets.length) return res.status(400).json({ error: 'Nenhum campo para atualizar' });
  sets.push(`updated_at = NOW()`);
  vals.push(id);
  vals.push(scope.tenant_id, scope.site_id, scope.edge_id);
  const r = await db.query(
    `UPDATE observe_discovery_policies SET ${sets.join(', ')}
     WHERE id = $${i++} AND tenant_id = $${i++} AND site_id = $${i++} AND edge_id = $${i}
     RETURNING *`,
    vals
  );
  if (!r.rowCount) return res.status(404).json({ error: 'Política não encontrada' });
  res.json({ policy: r.rows[0] });
}));

app.delete('/api/discovery/policies/:id', requireAuth, asyncRoute(async (req, res) => {
  const scope = parseScope(req.query);
  const r = await db.query(
    `DELETE FROM observe_discovery_policies
     WHERE id = $1 AND tenant_id = $2 AND site_id = $3 AND edge_id = $4
     RETURNING id, name`,
    [req.params.id, scope.tenant_id, scope.site_id, scope.edge_id]
  );
  if (!r.rowCount) return res.status(404).json({ error: 'Política não encontrada' });
  res.json({ deleted: true, id: req.params.id, name: r.rows[0].name });
}));

// ── Targets CRUD ──────────────────────────────────────────────────────────────
app.get('/api/discovery/targets', requireAuth, asyncRoute(async (req, res) => {
  const scope = parseScope(req.query);
  const r = await db.query(
    `SELECT t.*, p.name AS policy_name FROM observe_discovery_targets t
     LEFT JOIN observe_discovery_policies p ON t.policy_id = p.id
     WHERE t.tenant_id = $1 AND t.site_id = $2 AND t.edge_id = $3
     ORDER BY t.created_at DESC LIMIT 500`,
    [scope.tenant_id, scope.site_id, scope.edge_id]
  );
  res.json({ targets: r.rows, total: r.rowCount });
}));

app.post('/api/discovery/targets', requireAuth, asyncRoute(async (req, res) => {
  const b = req.body || {};
  if (!b.address) return res.status(400).json({ error: 'address é obrigatório' });
  const validTypes = ['ip', 'hostname', 'cidr', 'range'];
  if (b.discovery_type && !validTypes.includes(b.discovery_type)) {
    return res.status(400).json({ error: 'discovery_type inválido' });
  }
  const scope = parseScope(b);
  const r = await db.query(
    `INSERT INTO observe_discovery_targets
      (id, tenant_id, site_id, edge_id, policy_id, discovery_type, address, label, enabled, metadata)
     VALUES (gen_random_uuid(),$1,$2,$3,$4,$5,$6,$7,$8,$9)
     ON CONFLICT (tenant_id, site_id, edge_id, discovery_type, address)
     DO UPDATE SET label = EXCLUDED.label, enabled = EXCLUDED.enabled, updated_at = NOW()
     RETURNING *`,
    [
      scope.tenant_id, scope.site_id, scope.edge_id,
      b.policy_id || null, b.discovery_type || 'ip', b.address,
      b.label || null, b.enabled !== false, JSON.stringify(b.metadata || {}),
    ]
  );
  res.status(201).json({ target: r.rows[0] });
}));

app.patch('/api/discovery/targets/:id', requireAuth, asyncRoute(async (req, res) => {
  const { id } = req.params;
  const b = req.body || {};
  const scope = parseScope(b);
  if (b.policy_id && !(await ensurePolicyInScope(b.policy_id, scope))) {
    return res.status(400).json({ error: 'policy_id fora do escopo' });
  }
  const sets = [];
  const vals = [];
  let i = 1;
  const maybe = (col, val) => { if (val !== undefined) { sets.push(`${col} = $${i++}`); vals.push(val); } };
  maybe('address', b.address);
  maybe('label', b.label);
  maybe('enabled', b.enabled);
  maybe('policy_id', b.policy_id);
  maybe('discovery_type', b.discovery_type);
  if (!sets.length) return res.status(400).json({ error: 'Nenhum campo para atualizar' });
  sets.push(`updated_at = NOW()`);
  vals.push(id);
  vals.push(scope.tenant_id, scope.site_id, scope.edge_id);
  const r = await db.query(
    `UPDATE observe_discovery_targets SET ${sets.join(', ')}
     WHERE id = $${i++} AND tenant_id = $${i++} AND site_id = $${i++} AND edge_id = $${i}
     RETURNING *`,
    vals
  );
  if (!r.rowCount) return res.status(404).json({ error: 'Target não encontrado' });
  res.json({ target: r.rows[0] });
}));

app.delete('/api/discovery/targets/:id', requireAuth, asyncRoute(async (req, res) => {
  const scope = parseScope(req.query);
  const r = await db.query(
    `DELETE FROM observe_discovery_targets
     WHERE id = $1 AND tenant_id = $2 AND site_id = $3 AND edge_id = $4
     RETURNING id, address`,
    [req.params.id, scope.tenant_id, scope.site_id, scope.edge_id]
  );
  if (!r.rowCount) return res.status(404).json({ error: 'Target não encontrado' });
  res.json({ deleted: true, id: req.params.id, address: r.rows[0].address });
}));

// ── Assets — atualizar ciclo de vida ─────────────────────────────────────────
const VALID_LIFECYCLE = ['discovered', 'approved', 'monitored', 'ignored', 'quarantined', 'disappeared'];

app.patch('/api/discovery/assets/:id', requireAuth, asyncRoute(async (req, res) => {
  const { id } = req.params;
  const b = req.body || {};
  const scope = parseScope(b);
  if (b.lifecycle_state && !VALID_LIFECYCLE.includes(b.lifecycle_state)) {
    return res.status(400).json({ error: 'lifecycle_state inválido' });
  }
  const sets = [];
  const vals = [];
  let i = 1;
  const maybe = (col, val) => { if (val !== undefined) { sets.push(`${col} = $${i++}`); vals.push(val); } };
  maybe('lifecycle_state', b.lifecycle_state);
  maybe('criticality', b.criticality);
  maybe('display_name', b.display_name);
  if (!sets.length) return res.status(400).json({ error: 'Nenhum campo para atualizar' });
  sets.push(`updated_at = NOW()`);
  vals.push(id);
  vals.push(scope.tenant_id, scope.site_id, scope.edge_id);
  const r = await db.query(
    `UPDATE observe_assets SET ${sets.join(', ')}
     WHERE id = $${i++} AND tenant_id = $${i++} AND site_id = $${i++} AND edge_id = $${i}
     RETURNING *`,
    vals
  );
  if (!r.rowCount) return res.status(404).json({ error: 'Ativo não encontrado' });
  res.json({ asset: r.rows[0] });
}));

app.post('/api/discovery/passive/events', requireAuth, asyncRoute(async (req, res) => {
  const events = Array.isArray(req.body?.events) ? req.body.events : [req.body];
  const accepted = await ingestPassiveEvents(events);
  res.status(202).json({ accepted });
}));

app.get('/api/discovery/history', requireAuth, asyncRoute(async (req, res) => {
  const assetId = req.query.asset_id;
  if (!assetId) return res.status(400).json({ error: 'asset_id is required' });
  const scope = parseScope(req.query);
  const r = await db.query(
    `SELECT * FROM observe_asset_changes WHERE asset_id = $1 AND tenant_id = $2 AND site_id = $3 AND edge_id = $4 ORDER BY changed_at DESC LIMIT 500`,
    [assetId, scope.tenant_id, scope.site_id, scope.edge_id]
  );
  res.json({ changes: r.rows, total: r.rowCount });
}));

app.get('/api/discovery/prometheus/http-sd', asyncRoute(async (req, res) => {
  const scope = parseScope(req.query);
  const r = await db.query(
    `SELECT a.tenant_id, a.site_id, a.edge_id, a.asset_name, a.primary_ip, s.port, s.protocol
     FROM observe_assets a
     JOIN observe_asset_services s ON s.asset_id = a.id
     WHERE s.status = 'open'
       AND a.lifecycle_state IN ('approved', 'monitored')
       AND a.tenant_id = $1 AND a.site_id = $2 AND a.edge_id = $3
       AND s.port IN (9100,9104,9108,9115,9117,9121,9187,9256,9419,9090,9091)
     ORDER BY a.updated_at DESC LIMIT 1000`
    , [scope.tenant_id, scope.site_id, scope.edge_id]
  );

  const assets = new Map();
  for (const row of r.rows) {
    const key = `${row.tenant_id}:${row.site_id}:${row.edge_id}:${row.asset_name}`;
    if (!assets.has(key)) {
      assets.set(key, {
        tenant_id: row.tenant_id,
        site_id: row.site_id,
        edge_id: row.edge_id,
        asset_name: row.asset_name,
        services: [],
      });
    }
    assets.get(key).services.push({
      exporter_target: `${row.primary_ip}:${row.port}`,
      job: row.port === 9100 ? 'node-exporter' : 'discovered-exporter',
    });
  }

  res.json(toPromSdGroups([...assets.values()]));
}));

app.get('/api/discovery/graph/shortest-path', requireAuth, asyncRoute(async (req, res) => {
  if (!neo4jEnabled()) return res.status(503).json({ error: 'neo4j_not_enabled' });
  const scope = parseScope(req.query);
  const from = String(req.query.from || '').trim();
  const to = String(req.query.to || '').trim();
  if (!from || !to) return res.status(400).json({ error: 'from e to são obrigatórios' });
  const out = await shortestPath({
    tenant: scope,
    fromAssetKey: from,
    toAssetRef: to,
    maxDepth: parseInt(req.query.max_depth || '8', 10),
  });
  res.json(out);
}));

app.get('/api/discovery/graph/blast-radius', requireAuth, asyncRoute(async (req, res) => {
  if (!neo4jEnabled()) return res.status(503).json({ error: 'neo4j_not_enabled' });
  const scope = parseScope(req.query);
  const asset = String(req.query.asset_key || '').trim();
  if (!asset) return res.status(400).json({ error: 'asset_key é obrigatório' });
  const out = await blastRadius({
    tenant: scope,
    assetKey: asset,
    depth: parseInt(req.query.depth || '2', 10),
    limit: parseInt(req.query.limit || '200', 10),
  });
  res.json(out);
}));

app.get('/observe/discovery', (_req, res) => {
  res.set('Content-Type', 'text/html; charset=utf-8');
  res.end(DISCOVERY_UI_HTML);
});

function truncateValue(value, max = 180) {
  if (value == null) return value;
  const text = typeof value === 'string' ? value : JSON.stringify(value);
  if (text.length <= max) return value;
  return `${text.slice(0, max)}...`;
}

function extractPassiveDeviceHints(raw) {
  const text = String(raw || '').replace(/[^\x20-\x7E]+/g, ' ');
  const read = (key) => {
    const re = new RegExp(`${key}=([^=]+?)(?=\\s+(?:acl|deviceid|features|fex|rsf|fv|at|flags|model|integrator|manufacturer|serialNumber|protovers|srcvers|pi|psi|gid|gcgl|Cpk)=|\\s+_services|$)`, 'i');
    const m = text.match(re);
    return m ? m[1].trim() : null;
  };
  const out = {};
  const model = read('model') || (text.match(/([A-Za-z0-9][A-Za-z0-9 ._-]{2,80})\s+_airplay\b/i)?.[1] || '').trim();
  const manufacturer = read('manufacturer') || read('integrator');
  if (model) out.model = truncateValue(model, 120);
  if (manufacturer) out.manufacturer = truncateValue(manufacturer, 120);
  if (text.includes('_airplay')) out.service = 'AirPlay';
  return out;
}

function normalizeObservedMac(mac) {
  if (!mac) return null;
  const cleaned = String(mac).trim().toLowerCase().replace(/-/g, ':');
  return /^[0-9a-f]{2}(:[0-9a-f]{2}){5}$/.test(cleaned) ? cleaned : null;
}

function uniqueValues(values) {
  return Array.from(new Set(values.filter(Boolean)));
}

function usefulHostname(hostname) {
  const value = String(hostname || '').trim().toLowerCase();
  if (!value || value === 'localhost') return null;
  if (!/[a-z]/.test(value)) return null;
  return value;
}

function compactFindingPayload(payload) {
  if (!payload || typeof payload !== 'object') return {};
  const nested = payload.payload && typeof payload.payload === 'object' ? payload.payload : {};
  const keys = ['message', 'reason', 'error', 'hostname', 'source_ip', 'address', 'target', 'protocol', 'service', 'port', 'server', 'location', 'asset'];
  const nestedKeys = ['st', 'nt', 'usn', 'server', 'location', 'model', 'model_name', 'friendly_name', 'device_type', 'manufacturer', 'integrator'];
  const out = {};
  for (const key of keys) {
    if (payload[key] !== undefined && payload[key] !== null && payload[key] !== '') out[key] = truncateValue(payload[key]);
  }
  for (const key of nestedKeys) {
    if (nested[key] !== undefined && nested[key] !== null && nested[key] !== '') out[key] = truncateValue(nested[key], 220);
  }
  Object.assign(out, extractPassiveDeviceHints(nested.raw || payload.raw || ''));
  if (!Object.keys(out).length && payload.type) out.type = truncateValue(payload.type);
  return out;
}

function compactFingerprint(fingerprint) {
  if (!fingerprint || typeof fingerprint !== 'object') return {};
  const fp = fingerprint.fingerprint && typeof fingerprint.fingerprint === 'object' ? fingerprint.fingerprint : fingerprint;
  const out = {};
  for (const key of ['vendor', 'product', 'service', 'category', 'technology', 'criticality', 'confidence', 'firmware_hint', 'os_hint', 'passive_type']) {
    if (fp[key] !== undefined && fp[key] !== null && fp[key] !== '') out[key] = truncateValue(fp[key], 120);
  }
  const evidence = fp.evidence || fp.evidences;
  if (Array.isArray(evidence) && evidence.length) out.evidence = evidence.slice(0, 3).map((item) => truncateValue(item, 140));
  return out;
}

function passiveMediaSummary(metadata) {
  const passive = metadata?.passive || {};
  const payloadText = JSON.stringify(passive.payload || {}).toLowerCase();
  if (!payloadText.includes('smarttv') && !payloadText.includes('_airplay') && !payloadText.includes('mediarenderer') && !payloadText.includes('mediaserver') && !payloadText.includes('dlna')) return null;
  const payload = passive.payload || {};
  const hints = extractPassiveDeviceHints(payload.raw || '');
  const kind = payloadText.includes('mediarenderer') ? 'MediaRenderer' : payloadText.includes('mediaserver') ? 'MediaServer' : 'DLNA/UPnP';
  return {
    asset_type: 'media_device',
    model: hints.model || null,
    product: `TV / ${kind}`,
    vendor: hints.manufacturer || metadata?.fingerprint?.vendor || 'Não identificado',
  };
}

function assetObservation(asset) {
  const media = passiveMediaSummary(asset.metadata) || {};
  const keyMac = asset.asset_key?.startsWith('mac:') ? asset.asset_key.slice(4) : null;
  const metadataMac = asset.metadata?.passive?.mac || asset.metadata?.arp?.mac_address || null;
  return {
    ...asset,
    ...media,
    observed_mac: normalizeObservedMac(asset.mac_address || keyMac || metadataMac),
    observed_ip: asset.primary_ip || asset.metadata?.passive?.source_ip || null,
    observed_hostname: usefulHostname(asset.hostname),
    observed_model: media.model || asset.metadata?.fingerprint?.product || null,
    observed_vendor: media.vendor || asset.vendor || null,
  };
}

function betterAssetValue(current, candidate) {
  if (!candidate) return current;
  if (!current || current === 'Unknown' || current === 'Não identificado' || current === 'Nao identificado' || current === 'Sem sinal de serviço' || current === 'Sem sinal de servico') return candidate;
  return current;
}

function mergeAssetGroup(group) {
  const sorted = group.slice().sort((a, b) => new Date(b.updated_at || 0) - new Date(a.updated_at || 0));
  const base = sorted[0] || {};
  const macs = uniqueValues(sorted.map(a => a.observed_mac));
  const ips = uniqueValues(sorted.map(a => a.observed_ip));
  const hostnames = uniqueValues(sorted.map(a => a.observed_hostname));
  const assetKeys = uniqueValues(sorted.map(a => a.asset_key));
  const GENERIC_LABELS = new Set(['TV / Media Device', 'TV / MediaRenderer', 'TV / MediaServer', 'TV / DLNA/UPnP', 'Smartphone', 'Mobile Device', 'Sem sinal de serviço', 'Sem sinal de servico']);
  const allModels = uniqueValues(sorted.map(a => a.observed_model || a.product).filter(v => v && !GENERIC_LABELS.has(v)));
  const genericModels = uniqueValues(sorted.map(a => a.observed_model || a.product).filter(v => v && GENERIC_LABELS.has(v)));
  const models = allModels.length ? allModels : genericModels;
  const vendors = uniqueValues(sorted.map(a => a.observed_vendor || a.vendor).filter(v => v && v !== 'Não identificado' && v !== 'Nao identificado' && v !== 'Unknown'));
  const services = group.flatMap(a => a.services || []);

  // Produto: usar o modelo específico se disponível, senão o genérico do asset_type
  const specificProduct = allModels[0] || null;
  const genericProduct = group.some(a => a.asset_type === 'media_device')
    ? 'TV / Media Device'
    : group.some(a => a.asset_type === 'mobile' || (a.product || '').toLowerCase().includes('smartphone'))
    ? 'Smartphone'
    : null;

  const merged = {
    ...base,
    id: base.id,
    asset_key: assetKeys[0] || base.asset_key,
    asset_keys: assetKeys,
    primary_ip: ips[0] || base.primary_ip,
    observed_ips: ips,
    hostname: hostnames[0] || base.hostname,
    observed_hostnames: hostnames,
    mac_address: macs[0] || null,
    observed_macs: macs,
    model: models[0] || null,
    vendor: vendors[0] || betterAssetValue(base.vendor, vendors[0]),
    product: specificProduct || genericProduct || betterAssetValue(base.product, base.product),
    display_name: hostnames[0] || base.display_name || base.asset_name || ips[0] || macs[0] || base.asset_key,
    asset_name: hostnames[0] || base.asset_name || ips[0] || macs[0] || base.asset_key,
    asset_type: group.some(a => a.asset_type === 'media_device') ? 'media_device' : base.asset_type,
    services,
    identity_conflicts: {
      multiple_macs_for_ip: ips.length === 1 && macs.length > 1,
      multiple_ips_for_mac: macs.length === 1 && ips.length > 1,
      duplicate_asset_keys: assetKeys.length > 1,
    },
  };
  return merged;
}

function consolidateAssets(assets) {
  const observed = assets.map(assetObservation);
  const parent = new Map();
  const find = (key) => {
    if (!parent.has(key)) parent.set(key, key);
    const current = parent.get(key);
    if (current === key) return key;
    const root = find(current);
    parent.set(key, root);
    return root;
  };
  const union = (left, right) => {
    const a = find(left);
    const b = find(right);
    if (a !== b) parent.set(b, a);
  };

  for (const asset of observed) {
    const keys = uniqueValues([
      asset.asset_key && `key:${asset.asset_key}`,
      asset.observed_ip && `ip:${asset.observed_ip}`,
      asset.observed_mac && `mac:${asset.observed_mac}`,
      asset.observed_hostname && `host:${asset.observed_hostname}`,
    ]);
    if (!keys.length) keys.push(`id:${asset.id}`);
    keys.forEach(find);
    keys.slice(1).forEach(key => union(keys[0], key));
  }

  const groups = new Map();
  for (const asset of observed) {
    const keys = uniqueValues([
      asset.asset_key && `key:${asset.asset_key}`,
      asset.observed_ip && `ip:${asset.observed_ip}`,
      asset.observed_mac && `mac:${asset.observed_mac}`,
      asset.observed_hostname && `host:${asset.observed_hostname}`,
      `id:${asset.id}`,
    ]);
    const root = find(keys[0]);
    if (!groups.has(root)) groups.set(root, []);
    groups.get(root).push(asset);
  }

  return Array.from(groups.values()).map(mergeAssetGroup)
    .sort((a, b) => new Date(b.updated_at || 0) - new Date(a.updated_at || 0));
}

app.get('/observe/discovery/data/summary', requireAuth, asyncRoute(async (req, res) => {
  const scope = parseScope(req.query);
  const [runs, assets, services, findings, fps, edges, policies, targets] = await Promise.all([
    db.query('SELECT id, status, started_at, completed_at, summary, metadata FROM observe_discovery_runs WHERE tenant_id=$1 AND site_id=$2 AND edge_id=$3 ORDER BY started_at DESC LIMIT 100', [scope.tenant_id, scope.site_id, scope.edge_id]),
    db.query(`SELECT a.id, a.asset_key, a.asset_name, a.display_name, a.asset_type, a.primary_ip, a.hostname,
                     COALESCE(NULLIF(i.mac_address, ''), CASE WHEN a.asset_key LIKE 'mac:%' THEN substring(a.asset_key from 5) END, a.metadata #>> '{arp,mac_address}', a.metadata #>> '{passive,mac}', same_ip.mac_address) AS mac_address,
                     a.vendor, a.product, a.os_hint, a.lifecycle_state, a.criticality, a.confidence, a.metadata, a.first_seen_at, a.last_seen_at, a.updated_at
                FROM observe_assets a
                LEFT JOIN LATERAL (
                  SELECT mac_address
                    FROM observe_asset_interfaces
                   WHERE asset_id = a.id AND tenant_id = a.tenant_id AND site_id = a.site_id AND edge_id = a.edge_id
                     AND mac_address IS NOT NULL AND mac_address <> ''
                   ORDER BY updated_at DESC LIMIT 1
                ) i ON true
                LEFT JOIN LATERAL (
                  SELECT COALESCE(CASE WHEN other.asset_key LIKE 'mac:%' THEN substring(other.asset_key from 5) END, other.metadata #>> '{arp,mac_address}', other.metadata #>> '{passive,mac}') AS mac_address
                    FROM observe_assets other
                   WHERE other.tenant_id = a.tenant_id AND other.site_id = a.site_id AND other.edge_id = a.edge_id
                     AND other.primary_ip = a.primary_ip AND other.id <> a.id
                     AND (other.asset_key LIKE 'mac:%' OR other.metadata #>> '{arp,mac_address}' IS NOT NULL OR other.metadata #>> '{passive,mac}' IS NOT NULL)
                   ORDER BY CASE WHEN other.asset_key LIKE 'mac:%' THEN 0 ELSE 1 END, other.updated_at DESC LIMIT 1
                ) same_ip ON true
               WHERE a.tenant_id=$1 AND a.site_id=$2 AND a.edge_id=$3
               ORDER BY a.updated_at DESC LIMIT 500`, [scope.tenant_id, scope.site_id, scope.edge_id]),
    db.query(`SELECT s.id, s.asset_id, a.asset_key, a.asset_name, COALESCE(a.display_name, a.asset_name) AS asset_display_name,
                     s.service_key, s.service_name, s.protocol, s.port, s.status, s.first_seen_at, s.last_seen_at, s.updated_at
               FROM observe_asset_services s
                LEFT JOIN observe_assets a ON a.id = s.asset_id
               WHERE s.tenant_id=$1 AND s.site_id=$2 AND s.edge_id=$3
                 AND NOT (s.service_name LIKE 'passive-ssdp-%' AND COALESCE(s.port, 0) <> 1900)
                 AND NOT (s.service_name LIKE 'passive-mdns-%' AND COALESCE(s.port, 0) <> 5353)
               ORDER BY s.updated_at DESC LIMIT 1000`, [scope.tenant_id, scope.site_id, scope.edge_id]),
    db.query('SELECT id, run_id, finding_type, severity, source, asset_key, payload, observed_at FROM observe_discovery_findings WHERE tenant_id=$1 AND site_id=$2 AND edge_id=$3 ORDER BY observed_at DESC LIMIT 500', [scope.tenant_id, scope.site_id, scope.edge_id]),
    db.query(`SELECT fp.id, fp.asset_id, a.asset_key, a.asset_name, COALESCE(a.display_name, a.asset_name) AS asset_display_name,
                     fp.service_key, fp.fingerprint - 'raw_signals' - 'evidence' - 'evidences' AS fingerprint, fp.confidence, fp.observed_at
                FROM observe_service_fingerprints fp
                LEFT JOIN observe_assets a ON a.id = fp.asset_id
               WHERE fp.tenant_id=$1 AND fp.site_id=$2 AND fp.edge_id=$3
               ORDER BY fp.observed_at DESC LIMIT 500`, [scope.tenant_id, scope.site_id, scope.edge_id]),
    db.query(`SELECT e.id, e.run_id, e.edge_type, e.from_asset_id, a.asset_key AS from_asset_key,
                     COALESCE(a.display_name, a.asset_name) AS from_asset_name,
                     e.to_asset_ref, e.protocol, e.source, e.metadata, e.observed_at
                FROM observe_topology_edges e
                LEFT JOIN observe_assets a ON a.id = e.from_asset_id
               WHERE e.tenant_id=$1 AND e.site_id=$2 AND e.edge_id=$3
               ORDER BY e.observed_at DESC LIMIT 500`, [scope.tenant_id, scope.site_id, scope.edge_id]),
    db.query('SELECT * FROM observe_discovery_policies WHERE tenant_id=$1 AND site_id=$2 AND edge_id=$3 ORDER BY is_default DESC, updated_at DESC', [scope.tenant_id, scope.site_id, scope.edge_id]),
    db.query('SELECT t.*, p.name AS policy_name FROM observe_discovery_targets t LEFT JOIN observe_discovery_policies p ON t.policy_id = p.id WHERE t.tenant_id=$1 AND t.site_id=$2 AND t.edge_id=$3 ORDER BY t.created_at DESC LIMIT 500', [scope.tenant_id, scope.site_id, scope.edge_id]),
  ]);
  const servicesByAsset = new Map();
  for (const svc of services.rows) {
    if (!servicesByAsset.has(svc.asset_id)) servicesByAsset.set(svc.asset_id, []);
    servicesByAsset.get(svc.asset_id).push(svc);
  }
  const assetObservations = assets.rows.map((asset) => {
    const media = passiveMediaSummary(asset.metadata);
    return {
      ...asset,
      ...(media || {}),
      services: servicesByAsset.get(asset.id) || [],
    };
  });
  const enrichedAssets = consolidateAssets(assetObservations).map((asset) => {
    const { metadata: _metadata, ...publicAsset } = asset;
    return publicAsset;
  });
  const compactFindings = findings.rows.map((finding) => ({
    ...finding,
    payload: compactFindingPayload(finding.payload),
  }));
  const compactFingerprints = fps.rows.map((fp) => ({
    ...fp,
    fingerprint: compactFingerprint(fp.fingerprint),
  }));
  res.json({
    scope,
    runs: runs.rows,
    assets: enrichedAssets,
    services: services.rows,
    findings: compactFindings,
    fingerprints: compactFingerprints,
    topology: edges.rows,
    policies: policies.rows,
    targets: targets.rows,
  });
}));

app.get('/observe/discovery/data/scopes', requireAuth, asyncRoute(async (_req, res) => {
  const list = async (column) => {
    const r = await db.query(
      `WITH scoped AS (
         SELECT ${column} AS value FROM observe_discovery_runs
         UNION SELECT ${column} AS value FROM observe_assets
         UNION SELECT ${column} AS value FROM observe_discovery_findings
         UNION SELECT ${column} AS value FROM observe_topology_edges
         UNION SELECT ${column} AS value FROM observe_service_fingerprints
         UNION SELECT ${column} AS value FROM observe_discovery_policies
         UNION SELECT ${column} AS value FROM observe_discovery_targets
       )
       SELECT DISTINCT value FROM scoped WHERE value IS NOT NULL AND value <> '' ORDER BY value`
    );
    return r.rows.map(row => row.value);
  };

  const [tenants, sites, edges] = await Promise.all([list('tenant_id'), list('site_id'), list('edge_id')]);
  res.json({ tenants, sites, edges });
}));

app.use((_req, res) => res.status(404).json({ error: 'not_found' }));

app.use((err, _req, res, _next) => {
  log('error', 'Unhandled discovery error', { err: err.message });
  res.status(500).json({ error: err.message });
});

if (process.env.NODE_ENV === 'development') {
  import('vite').then(({ createServer: createVite }) =>
    createVite({
      root: path.join(__dirname, '..'),
      configFile: path.join(__dirname, '../vite.config.js'),
      server: { middlewareMode: true },
      appType: 'custom',
    }).then((v) => { _vite = v; log('info', 'Vite dev server ativo (HMR em /src/ui/main.js)'); })
  ).catch((e) => log('warn', 'Vite init falhou', { err: e.message }));
}

let stopPassiveReceivers = null;
if (PASSIVE_LISTENERS_ENABLED) {
  try {
    stopPassiveReceivers = startPassiveReceivers({
      onEvent: (evt) => {
        ingestPassiveEvents([evt]).catch((e) => {
          log('warn', 'Passive listener event ingestion failed', { err: e.message });
        });
      },
    });
  } catch (e) {
    log('warn', 'Passive listeners startup failed', { err: e.message });
  }
}

const server = app.listen(PORT, '0.0.0.0', () => log('info', `Discovery API on :${PORT}`));

let shuttingDown = false;
async function gracefulShutdown(signal) {
  if (shuttingDown) return;
  shuttingDown = true;
  log('info', 'Discovery API shutting down', { signal });
  try {
    if (typeof stopPassiveReceivers === 'function') stopPassiveReceivers();
  } catch (_) {}
  try {
    await closeNeo4jDriver();
  } catch (_) {}
  try {
    if (redis) await redis.quit();
  } catch (_) {}
  try {
    await db.end();
  } catch (_) {}
  try {
    await new Promise((resolve) => server.close(resolve));
  } catch (_) {}
  process.exit(0);
}

process.once('SIGINT', () => {
  gracefulShutdown('SIGINT').catch((e) => {
    log('warn', 'Graceful shutdown failed', { err: e.message });
    process.exit(1);
  });
});

process.once('SIGTERM', () => {
  gracefulShutdown('SIGTERM').catch((e) => {
    log('warn', 'Graceful shutdown failed', { err: e.message });
    process.exit(1);
  });
});

async function startScanQueueConsumer() {
  if (!redis || !SCAN_QUEUE_ENABLED) return;
  log('info', 'Scan queue consumer enabled', { queue: 'observe:scan:network' });
  for (;;) {
    try {
      const item = await redis.blpop('observe:scan:network', 1);
      if (!item) continue;
      const [, raw] = item;
      let task = {};
      try { task = JSON.parse(raw); } catch { task = {}; }
      const scope = parseScope(task);
      const targets = Array.isArray(task.targets) ? task.targets : undefined;
      try {
        await runDiscovery({
          db,
          redis,
          input: {
            ...scope,
            profile: task.profile || 'safe',
            trigger: 'queue:observe:scan:network',
            subnet: task.subnet || null,
            targets,
            docker_discovery_enabled: DOCKER_DISCOVERY_ENABLED,
          },
          onProgress: makeOnProgress(),
        });
        incRunMetric('completed');
      } catch (e) {
        incRunMetric('failed');
        throw e;
      }
    } catch (e) {
      log('warn', 'Scan queue consumer error', { err: e.message });
    }
  }
}

startScanQueueConsumer().catch((e) => log('warn', 'Scan queue startup error', { err: e.message }));

module.exports = app;

const IS_DEV = process.env.NODE_ENV === 'development';
const DEV_SCRIPT = IS_DEV
  ? '<script type="module" src="/src/ui/main.js"></script>'
  : '';

const DISCOVERY_UI_HTML = `<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Results · R-Observe Discovery</title>
  ${IS_DEV ? DEV_SCRIPT : '<link rel="stylesheet" href="/observe/discovery/data/discovery.css">'}
  <script crossorigin src="https://unpkg.com/react@18/umd/react.production.min.js"></script>
  <script crossorigin src="https://unpkg.com/react-dom@18/umd/react-dom.production.min.js"></script>
</head>
<body>
  <div class="topbar">
    <svg viewBox="0 0 28 28" xmlns="http://www.w3.org/2000/svg" width="28" height="28" style="flex-shrink:0"><rect width="28" height="28" rx="6" fill="#CC1212"/><text x="14" y="20" font-family="Arial Black,Arial,sans-serif" font-weight="900" font-size="16" fill="white" text-anchor="middle">R</text></svg>
    <span class="topbar-brand">Results · Sistemas de Informática</span>
    <span class="topbar-title">/ R-Observe Discovery</span>
    <div style="margin-left:auto;display:flex;gap:.5rem;align-items:center;">
      <a href="/observe/home" class="topbar-link">⌂ Home</a>
    </div>
  </div>
  <div id="app"></div>
  <script>
  const e = React.createElement;
  const API = '/observe/discovery/api/discovery';

  // ── Traduções ────────────────────────────────────────────────────────────────
  const ESTADO = { discovered:'Descoberto', approved:'Aprovado', monitored:'Monitorado', ignored:'Ignorado', quarantined:'Quarentenado', disappeared:'Desaparecido' };
  const RUN_STATUS = { completed:'Concluído', running:'Em execução', failed:'Falhou', queued:'Na fila' };
  const SEVERITY = { warning:'Aviso', critical:'Crítico', info:'Informação', error:'Erro' };
  const FINDING_TYPE = { prometheus_sd_write_error:'Erro Prometheus SD', target_blocked:'Alvo bloqueado', docker_container_discovered:'Container Docker', asset_changed:'Ativo alterado', passive_signal:'Sinal passivo', new_asset:'Novo ativo' };
  const LIFECYCLE_OPTS = ['discovered','approved','monitored','ignored','quarantined','disappeared'];
  const CRITICALITY_OPTS = ['low','medium','high','critical'];
  const PROFILE_OPTS = ['safe','balanced','aggressive'];
  const TYPE_OPTS = ['ip','hostname','cidr','range'];

  function tr(map, val) { const v = String(val||'-').toLowerCase(); return map[v] || val || '-'; }
  function badge(cls, text) { return e('span', { className: 'badge badge-' + String(cls||'').toLowerCase() }, text); }
  function estadoBadge(v)  { return badge(v, tr(ESTADO, v)); }
  function runBadge(v)     { return badge(v, tr(RUN_STATUS, v)); }
  function sevBadge(v)     { return badge(v, tr(SEVERITY, v)); }
  function fmtDt(s)        { return s ? new Date(s).toLocaleString('pt-BR', {dateStyle:'short',timeStyle:'short'}) : '—'; }
  function fmtPct(v)       { const n=parseFloat(v); return isNaN(n) ? '—' : (n*100).toFixed(0)+'%'; }
  function apiScope(scope) { return { tenant_id: scope.tenant, site_id: scope.site, edge_id: scope.edge }; }
  function scopeQuery(scope) {
    return '?tenant_id=' + encodeURIComponent(scope.tenant) + '&site_id=' + encodeURIComponent(scope.site) + '&edge_id=' + encodeURIComponent(scope.edge);
  }
  function shortId(id) { return id ? String(id).slice(0, 8) : '—'; }
  function cleanUnknown(v) { return v && v !== 'Unknown' ? v : ''; }
  function serviceText(s) {
    if (!s) return '—';
    const proto = s.protocol ? String(s.protocol).toUpperCase() : '';
    const port = s.port ? String(s.port) : '';
    const name = s.service_name || s.fingerprint?.product || s.fingerprint?.service || '';
    return [proto && port ? proto + ':' + port : (s.service_key || port || proto), name].filter(Boolean).join(' ');
  }
  function assetServiceSummary(asset) {
    const svcs = Array.isArray(asset.services) ? asset.services : [];
    if (!svcs.length) return '—';
    const shown = svcs.slice(0, 4).map(serviceText).filter(Boolean);
    return shown.join(', ') + (svcs.length > shown.length ? ' +' + (svcs.length - shown.length) : '');
  }
  function macCell(asset) {
    const macs = Array.isArray(asset.observed_macs) && asset.observed_macs.length ? asset.observed_macs : (asset.mac_address ? [asset.mac_address] : []);
    if (macs.length) {
      const conflict = macs.length > 1;
      return e('span', { title: conflict ? 'Múltiplos MACs observados para a mesma identidade: '+macs.join(', ') : macs[0] },
        e('code', { style:{ color:conflict?'#f59e0b':'#8b949e', fontSize:'.8rem' } }, macs.slice(0, 2).join(', ')),
        macs.length > 2 ? e('span', { style:{ color:'#64748b', fontSize:'.72rem' } }, ' +'+(macs.length-2)) : null
      );
    }
    return e('span', {
      title:'MAC não observado. Normal quando o ativo foi descoberto fora do segmento L2/local, via rota, DNS, TCP, proxy, NAT, VPN ou cloud.',
      style:{ color:'#64748b', fontSize:'.78rem' },
    }, 'não observado');
  }
  function modelCell(asset) {
    const model = asset.model || (asset.product && asset.product !== 'Sem sinal de serviço' && asset.product !== 'Sem sinal de servico' ? asset.product : '');
    return model ? e('span', { style:{ color:'#c9d1d9' } }, model) : '—';
  }
  function summarizePayload(payload) {
    const p = payload && typeof payload === 'object' ? payload : {};
    const keys = ['message','reason','error','hostname','source_ip','address','target','protocol','service','port','server','location','st','nt','usn','model','model_name','friendly_name','device_type','manufacturer','integrator'];
    const parts = [];
    keys.forEach(k => {
      const v = p[k];
      if (v !== undefined && v !== null && v !== '') parts.push(k + ': ' + String(v));
    });
    if (!parts.length && p.asset) parts.push('asset: ' + String(p.asset));
    if (!parts.length && p.old && p.new) parts.push('old/new registrado');
    return parts.join(' · ') || '—';
  }
  function fingerprintText(fp) {
    const f = fp && fp.fingerprint && typeof fp.fingerprint === 'object' ? fp.fingerprint : {};
    return [f.product || f.service || f.name || f.type, f.vendor, f.version, f.category].filter(Boolean).join(' · ') || JSON.stringify(f).slice(0, 160) || '—';
  }

  // ── Feedback (toast inline) ──────────────────────────────────────────────────
  function Feedback({ msg }) {
    if (!msg) return null;
    const ok = msg.startsWith('✓');
    return e('div', { className: 'scan-feedback ' + (ok ? 'ok' : 'err'), style:{margin:'6px 0'} }, msg);
  }

  // ── Input helpers ────────────────────────────────────────────────────────────
  function Input({ label, value, onChange, type='text', placeholder='', required, style }) {
    return e('label', { style:{ display:'flex', flexDirection:'column', gap:3, fontSize:'.8rem', color:'#8b949e', ...style } },
      label,
      e('input', {
        type, value, onChange: ev => onChange(ev.target.value),
        placeholder, required,
        style:{ background:'#0d1117', border:'1px solid #30363d', borderRadius:6, color:'#c9d1d9', padding:'5px 8px', fontSize:'.85rem' }
      })
    );
  }
  function Select({ label, value, onChange, options, style }) {
    return e('label', { style:{ display:'flex', flexDirection:'column', gap:3, fontSize:'.8rem', color:'#8b949e', ...style } },
      label,
      e('select', { value, onChange: ev => onChange(ev.target.value),
        style:{ background:'#0d1117', border:'1px solid #30363d', borderRadius:6, color:'#c9d1d9', padding:'5px 8px', fontSize:'.85rem', appearance:'none' }
      },
        options.map(o => e('option', { key: o.value||o, value: o.value||o }, o.label||o))
      )
    );
  }
  function Check({ label, checked, onChange }) {
    return e('label', { style:{ display:'flex', alignItems:'center', gap:6, fontSize:'.85rem', color:'#c9d1d9', cursor:'pointer' } },
      e('input', { type:'checkbox', checked, onChange: ev => onChange(ev.target.checked) }),
      label
    );
  }
  function Textarea({ label, value, onChange, rows=3, placeholder }) {
    return e('label', { style:{ display:'flex', flexDirection:'column', gap:3, fontSize:'.8rem', color:'#8b949e' } },
      label,
      e('textarea', {
        value, onChange: ev => onChange(ev.target.value), rows, placeholder,
        style:{ background:'#0d1117', border:'1px solid #30363d', borderRadius:6, color:'#c9d1d9', padding:'5px 8px', fontSize:'.8rem', fontFamily:'monospace', resize:'vertical' }
      })
    );
  }
  function BtnPrimary({ children, onClick, disabled }) {
    return e('button', { onClick, disabled,
      style:{ background:'#CC1212', color:'#fff', border:'none', borderRadius:6, padding:'5px 12px', cursor:disabled?'not-allowed':'pointer', fontSize:'.82rem', fontWeight:600, opacity:disabled?.6:1 }
    }, children);
  }
  function BtnSecondary({ children, onClick }) {
    return e('button', { onClick,
      style:{ background:'transparent', color:'#8b949e', border:'1px solid #30363d', borderRadius:6, padding:'5px 10px', cursor:'pointer', fontSize:'.82rem' }
    }, children);
  }
  function BtnDanger({ children, onClick }) {
    return e('button', { onClick,
      style:{ background:'transparent', color:'#f85149', border:'1px solid #f8514955', borderRadius:6, padding:'4px 8px', cursor:'pointer', fontSize:'.78rem' }
    }, children);
  }
  function SectionTitle({ children }) {
    return e('div', { className:'panel-title', style:{marginTop:20} }, children);
  }
  function FormCard({ children, onSubmit }) {
    return e('form', { onSubmit: ev => { ev.preventDefault(); onSubmit(); },
      style:{ background:'#1c2128', border:'1px solid #30363d', borderRadius:10, padding:'16px', marginBottom:12 }
    }, children);
  }
  function FormGrid({ children }) {
    return e('div', { style:{ display:'grid', gridTemplateColumns:'repeat(auto-fit, minmax(200px,1fr))', gap:'10px', marginBottom:12 } }, children);
  }

  // ── Paginação reutilizável ───────────────────────────────────────────────────
  function usePage(items, pageSize) {
    const [page, setPage] = React.useState(1);
    const total = Math.max(1, Math.ceil(items.length / pageSize));
    const clamp = Math.min(page, total);
    React.useEffect(() => { if (clamp !== page) setPage(clamp); }, [clamp]);
    const start = (clamp - 1) * pageSize;
    return { page: clamp, setPage, total, pageItems: items.slice(start, start + pageSize) };
  }

  const PAGE_SIZES = [10, 25, 50, 100];

  function Pager({ page, total, count, setPage, pageSize, setPageSize }) {
    const S = { color:'#8b949e', fontSize:'.78rem' };
    const Btn = ({ children, onClick, disabled }) =>
      e('button', { onClick, disabled,
        style:{ background:'transparent', border:'1px solid #30363d', borderRadius:5, padding:'3px 10px', color:disabled?'#334155':'#8b949e', cursor:disabled?'not-allowed':'pointer', fontSize:'.8rem' }
      }, children);
    return e('div', { style:{ display:'flex', alignItems:'center', gap:8, padding:'8px 0', flexWrap:'wrap' } },
      e(Btn, { onClick:()=>setPage(1),        disabled:page<=1 }, '«'),
      e(Btn, { onClick:()=>setPage(p=>p-1),  disabled:page<=1 }, '‹ Anterior'),
      e('span', { style:S }, 'Pág. '+page+' / '+total+' — '+count+' itens'),
      e(Btn, { onClick:()=>setPage(p=>p+1),  disabled:page>=total }, 'Próxima ›'),
      e(Btn, { onClick:()=>setPage(total),   disabled:page>=total }, '»'),
      setPageSize
        ? e('select', { value:pageSize, onChange:ev=>{ setPageSize(Number(ev.target.value)); setPage(1); },
            style:{ background:'#0d1117', border:'1px solid #30363d', borderRadius:5, color:'#8b949e', padding:'3px 6px', fontSize:'.78rem', marginLeft:4 }
          }, PAGE_SIZES.map(n => e('option',{key:n,value:n}, n+'/pág.')))
        : null
    );
  }

  function sortItems(items, sort, getters) {
    if (!sort || !sort.key || !getters[sort.key]) return items.slice();
    const dir = sort.dir === 'desc' ? -1 : 1;
    const read = getters[sort.key];
    return items
      .map((item, index) => ({ item, index }))
      .sort((left, right) => {
        const a = read(left.item);
        const b = read(right.item);
        let cmp = 0;
        if (typeof a === 'boolean' || typeof b === 'boolean') {
          cmp = Number(Boolean(a)) - Number(Boolean(b));
        } else if (typeof a === 'number' && typeof b === 'number') {
          cmp = a - b;
        } else {
          const aDate = typeof a === 'string' && /[-:T]/.test(a) ? Date.parse(a) : NaN;
          const bDate = typeof b === 'string' && /[-:T]/.test(b) ? Date.parse(b) : NaN;
          if (!Number.isNaN(aDate) && !Number.isNaN(bDate)) {
            cmp = aDate - bDate;
          } else {
            cmp = String(a || '').localeCompare(String(b || ''), 'pt-BR', { numeric:true, sensitivity:'base' });
          }
        }
        return cmp === 0 ? left.index - right.index : cmp * dir;
      })
      .map(entry => entry.item);
  }

  function SortTh({ label, sortKey, sort, onSort }) {
    const active = sort.key === sortKey;
    const mark = active ? (sort.dir === 'asc' ? ' ^' : ' v') : '';
    return e('th', null,
      e('button', {
        type:'button',
        onClick:() => onSort(sortKey),
        style:{ background:'transparent', border:'none', color:active?'#c9d1d9':'#8b949e', padding:0, cursor:'pointer', fontSize:'11px', fontWeight:700, textTransform:'uppercase', letterSpacing:'.04em' },
        title:'Ordenar por '+label,
      }, label + mark)
    );
  }

  // ── Progress pipeline ────────────────────────────────────────────────────────
  const STAGE_LABEL = { policy:'Política', run_create:'Criando run', targets:'Listando alvos', scanning:'Varredura ativa', docker:'Docker', topology:'Topologia', prometheus_sd:'Prometheus SD', icinga:'Icinga', done:'Concluído' };
  const STAGE_ORDER = ['policy','run_create','targets','scanning','docker','topology','prometheus_sd','icinga','done'];

  function PipelinePanel({ progress }) {
    if (!progress || (!progress.active && !progress.summary)) return null;
    const elapsed = progress.elapsed_ms ? (progress.elapsed_ms/1000).toFixed(1)+'s' : '';
    const stMap = progress.stages || {};
    return e('div', { style:{ background:'#0f172a', border:'1px solid #334155', borderRadius:10, padding:'12px 14px', marginBottom:14 } },
      e('div', { style:{ display:'flex', alignItems:'center', justifyContent:'space-between', marginBottom:8 } },
        e('span', { style:{ fontWeight:600, fontSize:'.88rem', color:'#e2e8f0' } }, progress.active ? '⟳ Varredura em andamento' : '✓ Última varredura'),
        elapsed ? e('span', { style:{ fontSize:'.75rem', color:'#64748b' } }, elapsed) : null
      ),
      e('div', { style:{ display:'grid', gridTemplateColumns:'repeat(auto-fit, minmax(130px,1fr))', gap:6 } },
        STAGE_ORDER.map(s => {
          const st = stMap[s] || 'pending';
          const icon = st==='done'?'✓':st==='running'?'◎':st==='skipped'?'—':st==='error'?'✗':'○';
          const col  = st==='done'?'#3fb950':st==='running'?'#f59e0b':st==='skipped'?'#475569':st==='error'?'#f85149':'#334155';
          const txtCol = st==='pending'?'#475569':'#e2e8f0';
          return e('div', { key:s, style:{ display:'flex', alignItems:'center', gap:5, border:'1px solid '+(st==='running'?'#f59e0b44':'#1e293b'), borderRadius:5, padding:'4px 7px', background:st==='running'?'rgba(245,158,11,.07)':'transparent' } },
            e('span', { style:{ color:col, fontSize:'.82rem', fontWeight:700, flexShrink:0 } }, icon),
            e('span', { style:{ fontSize:'.74rem', color:txtCol } }, STAGE_LABEL[s]||s)
          );
        })
      ),
      (progress.total_targets > 0 || progress.discovered > 0) ? e('div', { style:{ display:'flex', gap:16, marginTop:8, paddingTop:8, borderTop:'1px solid #1e293b', flexWrap:'wrap' } },
        e('span', { style:{ fontSize:'.78rem', color:'#94a3b8' } }, 'Alvos: ', e('b', { style:{color:'#e2e8f0'} }, String(progress.scanned||0)+(progress.total_targets?'/'+progress.total_targets:''))),
        e('span', { style:{ fontSize:'.78rem', color:'#94a3b8' } }, 'Descobertos: ', e('b', { style:{color:'#3fb950'} }, String(progress.discovered||0)))
      ) : null
    );
  }

  // ── Aba: Descoberta ──────────────────────────────────────────────────────────
  function DiscoveryTab({ data, scanning, progress, scanMsg, onScan, token, setToken, scope, setScope, scopeOptions }) {
    const { assets=[], runs=[], findings=[], fingerprints=[], topology=[], services=[] } = data;
    const [assetSearch, setAssetSearch] = React.useState('');
    const [assetState, setAssetState]   = React.useState('all');
    const [pageSize, setPageSize]       = React.useState(25);
    const [assetSort, setAssetSort]     = React.useState({ key:'name', dir:'asc' });
    const [findingSearch, setFindingSearch]     = React.useState('');
    const [findingSeverity, setFindingSeverity] = React.useState('all');
    const [findingPageSize, setFindingPageSize] = React.useState(10);
    const [findingSort, setFindingSort]         = React.useState({ key:'type', dir:'asc' });
    const [runSearch, setRunSearch]     = React.useState('');
    const [runStatus, setRunStatus]     = React.useState('all');
    const [runPageSize, setRunPageSize] = React.useState(10);
    const [runSort, setRunSort]         = React.useState({ key:'started_at', dir:'desc' });
    const now = Date.now();
    const newCount       = assets.filter(a => (now - new Date(a.updated_at).getTime()) < 86400000).length;
    const disappearedCnt = assets.filter(a => a.lifecycle_state === 'disappeared').length;
    const changedCnt     = findings.filter(f => String(f.finding_type||'').includes('changed')).length;
    const lastRunStatus  = runs[0]?.status || '-';
    const lastRunSummary = runs[0]?.summary || {};
    const filteredAssets = assets.filter(a => {
      const stOk = assetState === 'all' || a.lifecycle_state === assetState;
      const haystack = [a.display_name, a.asset_name, a.primary_ip, a.mac_address, ...(a.observed_macs || []), a.vendor, a.model, a.product, a.asset_type, assetServiceSummary(a)].join(' ').toLowerCase();
      const srOk = !assetSearch || haystack.includes(assetSearch.toLowerCase());
      return stOk && srOk;
    });
    const sortedAssets = sortItems(filteredAssets, assetSort, {
      name: a => a.display_name || a.asset_name || '',
      mac: a => a.mac_address || '',
      ip: a => a.primary_ip || '',
      vendor: a => a.vendor || '',
      model: a => a.model || a.product || '',
      product: a => a.product || '',
      state: a => a.lifecycle_state || '',
    });
    const filteredFindings = findings.filter(f => {
      const sevOk = findingSeverity === 'all' || String(f.severity || '').toLowerCase() === findingSeverity;
      const srOk = !findingSearch || [f.finding_type, f.source, f.severity, f.asset_key, summarizePayload(f.payload)].some(v => String(v || '').toLowerCase().includes(findingSearch.toLowerCase()));
      return sevOk && srOk;
    });
    const sortedFindings = sortItems(filteredFindings, findingSort, {
      type: f => FINDING_TYPE[String(f.finding_type || '').toLowerCase()] || String(f.finding_type || ''),
      severity: f => f.severity || '',
      source: f => f.source || '',
    });
    const filteredRuns = runs.filter(r => {
      const statusOk = runStatus === 'all' || String(r.status || '').toLowerCase() === runStatus;
      const srOk = !runSearch || [r.status, r.started_at, r.completed_at].some(v => String(v || '').toLowerCase().includes(runSearch.toLowerCase()));
      return statusOk && srOk;
    });
    const sortedRuns = sortItems(filteredRuns, runSort, {
      status: r => r.status || '',
      started_at: r => r.started_at || '',
      completed_at: r => r.completed_at || '',
    });
    const { page: aPage, setPage: setAPage, total: aTotal, pageItems: aItems } = usePage(sortedAssets, pageSize);
    const { page: fPage, setPage: setFPage, total: fTotal, pageItems: fItems } = usePage(sortedFindings, findingPageSize);
    const { page: rPage, setPage: setRPage, total: rTotal, pageItems: rItems } = usePage(sortedRuns, runPageSize);

    function toggleSort(setSort, setPage, key) {
      setSort(current => current.key === key
        ? { key, dir: current.dir === 'asc' ? 'desc' : 'asc' }
        : { key, dir: 'asc' });
      setPage(1);
    }

    function comboOptions(current, values) {
      const list = [current, ...(values || []).filter(v => v !== current)];
      return list.filter(Boolean).map(v => ({ value:v, label:v }));
    }

    return e('div', null,
      e('div', { className:'hero' },
        e('div', null,
          e('h1', null, 'Descoberta Contínua e Autônoma'),
          e('div', { className:'sub' }, 'Inventário vivo, identificação de serviços e topologia operacional')
        ),
        e('button', { className:'btn', onClick: onScan, disabled: scanning },
          scanning ? e('span', { className:'spinner' }) : null,
          scanning ? ' Executando…' : 'Executar varredura'
        )
      ),
      e('div', { className:'filters' },
        e(Select, { label:'tenant_id', value:scope.tenant, onChange:v=>setScope({...scope,tenant:v}), options:comboOptions(scope.tenant, scopeOptions?.tenants), style:{minWidth:160} }),
        e(Select, { label:'site_id', value:scope.site, onChange:v=>setScope({...scope,site:v}), options:comboOptions(scope.site, scopeOptions?.sites), style:{minWidth:160} }),
        e(Select, { label:'edge_id', value:scope.edge, onChange:v=>setScope({...scope,edge:v}), options:comboOptions(scope.edge, scopeOptions?.edges), style:{minWidth:160} }),
        e(Input, { label:'Token interno', type:'password', value:token, onChange:v=>{ setToken(v); sessionStorage.setItem('observe_token',v); }, placeholder:'••••••••', style:{minWidth:160} })
      ),
      scanMsg ? e('div', { className:'scan-feedback '+(scanMsg.type), style:{marginBottom:8} }, scanMsg.text) : null,
      e(PipelinePanel, { progress }),
      e('div', { className:'grid', style:{marginBottom:12} },
        e('div', { className:'card' }, e('div', { className:'k' }, 'Ativos'), e('div', { className:'v' }, String(assets.length))),
        e('div', { className:'card' }, e('div', { className:'k' }, 'Serviços'), e('div', { className:'v' }, String(services.length))),
        e('div', { className:'card' }, e('div', { className:'k' }, 'Execuções'), e('div', { className:'v' }, String(runs.length))),
        e('div', { className:'card' }, e('div', { className:'k' }, 'Última execução'), e('div', { className:'v' }, runBadge(lastRunStatus))),
        e('div', { className:'card' }, e('div', { className:'k' }, 'Novos (24h)'), e('div', { className:'v' }, String(newCount))),
        e('div', { className:'card' }, e('div', { className:'k' }, 'Alterados'), e('div', { className:'v' }, String(changedCnt))),
        e('div', { className:'card' }, e('div', { className:'k' }, 'Desaparecidos'), e('div', { className:'v' }, String(disappearedCnt)))
      ),
      lastRunSummary.scanned_targets ? e('div', { style:{fontSize:'.8rem',color:'#8b949e',marginBottom:8} },
        'Última varredura: ', String(lastRunSummary.scanned_targets), ' alvos · ', String(lastRunSummary.discovered_assets), ' ativos · ', String(lastRunSummary.topology_edges), ' arestas'
      ) : null,
      e(SectionTitle, null, 'Ativos'),
      e('div', { style:{ display:'flex', gap:8, marginBottom:8, flexWrap:'wrap', alignItems:'center' } },
        e(Input, { label:'Buscar por nome, MAC, IP, fabricante ou modelo', value:assetSearch, onChange:v=>{ setAssetSearch(v); setAPage(1); }, placeholder:'SmartTV 4K, Hisense, 10.10.2.1', style:{ flex:1, minWidth:220 } }),
        e(Select, { label:'Estado', value:assetState, onChange:v=>{ setAssetState(v); setAPage(1); }, options:[{value:'all',label:'Todos os estados'}, ...LIFECYCLE_OPTS.map(o => ({ value:o, label:tr(ESTADO,o) }))], style:{ minWidth:180 } }),
        e('span', { style:{ fontSize:'.78rem', color:'#64748b', alignSelf:'end', paddingBottom:4 } }, filteredAssets.length+' ativos')
      ),
      e('table', null,
        e('thead', null, e('tr', null,
          e(SortTh, { label:'Ativo', sortKey:'name', sort:assetSort, onSort:key => toggleSort(setAssetSort, setAPage, key) }),
          e(SortTh, { label:'MAC', sortKey:'mac', sort:assetSort, onSort:key => toggleSort(setAssetSort, setAPage, key) }),
          e(SortTh, { label:'IP', sortKey:'ip', sort:assetSort, onSort:key => toggleSort(setAssetSort, setAPage, key) }),
          e(SortTh, { label:'Fabricante', sortKey:'vendor', sort:assetSort, onSort:key => toggleSort(setAssetSort, setAPage, key) }),
          e(SortTh, { label:'Modelo', sortKey:'model', sort:assetSort, onSort:key => toggleSort(setAssetSort, setAPage, key) }),
          e(SortTh, { label:'Produto', sortKey:'product', sort:assetSort, onSort:key => toggleSort(setAssetSort, setAPage, key) }),
          e('th', null, 'Serviços'),
          e(SortTh, { label:'Estado', sortKey:'state', sort:assetSort, onSort:key => toggleSort(setAssetSort, setAPage, key) })
        )),
        e('tbody', null,
          aItems.length === 0 ? e('tr',null, e('td',{colSpan:8,style:{textAlign:'center',color:'#8b949e',padding:'1.5rem'}},'Nenhum ativo encontrado.')) : null,
          aItems.map(a => e('tr', { key:a.id },
            e('td', null, a.display_name||a.asset_name),
            e('td', null, macCell(a)),
            e('td', null, a.primary_ip||'—'),
            e('td', null, a.vendor&&a.vendor!=='Unknown'?a.vendor:'—'),
            e('td', null, modelCell(a)),
            e('td', null,
              e('div', null, a.product&&a.product!=='Unknown'?a.product:'—'),
              a.asset_type === 'media_device' ? e('span', { style:{ color:'#64748b', fontSize:'.72rem' } }, 'TV / mídia') : null
            ),
            e('td', null, e('span', { title:assetServiceSummary(a), style:{ color:'#8b949e', fontSize:'.8rem' } }, assetServiceSummary(a))),
            e('td', null, estadoBadge(a.lifecycle_state))
          ))
        )
      ),
      e(Pager, { page:aPage, total:aTotal, count:filteredAssets.length, setPage:setAPage, pageSize, setPageSize }),
      e('div', { className:'split', style:{marginTop:10} },
        e('div', null,
          e(SectionTitle, null, 'Ocorrências'),
          e('div', { style:{ display:'flex', gap:8, marginBottom:8, flexWrap:'wrap', alignItems:'center' } },
            e(Input, { label:'Buscar tipo, ativo ou detalhe', value:findingSearch, onChange:v=>{ setFindingSearch(v); setFPage(1); }, placeholder:'SmartTV 4K, Hisense, SSDP', style:{ flex:1, minWidth:180 } }),
            e(Select, { label:'Gravidade', value:findingSeverity, onChange:v=>{ setFindingSeverity(v); setFPage(1); }, options:[{value:'all',label:'Todas as gravidades'}, ...Object.keys(SEVERITY).map(o => ({ value:o, label:tr(SEVERITY,o) }))], style:{ minWidth:180 } }),
            e('span', { style:{ fontSize:'.78rem', color:'#64748b', alignSelf:'end', paddingBottom:4 } }, filteredFindings.length+' ocorrências')
          ),
          e('table', null,
            e('thead', null, e('tr', null,
              e(SortTh, { label:'Tipo', sortKey:'type', sort:findingSort, onSort:key => toggleSort(setFindingSort, setFPage, key) }),
              e(SortTh, { label:'Gravidade', sortKey:'severity', sort:findingSort, onSort:key => toggleSort(setFindingSort, setFPage, key) }),
              e(SortTh, { label:'Fonte', sortKey:'source', sort:findingSort, onSort:key => toggleSort(setFindingSort, setFPage, key) }),
              e('th', null, 'Detalhe')
            )),
            e('tbody', null,
              fItems.length === 0 ? e('tr', null, e('td', { colSpan:4, style:{textAlign:'center',color:'#8b949e',padding:'1.5rem'} }, 'Nenhuma ocorrência encontrada.')) : null,
              fItems.map(f => e('tr', { key:f.id },
                e('td', null, FINDING_TYPE[String(f.finding_type||'').toLowerCase()]||String(f.finding_type||'—').replace(/_/g,' ')),
                e('td', null, sevBadge(f.severity)),
                e('td', null, f.source||'—'),
                e('td', null, e('span', { title:JSON.stringify(f.payload || {}).slice(0, 500), style:{ fontSize:'.78rem', color:'#8b949e' } }, summarizePayload(f.payload)))
              ))
            )
          ),
          e(Pager, { page:fPage, total:fTotal, count:filteredFindings.length, setPage:setFPage, pageSize:findingPageSize, setPageSize:setFindingPageSize })
        ),
        e('div', null,
          e(SectionTitle, null, 'Execuções'),
          e('div', { style:{ display:'flex', gap:8, marginBottom:8, flexWrap:'wrap', alignItems:'center' } },
            e(Input, { label:'Buscar status ou datas', value:runSearch, onChange:v=>{ setRunSearch(v); setRPage(1); }, placeholder:'Concluído', style:{ flex:1, minWidth:180 } }),
            e(Select, { label:'Status', value:runStatus, onChange:v=>{ setRunStatus(v); setRPage(1); }, options:[{value:'all',label:'Todos os status'}, ...Object.keys(RUN_STATUS).map(o => ({ value:o, label:tr(RUN_STATUS,o) }))], style:{ minWidth:180 } }),
            e('span', { style:{ fontSize:'.78rem', color:'#64748b', alignSelf:'end', paddingBottom:4 } }, filteredRuns.length+' execuções')
          ),
          e('table', null,
            e('thead', null, e('tr', null,
              e(SortTh, { label:'Status', sortKey:'status', sort:runSort, onSort:key => toggleSort(setRunSort, setRPage, key) }),
              e(SortTh, { label:'Início', sortKey:'started_at', sort:runSort, onSort:key => toggleSort(setRunSort, setRPage, key) }),
              e(SortTh, { label:'Fim', sortKey:'completed_at', sort:runSort, onSort:key => toggleSort(setRunSort, setRPage, key) })
            )),
            e('tbody', null,
              rItems.length === 0 ? e('tr', null, e('td', { colSpan:3, style:{textAlign:'center',color:'#8b949e',padding:'1.5rem'} }, 'Nenhuma execução encontrada.')) : null,
              rItems.map(r => e('tr', { key:r.id },
                e('td', null, runBadge(r.status)),
                e('td', null, fmtDt(r.started_at)),
                e('td', null, fmtDt(r.completed_at))
              ))
            )
          ),
          e(Pager, { page:rPage, total:rTotal, count:filteredRuns.length, setPage:setRPage, pageSize:runPageSize, setPageSize:setRunPageSize })
        )
      )
    );
  }

  // ── Aba: Políticas ───────────────────────────────────────────────────────────
  const EMPTY_POLICY = { name:'', scan_profile:'safe', active_enabled:true, passive_enabled:true, allowed_ranges:'', blocked_ranges:'', max_rate_per_minute:300, host_timeout_ms:12000, max_concurrency:5, allow_udp:false, auto_prometheus_sd:true, auto_icinga_sync:false, is_default:false };

  function PoliciesTab({ data, token, scope, reload }) {
    const { policies=[] } = data;
    const [showForm, setShowForm]   = React.useState(false);
    const [form, setForm]           = React.useState({ ...EMPTY_POLICY });
    const [editId, setEditId]       = React.useState(null);
    const [feedback, setFeedback]   = React.useState('');
    const [searchName, setSearchName] = React.useState('');
    const [pageSize, setPageSize]   = React.useState(10);
    const [sort, setSort]           = React.useState({ key:'name', dir:'asc' });
    const set = (k, v) => setForm(f => ({ ...f, [k]: v }));
    const filteredPol = policies.filter(p => !searchName || p.name.toLowerCase().includes(searchName.toLowerCase()));
    const sortedPol = sortItems(filteredPol, sort, {
      name: p => p.name || '',
      scan_profile: p => p.scan_profile || '',
      max_rate_per_minute: p => p.max_rate_per_minute || 0,
      max_concurrency: p => p.max_concurrency || 0,
      auto_prometheus_sd: p => p.auto_prometheus_sd === true,
      is_default: p => p.is_default === true,
    });
    const { page: pPage, setPage: setPPage, total: pTotal, pageItems: pItems } = usePage(sortedPol, pageSize);

    function toggleSort(key) {
      setSort(current => current.key === key
        ? { key, dir: current.dir === 'asc' ? 'desc' : 'asc' }
        : { key, dir: 'asc' });
      setPPage(1);
    }

    function parseRanges(str) {
      try {
        const trimmed = str.trim();
        if (trimmed.startsWith('[')) return JSON.parse(trimmed);
        return trimmed.split('\\n').map(s=>s.trim()).filter(Boolean);
      } catch { return []; }
    }

    async function save() {
      const body = {
        ...apiScope(scope),
        ...form,
        allowed_ranges: parseRanges(form.allowed_ranges),
        blocked_ranges: parseRanges(form.blocked_ranges),
      };
      const headers = { 'Content-Type':'application/json', ...(token?{'x-internal-token':token}:{}) };
      const url = editId ? API+'/policies/'+editId : API+'/policies';
      const method = editId ? 'PATCH' : 'POST';
      const r = await fetch(url, { method, headers, body: JSON.stringify(body) });
      const d = await r.json();
      if (!r.ok) { setFeedback('✗ '+( d.error||r.status)); return; }
      setFeedback('✓ Salvo com sucesso');
      setShowForm(false); setEditId(null); setForm({...EMPTY_POLICY});
      reload();
    }

    function startEdit(p) {
      setForm({
        name: p.name, scan_profile: p.scan_profile,
        active_enabled: p.active_enabled, passive_enabled: p.passive_enabled,
        allowed_ranges: (p.allowed_ranges||[]).join('\\n'),
        blocked_ranges: (p.blocked_ranges||[]).join('\\n'),
        max_rate_per_minute: p.max_rate_per_minute||300,
        host_timeout_ms: p.host_timeout_ms||12000,
        max_concurrency: p.max_concurrency||5,
        allow_udp: p.allow_udp||false,
        auto_prometheus_sd: p.auto_prometheus_sd!==false,
        auto_icinga_sync: p.auto_icinga_sync||false,
        is_default: p.is_default||false,
      });
      setEditId(p.id); setShowForm(true); setFeedback('');
    }

	    async function del(p) {
	      if (!confirm('Excluir política "'+p.name+'"?')) return;
	      const q = scopeQuery(scope);
	      const r = await fetch(API+'/policies/'+p.id+q, { method:'DELETE', headers:{ ...(token?{'x-internal-token':token}:{}) } });
      if (!r.ok) { setFeedback('✗ Erro ao excluir'); return; }
      setFeedback('✓ Excluída: '+p.name);
      reload();
    }

    return e('div', null,
      e('div', { style:{ display:'flex', alignItems:'center', gap:8, marginBottom:8, flexWrap:'wrap' } },
        e('h2', { style:{ fontSize:'1.1rem', margin:0, flex:1 } }, 'Políticas de Varredura'),
        e(Input, { label:'Buscar por nome', value:searchName, onChange:v=>{ setSearchName(v); setPPage(1); }, placeholder:'default-safe', style:{ width:220 } }),
        e(BtnPrimary, { onClick: () => { setShowForm(!showForm); setEditId(null); setForm({...EMPTY_POLICY}); setFeedback(''); } },
          showForm && !editId ? 'Cancelar' : '+ Nova Política'
        )
      ),
      e(Feedback, { msg: feedback }),
      (showForm) ? e(FormCard, { onSubmit: save },
        e('div', { style:{ fontWeight:600, marginBottom:10, color:'#c9d1d9' } }, editId ? 'Editar Política' : 'Nova Política'),
        e(FormGrid, null,
          e(Input, { label:'Nome *', value:form.name, onChange:v=>set('name',v), required:true }),
          e(Select, { label:'Perfil', value:form.scan_profile, onChange:v=>set('scan_profile',v), options:PROFILE_OPTS }),
          e(Input, { label:'Taxa máx/min', value:form.max_rate_per_minute, onChange:v=>set('max_rate_per_minute',Number(v)), type:'number' }),
          e(Input, { label:'Timeout host (ms)', value:form.host_timeout_ms, onChange:v=>set('host_timeout_ms',Number(v)), type:'number' }),
          e(Input, { label:'Concorrência máx', value:form.max_concurrency, onChange:v=>set('max_concurrency',Number(v)), type:'number' })
        ),
        e(Textarea, { label:'Ranges permitidos (um por linha)', value:form.allowed_ranges, onChange:v=>set('allowed_ranges',v), placeholder:'10.0.0.0/8\\n192.168.0.0/16\\n172.16.0.0/12', rows:3 }),
        e('div', { style:{ height:8 } }),
        e(Textarea, { label:'Ranges bloqueados (um por linha)', value:form.blocked_ranges, onChange:v=>set('blocked_ranges',v), placeholder:'10.0.0.0/8', rows:2 }),
        e('div', { style:{ display:'flex', flexWrap:'wrap', gap:16, marginTop:12 } },
          e(Check, { label:'Scan ativo', checked:form.active_enabled, onChange:v=>set('active_enabled',v) }),
          e(Check, { label:'Passivo', checked:form.passive_enabled, onChange:v=>set('passive_enabled',v) }),
          e(Check, { label:'UDP', checked:form.allow_udp, onChange:v=>set('allow_udp',v) }),
          e(Check, { label:'Auto Prometheus SD', checked:form.auto_prometheus_sd, onChange:v=>set('auto_prometheus_sd',v) }),
          e(Check, { label:'Auto Icinga sync', checked:form.auto_icinga_sync, onChange:v=>set('auto_icinga_sync',v) }),
          e(Check, { label:'Padrão', checked:form.is_default, onChange:v=>set('is_default',v) })
        ),
        e('div', { style:{ display:'flex', gap:8, marginTop:14 } },
          e(BtnPrimary, { onClick: save }, editId ? 'Atualizar' : 'Criar Política'),
          e(BtnSecondary, { onClick: () => { setShowForm(false); setEditId(null); setForm({...EMPTY_POLICY}); } }, 'Cancelar')
        )
      ) : null,
      e('table', null,
        e('thead', null, e('tr', null,
          e(SortTh, { label:'Nome', sortKey:'name', sort, onSort:toggleSort }),
          e(SortTh, { label:'Perfil', sortKey:'scan_profile', sort, onSort:toggleSort }),
          e(SortTh, { label:'Taxa/min', sortKey:'max_rate_per_minute', sort, onSort:toggleSort }),
          e(SortTh, { label:'Conc.', sortKey:'max_concurrency', sort, onSort:toggleSort }),
          e('th', null, 'Ranges'),
          e('th', null, 'Modos'),
          e(SortTh, { label:'SD', sortKey:'auto_prometheus_sd', sort, onSort:toggleSort }),
          e(SortTh, { label:'Padrão', sortKey:'is_default', sort, onSort:toggleSort }),
          e('th',null,'')
        )),
        e('tbody', null,
          pItems.length === 0 ? e('tr', null, e('td', { colSpan:9, style:{textAlign:'center',color:'#8b949e',padding:'2rem'} }, 'Nenhuma política encontrada.')) : null,
          pItems.map(p => e('tr', { key:p.id },
            e('td', null, p.name),
            e('td', null, badge(p.scan_profile==='aggressive'?'critical':p.scan_profile==='balanced'?'warning':'ok', p.scan_profile)),
            e('td', null, p.max_rate_per_minute),
            e('td', null, p.max_concurrency),
            e('td', null, 'permitidos '+((p.allowed_ranges||[]).length || 0)+' / bloqueados '+((p.blocked_ranges||[]).length || 0)),
            e('td', null, [p.active_enabled?'ativo':null, p.passive_enabled?'passivo':null, p.allow_udp?'udp':null, p.auto_icinga_sync?'icinga':null].filter(Boolean).join(', ') || '—'),
            e('td', null, p.auto_prometheus_sd ? '✓' : '—'),
            e('td', null, p.is_default ? badge('ok','sim') : '—'),
            e('td', null,
              e('div', { style:{display:'flex',gap:6} },
                e(BtnSecondary, { onClick: ()=>startEdit(p) }, 'Editar'),
                p.is_default ? null : e(BtnDanger, { onClick: ()=>del(p) }, 'Excluir')
              )
            )
          ))
        )
      ),
      e(Pager, { page:pPage, total:pTotal, count:filteredPol.length, setPage:setPPage, pageSize, setPageSize })
    );
  }

  // ── Aba: Targets ─────────────────────────────────────────────────────────────
  const EMPTY_TARGET = { address:'', discovery_type:'ip', label:'', enabled:true, policy_id:'' };

  function TargetsTab({ data, token, scope, reload }) {
    const { targets=[], policies=[] } = data;
    const [showForm, setShowForm] = React.useState(false);
    const [form, setForm] = React.useState({ ...EMPTY_TARGET });
    const [editId, setEditId] = React.useState(null);
    const [feedback, setFeedback] = React.useState('');
    const [filterEnabled, setFilterEnabled] = React.useState('all');
    const [filterType, setFilterType]       = React.useState('all');
    const [searchAddr, setSearchAddr]       = React.useState('');
    const [pageSize, setPageSize]           = React.useState(25);
    const [sort, setSort]                   = React.useState({ key:'address', dir:'asc' });
    const set = (k,v) => setForm(f => ({...f,[k]:v}));

    async function save() {
      const body = { ...apiScope(scope), ...form };
      if (!body.address) { setFeedback('✗ address é obrigatório'); return; }
      if (!body.policy_id) delete body.policy_id;
      const headers = { 'Content-Type':'application/json', ...(token?{'x-internal-token':token}:{}) };
      const url = editId ? API+'/targets/'+editId : API+'/targets';
      const method = editId ? 'PATCH' : 'POST';
      const r = await fetch(url, { method, headers, body: JSON.stringify(body) });
      const d = await r.json();
      if (!r.ok) { setFeedback('✗ '+(d.error||r.status)); return; }
      setFeedback('✓ Salvo: '+body.address);
      setShowForm(false); setEditId(null); setForm({...EMPTY_TARGET});
      reload();
    }

    function startEdit(t) {
      setForm({ address:t.address, discovery_type:t.discovery_type||'ip', label:t.label||'', enabled:t.enabled!==false, policy_id:t.policy_id||'' });
      setEditId(t.id); setShowForm(true); setFeedback('');
    }

	    async function del(t) {
	      if (!confirm('Excluir target "'+t.address+'"?')) return;
	      const q = scopeQuery(scope);
	      const r = await fetch(API+'/targets/'+t.id+q, { method:'DELETE', headers:{...(token?{'x-internal-token':token}:{})} });
      if (!r.ok) { setFeedback('✗ Erro ao excluir'); return; }
      setFeedback('✓ Excluído: '+t.address);
      reload();
    }

	    async function toggleEnabled(t) {
	      const r = await fetch(API+'/targets/'+t.id, { method:'PATCH',
	        headers:{ 'Content-Type':'application/json', ...(token?{'x-internal-token':token}:{}) },
	        body: JSON.stringify({ ...apiScope(scope), enabled: !t.enabled })
	      });
      if (r.ok) reload();
    }

    const filtered = targets.filter(t => {
      const enOk   = filterEnabled === 'all' || String(t.enabled) === filterEnabled;
      const typeOk = filterType    === 'all' || t.discovery_type === filterType;
      const srOk   = !searchAddr   || (t.address||'').toLowerCase().includes(searchAddr.toLowerCase()) || (t.label||'').toLowerCase().includes(searchAddr.toLowerCase());
      return enOk && typeOk && srOk;
    });
    const sorted = sortItems(filtered, sort, {
      address: t => t.address || '',
      discovery_type: t => t.discovery_type || '',
      label: t => t.label || '',
      policy_name: t => t.policy_name || '',
      enabled: t => t.enabled === true,
    });
    const { page: tPage, setPage: setTPage, total: tTotal, pageItems: tItems } = usePage(sorted, pageSize);
    const policyOpts = [{ value:'', label:'(sem política)' }, ...policies.map(p => ({ value:p.id, label:p.name }))];

    function toggleSort(key) {
      setSort(current => current.key === key
        ? { key, dir: current.dir === 'asc' ? 'desc' : 'asc' }
        : { key, dir: 'asc' });
      setTPage(1);
    }

    return e('div', null,
      e('div', { style:{ display:'flex', alignItems:'center', gap:8, marginBottom:8, flexWrap:'wrap' } },
        e('h2', { style:{ fontSize:'1.1rem', margin:0, flex:1 } }, 'Targets de Descoberta'),
        e(BtnPrimary, { onClick:()=>{ setShowForm(!showForm); setEditId(null); setForm({...EMPTY_TARGET}); setFeedback(''); } },
          showForm && !editId ? 'Cancelar' : '+ Novo Target'
        )
      ),
      e('div', { style:{ display:'flex', gap:8, marginBottom:10, flexWrap:'wrap', alignItems:'center' } },
        e(Input, { label:'Buscar endereço ou label', value:searchAddr, onChange:v=>{ setSearchAddr(v); setTPage(1); }, placeholder:'10.10.2.0/24', style:{ flex:1, minWidth:180 } }),
        e(Select, { label:'Tipo', value:filterType, onChange:v=>{ setFilterType(v); setTPage(1); }, options:[{value:'all',label:'Todos os tipos'}, ...TYPE_OPTS.map(o => ({ value:o, label:o }))], style:{ minWidth:170 } }),
        e(Select, { label:'Status', value:filterEnabled, onChange:v=>{ setFilterEnabled(v); setTPage(1); }, options:[{value:'all',label:'Todos'}, {value:'true',label:'Habilitados'}, {value:'false',label:'Desabilitados'}], style:{ minWidth:170 } }),
        e('span', { style:{ fontSize:'.78rem', color:'#64748b', alignSelf:'end', paddingBottom:4 } }, filtered.length+' targets')
      ),
      e(Feedback, { msg: feedback }),
      showForm ? e(FormCard, { onSubmit: save },
        e('div', { style:{ fontWeight:600, marginBottom:10, color:'#c9d1d9' } }, editId ? 'Editar Target' : 'Novo Target'),
        e(FormGrid, null,
          e(Input, { label:'Endereço *', value:form.address, onChange:v=>set('address',v), placeholder:'10.10.2.0/24 ou 192.168.1.1', required:true }),
          e(Select, { label:'Tipo', value:form.discovery_type, onChange:v=>set('discovery_type',v), options:TYPE_OPTS }),
          e(Input, { label:'Label (opcional)', value:form.label, onChange:v=>set('label',v), placeholder:'servidores-core' }),
          e(Select, { label:'Política', value:form.policy_id, onChange:v=>set('policy_id',v), options:policyOpts })
        ),
        e(Check, { label:'Habilitado', checked:form.enabled, onChange:v=>set('enabled',v) }),
        e('div', { style:{ display:'flex', gap:8, marginTop:14 } },
          e(BtnPrimary, { onClick:save }, editId ? 'Atualizar' : 'Criar Target'),
          e(BtnSecondary, { onClick:()=>{ setShowForm(false); setEditId(null); setForm({...EMPTY_TARGET}); } }, 'Cancelar')
        )
      ) : null,
      e('table', null,
        e('thead', null, e('tr', null,
          e(SortTh, { label:'Endereço', sortKey:'address', sort, onSort:toggleSort }),
          e(SortTh, { label:'Tipo', sortKey:'discovery_type', sort, onSort:toggleSort }),
          e(SortTh, { label:'Label', sortKey:'label', sort, onSort:toggleSort }),
          e(SortTh, { label:'Política', sortKey:'policy_name', sort, onSort:toggleSort }),
          e(SortTh, { label:'Status', sortKey:'enabled', sort, onSort:toggleSort }),
          e('th', null, 'Criado'),
          e('th',null,'')
        )),
        e('tbody', null,
          tItems.length === 0 ? e('tr', null, e('td', { colSpan:7, style:{textAlign:'center',color:'#8b949e',padding:'2rem'} }, 'Nenhum target encontrado.')) : null,
          tItems.map(t => e('tr', { key:t.id },
            e('td', null, e('code', { style:{color:'#58a6ff',fontSize:'.82rem'} }, t.address)),
            e('td', null, t.discovery_type),
            e('td', null, t.label||'—'),
            e('td', null, t.policy_name||'—'),
            e('td', null, t.enabled ? badge('ok','ativo') : badge('disabled','inativo')),
            e('td', null, fmtDt(t.created_at)),
            e('td', null,
              e('div', { style:{display:'flex',gap:6} },
                e(BtnSecondary, { onClick:()=>startEdit(t) }, 'Editar'),
                e(BtnSecondary, { onClick:()=>toggleEnabled(t) }, t.enabled ? 'Desabilitar' : 'Habilitar'),
                e(BtnDanger, { onClick:()=>del(t) }, 'Excluir')
              )
            )
          ))
        )
      ),
      e(Pager, { page:tPage, total:tTotal, count:filtered.length, setPage:setTPage, pageSize, setPageSize })
    );
  }

  // ── Aba: Ativos ──────────────────────────────────────────────────────────────
  function AssetsTab({ data, token, scope, reload }) {
    const { assets=[] } = data;
    const [filterState, setFilterState]       = React.useState('all');
    const [filterCrit, setFilterCrit]         = React.useState('all');
    const [filterName, setFilterName]         = React.useState('');
    const [pageSize, setPageSize]             = React.useState(25);
    const [sort, setSort]                     = React.useState({ key:'name', dir:'asc' });
    const [feedback, setFeedback]             = React.useState('');
    const [editing, setEditing]               = React.useState(null);

	    async function patchAsset(id, body) {
	      const r = await fetch(API+'/assets/'+id, { method:'PATCH',
	        headers:{ 'Content-Type':'application/json', ...(token?{'x-internal-token':token}:{}) },
	        body: JSON.stringify({ ...apiScope(scope), ...body })
	      });
      const d = await r.json();
      if (!r.ok) { setFeedback('✗ '+(d.error||r.status)); return false; }
      return true;
    }

    async function saveEdit() {
      const { id, form } = editing;
      const ok = await patchAsset(id, form);
      if (ok) { setFeedback('✓ Ativo atualizado'); setEditing(null); reload(); }
    }

    const filtered = assets.filter(a => {
      const stateOk = filterState === 'all' || a.lifecycle_state === filterState;
      const critOk  = filterCrit  === 'all' || a.criticality === filterCrit;
      const nameOk  = !filterName || (a.display_name||a.asset_name||'').toLowerCase().includes(filterName.toLowerCase()) || (a.primary_ip||'').includes(filterName);
      return stateOk && critOk && nameOk;
    });
    const sorted = sortItems(filtered, sort, {
      name: a => a.display_name || a.asset_name || '',
      primary_ip: a => a.primary_ip || '',
      vendor_os: a => [cleanUnknown(a.vendor), cleanUnknown(a.product), a.os_hint].filter(Boolean).join(' / '),
      services: a => (Array.isArray(a.services) ? a.services.length : 0),
      lifecycle_state: a => a.lifecycle_state || '',
      criticality: a => a.criticality || '',
      confidence: a => Number(a.confidence || 0),
      last_seen_at: a => a.last_seen_at || '',
    });
    const { page: aPage, setPage: setAPage, total: aTotal, pageItems: aItems } = usePage(sorted, pageSize);

    function toggleSort(key) {
      setSort(current => current.key === key
        ? { key, dir: current.dir === 'asc' ? 'desc' : 'asc' }
        : { key, dir: 'asc' });
      setAPage(1);
    }

    return e('div', null,
      e('div', { style:{ display:'flex', alignItems:'center', gap:8, marginBottom:8, flexWrap:'wrap' } },
        e('h2', { style:{ fontSize:'1.1rem', margin:0, flex:1 } }, 'Ativos Descobertos')
      ),
      e('div', { style:{ display:'flex', gap:8, marginBottom:10, flexWrap:'wrap', alignItems:'center' } },
        e(Input, { label:'Buscar por nome ou IP', value:filterName, onChange:v=>{ setFilterName(v); setAPage(1); }, placeholder:'10.10.2.1', style:{ flex:1, minWidth:180 } }),
        e(Select, { label:'Estado', value:filterState, onChange:v=>{ setFilterState(v); setAPage(1); }, options:[{value:'all',label:'Todos os estados'}, ...LIFECYCLE_OPTS.map(o => ({ value:o, label:tr(ESTADO,o) }))], style:{ minWidth:170 } }),
        e(Select, { label:'Criticidade', value:filterCrit, onChange:v=>{ setFilterCrit(v); setAPage(1); }, options:[{value:'all',label:'Todas as criticidades'}, ...CRITICALITY_OPTS.map(o => ({ value:o, label:o }))], style:{ minWidth:170 } }),
        e('span', { style:{ fontSize:'.78rem', color:'#64748b', alignSelf:'end', paddingBottom:4 } }, filtered.length+' ativos')
      ),
      e(Feedback, { msg: feedback }),
      e('table', null,
        e('thead', null, e('tr', null,
          e(SortTh, { label:'Ativo', sortKey:'name', sort, onSort:toggleSort }),
          e(SortTh, { label:'IP', sortKey:'primary_ip', sort, onSort:toggleSort }),
          e(SortTh, { label:'Fabricante / Produto / OS', sortKey:'vendor_os', sort, onSort:toggleSort }),
          e(SortTh, { label:'Serviços', sortKey:'services', sort, onSort:toggleSort }),
          e(SortTh, { label:'Estado', sortKey:'lifecycle_state', sort, onSort:toggleSort }),
          e(SortTh, { label:'Criticidade', sortKey:'criticality', sort, onSort:toggleSort }),
          e(SortTh, { label:'Conf.', sortKey:'confidence', sort, onSort:toggleSort }),
          e(SortTh, { label:'Último visto', sortKey:'last_seen_at', sort, onSort:toggleSort }),
          e('th',null,'')
        )),
        e('tbody', null,
          aItems.length === 0 ? e('tr', null, e('td', { colSpan:9, style:{textAlign:'center',color:'#8b949e',padding:'2rem'} }, 'Nenhum ativo encontrado.')) : null,
          aItems.map(a => {
            const isEditing = editing && editing.id === a.id;
            return e('tr', { key:a.id },
              e('td', null,
                e('div', null, a.display_name||a.asset_name),
                e('code', { style:{ color:'#64748b', fontSize:'.72rem' } }, a.asset_key || shortId(a.id))
              ),
              e('td', null, a.primary_ip||'—'),
              e('td', null, [cleanUnknown(a.vendor), cleanUnknown(a.product), a.os_hint].filter(Boolean).join(' / ')||'—'),
              e('td', null, e('span', { title:assetServiceSummary(a), style:{ color:'#8b949e', fontSize:'.8rem' } }, assetServiceSummary(a))),
              e('td', null, isEditing
                ? e('select', { value:editing.form.lifecycle_state||a.lifecycle_state, onChange:ev=>setEditing({...editing,form:{...editing.form,lifecycle_state:ev.target.value}}),
                    style:{ background:'#0d1117', border:'1px solid #30363d', borderRadius:5, color:'#c9d1d9', padding:'3px 6px', fontSize:'.8rem' }
                  }, LIFECYCLE_OPTS.map(o => e('option',{key:o,value:o},tr(ESTADO,o))))
                : estadoBadge(a.lifecycle_state)
              ),
              e('td', null, isEditing
                ? e('select', { value:editing.form.criticality||a.criticality, onChange:ev=>setEditing({...editing,form:{...editing.form,criticality:ev.target.value}}),
                    style:{ background:'#0d1117', border:'1px solid #30363d', borderRadius:5, color:'#c9d1d9', padding:'3px 6px', fontSize:'.8rem' }
                  }, CRITICALITY_OPTS.map(o => e('option',{key:o,value:o},o)))
                : e('span', { style:{color:'#8b949e',fontSize:'.82rem'} }, a.criticality||'—')
              ),
              e('td', null, fmtPct(a.confidence)),
              e('td', null, fmtDt(a.last_seen_at || a.updated_at)),
              e('td', null,
                isEditing
                  ? e('div', { style:{display:'flex',gap:6} },
                      e(BtnPrimary, { onClick:saveEdit }, 'Salvar'),
                      e(BtnSecondary, { onClick:()=>setEditing(null) }, 'Cancelar')
                    )
                  : e(BtnSecondary, { onClick:()=>setEditing({ id:a.id, form:{ lifecycle_state:a.lifecycle_state, criticality:a.criticality } }) }, 'Editar')
              )
            );
          })
        )
      ),
      e(Pager, { page:aPage, total:aTotal, count:filtered.length, setPage:setAPage, pageSize, setPageSize })
    );
  }

  function ServicesTab({ data }) {
    const { services=[] } = data;
    const [search, setSearch] = React.useState('');
    const [protocol, setProtocol] = React.useState('all');
    const [status, setStatus] = React.useState('all');
    const [pageSize, setPageSize] = React.useState(25);
    const [sort, setSort] = React.useState({ key:'updated_at', dir:'desc' });
    const protocols = Array.from(new Set(services.map(s => s.protocol).filter(Boolean))).sort();
    const statuses = Array.from(new Set(services.map(s => s.status).filter(Boolean))).sort();
    const filtered = services.filter(s => {
      const protoOk = protocol === 'all' || String(s.protocol || '').toLowerCase() === protocol;
      const statusOk = status === 'all' || String(s.status || '').toLowerCase() === status;
      const text = [s.asset_display_name, s.asset_name, s.asset_key, s.service_key, s.service_name, s.protocol, s.port, s.status].join(' ').toLowerCase();
      return protoOk && statusOk && (!search || text.includes(search.toLowerCase()));
    });
    const sorted = sortItems(filtered, sort, {
      asset: s => s.asset_display_name || s.asset_name || '',
      service_key: s => s.service_key || '',
      service_name: s => s.service_name || '',
      protocol: s => s.protocol || '',
      port: s => Number(s.port || 0),
      status: s => s.status || '',
      updated_at: s => s.updated_at || s.last_seen_at || '',
    });
    const { page, setPage, total, pageItems } = usePage(sorted, pageSize);
    function toggleSort(key) {
      setSort(current => current.key === key ? { key, dir: current.dir === 'asc' ? 'desc' : 'asc' } : { key, dir: 'asc' });
      setPage(1);
    }
    return e('div', null,
      e(SectionTitle, null, 'Serviços Descobertos'),
      e('div', { className:'grid', style:{marginBottom:12} },
        e('div', { className:'card' }, e('div', { className:'k' }, 'Serviços'), e('div', { className:'v' }, String(services.length))),
        e('div', { className:'card' }, e('div', { className:'k' }, 'Portas'), e('div', { className:'v' }, String(new Set(services.map(s => s.port).filter(Boolean)).size))),
        e('div', { className:'card' }, e('div', { className:'k' }, 'Protocolos'), e('div', { className:'v' }, String(protocols.length))),
        e('div', { className:'card' }, e('div', { className:'k' }, 'Ativos com serviço'), e('div', { className:'v' }, String(new Set(services.map(s => s.asset_id).filter(Boolean)).size)))
      ),
      e('div', { style:{ display:'flex', gap:8, marginBottom:10, flexWrap:'wrap', alignItems:'center' } },
        e(Input, { label:'Buscar ativo, serviço ou porta', value:search, onChange:v=>{ setSearch(v); setPage(1); }, placeholder:'postgres, tcp:5432, 10.10.2.1', style:{ flex:1, minWidth:220 } }),
        e(Select, { label:'Protocolo', value:protocol, onChange:v=>{ setProtocol(v); setPage(1); }, options:[{value:'all',label:'Todos'}, ...protocols.map(p => ({ value:String(p).toLowerCase(), label:String(p).toUpperCase() }))], style:{ minWidth:150 } }),
        e(Select, { label:'Status', value:status, onChange:v=>{ setStatus(v); setPage(1); }, options:[{value:'all',label:'Todos'}, ...statuses.map(s => ({ value:String(s).toLowerCase(), label:s }))], style:{ minWidth:150 } }),
        e('span', { style:{ fontSize:'.78rem', color:'#64748b', alignSelf:'end', paddingBottom:4 } }, filtered.length+' serviços')
      ),
      e('table', null,
        e('thead', null, e('tr', null,
          e(SortTh, { label:'Ativo', sortKey:'asset', sort, onSort:toggleSort }),
          e(SortTh, { label:'Serviço', sortKey:'service_key', sort, onSort:toggleSort }),
          e(SortTh, { label:'Nome', sortKey:'service_name', sort, onSort:toggleSort }),
          e(SortTh, { label:'Proto', sortKey:'protocol', sort, onSort:toggleSort }),
          e(SortTh, { label:'Porta', sortKey:'port', sort, onSort:toggleSort }),
          e(SortTh, { label:'Status', sortKey:'status', sort, onSort:toggleSort }),
          e(SortTh, { label:'Último visto', sortKey:'updated_at', sort, onSort:toggleSort })
        )),
        e('tbody', null,
          pageItems.length === 0 ? e('tr', null, e('td', { colSpan:7, style:{textAlign:'center',color:'#8b949e',padding:'2rem'} }, 'Nenhum serviço encontrado.')) : null,
          pageItems.map(s => e('tr', { key:s.id },
            e('td', null,
              e('div', null, s.asset_display_name || s.asset_name || shortId(s.asset_id)),
              s.asset_key ? e('code', { style:{ color:'#64748b', fontSize:'.72rem' } }, s.asset_key) : null
            ),
            e('td', null, e('code', { style:{ color:'#58a6ff', fontSize:'.8rem' } }, s.service_key || '—')),
            e('td', null, s.service_name || '—'),
            e('td', null, s.protocol ? String(s.protocol).toUpperCase() : '—'),
            e('td', null, s.port || '—'),
            e('td', null, s.status ? badge(s.status === 'open' ? 'ok' : 'warning', s.status) : '—'),
            e('td', null, fmtDt(s.last_seen_at || s.updated_at))
          ))
        )
      ),
      e(Pager, { page, total, count:filtered.length, setPage, pageSize, setPageSize })
    );
  }

  function RunsTab({ data }) {
    const { runs=[] } = data;
    const [status, setStatus] = React.useState('all');
    const [pageSize, setPageSize] = React.useState(25);
    const [sort, setSort] = React.useState({ key:'started_at', dir:'desc' });
    const filtered = runs.filter(r => status === 'all' || String(r.status || '').toLowerCase() === status);
    const sorted = sortItems(filtered, sort, {
      status: r => r.status || '',
      started_at: r => r.started_at || '',
      completed_at: r => r.completed_at || '',
      scanned: r => Number(r.summary?.scanned_targets || 0),
      discovered: r => Number(r.summary?.discovered_assets || 0),
      blocked: r => Number(r.summary?.blocked_targets || 0),
    });
    const { page, setPage, total, pageItems } = usePage(sorted, pageSize);
    function toggleSort(key) {
      setSort(current => current.key === key ? { key, dir: current.dir === 'asc' ? 'desc' : 'asc' } : { key, dir: 'asc' });
      setPage(1);
    }
    return e('div', null,
      e(SectionTitle, null, 'Execuções de Discovery'),
      e('div', { style:{ display:'flex', gap:8, marginBottom:10, flexWrap:'wrap', alignItems:'center' } },
        e(Select, { label:'Status', value:status, onChange:v=>{ setStatus(v); setPage(1); }, options:[{value:'all',label:'Todos'}, ...Object.keys(RUN_STATUS).map(o => ({ value:o, label:tr(RUN_STATUS,o) }))], style:{ minWidth:180 } }),
        e('span', { style:{ fontSize:'.78rem', color:'#64748b', alignSelf:'end', paddingBottom:4 } }, filtered.length+' execuções')
      ),
      e('table', null,
        e('thead', null, e('tr', null,
          e('th', null, 'Run'),
          e(SortTh, { label:'Status', sortKey:'status', sort, onSort:toggleSort }),
          e('th', null, 'Trigger'),
          e(SortTh, { label:'Início', sortKey:'started_at', sort, onSort:toggleSort }),
          e(SortTh, { label:'Fim', sortKey:'completed_at', sort, onSort:toggleSort }),
          e(SortTh, { label:'Alvos', sortKey:'scanned', sort, onSort:toggleSort }),
          e(SortTh, { label:'Bloqueados', sortKey:'blocked', sort, onSort:toggleSort }),
          e(SortTh, { label:'Ativos', sortKey:'discovered', sort, onSort:toggleSort }),
          e('th', null, 'Topologia'),
          e('th', null, 'Erro')
        )),
        e('tbody', null,
          pageItems.length === 0 ? e('tr', null, e('td', { colSpan:10, style:{textAlign:'center',color:'#8b949e',padding:'2rem'} }, 'Nenhuma execução encontrada.')) : null,
          pageItems.map(r => e('tr', { key:r.id },
            e('td', null, e('code', { style:{ color:'#64748b', fontSize:'.75rem' } }, shortId(r.id))),
            e('td', null, runBadge(r.status)),
            e('td', null, r.metadata?.trigger || r.summary?.trigger || '—'),
            e('td', null, fmtDt(r.started_at)),
            e('td', null, fmtDt(r.completed_at)),
            e('td', null, String(r.summary?.scanned_targets ?? '—')),
            e('td', null, String(r.summary?.blocked_targets ?? '—')),
            e('td', null, String(r.summary?.discovered_assets ?? '—')),
            e('td', null, String(r.summary?.topology_edges ?? '—')),
            e('td', null, r.summary?.error || '—')
          ))
        )
      ),
      e(Pager, { page, total, count:filtered.length, setPage, pageSize, setPageSize })
    );
  }

  function FindingsTab({ data }) {
    const { findings=[] } = data;
    const [severity, setSeverity] = React.useState('all');
    const [search, setSearch] = React.useState('');
    const [pageSize, setPageSize] = React.useState(25);
    const [sort, setSort] = React.useState({ key:'observed_at', dir:'desc' });
    const filtered = findings.filter(f => {
      const sevOk = severity === 'all' || String(f.severity || '').toLowerCase() === severity;
      const text = [f.finding_type, f.source, f.asset_key, summarizePayload(f.payload), JSON.stringify(f.payload || {})].join(' ').toLowerCase();
      return sevOk && (!search || text.includes(search.toLowerCase()));
    });
    const sorted = sortItems(filtered, sort, {
      type: f => f.finding_type || '',
      severity: f => f.severity || '',
      source: f => f.source || '',
      observed_at: f => f.observed_at || '',
    });
    const { page, setPage, total, pageItems } = usePage(sorted, pageSize);
    function toggleSort(key) {
      setSort(current => current.key === key ? { key, dir: current.dir === 'asc' ? 'desc' : 'asc' } : { key, dir: 'asc' });
      setPage(1);
    }
    return e('div', null,
      e(SectionTitle, null, 'Achados e Ocorrências'),
      e('div', { style:{ display:'flex', gap:8, marginBottom:10, flexWrap:'wrap', alignItems:'center' } },
        e(Input, { label:'Buscar', value:search, onChange:v=>{ setSearch(v); setPage(1); }, placeholder:'asset, fonte, payload', style:{ flex:1, minWidth:220 } }),
        e(Select, { label:'Gravidade', value:severity, onChange:v=>{ setSeverity(v); setPage(1); }, options:[{value:'all',label:'Todas'}, ...Object.keys(SEVERITY).map(o => ({ value:o, label:tr(SEVERITY,o) }))], style:{ minWidth:170 } }),
        e('span', { style:{ fontSize:'.78rem', color:'#64748b', alignSelf:'end', paddingBottom:4 } }, filtered.length+' achados')
      ),
      e('table', null,
        e('thead', null, e('tr', null,
          e(SortTh, { label:'Tipo', sortKey:'type', sort, onSort:toggleSort }),
          e(SortTh, { label:'Gravidade', sortKey:'severity', sort, onSort:toggleSort }),
          e(SortTh, { label:'Fonte', sortKey:'source', sort, onSort:toggleSort }),
          e('th', null, 'Ativo'),
          e(SortTh, { label:'Observado', sortKey:'observed_at', sort, onSort:toggleSort }),
          e('th', null, 'Detalhe')
        )),
        e('tbody', null,
          pageItems.length === 0 ? e('tr', null, e('td', { colSpan:6, style:{textAlign:'center',color:'#8b949e',padding:'2rem'} }, 'Nenhum achado encontrado.')) : null,
          pageItems.map(f => e('tr', { key:f.id },
            e('td', null, FINDING_TYPE[String(f.finding_type||'').toLowerCase()] || String(f.finding_type || '—').replace(/_/g, ' ')),
            e('td', null, sevBadge(f.severity)),
            e('td', null, f.source || '—'),
            e('td', null, f.asset_key || '—'),
            e('td', null, fmtDt(f.observed_at)),
            e('td', null, e('span', { title:JSON.stringify(f.payload || {}).slice(0, 500), style:{ fontSize:'.78rem', color:'#8b949e' } }, summarizePayload(f.payload)))
          ))
        )
      ),
      e(Pager, { page, total, count:filtered.length, setPage, pageSize, setPageSize })
    );
  }

  function TopologyTab({ data }) {
    const { topology=[] } = data;
    const [search, setSearch] = React.useState('');
    const [pageSize, setPageSize] = React.useState(25);
    const filtered = topology.filter(edge => !search || [edge.edge_type, edge.from_asset_name, edge.from_asset_key, edge.to_asset_ref, edge.protocol, edge.source].some(v => String(v || '').toLowerCase().includes(search.toLowerCase())));
    const { page, setPage, total, pageItems } = usePage(filtered, pageSize);
    return e('div', null,
      e(SectionTitle, null, 'Topologia'),
      e('div', { className:'grid', style:{marginBottom:12} },
        e('div', { className:'card' }, e('div', { className:'k' }, 'Arestas'), e('div', { className:'v' }, String(topology.length))),
        e('div', { className:'card' }, e('div', { className:'k' }, 'Tipos'), e('div', { className:'v' }, String(new Set(topology.map(e => e.edge_type)).size))),
        e('div', { className:'card' }, e('div', { className:'k' }, 'Protocolos'), e('div', { className:'v' }, String(new Set(topology.map(e => e.protocol).filter(Boolean)).size)))
      ),
      e(Input, { label:'Buscar aresta', value:search, onChange:v=>{ setSearch(v); setPage(1); }, placeholder:'service_database, tcp, 10.10.2.1:5432', style:{ marginBottom:10 } }),
      e('table', null,
        e('thead', null, e('tr', null,
          e('th', null, 'Tipo'),
          e('th', null, 'Origem'),
          e('th', null, 'Destino'),
          e('th', null, 'Protocolo'),
          e('th', null, 'Fonte'),
          e('th', null, 'Observado')
        )),
        e('tbody', null,
          pageItems.length === 0 ? e('tr', null, e('td', { colSpan:6, style:{textAlign:'center',color:'#8b949e',padding:'2rem'} }, 'Nenhuma aresta encontrada.')) : null,
          pageItems.map(edge => e('tr', { key:edge.id },
            e('td', null, String(edge.edge_type || '—').replace(/_/g, ' ')),
            e('td', null,
              e('div', null, edge.from_asset_name || edge.from_asset_key || shortId(edge.from_asset_id)),
              edge.from_asset_key ? e('code', { style:{ color:'#64748b', fontSize:'.72rem' } }, edge.from_asset_key) : null
            ),
            e('td', null, edge.to_asset_ref || '—'),
            e('td', null, edge.protocol || '—'),
            e('td', null, edge.source || '—'),
            e('td', null, fmtDt(edge.observed_at))
          ))
        )
      ),
      e(Pager, { page, total, count:filtered.length, setPage, pageSize, setPageSize })
    );
  }

  function FingerprintsTab({ data }) {
    const { fingerprints=[] } = data;
    const [search, setSearch] = React.useState('');
    const [pageSize, setPageSize] = React.useState(25);
    const filtered = fingerprints.filter(fp => !search || [fp.asset_display_name, fp.asset_key, fp.service_key, fingerprintText(fp), JSON.stringify(fp.fingerprint || {})].join(' ').toLowerCase().includes(search.toLowerCase()));
    const { page, setPage, total, pageItems } = usePage(filtered, pageSize);
    return e('div', null,
      e(SectionTitle, null, 'Fingerprints'),
      e(Input, { label:'Buscar fingerprint', value:search, onChange:v=>{ setSearch(v); setPage(1); }, placeholder:'nginx, postgres, tcp:80', style:{ marginBottom:10 } }),
      e('table', null,
        e('thead', null, e('tr', null,
          e('th', null, 'Ativo'),
          e('th', null, 'Serviço'),
          e('th', null, 'Confiança'),
          e('th', null, 'Observado'),
          e('th', null, 'Fingerprint')
        )),
        e('tbody', null,
          pageItems.length === 0 ? e('tr', null, e('td', { colSpan:5, style:{textAlign:'center',color:'#8b949e',padding:'2rem'} }, 'Nenhum fingerprint encontrado.')) : null,
          pageItems.map(fp => e('tr', { key:fp.id },
            e('td', null,
              e('div', null, fp.asset_display_name || fp.asset_name || shortId(fp.asset_id)),
              fp.asset_key ? e('code', { style:{ color:'#64748b', fontSize:'.72rem' } }, fp.asset_key) : null
            ),
            e('td', null, fp.service_key || '—'),
            e('td', null, fmtPct(fp.confidence)),
            e('td', null, fmtDt(fp.observed_at)),
            e('td', null, e('span', { title:JSON.stringify(fp.fingerprint || {}).slice(0, 500), style:{ fontSize:'.78rem', color:'#8b949e' } }, fingerprintText(fp)))
          ))
        )
      ),
      e(Pager, { page, total, count:filtered.length, setPage, pageSize, setPageSize })
    );
  }

  // ── App principal ────────────────────────────────────────────────────────────
  function App() {
    const [tab, setTab] = React.useState('discovery');
    const [data, setData] = React.useState({ assets:[], services:[], runs:[], findings:[], fingerprints:[], topology:[], policies:[], targets:[] });
    const [scopeOptions, setScopeOptions] = React.useState({ tenants:[], sites:[], edges:[] });
    const [scope, setScope] = React.useState({ tenant:'default', site:'default-site', edge:'central' });
    const [token, setToken] = React.useState(() => sessionStorage.getItem('observe_token') || '');
    const [loadError, setLoadError] = React.useState('');
    const [scanning, setScanning] = React.useState(false);
	    const [scanMsg, setScanMsg] = React.useState(null);
	    const [progress, setProgress] = React.useState(null);
	    const pollRef = React.useRef(null);
	    const authHeaders = token ? {'x-internal-token': token} : {};

	    async function load() {
	      const q = scopeQuery(scope);
	      try {
	        const [s, scopeSets] = await Promise.all([
	          fetch('/observe/discovery/data/summary'+q, { headers: authHeaders }).then(async x=>{ if(!x.ok) throw new Error(x.status === 401 ? 'Token interno necessário para carregar dados de discovery.' : 'Falha ao carregar resumo: HTTP '+x.status); return x.json(); }),
	          fetch('/observe/discovery/data/scopes', { headers: authHeaders }).then(async x=>{ if(!x.ok) throw new Error(x.status === 401 ? 'Token interno necessário para carregar escopos de discovery.' : 'Falha ao carregar escopos: HTTP '+x.status); return x.json(); }),
	        ]);
	        setLoadError('');
        setData({ assets:s.assets||[], services:s.services||[], runs:s.runs||[], findings:s.findings||[], fingerprints:s.fingerprints||[], topology:s.topology||[], policies:s.policies||[], targets:s.targets||[] });
        setScopeOptions({
          tenants: Array.isArray(scopeSets.tenants) ? scopeSets.tenants : [],
          sites: Array.isArray(scopeSets.sites) ? scopeSets.sites : [],
          edges: Array.isArray(scopeSets.edges) ? scopeSets.edges : [],
        });
      } catch(err) { setLoadError(err.message || 'Falha ao carregar dados de discovery.'); }
    }

    async function pollProgress() {
      try {
        const r = await fetch(API+'/progress', { headers:token?{'x-internal-token':token}:{} });
        if (!r.ok) return;
        const p = await r.json();
        setProgress(p.active===false && !p.summary ? null : p);
        if (p.stage === 'done' || !p.active) {
          clearInterval(pollRef.current); pollRef.current = null;
          setScanning(false); setTimeout(load, 800);
        }
      } catch(_) {}
    }

    async function scanNow() {
      if (scanning) return;
      sessionStorage.setItem('observe_token', token);
      setScanning(true); setProgress(null); setScanMsg(null);
      try {
        const r = await fetch(API+'/scan', { method:'POST',
          headers:{ 'Content-Type':'application/json', ...(token?{'x-internal-token':token}:{}) },
          body: JSON.stringify({ profile:'safe', trigger:'ui', ...apiScope(scope) })
        });
        if (!r.ok) {
          const d = await r.json().catch(()=>({}));
          setScanMsg({ text:'Falha: '+(d.error||'HTTP '+r.status), type:'err' });
          setScanning(false); return;
        }
        if (!pollRef.current) pollRef.current = setInterval(pollProgress, 2000);
        pollProgress();
      } catch(err) {
        setScanMsg({ text:'Erro de comunicação com o serviço.', type:'err' });
        setScanning(false);
      }
    }

	    React.useEffect(() => {
	      load(); pollProgress();
	      return () => { if (pollRef.current) clearInterval(pollRef.current); };
    }, [scope.tenant, scope.site, scope.edge, token]);

    const TABS = [
      { id:'discovery', label:'Descoberta' },
      { id:'policies',  label:'Políticas' },
      { id:'targets',   label:'Targets' },
      { id:'assets',    label:'Ativos' },
      { id:'services',  label:'Serviços' },
      { id:'runs',      label:'Execuções' },
      { id:'findings',  label:'Achados' },
      { id:'topology',  label:'Topologia' },
      { id:'fingerprints', label:'Fingerprints' },
    ];

	    return e('div', { className:'wrap' },
	      loadError ? e('div', { className:'scan-feedback err' }, loadError) : null,
	      e('div', { style:{ display:'flex', gap:0, borderBottom:'1px solid #30363d', marginBottom:16, flexWrap:'wrap' } },
        TABS.map(t => e('button', { key:t.id,
          onClick:()=>setTab(t.id),
          style:{
            background:'transparent', border:'none', borderBottom: tab===t.id?'2px solid #CC1212':'2px solid transparent',
            color: tab===t.id?'#c9d1d9':'#8b949e', padding:'.6rem 1.1rem', cursor:'pointer',
            fontSize:'.85rem', fontWeight: tab===t.id?600:400, fontFamily:'inherit'
          }
        }, t.label))
      ),
      tab === 'discovery' ? e(DiscoveryTab, { data, scanning, progress, scanMsg, onScan:scanNow, token, setToken, scope, setScope, scopeOptions }) : null,
      tab === 'policies'  ? e(PoliciesTab,  { data, token, scope, reload:load }) : null,
      tab === 'targets'   ? e(TargetsTab,   { data, token, scope, reload:load }) : null,
      tab === 'assets'    ? e(AssetsTab,    { data, token, scope, reload:load }) : null,
      tab === 'services'  ? e(ServicesTab,  { data }) : null,
      tab === 'runs'      ? e(RunsTab,      { data }) : null,
      tab === 'findings'  ? e(FindingsTab,  { data }) : null,
      tab === 'topology'  ? e(TopologyTab,  { data }) : null,
      tab === 'fingerprints' ? e(FingerprintsTab, { data }) : null
    );
  }

  ReactDOM.createRoot(document.getElementById('app')).render(React.createElement(App));
  </script>
</body>
</html>`;
