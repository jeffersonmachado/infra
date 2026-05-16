'use strict';

const { execFile } = require('child_process');
const { promisify } = require('util');

const execFileAsync = promisify(execFile);

// ─── Config ───────────────────────────────────────────────────────────────────
const AUTO_THRESHOLD      = parseFloat(process.env.REMEDIATION_AUTO_THRESHOLD      || '0.85');
const MAX_AUTO_SEVERITY   = (process.env.REMEDIATION_MAX_AUTO_SEVERITY             || 'warning').toLowerCase();
const ICINGA_API_URL      = process.env.ICINGA_API_URL      || 'https://observe-icinga2:5665';
const ICINGA_API_USER     = process.env.ICINGA_API_USER     || 'icingaweb2';
const ICINGA_API_PASSWORD = process.env.ICINGA_API_PASSWORD || '';

// Containers permitidos para operações Docker (vazio = nenhum container liberado)
// Ex: REMEDIATION_ALLOWED_CONTAINERS=r-observe-worker,r-observe-api
const ALLOWED_CONTAINERS = new Set(
  (process.env.REMEDIATION_ALLOWED_CONTAINERS || '')
    .split(',').map(s => s.trim()).filter(Boolean)
);

const SEVERITY_ORDER = { critical: 3, warning: 2, info: 1, unknown: 0 };

// ─── Validações ───────────────────────────────────────────────────────────────
function validateContainerName(name) {
  if (!/^[a-zA-Z0-9][a-zA-Z0-9_.-]{0,127}$/.test(name)) {
    throw new Error(`Nome de container inválido: "${name}"`);
  }
  if (ALLOWED_CONTAINERS.size === 0) {
    throw new Error(
      `Remediação Docker bloqueada. Defina REMEDIATION_ALLOWED_CONTAINERS no .env.observe.`
    );
  }
  if (!ALLOWED_CONTAINERS.has(name)) {
    throw new Error(
      `Container "${name}" não está em REMEDIATION_ALLOWED_CONTAINERS.`
    );
  }
}

function validateUrl(url) {
  if (!/^https?:\/\/[^\s]{4,}/.test(url)) {
    throw new Error(`URL inválida para verificação: "${url}"`);
  }
}

// ─── Catálogo de ações ────────────────────────────────────────────────────────
// A IA escolhe UMA ação deste catálogo — nunca gera comandos livres.
const CATALOG = {

  // Risco nulo — reenvia check para o Icinga2 verificar novamente
  'icinga:reschedule': {
    description: 'Reagenda um check no Icinga2',
    risk:        'none',
    params:      ['host', 'service?'],
    async execute({ host, service }) {
      if (!host) throw new Error('"host" é obrigatório para icinga:reschedule');
      const filter = service
        ? `host.name==%22${encodeURIComponent(host)}%22%26%26service.name==%22${encodeURIComponent(service)}%22`
        : `host.name==%22${encodeURIComponent(host)}%22`;
      const type = service ? 'Service' : 'Host';
      const resp = await fetch(
        `${ICINGA_API_URL}/v1/actions/reschedule-check?type=${type}&filter=${filter}`,
        {
          method: 'POST',
          headers: {
            Accept: 'application/json',
            Authorization: 'Basic ' + Buffer.from(`${ICINGA_API_USER}:${ICINGA_API_PASSWORD}`).toString('base64'),
          },
        }
      );
      return {
        success: resp.ok,
        output:  `icinga reschedule HTTP ${resp.status}`,
      };
    },
  },

  // Risco nulo — apenas verifica se o endpoint voltou a responder
  'http:verify': {
    description: 'Verifica se um endpoint HTTP está respondendo',
    risk:        'none',
    params:      ['url'],
    async execute({ url }) {
      validateUrl(url);
      const resp = await fetch(url, { signal: AbortSignal.timeout(10_000) });
      return {
        success: resp.ok,
        output:  `HTTP ${resp.status} em ${url}`,
      };
    },
  },

  // Risco baixo — inicia container que estava parado
  'docker:start': {
    description: 'Inicia um container Docker parado',
    risk:        'low',
    params:      ['container'],
    async execute({ container }) {
      validateContainerName(container);
      const { stdout, stderr } = await execFileAsync(
        'docker', ['start', container], { timeout: 30_000 }
      );
      return { success: true, output: (stdout || stderr || '').trim() };
    },
  },

  // Risco médio — reinicia container em execução
  'docker:restart': {
    description: 'Reinicia um container Docker',
    risk:        'medium',
    params:      ['container'],
    async execute({ container }) {
      validateContainerName(container);
      const { stdout, stderr } = await execFileAsync(
        'docker', ['restart', container], { timeout: 60_000 }
      );
      return { success: true, output: (stdout || stderr || '').trim() };
    },
  },
};

// ─── API pública ──────────────────────────────────────────────────────────────

function isActionAllowed(action) {
  return action in CATALOG;
}

/**
 * Decide se uma ação pode ser executada automaticamente.
 * Critérios:
 *   1. Ação existe no catálogo
 *   2. Score >= AUTO_THRESHOLD
 *   3. Severidade do incidente <= MAX_AUTO_SEVERITY
 */
function canAutoExecute(action, confidenceScore, incidentSeverity) {
  if (!isActionAllowed(action)) return false;
  if (confidenceScore < AUTO_THRESHOLD) return false;
  const sev = (incidentSeverity || 'unknown').toLowerCase();
  return (SEVERITY_ORDER[sev] ?? 0) <= (SEVERITY_ORDER[MAX_AUTO_SEVERITY] ?? 0);
}

/**
 * Executa uma ação do catálogo.
 * Retorna { success: boolean, output: string }.
 * Nunca lança exceção — erros são capturados no output.
 */
async function executeAction(action, params) {
  if (!isActionAllowed(action)) {
    return { success: false, output: `Ação "${action}" não existe no catálogo.` };
  }
  try {
    return await CATALOG[action].execute(params || {});
  } catch (e) {
    return { success: false, output: e.message };
  }
}

/** Descrição do catálogo para incluir no prompt da IA. */
function catalogDescription() {
  return Object.entries(CATALOG).map(([key, val]) => ({
    action:      key,
    description: val.description,
    risk:        val.risk,
    params:      val.params,
  }));
}

module.exports = {
  isActionAllowed,
  canAutoExecute,
  executeAction,
  catalogDescription,
  AUTO_THRESHOLD,
  MAX_AUTO_SEVERITY,
};
