'use strict';

const express = require('express');
const helmet = require('helmet');
const rateLimit = require('express-rate-limit');

// ─── Config ──────────────────────────────────────────────────────────────────
const PORT        = parseInt(process.env.PORT || '3000', 10);
const LOG_LEVEL   = process.env.LOG_LEVEL || 'info';
const INTERNAL_TOKEN = process.env.INTERNAL_TOKEN || '';
const AI_PROVIDER = process.env.AI_PROVIDER || 'mock';
const AI_MODEL    = process.env.AI_MODEL    || 'gpt-4o-mini';
const AI_TIMEOUT_MS = parseInt(process.env.AI_TIMEOUT_MS || '30000', 10);

const SYSTEM_PROMPT =
  'Você é um especialista sênior em SRE e operações de infraestrutura. ' +
  'Analise incidentes de forma objetiva e concisa em português. ' +
  'Baseie sua análise no output real dos checks e no histórico fornecido. ' +
  'Nunca invente informações que não estejam no contexto. ' +
  'Suas sugestões devem ser acionáveis e específicas para o caso descrito. ' +
  'Ao sugerir remediação, escolha SOMENTE ações do catálogo fornecido. ' +
  'Atribua confidence_score com critério: ações de baixo risco e alta certeza merecem score alto; ' +
  'ações destrutivas ou situações ambíguas devem ter score baixo.';

// Catálogo de ações disponíveis para remediação (sincronizado com remediation.js)
const REMEDIATION_CATALOG = [
  { action: 'icinga:reschedule', description: 'Reagenda check no Icinga2', risk: 'none',   params: ['host', 'service?'] },
  { action: 'http:verify',       description: 'Verifica endpoint HTTP',     risk: 'none',   params: ['url'] },
  { action: 'docker:start',      description: 'Inicia container parado',    risk: 'low',    params: ['container'] },
  { action: 'docker:restart',    description: 'Reinicia container',         risk: 'medium', params: ['container'] },
];

// ─── Logger ───────────────────────────────────────────────────────────────────
function log(level, msg, extra = {}) {
  process.stdout.write(JSON.stringify({
    level, service: 'r-observe-ai', msg, ts: new Date().toISOString(), ...extra,
  }) + '\n');
}

// ─── Auth ─────────────────────────────────────────────────────────────────────
const requireAuth = (req, res, next) => {
  if (!INTERNAL_TOKEN) return next();
  const provided = req.headers['x-internal-token'] ||
    (req.headers['authorization'] || '').replace(/^Bearer\s+/i, '');
  if (provided !== INTERNAL_TOKEN) return res.status(401).json({ error: 'Unauthorized' });
  next();
};

// ─── Prompt ───────────────────────────────────────────────────────────────────
function buildPrompt(incident, context) {
  const lines = [
    '## Incidente Atual',
    `Título: ${incident.title || 'N/A'}`,
    `Severidade: ${incident.severity || 'N/A'}`,
    `Fonte: ${incident.source || 'N/A'}`,
  ];

  if (context?.event_output)  lines.push(`Output do check: ${context.event_output}`);
  if (context?.event_service) lines.push(`Serviço afetado: ${context.event_service}`);
  if (context?.host_address)  lines.push(`Endereço do host: ${context.host_address}`);

  const recentInc = context?.recent_incidents;
  if (recentInc?.length > 0) {
    lines.push('', '## Histórico do Host (últimos 7 dias)');
    for (const inc of recentInc) {
      const dt = new Date(inc.created_at).toISOString().slice(0, 16).replace('T', ' ');
      const st = inc.resolved_at ? 'resolvido' : inc.status;
      lines.push(`- ${dt} | ${inc.title} | ${inc.severity} | ${st}`);
    }
    lines.push(`(${recentInc.length} incidente(s) recente(s) neste host)`);
  }

  const recentRem = context?.recent_remediations;
  if (recentRem?.length > 0) {
    lines.push('', '## Remediações Anteriores (últimos 7 dias)');
    for (const rem of recentRem) {
      const dt = new Date(rem.requested_at).toISOString().slice(0, 10);
      const reason = rem.reason ? ` — ${rem.reason}` : '';
      lines.push(`- ${dt}: ${rem.action}${reason} (status: ${rem.status})`);
    }
  }

  lines.push('', '## Catálogo de Remediações Disponíveis');
  for (const a of REMEDIATION_CATALOG) {
    lines.push(`- ${a.action} (risco: ${a.risk}) — ${a.description} | params: ${a.params.join(', ')}`);
  }

  lines.push(
    '',
    'Com base nas informações acima, responda SOMENTE em JSON válido com os campos:',
    '- summary (string): o que está acontecendo em 1-2 frases',
    '- cause (string): causa provável, referenciando o output do check se disponível',
    '- suggestion (string): ação recomendada, específica e acionável',
    '- severity_classification (string): "critical" | "warning" | "info"',
    '- recurrence (boolean): true se este tipo de falha já ocorreu recentemente',
    '- pattern (string | null): padrão identificado no histórico, ou null',
    '- remediation (object | null): { action, params, confidence_score (0.0-1.0), reasoning }',
    '  Use null se nenhuma ação for aplicável ou se não houver certeza suficiente.',
    '  confidence_score >= 0.85 dispara execução automática — seja conservador.',
  );

  return lines.join('\n');
}

