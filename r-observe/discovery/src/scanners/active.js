'use strict';

const { reverseDns, tcpConnect, httpFingerprint, tlsCertificateFingerprint, faviconHash } = require('../utils/network');
const { resolveProfile } = require('../policies/profiles');

const WELL_KNOWN = [22, 25, 53, 80, 443, 554, 5060, 993, 3306, 5432, 9090, 3000, 9100];

async function activeScanTarget(target, policy) {
  const profile = resolveProfile(policy.profile);
  const ports = [...new Set([...(profile.ports || []), ...WELL_KNOWN])];
  const open = [];
  const hostTimeoutMs = policy.host_timeout_ms || 12000;
  const started = Date.now();

  for (const port of ports) {
    if (Date.now() - started > hostTimeoutMs) break;
    // scan sequencial por alvo para manter perfil seguro por padrão
    const ok = await tcpConnect(target.address, port, profile.timeoutMs);
    if (ok) open.push(port);
  }

  const hostname = await reverseDns(target.address);
  const http = open.includes(80) ? await httpFingerprint(`${target.address}:80`, false) : null;
  const https = open.includes(443) ? await httpFingerprint(`${target.address}:443`, true) : null;
  const tlsCert = open.includes(443) ? await tlsCertificateFingerprint(target.address, 443, Math.min(3000, hostTimeoutMs)) : null;
  const favHash = open.includes(80)
    ? await faviconHash(target.address, 80, false)
    : open.includes(443)
    ? await faviconHash(target.address, 443, true)
    : null;

  // Parsers baseline de sinais passivos/ativos simulados para SIP/RTSP/ONVIF/SNMP.
  const sipUserAgent = open.includes(5060) ? 'SIP-UA-unknown' : null;
  const rtspBanner = open.includes(554) ? 'RTSP/1.0' : null;
  const onvifModel = (open.includes(80) || open.includes(443)) && open.includes(554) ? 'ONVIF-possible' : null;
  const snmpSysDescr = open.includes(161) ? 'SNMP-available' : null;

  return {
    address: target.address,
    hostname,
    open_ports: open,
    http_server: http?.headers?.server || null,
    tls_subject: tlsCert?.subject || null,
    tls_issuer: tlsCert?.issuer || null,
    tls_sha256: tlsCert?.sha256 || null,
    favicon_hash: favHash,
    sip_user_agent: sipUserAgent,
    rtsp_banner: rtspBanner,
    onvif_model: onvifModel,
    snmp_sysdescr: snmpSysDescr,
    protocols_detected: {
      arp: false,
      icmp: true,
      tcp_syn: false,
      tcp_connect: true,
      udp_controlled: !!policy.allow_udp,
      reverse_dns: !!hostname,
      snmp: open.includes(161),
      sip: open.includes(5060),
      rtsp: open.includes(554),
      onvif: open.includes(80) || open.includes(443),
      mdns: false,
      ssdp: false,
      netbios: open.includes(137) || open.includes(139),
      docker_local: false,
    },
  };
}

module.exports = { activeScanTarget };
