'use strict';

const https = require('https');
const { log } = require('../utils/logger');

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
      res.on('end', () => {
        let json = null;
        try { json = raw ? JSON.parse(raw) : null; } catch (_) {}
        resolve({ ok: res.statusCode >= 200 && res.statusCode < 300, status: res.statusCode, raw, json });
      });
    });
    req.on('error', reject);
    req.setTimeout(10000, () => req.destroy(new Error('timeout')));
    if (payload) req.write(payload);
    req.end();
  });
}

function safeHostName(name) {
  return String(name || 'host-unknown').replace(/[^a-zA-Z0-9_.-]/g, '-').slice(0, 128);
}

function packageName(tenant) {
  const t = String(tenant?.tenant_id || 'default').replace(/[^a-zA-Z0-9_.-]/g, '-');
  const s = String(tenant?.site_id || 'default-site').replace(/[^a-zA-Z0-9_.-]/g, '-');
  const e = String(tenant?.edge_id || 'central').replace(/[^a-zA-Z0-9_.-]/g, '-');
  return `r-observe-${t}-${s}-${e}`;
}

function renderHostConfig(hosts) {
  const lines = [];
  for (const h of hosts) {
    const host = safeHostName(h.asset_name);
    const address = h.primary_ip || '127.0.0.1';
    lines.push(`object Host \"${host}\" {`);
    lines.push(`  import \"generic-host\"`);
    lines.push(`  address = \"${address}\"`);
    lines.push(`  display_name = \"${(h.display_name || host).replace(/"/g, '\\"')}\"`);
    lines.push(`  vars.discovered = true`);
    lines.push(`  vars.discovery_state = \"${h.lifecycle_state || 'approved'}\"`);
    lines.push('}');
    lines.push('');
  }
  return lines.join('\n');
}

async function stageConfigPackage(tenant, hosts) {
  const pkg = packageName(tenant);
  const stage = `run-${Date.now()}`;
  const fileContent = renderHostConfig(hosts);

  const candidates = [
    `/config/packages/${encodeURIComponent(pkg)}/stages/${encodeURIComponent(stage)}`,
    `/config/packages/${encodeURIComponent(pkg)}/${encodeURIComponent(stage)}`,
  ];

  let staged = null;
  for (const p of candidates) {
    const out = await request('POST', p, {
      files: {
        'conf.d/r-observe-discovery.conf': fileContent,
      },
    });
    if (out.ok) {
      staged = { pkg, stage, endpoint: p, status: out.status };
      break;
    }
  }

  return staged;
}

async function deployStage(pkg, stage) {
  const candidates = [
    `/config/packages/${encodeURIComponent(pkg)}/stages/${encodeURIComponent(stage)}`,
    `/config/stages/${encodeURIComponent(pkg)}/${encodeURIComponent(stage)}`,
  ];

  for (const p of candidates) {
    const out = await request('POST', p, { reload: true });
    if (out.ok) return { deployed: true, endpoint: p, status: out.status };
  }
  return { deployed: false };
}

async function listManagedHosts() {
  const out = await request('GET', '/objects/hosts?attrs=name&attrs=address&attrs=vars');
  if (!out.ok || !Array.isArray(out.json?.results)) return [];
  return out.json.results
    .map((r) => ({
      name: r.name,
      address: r.attrs?.address || null,
      discovered: !!r.attrs?.vars?.discovered,
    }))
    .filter((h) => h.discovered);
}

async function removeHost(name) {
  return request('DELETE', `/objects/hosts/${encodeURIComponent(name)}?cascade=1`);
}

async function reconcileHosts(discoveredHosts) {
  const existing = await listManagedHosts();
  const wanted = new Set(discoveredHosts.map((h) => safeHostName(h.asset_name)));
  const stale = existing.filter((h) => !wanted.has(h.name));
  let removed = 0;
  for (const s of stale) {
    const out = await removeHost(s.name);
    if (out.ok) removed++;
  }
  return { stale: stale.length, removed };
}

async function registerApprovedAsset(asset) {
  const name = safeHostName(asset.asset_name);
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

async function syncDiscoveredToIcinga({ tenant, assets }) {
  const approved = (assets || []).filter((a) => a.lifecycle_state === 'approved' || a.lifecycle_state === 'monitored');
  if (!approved.length) return { synced: 0, staged: false, deployed: false, reconcile: { stale: 0, removed: 0 } };

  const staged = await stageConfigPackage(tenant, approved);
  const deployed = staged ? await deployStage(staged.pkg, staged.stage) : { deployed: false };

  let synced = 0;
  for (const a of approved) {
    const out = await registerApprovedAsset(a);
    if (out.ok) synced++;
  }

  const reconcile = await reconcileHosts(approved);
  log('info', 'Icinga sync completed', {
    synced,
    staged: !!staged,
    deployed: !!deployed.deployed,
    stale: reconcile.stale,
    removed: reconcile.removed,
  });

  return { synced, staged: !!staged, deployed: !!deployed.deployed, reconcile, package: staged?.pkg || null, stage: staged?.stage || null };
}

module.exports = { registerApprovedAsset, syncDiscoveredToIcinga };
