'use strict';

const express = require('express');
const helmet = require('helmet');
const cors = require('cors');
const rateLimit = require('express-rate-limit');
const { Pool } = require('pg');
const Redis = require('ioredis');
const client = require('prom-client');
const { v4: uuidv4 } = require('uuid');
const icinga = require('./icinga');

// ─── Config ──────────────────────────────────────────────────────────────────
const PORT = parseInt(process.env.PORT || '3000', 10);
const BASE = process.env.BASE_PATH || '/observe/api';
const LOG_LEVEL = process.env.LOG_LEVEL || 'info';
const INTERNAL_TOKEN = process.env.INTERNAL_TOKEN || '';

// ─── Logger ───────────────────────────────────────────────────────────────────
function log(level, msg, extra = {}) {
  if (level === 'debug' && LOG_LEVEL !== 'debug') return;
  process.stdout.write(JSON.stringify({ level, service: 'r-observe-api', msg, ts: new Date().toISOString(), ...extra }) + '\n');
}

// ─── Metrics ─────────────────────────────────────────────────────────────────
const register = new client.Registry();
client.collectDefaultMetrics({ register });
const httpRequestsTotal = new client.Counter({
  name: 'r_observe_api_http_requests_total',
  help: 'Total HTTP requests',
  labelNames: ['method', 'route', 'status'],
  registers: [register],
});
const eventsReceivedTotal = new client.Counter({
  name: 'r_observe_events_received_total',
  help: 'Total events received',
  labelNames: ['source'],
  registers: [register],
});

// ─── Database ────────────────────────────────────────────────────────────────
const db = new Pool({
  host: process.env.DB_HOST,
  port: parseInt(process.env.DB_PORT || '5432', 10),
  database: process.env.DB_NAME,
  user: process.env.DB_USER,
  password: process.env.DB_PASSWORD,
  max: 10,
  idleTimeoutMillis: 30000,
  connectionTimeoutMillis: 5000,
});

db.on('error', (err) => log('error', 'Database pool error', { err: err.message }));

// ─── Redis ────────────────────────────────────────────────────────────────────
const redis = process.env.REDIS_URL
  ? new Redis(process.env.REDIS_URL, { lazyConnect: true, maxRetriesPerRequest: 3, enableReadyCheck: true })
  : null;

if (redis) {
  redis.on('error', (err) => log('error', 'Redis error', { err: err.message }));
  redis.connect().catch((e) => log('warn', 'Redis initial connect failed', { err: e.message }));
}

// ─── Express ──────────────────────────────────────────────────────────────────
const app = express();
app.set('trust proxy', 1);
app.use(helmet({ contentSecurityPolicy: false }));
app.use(cors({
  origin: process.env.CORS_ORIGINS ? process.env.CORS_ORIGINS.split(',').map(s => s.trim()) : false,
  methods: ['GET', 'POST'],
  allowedHeaders: ['Content-Type', 'Authorization', 'x-internal-token'],
}));
app.use(express.json({ limit: '512kb' }));

// ─── Middleware: request logging + metrics ────────────────────────────────────
app.use((req, res, next) => {
  res.on('finish', () => {
    httpRequestsTotal.inc({ method: req.method, route: req.path, status: String(res.statusCode) });
    if (LOG_LEVEL === 'debug') log('debug', 'request', { method: req.method, path: req.path, status: res.statusCode });
  });
  next();
});

// ─── Auth middleware ──────────────────────────────────────────────────────────
const requireAuth = (req, res, next) => {
  if (!INTERNAL_TOKEN) return next(); // sem token configurado = dev mode
  const provided = req.headers['x-internal-token'] ||
    (req.headers['authorization'] || '').replace(/^Bearer\s+/i, '');
  if (provided !== INTERNAL_TOKEN) {
    return res.status(401).json({ error: 'Unauthorized' });
  }
  next();
};

// ─── Rate limiters ────────────────────────────────────────────────────────────
const eventLimiter = rateLimit({ windowMs: 60_000, max: 100, standardHeaders: true, legacyHeaders: false });
const apiLimiter  = rateLimit({ windowMs: 60_000, max: 300, standardHeaders: true, legacyHeaders: false });

