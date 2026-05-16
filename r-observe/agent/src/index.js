'use strict';

const express = require('express');
const helmet = require('helmet');
const Docker = require('dockerode');
const { execFile } = require('child_process');
const { promisify } = require('util');

const execFileAsync = promisify(execFile);

// ─── Config ──────────────────────────────────────────────────────────────────
const PORT = parseInt(process.env.PORT || '3000', 10);
const LOG_LEVEL = process.env.LOG_LEVEL || 'info';
const AGENT_TOKEN = process.env.AGENT_TOKEN || '';
const API_URL = process.env.API_URL || 'http://observe-api:3000';
const API_BASE_PATH = process.env.API_BASE_PATH || '/observe/api';
const CHECK_INTERVAL_MS = parseInt(process.env.CHECK_INTERVAL_MS || '60000', 10);
const DISK_WARN_PERCENT = parseInt(process.env.DISK_WARN_PERCENT || '80', 10);
const AGENT_ID = process.env.AGENT_ID || 'local-agent-01';

// ─── Logger ───────────────────────────────────────────────────────────────────
function log(level, msg, extra = {}) {
  if (level === 'debug' && LOG_LEVEL !== 'debug') return;
  process.stdout.write(JSON.stringify({ level, service: 'r-observe-agent', msg, ts: new Date().toISOString(), ...extra }) + '\n');
}

// ─── Docker client (read-only via socket) ────────────────────────────────────
let docker = null;
try {
  docker = new Docker({ socketPath: '/var/run/docker.sock' });
} catch (e) {
  log('warn', 'Docker socket not available', { err: e.message });
}

// ─── Metrics state ───────────────────────────────────────────────────────────
let lastCheckAt = null;
let checkCount = 0;
let eventsSent = 0;

// ─── Send event to API ────────────────────────────────────────────────────────
async function sendEvent(type, data) {
  try {
    const resp = await fetch(`${API_URL}${API_BASE_PATH}/events`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'x-internal-token': AGENT_TOKEN,
      },
      body: JSON.stringify({
        type,
        source: 'r-observe-agent',
        agent_id: AGENT_ID,
        host: AGENT_ID,
        ...data,
      }),
      signal: AbortSignal.timeout(10_000),
    });
    if (resp.ok) {
      eventsSent++;
      log('debug', 'Event sent', { type });
    } else {
      log('warn', 'Event rejected', { type, status: resp.status });
    }
  } catch (e) {
    log('warn', 'Failed to send event', { type, err: e.message });
  }
}

// ─── Checks ───────────────────────────────────────────────────────────────────

// Check: Docker daemon
async function checkDockerDaemon() {
  if (!docker) return;
  try {
    await docker.ping();
    log('debug', 'Docker daemon: OK');
  } catch (e) {
    log('warn', 'Docker daemon unreachable', { err: e.message });
    await sendEvent('docker.daemon.down', { severity: 'critical', message: e.message });
  }
}

