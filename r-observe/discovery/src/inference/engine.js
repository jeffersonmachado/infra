'use strict';

const { lookupMacVendor } = require('../fingerprint/oui');
const { resolveFromMdnsServices, lookupSsdpServer } = require('../fingerprint/mdns-db');
const registry = require('./registry/inference-registry.json');

const UNKNOWN_VENDOR = 'Não identificado';
const UNKNOWN_PRODUCT = 'Sem sinal de serviço';
const GENERIC_PRODUCTS = new Set([
  'HTTP Service',
  'HTTPS Service',
  'RTSP Service',
  'SIP Endpoint',
  'Database Service',
  'Observability Service',
  'Network Device',
  'IoT Device',
  'Host',
]);

const PORT_HINTS = registry.port_hints || {};
const TEXT_PATTERNS = (registry.text_patterns || []).map((p) => ({ ...p, re: new RegExp(p.pattern, p.flags || '') }));
const INFERENCE_REGISTRY = registry.rules || [];
const THRESHOLDS = registry.thresholds || {};

function nowIso() {
  return new Date().toISOString();
}

function clamp(n, min = 0, max = 1) {
  return Math.max(min, Math.min(max, n));
}

function makeEvidence(type, source, confidence, raw, normalized) {
  return {
    evidence_type: type,
    evidence_source: source,
    confidence: clamp(confidence),
    raw_data: raw ?? null,
    normalized_data: normalized || {},
    timestamp: nowIso(),
  };
}

function asTextParts(input) {
  return [
    ['http_header', 'active-http', input.http_server],
    ['http_title', 'active-http', input.http_title],
    ['snmp_sysdescr', 'active-snmp', input.snmp_sysdescr],
    ['tls_certificate', 'active-tls', input.tls_subject],
    ['tls_certificate', 'active-tls', input.tls_issuer],
    ['ssh_banner', 'active-ssh', input.ssh_banner],
    ['service_banner', 'active-smtp', input.smtp_banner],
    ['service_banner', 'active-sip', input.sip_user_agent || input.sip_probe?.user_agent || input.sip_probe?.server],
    ['service_banner', 'active-rtsp', input.rtsp_banner || input.rtsp_probe?.server],
    ['reverse_dns', 'dns', input.hostname],
    ['onvif_device_info', 'active-onvif', input.onvif_model || input.onvif_probe?.model || input.onvif_probe?.manufacturer],
  ].filter(([, , value]) => value);
}

function patternSignals(value) {
  const out = [];
  for (const pattern of TEXT_PATTERNS) {
    if (pattern.re.test(String(value || ''))) out.push({
      vendor: pattern.vendor || null,
      product: pattern.product,
      technology: pattern.technology,
      category: pattern.category,
    });
  }
  return out;
}

function extractTopologyEvidence(input) {
  const topology = input.topology || {};
  const evidence = [];
  const flags = [];
  if (topology.poe === true || topology.power === 'poe') flags.push('poe');
  if (topology.edge_port === true || topology.port_role === 'edge') flags.push('edge_port');
  if (String(topology.vlan_role || topology.vlan || '').toLowerCase().includes('voice')) flags.push('voice_vlan');
  if (topology.gateway === true) flags.push('gateway');
  if (topology.uplink === true || topology.port_role === 'uplink') flags.push('uplink');
  if (flags.length) {
    evidence.push(makeEvidence('topology_context', 'topology', 0.68, topology, { topology_flags: flags }));
  }
  for (const n of topology.lldp_neighbors || []) {
    evidence.push(makeEvidence('lldp_neighbor', 'topology-lldp', 0.72, n, { neighbor: n, topology_flags: flags }));
  }
  return evidence;
}