// ─── Helper: proxy para o serviço AI ─────────────────────────────────────────
async function proxyToAI(path, options = {}) {
  const aiUrl = process.env.AI_SERVICE_URL || 'http://observe-ai:3000';
  const timeout = parseInt(process.env.AI_TIMEOUT_MS || '30000', 10);
  const ctrl = new AbortController();
  const timer = setTimeout(() => ctrl.abort(), timeout);
  try {
    const resp = await fetch(`${aiUrl}${path}`, {
      ...options,
      headers: { 'Content-Type': 'application/json', 'x-internal-token': INTERNAL_TOKEN, ...(options.headers || {}) },
      signal: ctrl.signal,
    });
    return { ok: true, status: resp.status, data: await resp.json() };
  } catch (e) {
    if (e.name === 'AbortError') return { ok: false, status: 504, data: { error: 'AI service timeout' } };
    return { ok: false, status: 502, data: { error: 'AI service unavailable', detail: e.message } };
  } finally {
    clearTimeout(timer);
  }
}

// ─── Routes ───────────────────────────────────────────────────────────────────

// Health
app.get(`${BASE}/health`, (_req, res) => {
  res.json({ status: 'ok', service: 'r-observe-api', version: '0.1.0' });
});

// Prometheus metrics
app.get(`${BASE}/metrics`, async (_req, res) => {
  res.set('Content-Type', register.contentType);
  res.end(await register.metrics());
});

// Status — verifica conectividade com DB e Redis
app.get(`${BASE}/status`, requireAuth, async (_req, res) => {
  const components = { api: 'ok', db: 'unknown', redis: 'unknown' };
  try { await db.query('SELECT 1'); components.db = 'ok'; }
  catch (e) { components.db = 'error'; log('warn', 'DB status check failed', { err: e.message }); }
  try {
    if (redis) { await redis.ping(); components.redis = 'ok'; }
    else { components.redis = 'disabled'; }
  } catch (e) { components.redis = 'error'; log('warn', 'Redis status check failed', { err: e.message }); }
  const healthy = components.db === 'ok';
  res.status(healthy ? 200 : 503).json({ status: healthy ? 'ok' : 'degraded', components });
});

// Hosts — listagem
app.get(`${BASE}/hosts`, requireAuth, apiLimiter, async (_req, res) => {
  try {
    const r = await db.query('SELECT * FROM observe_hosts ORDER BY updated_at DESC LIMIT 200');
    res.json({ hosts: r.rows, total: r.rowCount });
  } catch (e) { res.status(500).json({ error: e.message }); }
});

// Hosts — host individual
app.get(`${BASE}/hosts/:name`, requireAuth, async (req, res) => {
  try {
    const r = await db.query('SELECT * FROM observe_hosts WHERE name = $1', [req.params.name]);
    if (!r.rowCount) return res.status(404).json({ error: 'Host não encontrado' });
    res.json({ host: r.rows[0] });
  } catch (e) { res.status(500).json({ error: e.message }); }
});

// Hosts — criação/upsert manual (registra em DB + Icinga2)
app.post(`${BASE}/hosts`, requireAuth, async (req, res) => {
  const { name, address, display_name, vars } = req.body;
  if (!name || !address) return res.status(400).json({ error: '"name" e "address" são obrigatórios' });
  if (!/^[a-zA-Z0-9][a-zA-Z0-9_.-]{0,127}$/.test(name))
    return res.status(400).json({ error: 'name inválido (use apenas letras, números, hífens, pontos)' });
  try {
    // Upsert no banco
    const r = await db.query(
      `INSERT INTO observe_hosts (id, name, address, metadata, updated_at)
       VALUES ($1, $2, $3, $4, NOW())
       ON CONFLICT (name)
       DO UPDATE SET address = EXCLUDED.address, metadata = EXCLUDED.metadata, updated_at = NOW()
       RETURNING *`,
      [uuidv4(), name, address, JSON.stringify({ source: 'manual', display_name, vars })]
    );
    // Sincroniza com Icinga2 (não-fatal: falha no Icinga não impede resposta)
    let icingaResult = { ok: false, existed: false, error: 'unreachable' };
    try {
      icingaResult = await icinga.registerHost(name, address, display_name || name, vars || {});
    } catch (e) {
      icingaResult.error = e.message;
    }
    log('info', 'Host registered', { name, address, icinga_ok: icingaResult.ok });
    res.status(201).json({ host: r.rows[0], icinga: { ok: icingaResult.ok, existed: icingaResult.existed, error: icingaResult.error } });
  } catch (e) { res.status(500).json({ error: e.message }); }
});