// ─── Providers ────────────────────────────────────────────────────────────────
async function callOpenAI(prompt) {
  const apiKey = process.env.OPENAI_API_KEY;
  if (!apiKey) throw new Error('OPENAI_API_KEY not configured');
  const resp = await fetch('https://api.openai.com/v1/chat/completions', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${apiKey}` },
    body: JSON.stringify({
      model: AI_MODEL,
      messages: [
        { role: 'system', content: SYSTEM_PROMPT },
        { role: 'user',   content: prompt },
      ],
      max_tokens: 600,
      temperature: 0.2,
      response_format: { type: 'json_object' },
    }),
    signal: AbortSignal.timeout(AI_TIMEOUT_MS),
  });
  if (!resp.ok) {
    const err = await resp.text();
    throw new Error(`OpenAI API error ${resp.status}: ${err}`);
  }
  const data = await resp.json();
  return data.choices?.[0]?.message?.content || '';
}

async function callDeepSeek(prompt) {
  const apiKey = process.env.DEEPSEEK_API_KEY;
  if (!apiKey) throw new Error('DEEPSEEK_API_KEY not configured');
  const resp = await fetch('https://api.deepseek.com/v1/chat/completions', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${apiKey}` },
    body: JSON.stringify({
      model: AI_MODEL || 'deepseek-chat',
      messages: [
        { role: 'system', content: SYSTEM_PROMPT },
        { role: 'user',   content: prompt },
      ],
      max_tokens: 600,
    }),
    signal: AbortSignal.timeout(AI_TIMEOUT_MS),
  });
  if (!resp.ok) throw new Error(`DeepSeek API error ${resp.status}`);
  const data = await resp.json();
  return data.choices?.[0]?.message?.content || '';
}

function mockAnalyze(incident, context) {
  const hasHistory     = (context?.recent_incidents?.length ?? 0) > 0;
  const hasOutput      = !!context?.event_output;
  const hasPrevRem     = (context?.recent_remediations?.length ?? 0) > 0;

  const cause = hasOutput
    ? `Output do check: "${context.event_output.slice(0, 120)}"`
    : 'Causa provável: falha no serviço ou recurso indisponível.';

  const suggestion = hasPrevRem
    ? `Ação anterior registrada: "${context.recent_remediations[0].action}". Verificar se ainda é aplicável.`
    : 'Verificar logs do serviço afetado e reiniciar se necessário.';

  const pattern = hasHistory
    ? `${context.recent_incidents.length} ocorrência(s) nos últimos 7 dias`
    : null;

  // Mock de remediação: sugere icinga:reschedule para qualquer incidente (risco nulo, score alto)
  // e docker:start/restart para eventos de container (score médio, exige aprovação)
  let mockRemediation = null;
  const title = (incident.title || '').toLowerCase();
  if (title.includes('container.stopped') || title.includes('container.unhealthy')) {
    const container = context?.event_service || 'unknown-container';
    mockRemediation = {
      action:           'docker:start',
      params:           { container },
      confidence_score: 0.72,
      reasoning:        '[MOCK] Container parado detectado. Score conservador — requer aprovação humana.',
    };
  } else if (incident.source === 'icinga' || title.includes('host.down') || title.includes('service.')) {
    mockRemediation = {
      action:           'icinga:reschedule',
      params:           { host: context?.host_address || 'unknown' },
      confidence_score: 0.90,
      reasoning:        '[MOCK] Reagendar check é seguro (risco nulo). Score alto.',
    };
  }

  return {
    summary:                `Incidente detectado: ${incident.title} com severidade ${incident.severity}.`,
    cause,
    suggestion,
    severity_classification: incident.severity || 'warning',
    recurrence:              hasHistory,
    pattern,
    remediation:             mockRemediation,
    provider: 'mock',
    model:    'mock-v1',
  };
}