function extractHistoricalEvidence(input) {
  const history = input.history || input.historical || null;
  if (!history) return [];
  const stable = [];
  if (history.asset_key_match || history.mac_match) stable.push('asset_key');
  if (history.tls_sha256_match || history.cert_match) stable.push('certificate');
  if (history.fingerprint_match) stable.push('fingerprint');
  if (history.previous_asset_type || history.previous_technology) stable.push('previous_classification');
  if (!stable.length) return [];
  const age = Number(history.age_days ?? history.last_seen_days ?? 0);
  const decay = clamp(1 - (age / 90), 0.25, 1);
  return [makeEvidence('historical_identity', 'asset-history', 0.78 * decay, history, {
    stable_identifiers: stable,
    previous_asset_type: history.previous_asset_type || null,
    previous_technology: history.previous_technology || null,
    confidence_decay: Number((1 - decay).toFixed(3)),
  })];
}

function extractPassiveEvidence(input) {
  const events = input.passive_events || input.passive || [];
  const list = Array.isArray(events) ? events : [events];
  const evidence = [];
  for (const evt of list.filter(Boolean)) {
    const type = String(evt.type || evt.source || 'passive_signal').toLowerCase();
    const text = [evt.hostname, evt.protocol, evt.message, evt.payload?.message, evt.payload?.raw].filter(Boolean).join(' ');
    const signals = patternSignals(text);
    const services = evt.mdns_services || evt.payload?.mdns_services || [];
    for (const svc of services) {
      const match = resolveFromMdnsServices([svc]);
      if (match) evidence.push(makeEvidence('mdns_service', 'passive-mdns', 0.82, svc, match));
    }
    if (type.includes('mdns')) evidence.push(makeEvidence('passive_behavior', 'passive-mdns', 0.55, evt, { signals }));
    if (type.includes('ssdp')) evidence.push(makeEvidence('ssdp_response', 'passive-ssdp', 0.72, evt, { signals }));
    if (type.includes('dhcp')) evidence.push(makeEvidence('passive_behavior', 'passive-dhcp', 0.55, evt, { hostname: evt.hostname || null, signals }));
    if (type.includes('arp')) evidence.push(makeEvidence('passive_behavior', 'passive-arp', 0.5, evt, { mac: evt.mac || null }));
  }
  return evidence;
}

function extractEvidence(input) {
  const evidence = [];
  const ports = [...new Set(input.open_ports || [])].sort((a, b) => a - b);

  for (const port of ports) {
    const hint = PORT_HINTS[port] || { technology: 'unknown', category: 'unknown', product: `port-${port}` };
    const protocol = [53, 67, 68, 123, 161, 5060].includes(port) ? 'udp' : 'tcp';
    evidence.push(makeEvidence(protocol === 'udp' ? 'udp_open_port' : 'tcp_open_port', 'active-scan', 0.12, { port }, { port, protocol, signal_strength: 'weak', ...hint }));
  }

  for (const [type, source, value] of asTextParts(input)) {
    const signals = patternSignals(value);
    evidence.push(makeEvidence(type, source, signals.length ? 0.72 : 0.5, value, { signals }));
    for (const signal of signals) evidence.push(makeEvidence('text_fingerprint', source, 0.66, value, signal));
  }

  if (input.favicon_hash) evidence.push(makeEvidence('favicon_hash', 'active-http', 0.62, input.favicon_hash, { hash: input.favicon_hash }));
  if (input.sip_probe?.responded) {
    evidence.push(makeEvidence('sip_options_response', 'active-sip', 0.82, input.sip_probe, { technology: 'sip', product: 'SIP Endpoint', status: input.sip_probe.status || null }));
  }
  if (input.rtsp_probe?.responded) {
    evidence.push(makeEvidence('rtsp_response', 'active-rtsp', 0.82, input.rtsp_probe, { technology: 'rtsp', product: input.rtsp_probe.server || 'RTSP Service', status: input.rtsp_probe.status || null }));
  }
  if (input.onvif_probe?.responded) {
    const product = input.onvif_probe.model || input.onvif_model || 'ONVIF Device';
    evidence.push(makeEvidence('onvif_device_info', 'active-onvif', input.onvif_probe.device_info ? 0.86 : 0.7, input.onvif_probe, { technology: 'onvif', product, vendor: input.onvif_probe.manufacturer || null }));
  }
  if (input.tls_sha256) evidence.push(makeEvidence('tls_certificate', 'active-tls', 0.66, input.tls_sha256, { sha256: input.tls_sha256 }));

  const mdnsMatch = resolveFromMdnsServices(input.mdns_services || []);
  if (mdnsMatch) evidence.push(makeEvidence('mdns_service', 'passive-mdns', 0.84, input.mdns_services, mdnsMatch));

  const ssdpMatch = lookupSsdpServer(input.http_server || input.ssdp_server || '');
  if (ssdpMatch) evidence.push(makeEvidence('ssdp_response', 'passive-ssdp', 0.7, input.http_server || input.ssdp_server, ssdpMatch));

  const macVendor = input.mac ? lookupMacVendor(input.mac) : null;
  const explicitOuiVendor = input.mac_oui_vendor || input.oui_vendor || null;
  const txtVendor = input.txt_manufacturer || null;
  const vendor = macVendor || explicitOuiVendor || txtVendor;
  if (vendor) evidence.push(makeEvidence('vendor_identity', macVendor ? 'oui-database' : 'metadata', macVendor ? 0.74 : 0.62, input.mac || input.mac_oui || vendor, { vendor }));
  if (input.txt_model) evidence.push(makeEvidence('text_fingerprint', 'metadata-txt', 0.68, input.txt_model, { product: input.txt_model }));

  evidence.push(...extractTopologyEvidence(input));
  evidence.push(...extractHistoricalEvidence(input));
  evidence.push(...extractPassiveEvidence(input));

  if (!evidence.length) evidence.push(makeEvidence('minimal_signal', 'discovery', 0.05, null, {}));
  return evidence;
}