// Hosts — remoção (remove de DB + Icinga2)
app.delete(`${BASE}/hosts/:name`, requireAuth, async (req, res) => {
  const { name } = req.params;
  try {
    const r = await db.query('DELETE FROM observe_hosts WHERE name = $1 RETURNING id', [name]);
    if (!r.rowCount) return res.status(404).json({ error: 'Host não encontrado' });
    let icingaResult = { ok: false, error: 'unreachable' };
    try {
      icingaResult = await icinga.removeHost(name);
    } catch (e) {
      icingaResult.error = e.message;
    }
    log('info', 'Host removed', { name, icinga_ok: icingaResult.ok });
    res.json({ removed: true, name, icinga: { ok: icingaResult.ok, error: icingaResult.error } });
  } catch (e) { res.status(500).json({ error: e.message }); }
});

// Hosts — dispara scan de rede (enfileira para o agente/script externo processar)
app.post(`${BASE}/hosts/scan`, requireAuth, async (req, res) => {
  const { subnet } = req.body;
  const task = JSON.stringify({ type: 'scan:requested', subnet: subnet || null, requested_at: new Date().toISOString() });
  try {
    if (redis) await redis.rpush('observe:scan:network', task);
    log('info', 'Network scan requested', { subnet: subnet || 'auto' });
    res.status(202).json({
      accepted: true,
      subnet:   subnet || 'auto-detect',
      message:  'Scan enfileirado. Execute: npm run observe:discover -- --mode r-observe' + (subnet ? ` --subnet ${subnet}` : ''),
    });
  } catch (e) { res.status(500).json({ error: e.message }); }
});

// Hosts — lista hosts do Icinga2 diretamente
app.get(`${BASE}/hosts/icinga/list`, requireAuth, async (_req, res) => {
  try {
    const hosts = await icinga.listHosts();
    res.json({ hosts, total: hosts.length });
  } catch (e) { res.status(500).json({ error: e.message }); }
});

// Services
app.get(`${BASE}/services`, requireAuth, apiLimiter, async (_req, res) => {
  try {
    const r = await db.query('SELECT * FROM observe_services ORDER BY updated_at DESC LIMIT 200');
    res.json({ services: r.rows, total: r.rowCount });
  } catch (e) { res.status(500).json({ error: e.message }); }
});

// Incidents
app.get(`${BASE}/incidents`, requireAuth, apiLimiter, async (_req, res) => {
  try {
    const r = await db.query('SELECT * FROM observe_incidents ORDER BY created_at DESC LIMIT 50');
    res.json({ incidents: r.rows, total: r.rowCount });
  } catch (e) { res.status(500).json({ error: e.message }); }
});

app.get(`${BASE}/incidents/:id`, requireAuth, async (req, res) => {
  try {
    const r = await db.query('SELECT * FROM observe_incidents WHERE id = $1', [req.params.id]);
    if (!r.rowCount) return res.status(404).json({ error: 'Not found' });
    const incident = r.rows[0];
    const events = await db.query(
      'SELECT * FROM observe_incident_events WHERE incident_id = $1 ORDER BY occurred_at ASC',
      [req.params.id]
    );
    res.json({ incident, timeline: events.rows });
  } catch (e) { res.status(500).json({ error: e.message }); }
});

// Events (generic)
app.post(`${BASE}/events`, requireAuth, eventLimiter, async (req, res) => {
  const event = req.body;
  if (!event || !event.type) return res.status(400).json({ error: 'event.type is required' });
  try {
    eventsReceivedTotal.inc({ source: event.source || 'generic' });
    if (redis) await redis.rpush('observe:events', JSON.stringify({ ...event, receivedAt: new Date().toISOString() }));
    res.status(202).json({ accepted: true, id: uuidv4() });
  } catch (e) { res.status(500).json({ error: e.message }); }
});

