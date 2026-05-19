'use strict';

const path = require('path');
const express = require('express');
const helmet = require('helmet');
const rateLimit = require('express-rate-limit');
const { Pool } = require('pg');
const Redis = require('ioredis');
const client = require('prom-client');

const { log } = require('./utils/logger');
const { runDiscovery } = require('./engine/discovery-engine');
const { normalizePassiveEvent } = require('./passive/parser');
const { toPromSdGroups } = require('./exporters/prometheus-sd');
const { emitEvent } = require('./queues/events');

const PORT = parseInt(process.env.PORT || '3010', 10);
const INTERNAL_TOKEN = process.env.INTERNAL_TOKEN || '';
const SCAN_QUEUE_ENABLED = process.env.DISCOVERY_SCAN_QUEUE_ENABLED !== 'false';
const DOCKER_DISCOVERY_ENABLED = process.env.DISCOVERY_DOCKER_ENABLED === 'true';

const db = new Pool({
  host: process.env.DB_HOST,
  port: parseInt(process.env.DB_PORT || '5432', 10),
  database: process.env.DB_NAME,
  user: process.env.DB_USER,
  password: process.env.DB_PASSWORD,
  max: 10,
});

const redis = process.env.REDIS_URL ? new Redis(process.env.REDIS_URL, { lazyConnect: true, maxRetriesPerRequest: 3 }) : null;
if (redis) redis.connect().catch((e) => log('warn', 'Redis connect failed', { err: e.message }));

const register = new client.Registry();
client.collectDefaultMetrics({ register });
const runsTotal = new client.Counter({ name: 'r_observe_discovery_runs_total', help: 'Total discovery runs', labelNames: ['status'], registers: [register] });

const app = express();
app.use('/observe/discovery/data', express.static(path.join(__dirname, '../public')));
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

