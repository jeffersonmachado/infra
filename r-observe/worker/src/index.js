'use strict';

const express    = require('express');
const { Pool }   = require('pg');
const Redis      = require('ioredis');
const { v4: uuidv4 } = require('uuid');
const remediation = require('./remediation');
const icinga      = require('./icinga');

// ─── Config ──────────────────────────────────────────────────────────────────
const PORT           = parseInt(process.env.PORT || '3000', 10);
const LOG_LEVEL      = process.env.LOG_LEVEL || 'info';
const INTERNAL_TOKEN = process.env.INTERNAL_TOKEN || '';
const AI_ENABLED     = process.env.AI_ENABLED === 'true';
const AI_SERVICE_URL = process.env.AI_SERVICE_URL || 'http://observe-ai:3000';
const QUEUE_KEYS             = ['observe:events', 'observe:events:icinga', 'observe:remediation:execute', 'observe:scan:results'];
const PROACTIVE_INTERVAL_MS  = parseInt(process.env.AI_PROACTIVE_INTERVAL_MS || '300000', 10); // 5 min
const PROACTIVE_STALE_MIN    = parseInt(process.env.AI_PROACTIVE_STALE_MIN   || '10',     10); // sem análise há 10+ min
const REMEDIATION_ENABLED = process.env.REMEDIATION_ENABLED !== 'false';

// ─── Logger ───────────────────────────────────────────────────────────────────
function log(level, msg, extra = {}) {
  if (level === 'debug' && LOG_LEVEL !== 'debug') return;
  process.stdout.write(JSON.stringify({ level, service: 'r-observe-worker', msg, ts: new Date().toISOString(), ...extra }) + '\n');
}

// ─── DB ───────────────────────────────────────────────────────────────────────
const db = new Pool({
  host: process.env.DB_HOST,
  port: parseInt(process.env.DB_PORT || '5432', 10),
  database: process.env.DB_NAME,
  user: process.env.DB_USER,
  password: process.env.DB_PASSWORD,
  max: 5,
  idleTimeoutMillis: 30000,
  connectionTimeoutMillis: 5000,
});
db.on('error', (e) => log('error', 'DB error', { err: e.message }));

// ─── Redis ────────────────────────────────────────────────────────────────────
const redis = new Redis(process.env.REDIS_URL, {
  lazyConnect: true,
  maxRetriesPerRequest: 3,
  enableReadyCheck: true,
});
redis.on('error', (e) => log('error', 'Redis error', { err: e.message }));

// ─── Heartbeat state ──────────────────────────────────────────────────────────
let lastProcessedAt = null;
let processedCount  = 0;
let errorCount      = 0;
let running         = false;

// ─── Contadores de remediação (expostos em /metrics) ─────────────────────────
const rem = { auto: 0, pending: 0, approved: 0, rejected: 0, success: 0, failed: 0 };

// ─── AI helper ────────────────────────────────────────────────────────────────
async function callAI(incident, context) {
  if (!AI_ENABLED) return null;
  try {
    const resp = await fetch(`${AI_SERVICE_URL}/ai/explain`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'x-internal-token': INTERNAL_TOKEN },
      body: JSON.stringify({ incident, context }),
      signal: AbortSignal.timeout(30_000),
    });
    if (!resp.ok) return null;
    return await resp.json();
  } catch (e) {
    log('warn', 'AI call failed', { err: e.message });
    return null;
  }
}

