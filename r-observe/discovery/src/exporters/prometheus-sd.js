'use strict';

const fs = require('fs/promises');
const path = require('path');

const DEFAULT_SD_PATH = process.env.DISCOVERY_SD_FILE || '/app/data/prometheus-discovery.json';

function toPromSdGroups(assets) {
  const groups = [];
  for (const a of assets) {
    if (!Array.isArray(a.services)) continue;
    for (const s of a.services) {
      if (!s.exporter_target) continue;
      groups.push({
        targets: [s.exporter_target],
        labels: {
          job: s.job || 'discovered',
          tenant_id: a.tenant_id,
          site_id: a.site_id,
          edge_id: a.edge_id || 'central',
          discovered: 'true',
          asset: a.asset_name,
        },
      });
    }
  }
  return groups;
}

async function writeFileSd(assets) {
  const groups = toPromSdGroups(assets);
  await fs.mkdir(path.dirname(DEFAULT_SD_PATH), { recursive: true });
  await fs.writeFile(DEFAULT_SD_PATH, JSON.stringify(groups, null, 2));
  return { path: DEFAULT_SD_PATH, total: groups.length };
}

module.exports = { writeFileSd, toPromSdGroups };