// ─── Análise principal ────────────────────────────────────────────────────────
async function analyze(incident, context) {
  if (AI_PROVIDER === 'mock') return mockAnalyze(incident, context);

  const prompt = buildPrompt(incident, context);
  let raw = '';

  if (AI_PROVIDER === 'openai')    raw = await callOpenAI(prompt);
  else if (AI_PROVIDER === 'deepseek') raw = await callDeepSeek(prompt);
  else return mockAnalyze(incident, context);

  try {
    const match  = raw.match(/\{[\s\S]*\}/);
    const parsed = match ? JSON.parse(match[0]) : {};
    return { ...parsed, provider: AI_PROVIDER, model: AI_MODEL };
  } catch {
    return { summary: raw, provider: AI_PROVIDER, model: AI_MODEL };
  }
}

// ─── Express ──────────────────────────────────────────────────────────────────
const app = express();
app.use(helmet({ contentSecurityPolicy: false }));
app.use(express.json({ limit: '512kb' }));

const limiter = rateLimit({ windowMs: 60_000, max: 60, standardHeaders: true, legacyHeaders: false });

app.get('/health', (_req, res) => {
  res.json({ status: 'ok', service: 'r-observe-ai', provider: AI_PROVIDER, version: '0.2.0' });
});

app.get('/metrics', (_req, res) => {
  res.set('Content-Type', 'text/plain');
  res.end('# R-Observe AI metrics\n');
});

// POST /ai/explain — análise de incidente com contexto enriquecido
app.post('/ai/explain', requireAuth, limiter, async (req, res) => {
  const { incident, context } = req.body;
  if (!incident) return res.status(400).json({ error: 'incident é obrigatório' });
  try {
    log('info', 'Analyzing incident', { id: incident.id, provider: AI_PROVIDER });
    const result = await analyze(incident, context);
    res.json(result);
  } catch (e) {
    log('error', 'AI analysis failed', { err: e.message });
    if (e.name === 'AbortError') return res.status(504).json({ error: 'AI provider timeout' });
    res.status(502).json({ error: 'AI analysis failed', detail: e.message });
  }
});

// POST /ai/classify — classifica severidade de um evento
app.post('/ai/classify', requireAuth, limiter, async (req, res) => {
  const { event } = req.body;
  if (!event) return res.status(400).json({ error: 'event é obrigatório' });
  try {
    const incident = { title: event.type, severity: event.severity, source: event.source };
    const context  = { event_output: event.output || event.message };
    const result   = await analyze(incident, context);
    res.json({ severity: result.severity_classification || 'warning', analysis: result });
  } catch (e) {
    res.status(502).json({ error: e.message });
  }
});

// POST /ai/summarize — resumo de conjunto de eventos
app.post('/ai/summarize', requireAuth, limiter, async (req, res) => {
  const { events } = req.body;
  if (!Array.isArray(events)) return res.status(400).json({ error: 'events deve ser um array' });
  try {
    const incident = {
      title:    `${events.length} eventos agregados`,
      severity: 'unknown',
      source:   'aggregated',
    };
    const context = {
      event_output: events.slice(0, 10)
        .map(e => `[${e.type}] ${e.host || ''}: ${e.output || e.message || ''}`)
        .join('\n'),
    };
    const result = await analyze(incident, context);
    res.json(result);
  } catch (e) {
    res.status(502).json({ error: e.message });
  }
});

app.use((_req, res) => res.status(404).json({ error: 'Not found' }));

app.listen(PORT, '0.0.0.0', () => {
  log('info', `AI service listening on :${PORT}`, { provider: AI_PROVIDER, model: AI_MODEL });
});
