'use strict';

const OUI_VENDOR = {
  '00:1B:44': 'MikroTik',
  '24:A4:3C': 'Intelbras',
  '3C:5A:B4': 'Hikvision',
  'F0:AD:4E': 'Grandstream',
  'AC:DE:48': 'ControlID',
};

function inferFromServicePorts(ports) {
  const p = new Set(ports || []);
  if (p.has(6379)) return { service: 'Redis', technology: 'redis' };
  if (p.has(5432)) return { service: 'PostgreSQL', technology: 'postgres' };
  if (p.has(3306)) return { service: 'MariaDB', technology: 'mysql' };
  if (p.has(9090)) return { service: 'Prometheus', technology: 'prometheus' };
  if (p.has(3000)) return { service: 'Grafana', technology: 'grafana' };
  if (p.has(5000)) return { service: 'Docker Registry', technology: 'docker-registry' };
  if (p.has(5060) && p.has(8088)) return { service: 'Asterisk', technology: 'asterisk' };
  if (p.has(53)) return { service: 'PowerDNS', technology: 'dns' };
  if (p.has(5060)) return { service: 'SIP Endpoint', technology: 'sip' };
  if (p.has(554)) return { service: 'RTSP Camera', technology: 'rtsp' };
  if (p.has(80) || p.has(443)) return { service: 'HTTP Service', technology: 'web' };
  return { service: 'Unknown', technology: 'unknown' };
}

function inferFromSignals(input) {
  const server = String(input.http_server || '').toLowerCase();
  const hints = [
    server,
    String(input.snmp_sysdescr || '').toLowerCase(),
    String(input.tls_subject || '').toLowerCase(),
    String(input.hostname || '').toLowerCase(),
    String(input.onvif_model || '').toLowerCase(),
  ].join(' ');

  if (hints.includes('apache')) return { service: 'Apache', technology: 'apache' };
  if (hints.includes('nginx')) return { service: 'Nginx', technology: 'nginx' };
  if (hints.includes('controlid') || hints.includes('faceid')) return { service: 'ControlID FaceID', technology: 'controlid-faceid' };
  if (hints.includes('grandstream')) return { service: 'Grandstream', technology: 'grandstream' };
  if (hints.includes('mikrotik')) return { service: 'Mikrotik', technology: 'mikrotik' };
  if (hints.includes('hikvision')) return { service: 'Hikvision', technology: 'hikvision' };
  if (hints.includes('intelbras')) return { service: 'Intelbras', technology: 'intelbras' };
  return null;
}

function inferCategory(service) {
  if (['Apache', 'Nginx'].includes(service)) return 'web';
  if (['PostgreSQL', 'MariaDB'].includes(service)) return 'database';
  if (['Redis'].includes(service)) return 'cache';
  if (['Prometheus', 'Grafana'].includes(service)) return 'observability';
  if (['Asterisk', 'Grandstream'].includes(service)) return 'voice';
  if (['PowerDNS'].includes(service)) return 'dns';
  if (['Docker Registry'].includes(service)) return 'registry';
  if (['ControlID FaceID', 'Hikvision', 'Intelbras'].includes(service)) return 'iot';
  return 'unknown';
}

function inferRisk(service, metadata) {
  if (service === 'RTSP Camera' || service === 'SIP Endpoint') return 'high';
  if (service === 'MariaDB' || service === 'PostgreSQL') return 'high';
  if ((metadata?.hostname || '').includes('core') || (metadata?.hostname || '').includes('gw')) return 'high';
  return 'medium';
}

function fingerprintAsset(input) {
  const ports = input.open_ports || [];
  const signalGuess = inferFromSignals(input);
  const guessed = signalGuess || inferFromServicePorts(ports);
  const vendor = input.mac_oui ? OUI_VENDOR[input.mac_oui] || 'Unknown' : 'Unknown';
  const evidences = [];

  if (vendor !== 'Unknown') evidences.push(`oui:${input.mac_oui}:${vendor}`);
  if (ports.length) evidences.push(`ports:${ports.sort((a, b) => a - b).join(',')}`);
  if (input.http_server) evidences.push(`http_server:${input.http_server}`);
  if (input.tls_subject) evidences.push(`tls_subject:${input.tls_subject}`);
  if (input.favicon_hash) evidences.push(`favicon_hash:${input.favicon_hash}`);
  if (input.sip_user_agent) evidences.push(`sip_user_agent:${input.sip_user_agent}`);
  if (input.rtsp_banner) evidences.push(`rtsp:${input.rtsp_banner}`);
  if (input.onvif_model) evidences.push(`onvif:${input.onvif_model}`);
  if (input.snmp_sysdescr) evidences.push(`snmp_sysdescr:${input.snmp_sysdescr}`);
  if (evidences.length === 0) evidences.push('signal:minimal');

  let confidence = 0.35;
  if (guessed.service !== 'Unknown') confidence += 0.25;
  if (vendor !== 'Unknown') confidence += 0.2;
  if (input.http_server || input.tls_subject || input.snmp_sysdescr) confidence += 0.15;
  if (evidences.length >= 4) confidence += 0.05;
  confidence = Math.max(0, Math.min(0.98, confidence));

  return {
    vendor,
    product: guessed.service,
    category: inferCategory(guessed.service),
    service: guessed.service,
    technology: guessed.technology,
    firmware_hint: input.snmp_sysdescr || null,
    os_hint: input.os_hint || null,
    criticality: inferRisk(guessed.service, { hostname: input.hostname }),
    confidence,
    evidence: evidences,
    evidences,
    raw_signals: {
      snmp_sysdescr: input.snmp_sysdescr || null,
      tls_subject: input.tls_subject || null,
      http_server: input.http_server || null,
      dns_name: input.hostname || null,
      favicon_hash: input.favicon_hash || null,
      sip_user_agent: input.sip_user_agent || null,
      rtsp_banner: input.rtsp_banner || null,
      onvif_model: input.onvif_model || null,
    },
  };
}

module.exports = { fingerprintAsset };