// ─── Contexto enriquecido para a IA ──────────────────────────────────────────
async function buildAIContext(hostId, event) {
  const ctx = {
    event_output:  event.output  || event.message || null,
    event_service: event.service || null,
    host_address:  event.address || null,
    recent_incidents:    [],
    recent_remediations: [],
  };
  try {
    const [incidents, remediations] = await Promise.all([
      db.query(
        `SELECT title, severity, status, created_at, resolved_at
         FROM observe_incidents
         WHERE host_id = $1
           AND created_at > NOW() - INTERVAL '7 days'
         ORDER BY created_at DESC
         LIMIT 5`,
        [hostId]
      ),
      db.query(
        `SELECT r.action, r.reason, r.status, r.requested_at
         FROM observe_remediations r
         JOIN observe_incidents i ON r.incident_id = i.id
         WHERE i.host_id = $1
           AND r.requested_at > NOW() - INTERVAL '7 days'
         ORDER BY r.requested_at DESC
         LIMIT 3`,
        [hostId]
      ),
    ]);
    ctx.recent_incidents    = incidents.rows;
    ctx.recent_remediations = remediations.rows;
  } catch (e) {
    log('warn', 'Failed to build AI context', { err: e.message });
  }
  return ctx;
}

// ─── Carrega catálogo dinâmico do banco ───────────────────────────────────────
async function loadCatalogFromDb() {
  try {
    const r = await db.query(
      `SELECT action, description, risk, params, enabled, auto_ok, max_severity
       FROM observe_ai_catalog WHERE enabled = true ORDER BY action`
    );
    remediation.setCatalogMeta(r.rows);
    log('info', 'Catálogo dinâmico carregado', { count: r.rowCount });
  } catch (e) {
    log('warn', 'Falha ao carregar catálogo do DB — usando embutido', { err: e.message });
  }
}

// ─── Upsert host (DB + Icinga2) ───────────────────────────────────────────────
async function upsertHost(name, address, metadata = {}) {
  const r = await db.query(
    `INSERT INTO observe_hosts (id, name, address, metadata, updated_at)
     VALUES ($1, $2, $3, $4, NOW())
     ON CONFLICT (name)
     DO UPDATE SET address = EXCLUDED.address, metadata = EXCLUDED.metadata, updated_at = NOW()
     RETURNING id, (xmax = 0) AS inserted`,
    [uuidv4(), name, address, JSON.stringify(metadata)]
  );
  const { id, inserted } = r.rows[0];

  // Registra no Icinga2 apenas hosts novos com endereço válido
  if (inserted && address) {
    icinga.registerHost(name, address, name, {}).then(res => {
      if (!res.ok) log('warn', 'Icinga2 host sync failed', { name, status: res.status });
      else         log('debug', 'Icinga2 host synced', { name, existed: res.existed });
    }).catch(e => log('warn', 'Icinga2 host sync error', { name, err: e.message }));
  }

  return id;
}

// ─── Processa resultado de scan de rede ───────────────────────────────────────
async function processScanResults(raw) {
  let payload;
  try { payload = JSON.parse(raw); } catch { return; }
  const hosts = Array.isArray(payload.hosts) ? payload.hosts : [];
  log('info', 'Processing scan results', { total: hosts.length });
  for (const h of hosts) {
    if (!h.name || !h.address) continue;
    try {
      await upsertHost(h.name, h.address, { source: 'network-scan', ports: h.ports || [], display_name: h.display_name });
    } catch (e) {
      log('warn', 'Failed to upsert scanned host', { name: h.name, err: e.message });
    }
  }
}