// Check: containers parados / unhealthy
async function checkContainers() {
  if (!docker) return;
  try {
    const containers = await docker.listContainers({ all: true });
    for (const c of containers) {
      const name = (c.Names[0] || '').replace(/^\//, '');
      if (c.State === 'exited' || c.State === 'dead') {
        log('warn', 'Container stopped', { name, state: c.State });
        await sendEvent('container.stopped', {
          severity: 'warning',
          service: name,
          container_id: c.Id.slice(0, 12),
          state: c.State,
          image: c.Image,
          message: `Container ${name} está ${c.State}`,
        });
      }
      // Healthcheck failing
      if (c.Status && c.Status.includes('unhealthy')) {
        log('warn', 'Container unhealthy', { name });
        await sendEvent('container.unhealthy', {
          severity: 'warning',
          service: name,
          container_id: c.Id.slice(0, 12),
          status: c.Status,
          message: `Container ${name} healthcheck failing`,
        });
      }
    }
  } catch (e) {
    log('error', 'Container check failed', { err: e.message });
  }
}

// Check: uso de disco
async function checkDisk() {
  try {
    const { stdout } = await execFileAsync('df', ['-h', '/'], { timeout: 5000 });
    const lines = stdout.trim().split('\n');
    // Linha 1: cabeçalho, linha 2: dados
    const data = lines[1]?.split(/\s+/);
    if (!data) return;
    const usedPercent = parseInt((data[4] || '0').replace('%', ''), 10);
    const filesystem = data[0];
    const used = data[2];
    const avail = data[3];
    log('debug', 'Disk check', { filesystem, usedPercent });
    if (usedPercent >= DISK_WARN_PERCENT) {
      await sendEvent('disk.high_usage', {
        severity: usedPercent >= 95 ? 'critical' : 'warning',
        filesystem,
        used,
        avail,
        percent: usedPercent,
        message: `Disco ${filesystem} em ${usedPercent}% de uso`,
      });
    }
  } catch (e) {
    log('warn', 'Disk check failed', { err: e.message });
  }
}

// Check: HTTP health de um endpoint
async function checkHTTP(url, label) {
  try {
    const resp = await fetch(url, {
      signal: AbortSignal.timeout(5000),
      headers: { 'x-internal-token': AGENT_TOKEN },
    });
    if (!resp.ok) {
      await sendEvent('check.failed', {
        severity: 'warning',
        check: 'http',
        target: label || url,
        status_code: resp.status,
        message: `HTTP check falhou: ${url} retornou ${resp.status}`,
      });
    }
  } catch (e) {
    await sendEvent('check.failed', {
      severity: 'warning',
      check: 'http',
      target: label || url,
      message: `HTTP check inacessível: ${url} — ${e.message}`,
    });
  }
}

// Check: volumes órfãos (apenas conta — não remove nunca)
async function checkOrphanVolumes() {
  if (!docker) return;
  try {
    const { Volumes } = await docker.listVolumes({ filters: { dangling: ['true'] } });
    if (Volumes && Volumes.length > 0) {
      log('info', 'Orphan volumes found', { count: Volumes.length });
      // Apenas reporta, não remove
      await sendEvent('docker.orphan_volumes', {
        severity: 'info',
        count: Volumes.length,
        names: Volumes.slice(0, 10).map(v => v.Name),
        message: `${Volumes.length} volume(s) órfão(s) detectado(s)`,
      });
    }
  } catch (e) {
    log('warn', 'Orphan volume check failed', { err: e.message });
  }
}

// ─── Run all checks ───────────────────────────────────────────────────────────
async function runChecks() {
  log('debug', 'Running checks...');
  checkCount++;
  lastCheckAt = new Date().toISOString();

  await Promise.allSettled([
    checkDockerDaemon(),
    checkContainers(),
    checkDisk(),
    checkOrphanVolumes(),
    // HTTP check da própria API para validar conectividade
    checkHTTP(`${API_URL}${API_BASE_PATH}/health`, 'r-observe-api'),
  ]);

  log('debug', 'Checks complete', { checkCount });
}

// ─── Express ──────────────────────────────────────────────────────────────────
const app = express();
app.use(helmet({ contentSecurityPolicy: false }));
app.use(express.json({ limit: '64kb' }));

app.get('/health', (_req, res) => {
  res.json({
    status: 'ok',
    service: 'r-observe-agent',
    agent_id: AGENT_ID,
    checkCount,
    eventsSent,
    lastCheckAt,
    version: '0.1.0',
  });
});

app.get('/metrics', (_req, res) => {
  res.set('Content-Type', 'text/plain');
  res.end([
    `# HELP r_observe_agent_checks_total Total check cycles run`,
    `# TYPE r_observe_agent_checks_total counter`,
    `r_observe_agent_checks_total ${checkCount}`,
    `# HELP r_observe_agent_events_sent_total Total events sent to API`,
    `# TYPE r_observe_agent_events_sent_total counter`,
    `r_observe_agent_events_sent_total ${eventsSent}`,
  ].join('\n') + '\n');
});

// Trigger manual check (para uso em smoke tests)
app.post('/checks/run', (_req, res) => {
  runChecks().catch((e) => log('error', 'Manual check failed', { err: e.message }));
  res.json({ accepted: true, message: 'Check cycle initiated' });
});

app.use((_req, res) => res.status(404).json({ error: 'Not found' }));

app.listen(PORT, '0.0.0.0', () => {
  log('info', `Agent listening on :${PORT}`, { agent_id: AGENT_ID, interval_ms: CHECK_INTERVAL_MS });
  // Inicia loop de checks
  runChecks().catch((e) => log('error', 'Initial check failed', { err: e.message }));
  setInterval(() => {
    runChecks().catch((e) => log('error', 'Check interval failed', { err: e.message }));
  }, CHECK_INTERVAL_MS);
});