// Icinga events
app.post(`${BASE}/icinga/events`, requireAuth, eventLimiter, async (req, res) => {
  const event = req.body;
  if (!event) return res.status(400).json({ error: 'Body obrigatório' });
  try {
    eventsReceivedTotal.inc({ source: 'icinga' });
    const payload = JSON.stringify({ ...event, source: 'icinga', receivedAt: new Date().toISOString() });
    if (redis) await redis.rpush('observe:events:icinga', payload);
    res.status(202).json({ accepted: true, id: uuidv4() });
  } catch (e) { res.status(500).json({ error: e.message }); }
});

// AI explain (proxy para observe-ai)
app.post(`${BASE}/ai/explain`, requireAuth, async (req, res) => {
  if (process.env.AI_ENABLED !== 'true') {
    return res.status(503).json({ error: 'AI service disabled' });
  }
  const { incident, context } = req.body;
  if (!incident) return res.status(400).json({ error: 'incident é obrigatório' });
  const result = await proxyToAI('/ai/explain', { method: 'POST', body: JSON.stringify({ incident, context }) });
  res.status(result.status).json(result.data);
});

// AI models — lista modelos do provider (real ou fallback estático)
app.get(`${BASE}/ai/models`, requireAuth, async (req, res) => {
  const qs = req.query.provider ? `?provider=${encodeURIComponent(req.query.provider)}` : '';
  const result = await proxyToAI(`/ai/models${qs}`);
  res.status(result.status).json(result.data);
});

// AI settings — lê configuração atual do provider
app.get(`${BASE}/ai/settings`, requireAuth, async (_req, res) => {
  const result = await proxyToAI('/ai/settings');
  res.status(result.status).json(result.data);
});

// AI settings — atualiza provider/model/api_key em runtime
app.post(`${BASE}/ai/settings`, requireAuth, async (req, res) => {
  const result = await proxyToAI('/ai/settings', { method: 'POST', body: JSON.stringify(req.body) });
  res.status(result.status).json(result.data);
});

// Remediation request (manual)
app.post(`${BASE}/remediation/request`, requireAuth, async (req, res) => {
  const { incident_id, action, params, reason } = req.body;
  if (!incident_id || !action) return res.status(400).json({ error: 'incident_id e action são obrigatórios' });
  try {
    const r = await db.query(
      `INSERT INTO observe_remediations
         (incident_id, action, params, reason, status, confidence_score, requested_at, updated_at)
       VALUES ($1, $2, $3, $4, 'pending_approval', NULL, NOW(), NOW()) RETURNING *`,
      [incident_id, action, JSON.stringify(params || {}), reason || '']
    );
    log('info', 'Remediation requested', { incident_id, action });
    res.status(202).json({ remediation: r.rows[0] });
  } catch (e) { res.status(500).json({ error: e.message }); }
});

// Lista remediações pendentes de aprovação
app.get(`${BASE}/remediation/pending`, requireAuth, apiLimiter, async (_req, res) => {
  try {
    const r = await db.query(`
      SELECT rem.*, inc.title AS incident_title, inc.severity AS incident_severity
      FROM observe_remediations rem
      LEFT JOIN observe_incidents inc ON rem.incident_id = inc.id
      WHERE rem.status = 'pending_approval'
      ORDER BY rem.requested_at DESC
      LIMIT 50
    `);
    res.json({ remediations: r.rows, total: r.rowCount });
  } catch (e) { res.status(500).json({ error: e.message }); }
});

// Aprova e enfileira remediação para execução pelo worker
app.post(`${BASE}/remediation/:id/approve`, requireAuth, async (req, res) => {
  const { id } = req.params;
  const { approved_by } = req.body;
  try {
    const r = await db.query(
      `UPDATE observe_remediations
       SET status = 'approved', approved_by = $1, updated_at = NOW()
       WHERE id = $2 AND status = 'pending_approval'
       RETURNING *`,
      [approved_by || 'api', id]
    );
    if (!r.rowCount) return res.status(404).json({ error: 'Remediação não encontrada ou já processada' });
    const rem = r.rows[0];
    if (redis) {
      await redis.rpush('observe:remediation:execute', JSON.stringify({
        remediation_id: rem.id,
        incident_id:    rem.incident_id,
        action:         rem.action,
        params:         rem.params,
      }));
    }
    log('info', 'Remediation approved', { id, action: rem.action, approved_by });
    res.json({ remediation: rem, queued: !!redis });
  } catch (e) { res.status(500).json({ error: e.message }); }
});

