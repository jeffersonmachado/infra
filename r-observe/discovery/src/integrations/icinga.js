'use strict';

const https = require('https');

const ICINGA_API_URL = process.env.ICINGA_API_URL || 'https://observe-icinga2:5665';
const ICINGA_API_USER = process.env.ICINGA_API_USER || 'icingaweb2';
const ICINGA_API_PASSWORD = process.env.ICINGA_API_PASSWORD || '';

function request(method, path, body = null) {
  return new Promise((resolve, reject) => {
    if (!ICINGA_API_PASSWORD) return resolve({ ok: false, status: 0 });
    const parsed = new URL(`${ICINGA_API_URL}/v1${path}`);
    const auth = Buffer.from(`${ICINGA_API_USER}:${ICINGA_API_PASSWORD}`).toString('base64');
    const payload = body ? JSON.stringify(body) : null;
    const req = https.request({
      hostname: parsed.hostname,
      port: parsed.port || 5665,
      path: parsed.pathname + parsed.search,
      method,
      rejectUnauthorized: false,
      headers: {
        Accept: 'application/json',
        Authorization: `Basic ${auth}`,
        ...(payload ? { 'Content-Type': 'application/json', 'Content-Length': Buffer.byteLength(payload) } : {}),
      },
    }, (res) => {
      let raw = '';
      res.on('data', (d) => { raw += d; });
      res.on('end', () => resolve({ ok: res.statusCode >= 200 && res.statusCode < 300, status: res.statusCode, raw }));
    });
    req.on('error', reject);
    req.setTimeout(10000, () => req.destroy(new Error('timeout')));
    if (payload) req.write(payload);
    req.end();
  });
}

async function registerApprovedAsset(asset) {
  const name = asset.asset_name;
  const address = asset.primary_ip;
  return request('PUT', `/objects/hosts/${encodeURIComponent(name)}`, {
    templates: ['generic-host'],
    attrs: {
      address,
      display_name: asset.display_name || name,
      vars: { discovered: true, discovery_state: asset.lifecycle_state || 'approved' },
    },
  });
}

module.exports = { registerApprovedAsset };
