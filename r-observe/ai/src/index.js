'use strict';

const express = require('express');
const helmet = require('helmet');
const rateLimit = require('express-rate-limit');

// ─── Config ──────────────────────────────────────────────────────────────────
const PORT           = parseInt(process.env.PORT || '3000', 10);
const LOG_LEVEL      = process.env.LOG_LEVEL || 'info';
const INTERNAL_TOKEN = process.env.INTERNAL_TOKEN || '';
const AI_TIMEOUT_MS  = parseInt(process.env.AI_TIMEOUT_MS || '30000', 10);

const VALID_PROVIDERS = ['openai', 'deepseek', 'anthropic', 'mock'];

// Modelo padrão por provider quando "auto" é selecionado
const AUTO_MODELS = {
  openai:    'gpt-4o-mini',
  deepseek:  'deepseek-chat',
  anthropic: 'claude-haiku-4-5-20251001',
  mock:      '',
};

// Lista estática usada como fallback quando não há chave ou a API está indisponível
const STATIC_MODELS = {
  openai: [
    'chatgpt-4o-latest',
    'gpt-5', 'gpt-5-mini', 'gpt-5-nano', 'gpt-5-pro',
    'gpt-5.1', 'gpt-5.2', 'gpt-5.4', 'gpt-5.5',
    'gpt-4.5-preview',
    'gpt-4o', 'gpt-4o-mini', 'gpt-4o-search-preview',
    'gpt-4.1', 'gpt-4.1-mini', 'gpt-4.1-nano',
    'gpt-4-turbo', 'gpt-4',
    'gpt-3.5-turbo',
    'o1', 'o1-pro', 'o1-mini', 'o1-preview',
    'o3', 'o3-mini',
    'o4-mini',
  ],
  anthropic: [
    'claude-opus-4-7',
    'claude-sonnet-4-6',
    'claude-haiku-4-5-20251001',
    'claude-3-5-sonnet-20241022',
    'claude-3-5-haiku-20241022',
    'claude-3-opus-20240229',
    'claude-3-sonnet-20240229',
    'claude-3-haiku-20240307',
  ],
  deepseek: [
    'deepseek-chat',
    'deepseek-reasoner',
  ],
  mock: [],
};

// Prefixos de chat da OpenAI; sufixos excluídos filtram audio, realtime, tts, imagem, etc.
const OPENAI_CHAT_PREFIXES  = ['chat-latest', 'chatgpt', 'gpt-3', 'gpt-4', 'gpt-5', 'o1', 'o2', 'o3', 'o4'];
const OPENAI_EXCLUDE_PARTS  = ['-audio', '-realtime', '-transcribe', '-tts', '-image', '-search-api', 'babbage', 'davinci', 'whisper', 'embedding', 'moderation', 'sora'];

// Ordena modelos do mais recente para o mais antigo.
// Score: família × 1000 + versão minor × 100 + variante (pro>base>mini>nano) − penalidade dated.
function sortModelsByRecency(models) {
  function score(id) {
    const dated = /\d{4}-\d{2}/.test(id) ? -2 : 0;
    const variant = id.includes('-pro') || id.includes('-max') ? 3
      : !(id.includes('-mini') || id.includes('-nano') || id.includes('-codex')) ? 2
      : id.includes('-mini') ? 1
      : 0;

    const g5 = id.match(/^gpt-5(?:\.(\d+))?/);
    if (g5) return 5000 + parseInt(g5[1] ?? 0) * 100 + variant + dated;

    const o = id.match(/^o(\d+(?:\.\d+)?)/);
    if (o) return 4500 + parseFloat(o[1]) * 10 + variant + dated;

    if (id.startsWith('chat')) return 4300 + dated;

    const g4x = id.match(/^gpt-4\.(\d+)/);
    if (g4x) return 4100 + parseInt(g4x[1]) * 10 + variant + dated;

    if (id.startsWith('gpt-4o')) return 4020 + variant + dated;
    if (id.startsWith('gpt-4-turbo')) return 4010 + dated;
    if (id.startsWith('gpt-4')) return 4000 + variant + dated;
    if (id.startsWith('gpt-3')) return 3500 + variant + dated;

    return 0;
  }
  return [...models].sort((a, b) => score(b) - score(a));
}