// ─── Process event ────────────────────────────────────────────────────────────
async function processEvent(raw) {
  let event;
  try { event = JSON.parse(raw); }
  catch (e) { log('warn', 'Invalid JSON in event', { raw }); return; }

  log('debug', 'Processing event', { type: event.type, source: event.source });

  // Eventos de recuperação fecham incidentes abertos do host
  if (event.type === 'host.recovery' || event.type === 'service.recovery') {
    const hostName = event.host || event.hostname || 'unknown';
    const hostId = await upsertHost(hostName, event.address || null, { source: event.source });
    const closed = await db.query(
      `UPDATE observe_incidents SET status = 'resolved', resolved_at = NOW(), updated_at = NOW()
       WHERE host_id = $1 AND status = 'open' RETURNING id`,
      [hostId]
    );
    if (closed.rowCount > 0) {
      log('info', 'Incidents resolved via recovery', { hostName, count: closed.rowCount });
    }
    return;
  }

  // Tipos de evento que geram incidente
  const incidentTriggers = [
    'host.down',
    'service.critical', 'service.warning',
    'container.stopped', 'container.unhealthy',
    'docker.daemon.down',
    'disk.high_usage', 'disk.full',
    'check.failed',
  ];
  if (!incidentTriggers.includes(event.type)) {
    log('debug', 'Event type does not trigger incident', { type: event.type });
    return;
  }

  const hostName = event.host || event.hostname || 'unknown';
  const hostId = await upsertHost(hostName, event.address || null, { source: event.source });

  // Verifica se já existe incidente aberto para este host/tipo
  const existing = await db.query(
    `SELECT id FROM observe_incidents
     WHERE host_id = $1 AND status = 'open' AND source_event_id = $2`,
    [hostId, event.id || null]
  );
  if (existing.rowCount > 0) {
    log('debug', 'Incident already exists, skipping', { incident_id: existing.rows[0].id });
    return;
  }

  const incidentId = uuidv4();
  const severity = event.severity || (event.type === 'host.down' ? 'critical' : 'warning');
  const title = `[${event.type.toUpperCase()}] ${hostName}${event.service ? ' / ' + event.service : ''}`;

  // Cria incidente
  await db.query(
    `INSERT INTO observe_incidents
       (id, title, severity, status, source, source_event_id, host_id, metadata, created_at, updated_at)
     VALUES ($1, $2, $3, 'open', $4, $5, $6, $7, NOW(), NOW())`,
    [incidentId, title, severity, event.source || 'unknown', event.id || null, hostId, JSON.stringify(event)]
  );

  // Registra evento na timeline
  await db.query(
    `INSERT INTO observe_incident_events (id, incident_id, event_type, source, payload, occurred_at)
     VALUES ($1, $2, 'event_received', $3, $4, NOW())`,
    [uuidv4(), incidentId, event.source || 'unknown', JSON.stringify(event)]
  );

  log('info', 'Incident created', { incident_id: incidentId, title, severity });

  // Tenta acionar IA com contexto enriquecido
  if (AI_ENABLED) {
    const aiContext = await buildAIContext(hostId, event);
    const aiResult  = await callAI({ id: incidentId, title, severity, source: event.source }, aiContext);
    if (aiResult) {
      await db.query(
        `UPDATE observe_incidents
         SET ai_summary = $1, ai_cause = $2, ai_suggestion = $3, ai_pattern = $4, updated_at = NOW()
         WHERE id = $5`,
        [aiResult.summary || null, aiResult.cause || null, aiResult.suggestion || null,
         aiResult.pattern || null, incidentId]
      );
      await db.query(
        `INSERT INTO observe_incident_events (id, incident_id, event_type, source, payload, occurred_at)
         VALUES ($1, $2, 'ai_analysis_complete', 'r-observe-ai', $3, NOW())`,
        [uuidv4(), incidentId, JSON.stringify(aiResult)]
      );
      log('info', 'AI analysis attached', { incident_id: incidentId });

      // Avalia e agenda/executa remediação com base no score de confiança
      if (REMEDIATION_ENABLED && aiResult.remediation?.action) {
        await scheduleOrExecuteRemediation(incidentId, severity, aiResult.remediation);
      }
    }
  }

  lastProcessedAt = new Date().toISOString();
  processedCount++;
}

