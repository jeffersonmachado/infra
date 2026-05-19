'use strict';

function buildTopologyEdges(runId, assets) {
  const edges = [];
  const uniq = new Set();

  const pushEdge = (edge) => {
    const key = `${edge.from_asset_id}|${edge.to_asset_ref}|${edge.edge_type}|${edge.protocol}`;
    if (uniq.has(key)) return;
    uniq.add(key);
    edges.push(edge);
  };

  for (const asset of assets) {
    if (!Array.isArray(asset.services)) continue;
    for (const svc of asset.services) {
      if (svc.container_id) {
        pushEdge({
          run_id: runId,
          from_asset_id: asset.id,
          to_asset_ref: `container:${svc.container_id}`,
          edge_type: 'container_service',
          protocol: svc.protocol || 'tcp',
          source: 'active-discovery',
        });
      }

      if (svc.dependency_target) {
        pushEdge({
          run_id: runId,
          from_asset_id: asset.id,
          to_asset_ref: svc.dependency_target,
          edge_type: 'service_dependency',
          protocol: svc.protocol || 'tcp',
          source: 'active-discovery',
        });
      }

      // host -> service
      pushEdge({
        run_id: runId,
        from_asset_id: asset.id,
        to_asset_ref: `${asset.primary_ip || 'unknown'}:${svc.port}`,
        edge_type: 'host_service',
        protocol: svc.protocol || 'tcp',
        source: 'active-discovery',
      });

      // service -> exporter
      if ([9100, 9104, 9108, 9115, 9117, 9121, 9187, 9256, 9419, 9090, 9091].includes(svc.port)) {
        pushEdge({
          run_id: runId,
          from_asset_id: asset.id,
          to_asset_ref: `exporter:${svc.port}`,
          edge_type: 'service_exporter',
          protocol: svc.protocol || 'tcp',
          source: 'active-discovery',
        });
      }

      // service -> database / dns
      if ([3306, 5432].includes(svc.port)) {
        pushEdge({
          run_id: runId,
          from_asset_id: asset.id,
          to_asset_ref: `database:${svc.port}`,
          edge_type: 'service_database',
          protocol: svc.protocol || 'tcp',
          source: 'active-discovery',
        });
      }
      if (svc.port === 53) {
        pushEdge({
          run_id: runId,
          from_asset_id: asset.id,
          to_asset_ref: 'dns:53',
          edge_type: 'service_dns',
          protocol: svc.protocol || 'udp',
          source: 'active-discovery',
        });
      }
    }
  }
  return edges;
}

module.exports = { buildTopologyEdges };