function resolveModel(provider, model) {
  if (!model || model === 'auto') return AUTO_MODELS[provider] || '';
  return model;
}

// Busca a lista real de modelos na API do provider; fallback para lista estática.
// Retorna { models, source } onde source é 'api' ou 'static'.
async function fetchProviderModels(provider) {
  if (!runtimeConfig.apiKey) return { models: sortModelsByRecency(STATIC_MODELS[provider] || []), source: 'static' };

  try {
    if (provider === 'openai') {
      const resp = await fetch('https://api.openai.com/v1/models', {
        headers: { Authorization: `Bearer ${runtimeConfig.apiKey}` },
        signal: AbortSignal.timeout(8000),
      });
      if (!resp.ok) return { models: sortModelsByRecency(STATIC_MODELS.openai), source: 'static' };
      const { data } = await resp.json();
      return {
        models: sortModelsByRecency(
          data
            .map(m => m.id)
            .filter(id =>
              OPENAI_CHAT_PREFIXES.some(p => id.startsWith(p)) &&
              !OPENAI_EXCLUDE_PARTS.some(x => id.includes(x))
            )
        ),
        source: 'api',
      };
    }

    if (provider === 'anthropic') {
      const resp = await fetch('https://api.anthropic.com/v1/models', {
        headers: { 'x-api-key': runtimeConfig.apiKey, 'anthropic-version': '2023-06-01' },
        signal: AbortSignal.timeout(8000),
      });
      if (!resp.ok) return { models: STATIC_MODELS.anthropic, source: 'static' };
      const { data } = await resp.json();
      return { models: data.map(m => m.id), source: 'api' }; // Anthropic API já retorna do mais recente
    }
  } catch {
    // provider indisponível ou chave inválida — usa fallback
  }

  return { models: STATIC_MODELS[provider] || [], source: 'static' };
}

// Config mutável em runtime — alterável via POST /ai/settings sem restart
const runtimeConfig = {
  provider: process.env.AI_PROVIDER || 'mock',
  model:    process.env.AI_MODEL    || 'auto',
  apiKey:   process.env.ANTHROPIC_API_KEY || process.env.OPENAI_API_KEY || process.env.DEEPSEEK_API_KEY || '',
};

const SYSTEM_PROMPT =
  'Você é um especialista sênior em SRE e operações de infraestrutura. ' +
  'Analise incidentes de forma objetiva e concisa em português. ' +
  'Baseie sua análise no output real dos checks e no histórico fornecido. ' +
  'Nunca invente informações que não estejam no contexto. ' +
  'Suas sugestões devem ser acionáveis e específicas para o caso descrito. ' +
  'Ao sugerir remediação, escolha SOMENTE ações do catálogo fornecido. ' +
  'Atribua confidence_score com critério: ações de baixo risco e alta certeza merecem score alto; ' +
  'ações destrutivas ou situações ambíguas devem ter score baixo.';

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
  if (!runtimeConfig.apiKey) throw new Error('OPENAI_API_KEY not configured');
  const model = resolveModel('openai', runtimeConfig.model);
  const resp = await fetch('https://api.openai.com/v1/chat/completions', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${runtimeConfig.apiKey}` },
    body: JSON.stringify({
      model,
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
  if (!runtimeConfig.apiKey) throw new Error('DEEPSEEK_API_KEY not configured');
  const model = resolveModel('deepseek', runtimeConfig.model);
  const resp = await fetch('https://api.deepseek.com/v1/chat/completions', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${runtimeConfig.apiKey}` },
    body: JSON.stringify({
      model,
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

async function callAnthropic(prompt) {
  if (!runtimeConfig.apiKey) throw new Error('ANTHROPIC_API_KEY not configured');
  const model = resolveModel('anthropic', runtimeConfig.model);
  const resp = await fetch('https://api.anthropic.com/v1/messages', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'x-api-key': runtimeConfig.apiKey,
      'anthropic-version': '2023-06-01',
    },
    body: JSON.stringify({
      model,
      max_tokens: 600,
      system: SYSTEM_PROMPT,
      messages: [{ role: 'user', content: prompt }],
    }),
    signal: AbortSignal.timeout(AI_TIMEOUT_MS),
  });
  if (!resp.ok) {
    const err = await resp.text();
    throw new Error(`Anthropic API error ${resp.status}: ${err}`);
  }
  const data = await resp.json();
  return data.content?.[0]?.text || '';
}