// ─── Remediação com supervisão por score ──────────────────────────────────────
async function scheduleOrExecuteRemediation(incidentId, severity, rem) {
  const { action, params = {}, confidence_score = 0, reasoning = '' } = rem;

  if (!remediation.isActionAllowed(action)) {
    log('warn', 'Remediation action not in catalog, skipping', { action });
    return;
  }

  const autoOk = remediation.canAutoExecute(action, confidence_score, severity);
  const remId  = uuidv4();
  const status = autoOk ? 'executing' : 'pending_approval';

  await db.query(
    `INSERT INTO observe_remediations
       (id, incident_id, action, params, reason, status, confidence_score, auto_executed, requested_at, updated_at)
     VALUES ($1,$2,$3,$4,$5,$6,$7,$8,NOW(),NOW())`,
    [remId, incidentId, action, JSON.stringify(params), reasoning || null,
     status, confidence_score, autoOk]
  );

  if (!autoOk) {
    rem.pending++;
    log('info', 'Remediation pending approval', {
      incident_id: incidentId, action,
      score: confidence_score,
      threshold: remediation.AUTO_THRESHOLD,
      max_severity: remediation.MAX_AUTO_SEVERITY,
      incident_severity: severity,
    });
    return;
  }

  rem.auto++;
  log('info', 'Auto-executing remediation', { incident_id: incidentId, action, score: confidence_score });
  const result = await remediation.executeAction(action, params);
  const finalStatus = result.success ? 'executed' : 'failed';
  result.success ? rem.success++ : rem.failed++;

  await db.query(
    `UPDATE observe_remediations
     SET status = $1, execution_output = $2, executed_at = NOW(), updated_at = NOW()
     WHERE id = $3`,
    [finalStatus, result.output || null, remId]
  );
  await db.query(
    `INSERT INTO observe_incident_events
       (id, incident_id, event_type, source, payload, occurred_at)
     VALUES ($1,$2,'remediation_auto_executed','r-observe-worker',$3,NOW())`,
    [uuidv4(), incidentId, JSON.stringify({ action, params, result, confidence_score })]
  );

  log(result.success ? 'info' : 'warn', 'Remediation result',
    { incident_id: incidentId, action, ...result });
}

// ─── Despacha remediações aprovadas via fila observe:remediation:execute ──────
async function dispatchApprovedRemediation(raw) {
  let task;
  try { task = JSON.parse(raw); } catch { return; }
  const { remediation_id, action, params, incident_id } = task;

  rem.approved++;
  log('info', 'Executing approved remediation', { remediation_id, action });
  const result = await remediation.executeAction(action, params || {});
  result.success ? rem.success++ : rem.failed++;
  const finalStatus = result.success ? 'executed' : 'failed';

  await db.query(
    `UPDATE observe_remediations
     SET status = $1, execution_output = $2, executed_at = NOW(),
         auto_executed = false, updated_at = NOW()
     WHERE id = $3`,
    [finalStatus, result.output || null, remediation_id]
  );
  if (incident_id) {
    await db.query(
      `INSERT INTO observe_incident_events
         (id, incident_id, event_type, source, payload, occurred_at)
       VALUES ($1,$2,'remediation_executed','r-observe-worker',$3,NOW())`,
      [uuidv4(), incident_id, JSON.stringify({ action, params, result, remediation_id })]
    );
  }
  log(result.success ? 'info' : 'warn', 'Approved remediation result',
    { remediation_id, action, ...result });
}

// ─── Monitoramento proativo — re-analisa incidentes abertos sem análise ───────
async function proactiveAnalysis() {
  if (!AI_ENABLED) return;
  try {
    const r = await db.query(
      `SELECT i.id, i.title, i.severity, i.source, i.host_id,
              h.address AS host_address, h.name AS host_name,
              i.metadata->>'event_output' AS event_output,
              i.metadata->>'event_service' AS event_service
       FROM observe_incidents i
       LEFT JOIN observe_hosts h ON i.host_id = h.id
       WHERE i.status = 'open'
         AND i.ai_summary IS NULL
         AND i.created_at < NOW() - INTERVAL '${PROACTIVE_STALE_MIN} minutes'
       ORDER BY i.created_at ASC
       LIMIT 5`
    );
    if (r.rowCount === 0) return;
    log('info', 'Proactive analysis', { stale_incidents: r.rowCount });

    for (const inc of r.rows) {
      const aiContext = await buildAIContext(inc.host_id, {
        output:  inc.event_output,
        service: inc.event_service,
        address: inc.host_address,
      });
      const aiResult = await callAI(
        { id: inc.id, title: inc.title, severity: inc.severity, source: inc.source },
        aiContext
      );
      if (!aiResult) continue;
      await db.query(
        `UPDATE observe_incidents
         SET ai_summary = $1, ai_cause = $2, ai_suggestion = $3, ai_pattern = $4, updated_at = NOW()
         WHERE id = $5`,
        [aiResult.summary || null, aiResult.cause || null, aiResult.suggestion || null,
         aiResult.pattern || null, inc.id]
      );
      log('info', 'Proactive AI analysis attached', { incident_id: inc.id });
      if (REMEDIATION_ENABLED && aiResult.remediation?.action) {
        await scheduleOrExecuteRemediation(inc.id, inc.severity, aiResult.remediation);
      }
      processedCount++;
    }
  } catch (e) {
    log('warn', 'Proactive analysis error', { err: e.message });
  }
}