function evidenceTechnologies(evidence) {
  const values = new Set();
  const add = (v) => {
    if (!v) return;
    if (Array.isArray(v)) v.forEach(add);
    else values.add(String(v).toLowerCase());
  };
  for (const ev of evidence) {
    const n = ev.normalized_data || {};
    add(n.technology);
    add(n.technologies);
    for (const sig of n.signals || []) add(sig.technology);
    for (const flag of n.topology_flags || []) add(flag);
    add(n.previous_technology);
  }
  return values;
}

function scoreRule(rule, evidence) {
  const techs = evidenceTechnologies(evidence);
  let score = 0;
  let count = 0;
  const matched = [];
  for (const ev of evidence) {
    const base = rule.weights[ev.evidence_type] || 0;
    if (!base) continue;
    const n = ev.normalized_data || {};
    const evTechs = [n.technology, ...(n.signals || []).map((s) => s.technology), n.previous_technology].filter(Boolean).map((v) => String(v).toLowerCase());
    const techMatch = evTechs.some((t) => rule.technologies.includes(t)) || (n.topology_flags || []).some((f) => (rule.topology || []).includes(f));
    const genericMatch = ['historical_identity', 'vendor_identity', 'topology_context', 'lldp_neighbor'].includes(ev.evidence_type);
    if (!techMatch && !genericMatch) continue;
    score += base * ev.confidence;
    count++;
    matched.push(ev);
  }
  for (const t of rule.technologies) {
    if (techs.has(t)) score += 0.06;
  }
  return { score: clamp(score, 0, 0.98), count, evidence: matched };
}