function mockAnalyze(incident, context) {
  const hasHistory = (context?.recent_incidents?.length ?? 0) > 0;
  const hasOutput  = !!context?.event_output;
  const hasPrevRem = (context?.recent_remediations?.length ?? 0) > 0;

  const cause = hasOutput
    ? `Output do check: "${context.event_output.slice(0, 120)}"`
    : 'Causa provável: falha no serviço ou recurso indisponível.';

  const suggestion = hasPrevRem
    ? `Ação anterior registrada: "${context.recent_remediations[0].action}". Verificar se ainda é aplicável.`
    : 'Verificar logs do serviço afetado e reiniciar se necessário.';

  const pattern = hasHistory
    ? `${context.recent_incidents.length} ocorrência(s) nos últimos 7 dias`
    : null;

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
  if (runtimeConfig.provider === 'mock') return mockAnalyze(incident, context);

  const prompt = buildPrompt(incident, context);
  const effectiveModel = resolveModel(runtimeConfig.provider, runtimeConfig.model);
  let raw = '';

  if (runtimeConfig.provider === 'openai')        raw = await callOpenAI(prompt);
  else if (runtimeConfig.provider === 'deepseek') raw = await callDeepSeek(prompt);
  else if (runtimeConfig.provider === 'anthropic') raw = await callAnthropic(prompt);
  else return mockAnalyze(incident, context);

  try {
    const match  = raw.match(/\{[\s\S]*\}/);
    const parsed = match ? JSON.parse(match[0]) : {};
    return { ...parsed, provider: runtimeConfig.provider, model: effectiveModel };
  } catch {
    return { summary: raw, provider: runtimeConfig.provider, model: effectiveModel };
  }
}

// ─── Express ──────────────────────────────────────────────────────────────────
const app = express();
app.use(helmet({ contentSecurityPolicy: false }));
app.use(express.json({ limit: '512kb' }));

const limiter = rateLimit({ windowMs: 60_000, max: 60, standardHeaders: true, legacyHeaders: false });

app.get('/health', (_req, res) => {
  res.json({
    status:           'ok',
    service:          'r-observe-ai',
    provider:         runtimeConfig.provider,
    model:            runtimeConfig.model,
    effective_model:  resolveModel(runtimeConfig.provider, runtimeConfig.model),
    version:          '0.4.0',
  });
});

app.get('/metrics', (_req, res) => {
  res.set('Content-Type', 'text/plain');
  res.end('# R-Observe AI metrics\n');
});

// GET /ai/settings — config atual (sem expor a chave)
app.get('/ai/settings', requireAuth, (_req, res) => {
  res.json({
    provider:       runtimeConfig.provider,
    model:          runtimeConfig.model,
    effective_model: resolveModel(runtimeConfig.provider, runtimeConfig.model),
    has_api_key:    !!runtimeConfig.apiKey,
  });
});

// GET /ai/models — lista modelos disponíveis (API real ou fallback estático)
app.get('/ai/models', requireAuth, async (req, res) => {
  const provider = req.query.provider || runtimeConfig.provider;
  if (!VALID_PROVIDERS.includes(provider)) {
    return res.status(400).json({ error: `provider inválido. Use: ${VALID_PROVIDERS.join(', ')}` });
  }
  const { models, source } = await fetchProviderModels(provider);
  res.json({ provider, models, auto: AUTO_MODELS[provider] || null, source });
});

