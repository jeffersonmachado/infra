'use strict';

const Docker = require('dockerode');
const { log } = require('../utils/logger');

async function discoverLocalDocker() {
  try {
    const docker = new Docker({ socketPath: '/var/run/docker.sock' });
    const list = await docker.listContainers({ all: true });
    return list.map((c) => ({
      container_id: c.Id.slice(0, 12),
      name: (c.Names?.[0] || '').replace(/^\//, ''),
      image: c.Image,
      state: c.State,
      status: c.Status,
      ports: c.Ports || [],
      source: 'docker-local',
    }));
  } catch (e) {
    log('warn', 'Docker local discovery failed', { err: e?.message, code: e?.code });
    return [];
  }
}

module.exports = { discoverLocalDocker };
