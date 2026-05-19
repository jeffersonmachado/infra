'use strict';

function normalizePassiveEvent(evt) {
  if (!evt || !evt.type) return null;
  return {
    type: String(evt.type).toLowerCase(),
    source_ip: evt.source_ip || null,
    dest_ip: evt.dest_ip || null,
    hostname: evt.hostname || evt.http_host || evt.tls_sni || null,
    protocol: evt.protocol || null,
    payload: evt.payload || {},
    seen_at: new Date().toISOString(),
  };
}

module.exports = { normalizePassiveEvent };