// ─── Poll loop (blpop bloqueante — sem busy-wait) ────────────────────────────
async function pollQueues() {
  const result = await redis.blpop(...QUEUE_KEYS, 1);
  if (!result) return;
  const [key, raw] = result;
  if (key === 'observe:remediation:execute') {
    await dispatchApprovedRemediation(raw);
  } else if (key === 'observe:scan:results') {
    await processScanResults(raw);
  } else {
    await processEvent(raw);
  }
}

async function startWorker() {
  await redis.connect();
  await loadCatalogFromDb();
  log('info', 'Worker started, polling queues', { queues: QUEUE_KEYS });
  running = true;

  // Monitoramento proativo — roda a cada PROACTIVE_INTERVAL_MS
  if (AI_ENABLED && PROACTIVE_INTERVAL_MS > 0) {
    setInterval(proactiveAnalysis, PROACTIVE_INTERVAL_MS);
    log('info', 'Proactive AI analysis scheduled', { interval_ms: PROACTIVE_INTERVAL_MS });
  }

  const loop = async () => {
    if (!running) return;
    try {
      await pollQueues();
    } catch (e) {
      errorCount++;
      log('error', 'Queue poll error', { err: e.message });
      await new Promise((r) => setTimeout(r, 1000));
    }
    setImmediate(loop);
  };
  loop();
}

// ─── HTTP server (health + metrics) ──────────────────────────────────────────
const app = express();

app.get('/health', (_req, res) => {
  res.json({ status: 'ok', service: 'r-observe-worker', running, processedCount, errorCount, lastProcessedAt });
});

app.get('/metrics', (_req, res) => {
  res.set('Content-Type', 'text/plain');
  res.end([
    `# HELP r_observe_worker_events_processed_total Total events processed`,
    `# TYPE r_observe_worker_events_processed_total counter`,
    `r_observe_worker_events_processed_total ${processedCount}`,
    `# HELP r_observe_worker_errors_total Total processing errors`,
    `# TYPE r_observe_worker_errors_total counter`,
    `r_observe_worker_errors_total ${errorCount}`,
    `# HELP r_observe_remediations_total Total remediations by outcome`,
    `# TYPE r_observe_remediations_total counter`,
    `r_observe_remediations_total{outcome="auto_executed"} ${rem.auto}`,
    `r_observe_remediations_total{outcome="pending_approval"} ${rem.pending}`,
    `r_observe_remediations_total{outcome="approved_by_human"} ${rem.approved}`,
    `r_observe_remediations_total{outcome="execution_success"} ${rem.success}`,
    `r_observe_remediations_total{outcome="execution_failed"}  ${rem.failed}`,
  ].join('\n') + '\n');
});

app.listen(PORT, '0.0.0.0', () => {
  log('info', `HTTP server on :${PORT}`);
  startWorker().catch((e) => {
    log('error', 'Worker failed to start', { err: e.message });
    process.exit(1);
  });
});

process.on('SIGTERM', () => { running = false; log('info', 'Shutting down...'); });
