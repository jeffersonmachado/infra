'use strict';

const LOG_LEVEL = process.env.LOG_LEVEL || 'info';

function log(level, msg, extra = {}) {
  if (level === 'debug' && LOG_LEVEL !== 'debug') return;
  process.stdout.write(JSON.stringify({ level, service: 'r-observe-discovery', msg, ts: new Date().toISOString(), ...extra }) + '\n');
}

module.exports = { log };
