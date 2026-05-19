-- Migration 005: Dedupe/consistency indexes for Discovery Engine

DO $$
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM pg_constraint WHERE conname = 'uq_observe_topology_edges_dedupe'
  ) THEN
    ALTER TABLE observe_topology_edges
      ADD CONSTRAINT uq_observe_topology_edges_dedupe
      UNIQUE (tenant_id, site_id, edge_id, from_asset_id, to_asset_ref, edge_type, protocol);
  END IF;
EXCEPTION WHEN undefined_table THEN
  -- tabela ainda nao criada
  NULL;
END $$;

CREATE INDEX IF NOT EXISTS idx_observe_dependencies_scope
  ON observe_dependencies (tenant_id, site_id, edge_id, first_seen_at DESC);

CREATE INDEX IF NOT EXISTS idx_observe_discovery_targets_addr
  ON observe_discovery_targets (tenant_id, site_id, edge_id, address);