// Rejeita remediação
app.post(`${BASE}/remediation/:id/reject`, requireAuth, async (req, res) => {
  const { id } = req.params;
  const { rejected_by, reason } = req.body;
  try {
    const r = await db.query(
      `UPDATE observe_remediations
       SET status = 'rejected', approved_by = $1,
           execution_output = $2, updated_at = NOW()
       WHERE id = $3 AND status = 'pending_approval'
       RETURNING *`,
      [rejected_by || 'api', reason ? `Rejeitado: ${reason}` : 'Rejeitado manualmente', id]
    );
    if (!r.rowCount) return res.status(404).json({ error: 'Remediação não encontrada ou já processada' });
    log('info', 'Remediation rejected', { id, rejected_by });
    res.json({ remediation: r.rows[0] });
  } catch (e) { res.status(500).json({ error: e.message }); }
});

// ─── UI: página de configuração do provider de IA ─────────────────────────────
app.get('/observe/settings', (_req, res) => {
  res.set('Content-Type', 'text/html; charset=utf-8');
  res.end(SETTINGS_HTML);
});

// ─── 404 handler ─────────────────────────────────────────────────────────────
app.use((_req, res) => res.status(404).json({ error: 'Not found' }));

// ─── Error handler ────────────────────────────────────────────────────────────
app.use((err, _req, res, _next) => {
  log('error', 'Unhandled error', { err: err.message });
  res.status(500).json({ error: 'Internal server error' });
});

// ─── Start ────────────────────────────────────────────────────────────────────
app.listen(PORT, '0.0.0.0', () => {
  log('info', `Listening on :${PORT}`, { basePath: BASE });
});

module.exports = app;

