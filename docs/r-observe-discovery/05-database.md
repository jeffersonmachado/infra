# Modelo de dados Discovery

Tabelas novas criadas na migration 004:

- `observe_discovery_policies`
- `observe_discovery_targets`
- `observe_discovery_runs`
- `observe_assets`
- `observe_asset_interfaces`
- `observe_asset_services`
- `observe_discovery_findings`
- `observe_service_fingerprints`
- `observe_topology_edges`
- `observe_dependencies`
- `observe_asset_changes`
- `observe_asset_history`

## Separacao de responsabilidades

- `observe_hosts`/`observe_services`: monitoramento legado e compatibilidade.
- `observe_assets` e demais: inventario discovery enterprise.

## Estados de ciclo de vida do asset

- `discovered`
- `approved`
- `monitored`
- `ignored`
- `quarantined`
- `disappeared`