// POST /ai/settings — atualiza provider/model/api_key em runtime
app.post('/ai/settings', requireAuth, (req, res) => {
  const { provider, model, api_key } = req.body;

  if (provider !== undefined) {
    if (!VALID_PROVIDERS.includes(provider)) {
      return res.status(400).json({ error: `provider inválido. Use: ${VALID_PROVIDERS.join(', ')}` });
    }
    runtimeConfig.provider = provider;
  }
  if (model !== undefined) {
    runtimeConfig.model = model; // 'auto', '' ou nome explícito
  }
  if (api_key !== undefined) {
    runtimeConfig.apiKey = api_key;
  }

  log('info', 'Runtime config updated', {
    provider:      runtimeConfig.provider,
    model:         runtimeConfig.model,
    effective_model: resolveModel(runtimeConfig.provider, runtimeConfig.model),
  });

  res.json({
    ok:             true,
    provider:       runtimeConfig.provider,
    model:          runtimeConfig.model,
    effective_model: resolveModel(runtimeConfig.provider, runtimeConfig.model),
    has_api_key:    !!runtimeConfig.apiKey,
  });
});

// POST /ai/explain
app.post('/ai/explain', requireAuth, limiter, async (req, res) => {
  const { incident, context } = req.body;
  if (!incident) return res.status(400).json({ error: 'incident é obrigatório' });
  try {
    log('info', 'Analyzing incident', { id: incident.id, provider: runtimeConfig.provider });
    const result = await analyze(incident, context);
    res.json(result);
  } catch (e) {
    log('error', 'AI analysis failed', { err: e.message });
    if (e.name === 'AbortError') return res.status(504).json({ error: 'AI provider timeout' });
    res.status(502).json({ error: 'AI analysis failed', detail: e.message });
  }
});

// POST /ai/classify
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

