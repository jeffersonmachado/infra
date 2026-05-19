'use strict';

const PROFILES = {
  safe: {
    maxConcurrency: 10,
    ports: [22, 80, 443, 25, 53, 993],
    timeoutMs: 1200,
    allowUdp: false,
  },
  balanced: {
    maxConcurrency: 30,
    ports: [22, 25, 53, 80, 110, 143, 443, 465, 587, 993, 995, 554, 5060, 8080, 9100],
    timeoutMs: 1500,
    allowUdp: true,
  },
  aggressive: {
    maxConcurrency: 80,
    ports: [21, 22, 23, 25, 53, 67, 68, 80, 110, 123, 135, 137, 138, 139, 143, 161, 389, 443, 445, 465, 554, 587, 993, 995, 1723, 1883, 3306, 3389, 5060, 5432, 6379, 8080, 8081, 8443, 9100],
    timeoutMs: 1800,
    allowUdp: true,
  },
};

function resolveProfile(name) {
  return PROFILES[name] || PROFILES.safe;
}

module.exports = { PROFILES, resolveProfile };