// ─── HTML da UI de configuração ───────────────────────────────────────────────
const SETTINGS_HTML = /* html */`<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>R-Observe · Configuração IA</title>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --bg:       #0f1117;
      --surface:  #1a1d27;
      --border:   #2a2d3a;
      --text:     #e2e8f0;
      --muted:    #64748b;
      --accent:   #6366f1;
      --accent-h: #818cf8;
      --ok:       #22c55e;
      --warn:     #f59e0b;
      --err:      #ef4444;
    }

    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      background: var(--bg);
      color: var(--text);
      font-family: 'Inter', 'Segoe UI', system-ui, sans-serif;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      align-items: center;
      padding: 2rem 1rem;
    }

    .card {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 12px;
      padding: 2rem;
      width: 100%;
      max-width: 520px;
    }

    .header {
      display: flex;
      align-items: center;
      gap: .75rem;
      margin-bottom: 1.75rem;
    }

    .logo {
      width: 36px; height: 36px;
      background: var(--accent);
      border-radius: 8px;
      display: flex; align-items: center; justify-content: center;
      font-size: 1.1rem;
    }

    h1 { font-size: 1.15rem; font-weight: 600; }
    .subtitle { font-size: .8rem; color: var(--muted); margin-top: 2px; }

    .status-bar {
      display: flex;
      align-items: center;
      gap: .5rem;
      background: var(--bg);
      border: 1px solid var(--border);
      border-radius: 8px;
      padding: .65rem 1rem;
      margin-bottom: 1.75rem;
      font-size: .82rem;
    }

    .dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
    .dot.ok   { background: var(--ok); }
    .dot.warn { background: var(--warn); }
    .dot.err  { background: var(--err); }

    .status-text  { color: var(--muted); }
    .status-value { color: var(--text); font-weight: 500; margin-left: auto; text-align: right; }

    label {
      display: block;
      font-size: .82rem;
      font-weight: 500;
      color: var(--muted);
      margin-bottom: .4rem;
      text-transform: uppercase;
      letter-spacing: .04em;
    }

    .field { margin-bottom: 1.25rem; }

    input[type="text"],
    input[type="password"] {
      width: 100%;
      background: var(--bg);
      border: 1px solid var(--border);
      border-radius: 8px;
      color: var(--text);
      font-size: .9rem;
      padding: .6rem .85rem;
      outline: none;
      transition: border-color .15s;
    }
    input:focus { border-color: var(--accent); }

    select {
      width: 100%;
      background: var(--bg);
      border: 1px solid var(--border);
      border-radius: 8px;
      color: var(--text);
      font-size: .9rem;
      padding: .6rem .85rem;
      outline: none;
      cursor: pointer;
      appearance: none;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%2364748b' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: right .85rem center;
      padding-right: 2.2rem;
      transition: border-color .15s;
    }
    select:focus { border-color: var(--accent); }
    option { background: #1a1d27; color: var(--text); }

    /* grid 2×2 para 4 providers */
    .providers {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: .6rem;
      margin-bottom: 1.25rem;
    }

    .provider-btn {
      background: var(--bg);
      border: 1px solid var(--border);
      border-radius: 8px;
      color: var(--muted);
      cursor: pointer;
      font-size: .85rem;
      font-weight: 500;
      padding: .65rem .5rem;
      text-align: center;
      transition: border-color .15s, color .15s, background .15s;
    }
    .provider-btn:hover { border-color: var(--accent); color: var(--text); }
    .provider-btn.active {
      border-color: var(--accent);
      background: color-mix(in srgb, var(--accent) 15%, transparent);
      color: var(--accent-h);
    }

    .provider-icon { font-size: 1.2rem; display: block; margin-bottom: .25rem; }

    #api-key-section { display: none; }
    #api-key-section.visible { display: block; }

    .key-row { display: flex; gap: .5rem; }
    .key-row input { flex: 1; }

    .toggle-btn {
      background: var(--bg);
      border: 1px solid var(--border);
      border-radius: 8px;
      color: var(--muted);
      cursor: pointer;
      font-size: .85rem;
      padding: 0 .75rem;
      flex-shrink: 0;
      transition: color .15s;
    }
    .toggle-btn:hover { color: var(--text); }

    .hint { font-size: .76rem; color: var(--muted); margin-top: .4rem; }

    .effective-model {
      font-size: .76rem;
      color: var(--muted);
      margin-top: .4rem;
    }
    .effective-model span { color: var(--accent-h); }

    .actions { display: flex; gap: .75rem; margin-top: 1.5rem; }

    .btn {
      border: none;
      border-radius: 8px;
      cursor: pointer;
      font-size: .88rem;
      font-weight: 600;
      padding: .65rem 1.25rem;
      transition: opacity .15s, filter .15s;
    }
    .btn:disabled { opacity: .45; cursor: not-allowed; }
    .btn-primary { background: var(--accent); color: #fff; flex: 1; }
    .btn-primary:hover:not(:disabled) { filter: brightness(1.1); }
    .btn-secondary {
      background: var(--bg);
      border: 1px solid var(--border);
      color: var(--muted);
    }
    .btn-secondary:hover:not(:disabled) { color: var(--text); border-color: var(--muted); }

    #feedback {
      margin-top: 1rem;
      font-size: .84rem;
      border-radius: 8px;
      padding: .6rem .85rem;
      display: none;
    }
    #feedback.ok  { display: block; background: color-mix(in srgb, var(--ok)  15%, transparent); color: var(--ok);  border: 1px solid color-mix(in srgb, var(--ok)  40%, transparent); }
    #feedback.err { display: block; background: color-mix(in srgb, var(--err) 15%, transparent); color: var(--err); border: 1px solid color-mix(in srgb, var(--err) 40%, transparent); }

    .token-section { border-top: 1px solid var(--border); margin-top: 1.75rem; padding-top: 1.5rem; }
  </style>
</head>
<body>
  <div class="card">
    <div class="header">
      <div class="logo">⚡</div>
      <div>
        <h1>R-Observe · Configuração IA</h1>
        <div class="subtitle">Altere o provider de IA sem reiniciar o serviço</div>
      </div>
    </div>

    <div class="status-bar">
      <span class="dot warn" id="status-dot"></span>
      <span class="status-text" id="status-text">Carregando…</span>
      <span class="status-value" id="status-value"></span>
    </div>

    <div class="field">
      <label>Provider</label>
      <div class="providers">
        <button class="provider-btn" data-provider="openai"     onclick="selectProvider('openai')">
          <span class="provider-icon">🤖</span>OpenAI
        </button>
        <button class="provider-btn" data-provider="anthropic"  onclick="selectProvider('anthropic')">
          <span class="provider-icon">🟠</span>Anthropic
        </button>
        <button class="provider-btn" data-provider="deepseek"   onclick="selectProvider('deepseek')">
          <span class="provider-icon">🔍</span>DeepSeek
        </button>
        <button class="provider-btn" data-provider="mock"       onclick="selectProvider('mock')">
          <span class="provider-icon">🧪</span>Mock
        </button>
      </div>
    </div>

    <div class="field" id="model-section" style="display:none">
      <label>Modelo</label>
      <select id="model" onchange="onModelChange()"></select>
      <div class="effective-model" id="effective-model"></div>
    </div>

    <div id="api-key-section" class="field">
      <label>API Key</label>
      <div class="key-row">
        <input type="password" id="api-key" placeholder="Cole a chave aqui" autocomplete="new-password">
        <button class="toggle-btn" onclick="toggleKey()">👁</button>
      </div>
      <div class="hint" id="key-hint"></div>
    </div>

    <div class="token-section">
      <div class="field">
        <label>Token de Autenticação (OBSERVE_INTERNAL_TOKEN)</label>
        <div class="key-row">
          <input type="password" id="token" placeholder="Cole o token interno aqui" autocomplete="off">
          <button class="toggle-btn" onclick="toggleToken()">👁</button>
        </div>
      </div>
    </div>

    <div class="actions">
      <button class="btn btn-secondary" onclick="loadStatus()">↺ Atualizar</button>
      <button class="btn btn-primary" id="save-btn" onclick="saveSettings()" disabled>Salvar configuração</button>
    </div>

    <div id="feedback"></div>
  </div>

  <script>
    const API_BASE = '/observe/api';
    let selectedProvider = 'mock';

    // Fallback estático — usado enquanto a API carrega ou quando não há chave
    const AUTO_LABELS = {
      openai:    'gpt-4o-mini',
      anthropic: 'claude-haiku-4-5-20251001',
      deepseek:  'deepseek-chat',
    };

    function populateSelect(sel, models, autoLabel, currentModel) {
      sel.innerHTML = '';
      const auto = document.createElement('option');
      auto.value = 'auto';
      auto.textContent = 'Automático' + (autoLabel ? '  (' + autoLabel + ')' : '');
      sel.appendChild(auto);
      for (const id of models) {
        const opt = document.createElement('option');
        opt.value = id;
        opt.textContent = id;
        sel.appendChild(opt);
      }
      sel.value = currentModel || 'auto';
      if (!sel.value) sel.selectedIndex = 0;
    }

    async function loadModels(provider, currentModel) {
      const sel = document.getElementById('model');
      sel.innerHTML = '<option value="auto">Carregando modelos…</option>';
      sel.disabled = true;
      try {
        const resp = await fetch(API_BASE + '/ai/models?provider=' + provider, { headers: getHeaders() });
        if (resp.ok) {
          const d = await resp.json();
          populateSelect(sel, d.models, d.auto, currentModel);
          const src = d.source === 'api' ? '  ·  lista real da API' : '  ·  lista estática (sem chave)';
          document.getElementById('effective-model').innerHTML =
            'Modelo efetivo: <span>' + (currentModel && currentModel !== 'auto' ? currentModel : (d.auto || '')) + '</span>' + src;
        } else {
          populateSelect(sel, [], AUTO_LABELS[provider], currentModel);
        }
      } catch {
        populateSelect(sel, [], AUTO_LABELS[provider], currentModel);
      } finally {
        sel.disabled = false;
      }
    }

    function updateEffectiveModel(rawModel, provider) {
      const el = document.getElementById('effective-model');
      const eff = (!rawModel || rawModel === 'auto') ? (AUTO_LABELS[provider] || '') : rawModel;
      el.innerHTML = eff ? 'Modelo efetivo: <span>' + eff + '</span>' : '';
    }

    function onModelChange() {
      const sel = document.getElementById('model');
      sel.disabled = false;
      updateEffectiveModel(sel.value, selectedProvider);
    }

    function selectProvider(p, currentModel) {
      selectedProvider = p;
      document.querySelectorAll('.provider-btn').forEach(b =>
        b.classList.toggle('active', b.dataset.provider === p)
      );

      const keySection   = document.getElementById('api-key-section');
      const modelSection = document.getElementById('model-section');

      if (p === 'mock') {
        keySection.classList.remove('visible');
        modelSection.style.display = 'none';
      } else {
        keySection.classList.add('visible');
        modelSection.style.display = '';
        document.getElementById('key-hint').textContent =
          'Deixe em branco para manter a chave já configurada.';
        loadModels(p, currentModel);
      }
    }

    function toggleKey()   { const e = document.getElementById('api-key'); e.type = e.type === 'password' ? 'text' : 'password'; }
    function toggleToken() { const e = document.getElementById('token');   e.type = e.type === 'password' ? 'text' : 'password'; }

    // Token guardado em variável JS — não depende do DOM (browsers limpam
    // input[type=password] no refresh antes do script conseguir ler o valor).
    let _authToken = sessionStorage.getItem('observe_token') || '';

    const tokenEl = document.getElementById('token');
    if (_authToken) tokenEl.value = _authToken;

    tokenEl.addEventListener('input', () => {
      _authToken = tokenEl.value.trim();
      if (_authToken) sessionStorage.setItem('observe_token', _authToken);
      else            sessionStorage.removeItem('observe_token');
    });

    function getHeaders() {
      const h = { 'Content-Type': 'application/json' };
      if (_authToken) h['x-internal-token'] = _authToken;
      return h;
    }

    let _statusReady = false; // true após primeiro loadStatus bem-sucedido

    function setStatus(cls, text, value = '') {
      document.getElementById('status-dot').className  = 'dot ' + cls;
      document.getElementById('status-text').textContent = text;
      document.getElementById('status-value').textContent = value;
      // Habilita salvar quando o serviço responde (_statusReady).
      // Erros reais (401, rede) deixam _statusReady=false e já desabilitam o botão.
      // Não bloquear por cls==='err': usuário precisa salvar quando não há chave.
      document.getElementById('save-btn').disabled = !_statusReady;
    }

    function showFeedback(msg, isOk) {
      const el = document.getElementById('feedback');
      el.textContent = msg;
      el.className = isOk ? 'ok' : 'err';
    }

    async function loadStatus() {
      _statusReady = false;
      setStatus('warn', 'Consultando serviço de IA…', '');
      try {
        const resp = await fetch(API_BASE + '/ai/settings', { headers: getHeaders() });
        if (resp.status === 401) { setStatus('err', 'Cole o OBSERVE_INTERNAL_TOKEN abaixo e clique em ↺', ''); return; }
        if (!resp.ok)            { setStatus('err', 'Serviço AI indisponível.', resp.status); return; }

        const d = await resp.json();
        const hasKey = d.has_api_key;
        const effModel = d.effective_model || d.model || '';

        const label = d.provider === 'mock'
          ? 'Mock ativo — sem custo, sem IA real'
          : hasKey
            ? d.provider + ' · ' + effModel
            : d.provider + ' — chave não configurada';

        _statusReady = true;
        setStatus(d.provider === 'mock' ? 'warn' : hasKey ? 'ok' : 'err', 'Provider atual:', label);

        selectProvider(d.provider, d.model || 'auto');
        updateEffectiveModel(d.model, d.provider);

        if (hasKey && d.provider !== 'mock') {
          document.getElementById('key-hint').textContent = 'Chave já configurada. Deixe em branco para mantê-la.';
        }
      } catch (e) {
        setStatus('err', 'Erro ao consultar API.', e.message);
      }
    }

    async function saveSettings() {
      const btn = document.getElementById('save-btn');
      btn.disabled = true;
      btn.textContent = 'Salvando…';

      const apiKey = document.getElementById('api-key').value.trim();
      const body = { provider: selectedProvider, model: document.getElementById('model').value || 'auto' };
      if (apiKey) body.api_key = apiKey;

      try {
        const resp = await fetch(API_BASE + '/ai/settings', {
          method: 'POST', headers: getHeaders(), body: JSON.stringify(body),
        });
        const d = await resp.json();
        if (!resp.ok) {
          showFeedback('Erro: ' + (d.error || resp.status), false);
        } else {
          const eff = d.effective_model || d.model || '';
          showFeedback('Salvo! Provider: ' + d.provider + (eff ? ' · ' + eff : ''), true);
          document.getElementById('api-key').value = '';
          await loadStatus();
        }
      } catch (e) {
        showFeedback('Falha na requisição: ' + e.message, false);
      } finally {
        btn.disabled = false;
        btn.textContent = 'Salvar configuração';
      }
    }

    loadStatus();
  </script>
</body>
</html>`;