app.post('/api/discovery/scan', requireAuth, asyncRoute(async (req, res) => {
  const err = validateScanInput(req.body || {});
  if (err) return res.status(400).json({ error: err });
  const payload = { ...(req.body || {}), docker_discovery_enabled: DOCKER_DISCOVERY_ENABLED };

  if (SCAN_QUEUE_ENABLED && redis) {
    await redis.rpush('observe:scan:network', JSON.stringify(payload));
    await redis.publish('observe:scan:network', JSON.stringify({ event: 'scan_enqueued', ts: new Date().toISOString() }));
    return res.status(202).json({ queued: true, queue: 'observe:scan:network' });
  }

  const out = await runDiscovery({ db, redis, input: payload });
  runsTotal.inc({ status: 'completed' });
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

app.post('/api/discovery/passive/events', requireAuth, asyncRoute(async (req, res) => {
  const events = Array.isArray(req.body?.events) ? req.body.events : [req.body];
  let accepted = 0;
  for (const evt of events) {
    const parsed = normalizePassiveEvent(evt);
    if (!parsed) continue;
    await db.query(
      `INSERT INTO observe_discovery_findings
        (id, run_id, tenant_id, site_id, edge_id, finding_type, severity, source, asset_key, payload, observed_at)
       VALUES (gen_random_uuid(),NULL,$1,$2,$3,'passive_signal','info',$4,$5,$6,NOW())`,
      [evt.tenant_id || 'default', evt.site_id || 'default-site', evt.edge_id || 'central', `passive:${parsed.type}`, `${parsed.type}:${parsed.source_ip || 'unknown'}`, JSON.stringify(parsed)]
    );
    accepted++;
  }
  await emitEvent(redis, 'observe.discovery.asset_found', { passive: true, accepted });
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

app.get('/observe/discovery', (_req, res) => {
  res.set('Content-Type', 'text/html; charset=utf-8');
  res.end(DISCOVERY_UI_HTML);
});

app.get('/observe/discovery/data/summary', asyncRoute(async (req, res) => {
  const scope = parseScope(req.query);
  const [runs, assets, findings, fps, edges] = await Promise.all([
    db.query('SELECT id, status, started_at, completed_at FROM observe_discovery_runs WHERE tenant_id=$1 AND site_id=$2 AND edge_id=$3 ORDER BY started_at DESC LIMIT 50', [scope.tenant_id, scope.site_id, scope.edge_id]),
    db.query('SELECT id, asset_name, primary_ip, vendor, product, lifecycle_state, updated_at FROM observe_assets WHERE tenant_id=$1 AND site_id=$2 AND edge_id=$3 ORDER BY updated_at DESC LIMIT 200', [scope.tenant_id, scope.site_id, scope.edge_id]),
    db.query('SELECT id, finding_type, severity, source, observed_at FROM observe_discovery_findings WHERE tenant_id=$1 AND site_id=$2 AND edge_id=$3 ORDER BY observed_at DESC LIMIT 200', [scope.tenant_id, scope.site_id, scope.edge_id]),
    db.query('SELECT id, service_key, confidence, observed_at FROM observe_service_fingerprints WHERE tenant_id=$1 AND site_id=$2 AND edge_id=$3 ORDER BY observed_at DESC LIMIT 200', [scope.tenant_id, scope.site_id, scope.edge_id]),
    db.query('SELECT id, edge_type, from_asset_id, to_asset_ref, observed_at FROM observe_topology_edges WHERE tenant_id=$1 AND site_id=$2 AND edge_id=$3 ORDER BY observed_at DESC LIMIT 200', [scope.tenant_id, scope.site_id, scope.edge_id]),
  ]);
  res.json({
    scope,
    runs: runs.rows,
    assets: assets.rows,
    findings: findings.rows,
    fingerprints: fps.rows,
    topology: edges.rows,
  });
}));

app.use((_req, res) => res.status(404).json({ error: 'not_found' }));

app.use((err, _req, res, _next) => {
  log('error', 'Unhandled discovery error', { err: err.message });
  res.status(500).json({ error: 'internal_error' });
});

app.listen(PORT, '0.0.0.0', () => log('info', `Discovery API on :${PORT}`));

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
      });
    } catch (e) {
      log('warn', 'Scan queue consumer error', { err: e.message });
    }
  }
}

startScanQueueConsumer().catch((e) => log('warn', 'Scan queue startup error', { err: e.message }));

module.exports = app;

const DISCOVERY_UI_HTML = `<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Results · R-Observe Discovery</title>
  <link rel="stylesheet" href="/observe/discovery/data/discovery.css">
  <script crossorigin src="https://unpkg.com/react@18/umd/react.production.min.js"></script>
  <script crossorigin src="https://unpkg.com/react-dom@18/umd/react-dom.production.min.js"></script>
</head>
<body>
  <div class="topbar">
    <svg viewBox="0 0 28 28" xmlns="http://www.w3.org/2000/svg" width="28" height="28" style="flex-shrink:0"><rect width="28" height="28" rx="6" fill="#CC1212"/><text x="14" y="20" font-family="Arial Black,Arial,sans-serif" font-weight="900" font-size="16" fill="white" text-anchor="middle">R</text></svg>
    <span class="topbar-brand">Results · Sistemas de Informática</span>
    <span class="topbar-title">/ R-Observe Discovery</span>
  </div>
  <div id="app"></div>
  <script>
  const e = React.createElement;
  function App() {
    const [assets, setAssets] = React.useState([]);
    const [runs, setRuns] = React.useState([]);
    const [findings, setFindings] = React.useState([]);
    const [fingerprints, setFingerprints] = React.useState([]);
    const [topology, setTopology] = React.useState([]);
    const [tenant, setTenant] = React.useState('default');
    const [site, setSite] = React.useState('default-site');
    const [edge, setEdge] = React.useState('central');
    const [status, setStatus] = React.useState('all');
    const [token, setToken] = React.useState(() => sessionStorage.getItem('observe_token') || '');
    const [scanStatus, setScanStatus] = React.useState('');

    async function load() {
      const q = '?tenant_id=' + encodeURIComponent(tenant) + '&site_id=' + encodeURIComponent(site) + '&edge_id=' + encodeURIComponent(edge);
      const s = await fetch('/observe/discovery/data/summary' + q).then(x => x.json());
      setAssets(s.assets || []);
      setRuns(s.runs || []);
      setFindings(s.findings || []);
      setFingerprints(s.fingerprints || []);
      setTopology(s.topology || []);
    }

    async function scanNow() {
      sessionStorage.setItem('observe_token', token);
      setScanStatus('Executando discovery...');
      const r = await fetch('/observe/discovery/api/discovery/scan', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', ...(token ? { 'x-internal-token': token } : {}) },
        body: JSON.stringify({ profile: 'safe', trigger: 'ui', tenant_id: tenant, site_id: site, edge_id: edge }),
      });
      if (!r.ok) {
        setScanStatus('Falha ao executar discovery: HTTP ' + r.status);
        return;
      }
      setScanStatus('Discovery solicitado com sucesso');
      setTimeout(load, 1200);
    }

    React.useEffect(() => { load(); }, [tenant, site, edge]);

    const assetsFiltered = status === 'all' ? assets : assets.filter(a => a.lifecycle_state === status);
    const now = Date.now();
    const newCount = assets.filter(a => (now - new Date(a.updated_at).getTime()) < 86400000).length;
    const disappearedCount = assets.filter(a => a.lifecycle_state === 'disappeared').length;
    const changedCount = findings.filter(f => String(f.finding_type || '').includes('changed')).length;

    return e('div', { className: 'wrap' },
      e('div', { className: 'hero' },
        e('div', null,
          e('h1', null, 'Continuous Autonomous Discovery'),
          e('div', { className: 'sub' }, 'Inventario vivo, fingerprinting e topologia operacional')
        ),
        e('button', { className: 'btn', onClick: scanNow }, 'Executar discovery')
      ),
      e('div', { className: 'filters' },
        e('input', { value: tenant, onChange: (ev) => setTenant(ev.target.value), placeholder: 'tenant_id' }),
        e('input', { value: site, onChange: (ev) => setSite(ev.target.value), placeholder: 'site_id' }),
        e('input', { value: edge, onChange: (ev) => setEdge(ev.target.value), placeholder: 'edge_id' }),
        e('input', { type: 'password', value: token, onChange: (ev) => setToken(ev.target.value), placeholder: 'OBSERVE_INTERNAL_TOKEN' }),
        e('select', { value: status, onChange: (ev) => setStatus(ev.target.value) },
          e('option', { value: 'all' }, 'status: all'),
          e('option', { value: 'discovered' }, 'discovered'),
          e('option', { value: 'approved' }, 'approved'),
          e('option', { value: 'monitored' }, 'monitored'),
          e('option', { value: 'ignored' }, 'ignored'),
          e('option', { value: 'quarantined' }, 'quarantined'),
          e('option', { value: 'disappeared' }, 'disappeared')
        )
      ),
      scanStatus ? e('div', { className: 'sub' }, scanStatus) : null,
      e('div', { className: 'grid' },
        e('div', { className: 'card' }, e('div', { className: 'k' }, 'Assets descobertos'), e('div', { className: 'v' }, String(assets.length))),
        e('div', { className: 'card' }, e('div', { className: 'k' }, 'Runs'), e('div', { className: 'v' }, String(runs.length))),
        e('div', { className: 'card' }, e('div', { className: 'k' }, 'Ultimo run'), e('div', { className: 'v' }, runs[0]?.status || '-')),
        e('div', { className: 'card' }, e('div', { className: 'k' }, 'Ativos novos (24h)'), e('div', { className: 'v' }, String(newCount))),
        e('div', { className: 'card' }, e('div', { className: 'k' }, 'Ativos alterados'), e('div', { className: 'v' }, String(changedCount))),
        e('div', { className: 'card' }, e('div', { className: 'k' }, 'Ativos desaparecidos'), e('div', { className: 'v' }, String(disappearedCount)))
      ),
      e('div', { className: 'panel-title' }, 'Assets'),
      e('table', null,
        e('thead', null, e('tr', null,
          e('th', null, 'Asset'), e('th', null, 'IP'), e('th', null, 'Vendor'), e('th', null, 'Produto'), e('th', null, 'Estado')
        )),
        e('tbody', null, assetsFiltered.slice(0, 20).map((a) => e('tr', { key: a.id },
          e('td', null, a.asset_name), e('td', null, a.primary_ip || '-'), e('td', null, a.vendor || '-'), e('td', null, a.product || '-'), e('td', null, a.lifecycle_state)
        )))
      ),
      e('div', { className: 'split' },
        e('div', null,
          e('div', { className: 'panel-title' }, 'Findings'),
          e('table', null,
            e('thead', null, e('tr', null, e('th', null, 'Tipo'), e('th', null, 'Severidade'), e('th', null, 'Fonte'))),
            e('tbody', null, findings.slice(0, 10).map((f) => e('tr', { key: f.id }, e('td', null, f.finding_type), e('td', null, f.severity), e('td', null, f.source || '-'))))
          )
        ),
        e('div', null,
          e('div', { className: 'panel-title' }, 'Fingerprints'),
          e('table', null,
            e('thead', null, e('tr', null, e('th', null, 'Service'), e('th', null, 'Confidence'))),
            e('tbody', null, fingerprints.slice(0, 10).map((f) => e('tr', { key: f.id }, e('td', null, f.service_key), e('td', null, String(f.confidence)))) )
          )
        )
      ),
      e('div', { className: 'panel-title' }, 'Topology (amostra)'),
      e('table', null,
        e('thead', null, e('tr', null, e('th', null, 'Tipo'), e('th', null, 'From'), e('th', null, 'To'))),
        e('tbody', null, topology.slice(0, 10).map((t) => e('tr', { key: t.id }, e('td', null, t.edge_type), e('td', null, t.from_asset_id || '-'), e('td', null, t.to_asset_ref))))
      )
    );
  }
  ReactDOM.createRoot(document.getElementById('app')).render(React.createElement(App));
  </script>
</body>
</html>`;