function strongestSignal(evidence, field, preferredTechnologies = []) {
  let best = null;
  const preferred = new Set((preferredTechnologies || []).map((item) => String(item).toLowerCase()));
  const rank = (candidate) => {
    let value = candidate.confidence;
    const technology = String(candidate.technology || '').toLowerCase();
    if (preferred.has(technology)) value += 0.2;
    if (field === 'product' && GENERIC_PRODUCTS.has(candidate.value)) value -= 0.12;
    return value;
  };
  for (const ev of evidence) {
    const candidates = [];
    if (ev.normalized_data?.[field]) candidates.push({ value: ev.normalized_data[field], confidence: ev.confidence, technology: ev.normalized_data.technology });
    for (const sig of ev.normalized_data?.signals || []) {
      if (sig[field]) candidates.push({ value: sig[field], confidence: ev.confidence, technology: sig.technology });
    }
    for (const c of candidates) {
      const cRank = rank(c);
      if (!best || cRank > best.rank) best = { ...c, rank: cRank };
    }
  }
  return best?.value || null;
}

function findConflicts(scored) {
  const top = scored[0];
  if (!top) return [];
  return scored
    .slice(1)
    .filter((item) => item.score >= (THRESHOLDS.conflict ?? 0.45) && item.rule.category !== top.rule.category)
    .map((item) => ({
      asset_type: item.rule.asset_type,
      category: item.rule.category,
      confidence_score: Number(item.score.toFixed(3)),
    }));
}

function confidenceLevel(score) {
  if (score >= (THRESHOLDS.auto_approve ?? 0.85)) return 'high';
  if (score >= (THRESHOLDS.medium ?? 0.6)) return 'medium';
  if (score >= (THRESHOLDS.low ?? 0.35)) return 'low';
  return 'insufficient';
}

function governanceDecision(score, conflicts) {
  if (conflicts.length > 0) return 'REVIEW_PENDING';
  if (score >= (THRESHOLDS.auto_approve ?? 0.85)) return 'APPROVED';
  if (score >= (THRESHOLDS.medium ?? 0.6)) return 'REVIEW_OPTIONAL';
  return 'REVIEW_PENDING';
}

function inferAsset(input) {
  const evidence = extractEvidence(input || {});
  const scored = INFERENCE_REGISTRY
    .map((rule) => ({ rule, ...scoreRule(rule, evidence) }))
    .filter((item) => item.count > 0)
    .sort((a, b) => b.score - a.score);

  const top = scored[0] || { rule: { asset_type: 'host', product: UNKNOWN_PRODUCT, category: 'unknown', technology: 'unknown', threshold: 1 }, score: 0, evidence: [] };
  const conflicts = findConflicts(scored);
  const classified = top.score >= top.rule.threshold && top.evidence.length >= (THRESHOLDS.minimum_evidence_to_classify ?? 2) && conflicts.length === 0;
  const score = classified ? top.score : Math.min(top.score, 0.49);
  const vendor = strongestSignal(evidence, 'vendor') || UNKNOWN_VENDOR;
  const productSignal = strongestSignal(evidence, 'product', top.rule.technologies || []);
  const product = classified ? (productSignal || top.rule.product) : UNKNOWN_PRODUCT;
  const technology = classified ? (strongestSignal(evidence, 'technology', top.rule.technologies || []) || top.rule.technology) : 'unknown';
  const category = classified ? (strongestSignal(evidence, 'category', top.rule.technologies || []) || top.rule.category) : 'unknown';

  return {
    asset_type: classified ? top.rule.asset_type : 'host',
    vendor,
    product,
    service: product,
    category,
    technology,
    confidence_score: Number(score.toFixed(3)),
    confidence_level: confidenceLevel(score),
    evidence_count: evidence.length,
    conflicting_evidence: conflicts.length,
    conflicts,
    governance: {
      decision: governanceDecision(score, conflicts),
      auto_approve: governanceDecision(score, conflicts) === 'APPROVED',
    },
    evidence,
    matched_rule: classified ? top.rule.asset_type : null,
    candidate_scores: scored.slice(0, 5).map((item) => ({
      asset_type: item.rule.asset_type,
      confidence_score: Number(item.score.toFixed(3)),
      evidence_count: item.evidence.length,
    })),
  };
}

module.exports = {
  inferAsset,
  extractEvidence,
  confidenceLevel,
  governanceDecision,
  INFERENCE_REGISTRY,
  registry,
};
