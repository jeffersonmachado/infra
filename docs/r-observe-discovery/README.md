# r-observe-discovery

Engine enterprise de discovery continuo para R-Observe.

## Escopo implementado neste ciclo

- Servico dedicado `r-observe-discovery`
- APIs discovery completas
- Inventario enterprise e drift baseline
- Fingerprinting heuristico inicial
- Topologia operacional inicial
- Integracao com Redis event bus
- Integracao com Prometheus HTTP SD/File SD
- Integracao com Icinga (estado aprovado/monitorado)
- UI React inicial em `/observe/discovery`
- Testes unitarios/integracao/simulacao basicos

## Operacao

- Subir stack: `npm run observe:up:full`
- Executar discovery engine: `npm run observe:discover:engine:scan`
- Consultar runs: `npm run observe:discover:engine:runs`
- Consultar assets: `npm run observe:discover:engine:assets`

## Documentos

- `00-analysis-current-state.md`
- `01-architecture.md`
- `02-api.md`
- `03-events.md`
- `04-security.md`
- `05-database.md`
- `06-edge-agents.md`