// POST /ai/summarize
app.post('/ai/summarize', requireAuth, limiter, async (req, res) => {
  const { events } = req.body;
  if (!Array.isArray(events)) return res.status(400).json({ error: 'events deve ser um array' });
  try {
    const incident = { title: `${events.length} eventos agregados`, severity: 'unknown', source: 'aggregated' };
    const context  = {
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

// POST /ai/fingerprint — identifica dispositivos desconhecidos por sinais passivos
app.post('/ai/fingerprint', requireAuth, limiter, async (req, res) => {
  const { signals } = req.body;
  if (!signals) return res.status(400).json({ error: 'signals é obrigatório' });

  try {
    const result = await fingerprintDevice(signals);
    res.json(result);
  } catch (e) {
    res.status(502).json({ error: e.message });
  }
});

/** Prompt para identificação de dispositivo de rede por sinais passivos. */
function buildFingerprintPrompt(signals) {
  const lines = [
    'Você é um especialista em segurança de redes. Identifique o dispositivo de rede com base nos sinais passivos abaixo.',
    '',
    '## Sinais Observados',
  ];
  if (signals.mac)          lines.push(`MAC: ${signals.mac}`);
  if (signals.mac_vendor)   lines.push(`Fabricante pelo OUI: ${signals.mac_vendor}`);
  if (signals.hostname)     lines.push(`Hostname: ${signals.hostname}`);
  if (signals.ip)           lines.push(`IP: ${signals.ip}`);
  if (signals.open_ports?.length) lines.push(`Portas abertas: ${signals.open_ports.join(', ')}`);
  if (signals.mdns_services?.length) lines.push(`Serviços mDNS: ${signals.mdns_services.join(', ')}`);
  if (signals.ssdp_server)  lines.push(`SSDP Server: ${signals.ssdp_server}`);
  if (signals.txt_manufacturer) lines.push(`Fabricante (TXT): ${signals.txt_manufacturer}`);
  if (signals.txt_model)    lines.push(`Modelo (TXT): ${signals.txt_model}`);
  if (signals.txt_friendly_name) lines.push(`Nome amigável: ${signals.txt_friendly_name}`);
  if (signals.raw_mdns)     lines.push(`mDNS raw (trecho): ${String(signals.raw_mdns).slice(0, 300)}`);
  if (signals.raw_ssdp)     lines.push(`SSDP raw (trecho): ${String(signals.raw_ssdp).slice(0, 300)}`);

  lines.push(
    '',
    'Responda SOMENTE com JSON válido contendo:',
    '- vendor (string): fabricante do dispositivo, ou null se desconhecido',
    '- product (string): nome do produto/modelo, ou null se desconhecido',
    '- category (string): uma de: mobile, media, iot, host, router, switch, ap, printer, camera, voice, unknown',
    '- technology (string): tecnologia principal (ex: android, ios, linux, windows, upnp-media, etc.), ou null',
    '- asset_type (string): uma de: mobile, media_device, iot, host, network_device',
    '- confidence (number): 0.0 a 1.0 — sua confiança na classificação',
    '- reasoning (string): explicação em 1 frase do que levou à identificação',
  );
  return lines.join('\n');
}

/** Mock para quando provider = mock. */
function mockFingerprintDevice(signals) {
  const raw = [
    signals.ssdp_server, signals.txt_manufacturer, signals.txt_model,
    signals.hostname, (signals.mdns_services || []).join(' '), signals.raw_mdns,
  ].filter(Boolean).join(' ').toLowerCase();

  if (raw.includes('hisense') || raw.includes('smarttv') || raw.includes('airplay')) {
    return { vendor: signals.txt_manufacturer || 'Hisense', product: signals.txt_model || 'Smart TV', category: 'media', technology: 'upnp-media', asset_type: 'media_device', confidence: 0.85, reasoning: '[MOCK] Detectado por AirPlay/Hisense', provider: 'mock', model: 'mock-v1' };
  }
  if (raw.includes('linux') || raw.includes('avahi')) {
    return { vendor: 'Linux', product: 'Linux Host', category: 'host', technology: 'linux', asset_type: 'host', confidence: 0.80, reasoning: '[MOCK] Detectado por stack Linux/Avahi', provider: 'mock', model: 'mock-v1' };
  }
  if (raw.includes('googlecast') || raw.includes('motorola') || raw.includes('android')) {
    return { vendor: signals.txt_manufacturer || 'Motorola', product: signals.txt_model || 'Smartphone Android', category: 'mobile', technology: 'android', asset_type: 'mobile', confidence: 0.82, reasoning: '[MOCK] Detectado por Google Cast/Android', provider: 'mock', model: 'mock-v1' };
  }
  return { vendor: null, product: null, category: 'unknown', technology: null, asset_type: 'iot', confidence: 0.2, reasoning: '[MOCK] Sinais insuficientes para identificação', provider: 'mock', model: 'mock-v1' };
}

async function fingerprintDevice(signals) {
  if (runtimeConfig.provider === 'mock') return mockFingerprintDevice(signals);

  const prompt = buildFingerprintPrompt(signals);
  const effectiveModel = resolveModel(runtimeConfig.provider, runtimeConfig.model);
  let raw = '';

  if (runtimeConfig.provider === 'openai')         raw = await callOpenAI(prompt);
  else if (runtimeConfig.provider === 'deepseek')  raw = await callDeepSeek(prompt);
  else if (runtimeConfig.provider === 'anthropic') raw = await callAnthropic(prompt);
  else return mockFingerprintDevice(signals);

  try {
    const match  = raw.match(/\{[\s\S]*\}/);
    const parsed = match ? JSON.parse(match[0]) : {};
    return { ...parsed, provider: runtimeConfig.provider, model: effectiveModel };
  } catch {
    return { summary: raw, provider: runtimeConfig.provider, model: effectiveModel };
  }
}

app.use((_req, res) => res.status(404).json({ error: 'Not found' }));

app.listen(PORT, '0.0.0.0', () => {
  log('info', `AI service listening on :${PORT}`, {
    provider:      runtimeConfig.provider,
    model:         runtimeConfig.model,
    effective_model: resolveModel(runtimeConfig.provider, runtimeConfig.model),
  });
});
