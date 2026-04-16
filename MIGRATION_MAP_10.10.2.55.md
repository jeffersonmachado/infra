# Mapeamento de Migração do Servidor 10.10.2.55

## Resumo Executivo

O host `10.10.2.55` não é apenas um Apache simples. Ele exerce quatro papéis ao mesmo tempo:

1. Frontend HTTP legado para múltiplos domínios.
2. Terminador TLS interno em `127.0.0.1:443` atrás de `sslh` no IP `10.10.2.60:443`.
3. Reverse proxy para vários serviços externos, principalmente em `10.10.2.30` e `10.10.2.22`.
4. Hospedagem local de um Joomla legado em `/var/www/html/results`.

Para migrar isso com segurança para container, o correto é separar:

- workloads de aplicação e proxy reverso, que podem ir para containers;
- serviços de sistema, rede e proteção, que devem continuar fora dos containers ou serem reprojetados.

## Identidade do Host

- Hostname: `srvhttp.results.intranet`
- SO: `CentOS Linux 7 (Core)`
- Kernel: `3.10.0-1127.8.2.el7.x86_64`
- Uptime observado: `122 dias`
- IPs configurados:
  - `10.10.2.55`
  - `10.10.2.60`
  - `10.10.2.61`
  - `10.10.2.62`

## Serviços em Execução

Serviços relevantes observados como `running`:

- `httpd`
- `sshd`
- `sslh`
- `fail2ban`
- `firewalld`
- `chronyd`
- `bacula-fd`
- `rpcbind`
- `postfix`
- `crond`
- `rsyslog`
- `NetworkManager`

Serviços habilitados que afetam a migração:

- `snapd` habilitado, porém parado
- `kdump` habilitado, porém com falha

## Portas e Bindings Locais

Bindings relevantes observados:

- `*:22` via `sshd`
- `*:2222` via `sshd`
- `*:80` via `httpd`
- `127.0.0.1:443` via `httpd`
- `10.10.2.60:443` via `sslh`
- `*:9102` via `bacula-fd`
- `*:111` e `*:911` via `rpcbind`
- `127.0.0.1:25` via `postfix`

## Componentes com Papel na Migração

### 1. Apache legado

Papel:

- expõe HTTP público em `*:80`;
- expõe HTTPS apenas localmente em `127.0.0.1:443`;
- depende de `sslh` para expor TLS no IP `10.10.2.60`;
- funciona como reverse proxy para diversos backends;
- também serve conteúdo local do Joomla.

Características técnicas observadas:

- `Apache/2.4.6 (CentOS)`
- `OpenSSL/1.0.2k-fips`
- `PHP 7.2.34`
- MPM `prefork`
- muitos módulos carregados, incluindo `php7_module`, `ssl_module`, `proxy_*`, `cgi_module`, `lua_module`, `security2_module`, `info_module`, `status_module`

#### Módulos Apache observados

Principais módulos carregados no host:

- base HTTP e diretórios:
  - `alias_module`
  - `dir_module`
  - `mime_module`
  - `negotiation_module`
  - `autoindex_module`
  - `headers_module`
  - `rewrite_module`
  - `deflate_module`
  - `expires_module`

- autenticação e autorização:
  - `auth_basic_module`
  - `auth_digest_module`
  - `authn_*`
  - `authz_*`
  - `access_compat_module`

- proxy e balanceamento:
  - `proxy_module`
  - `proxy_http_module`
  - `proxy_wstunnel_module`
  - `proxy_connect_module`
  - `proxy_ftp_module`
  - `proxy_scgi_module`
  - `proxy_balancer_module`
  - `lbmethod_byrequests_module`
  - `lbmethod_bytraffic_module`
  - `lbmethod_bybusyness_module`
  - `lbmethod_heartbeat_module`

- TLS e cache:
  - `ssl_module`
  - `socache_dbm_module`
  - `socache_memcache_module`
  - `socache_shmcb_module`

- execução local/legado:
  - `php7_module`
  - `cgi_module`
  - `suexec_module`
  - `userdir_module`
  - `dav_module`
  - `dav_fs_module`
  - `dav_lock_module`
  - `lua_module`

- observabilidade e debug:
  - `status_module`
  - `info_module`
  - `dumpio_module`
  - `logio_module`
  - `unique_id_module`

- módulos adicionais relevantes:
  - `security2_module`
  - `remoteip_module`
  - `reqtimeout_module`
  - `limitipconn_module`

#### Classificação para migração

Módulos que provavelmente continuam no novo edge/proxy container:

- `alias_module`
- `dir_module`
- `mime_module`
- `headers_module`
- `rewrite_module`
- `deflate_module`
- `expires_module`
- `ssl_module`
- `proxy_module`
- `proxy_http_module`
- `proxy_wstunnel_module`
- `remoteip_module`
- `reqtimeout_module`

Módulos que só devem existir se houver necessidade comprovada no ambiente novo:

- `proxy_balancer_module`
- `lbmethod_*`
- `security2_module`
- `socache_*`
- `status_module`

Módulos que indicam legado/local e não devem ir por padrão para o container novo:

- `php7_module`
- `cgi_module`
- `suexec_module`
- `userdir_module`
- `dav_module`
- `dav_fs_module`
- `dav_lock_module`
- `lua_module`
- `info_module`
- `dumpio_module`
- `proxy_ftp_module`
- `proxy_connect_module`
- `proxy_scgi_module`
- `auth_digest_module`
- `authn_anon_module`
- `mime_magic_module`
- `vhost_alias_module`

#### Leitura prática para a migração

O Apache atual está supercarregado para três papéis diferentes:

1. servir conteúdo PHP/Joomla local;
2. fazer proxy reverso HTTP e WebSocket para aplicações em outros hosts;
3. manter uma pilha antiga de módulos de compatibilidade e debug.

No ambiente containerizado novo, o desenho recomendado é:

- container de edge/proxy com o conjunto mínimo de módulos de proxy, TLS e headers;
- container separado para o Joomla, caso ele realmente continue existindo;
- remoção da maior parte dos módulos legados que hoje só ampliam superfície de ataque.

Impacto na migração:

- este papel deve ser dividido em pelo menos dois workloads:
  - um proxy reverso/container de edge;
  - um container separado para a aplicação Joomla/PHP local, se ela ainda for necessária.

### 2. SSLH

Papel:

- escuta em `10.10.2.60:443`;
- multiplexa TLS, SSH, OpenVPN, HTTP e fallback para `localhost:443`.

Configuração observada em `/etc/sslh.cfg`:

- `rvpn.results.com.br` com SNI para `10.10.2.30:5555`
- `ssh` para `localhost:22`
- `openvpn` para `localhost:1194`
- `http` para `localhost:80`
- `tls` e `anyprot` para `localhost:443`

Impacto na migração:

- não deve ser embutido no mesmo container do Apache;
- ou permanece no host como camada de borda, ou vira um container dedicado de edge;
- hoje ele já apresenta erro operacional recorrente de encaminhamento TLS.

### 3. Joomla local

Papel:

- CMS local servido em `/var/www/html/results`.

Identificação observada:

- `Joomla 3.9.11`
- `PHP 7.2`
- `DocumentRoot`: `/var/www/html/results`

Dependências observadas:

- banco remoto:
  - host: `srvmysql.results.intranet`
  - database: `joomla`
- logs locais em `/var/www/html/results/logs`
- cache local em `/var/www/html/results/cache`
- `tmp_path` configurado como `/tmp`

Estado atual:

- `cache` consome aproximadamente `2.7G`
- o volume `/var/www/html` está em `100%`

#### Lsyncd real observado no legado

Configuração verificada em `/etc/lsyncd.conf`:

- `source="/var/www/html_tra/"`
- `target="/var/www/html/"`
- `excludeFrom="/etc/lsyncd.exclude"`
- exclusão ativa em `/etc/lsyncd.exclude`: `jdownloads/Lianja`

Achado crítico:

- `/var/www/html_tra` é apenas um link simbólico para `/var/www/html`
- portanto, o `lsyncd` legado não alimenta um diretório externo independente do Joomla; na prática ele observa e replica o mesmo tree lógico do destino
- existe ainda um `onStartup` que sobrescreve `/var/www/html/index.html` com um HTML mínimo: `<!DOCTYPE html><title></title>`

Leitura prática:

- a página branca na raiz do legado não é evidência de falha do `lsyncd`; ela é compatível com a própria configuração observada
- o conteúdo real do site principal continua sendo servido pelo Joomla em `/var/www/html/results`
- replicar apenas `index.html` não reproduz `results.com.br`

Estado operacional observado:

- `lsyncd.service` aparece desabilitado
- havia referência em `crontab` para restart periódico do serviço (`service lsyncd restart`), mas no momento da inspeção não havia processo `lsyncd` ativo

#### Vhost real de `results.com.br`

Configuração verificada em `/etc/httpd/conf.d/results.conf` e `/etc/httpd/conf.d/results-le-ssl.conf`:

- `results.com.br` em HTTP usa `DocumentRoot "/var/www/html/results"` e redireciona para HTTPS no mesmo hostname
- `www.results.com.br` em HTTP redireciona para `http://results.com.br/`
- o vhost HTTPS principal usa `DocumentRoot "/var/www/html/results"` com `ServerName results.com.br` e `ServerAlias www.results.com.br`
- o certificado HTTPS principal do legado vem de `/etc/letsencrypt/live/www.results.com.br/`
- o Joomla local convive com vários `ProxyPass` por path no mesmo vhost, incluindo `/n8n`, `/suporte`, `/egroupware`, `/colaboracao`, `/barcode`, `/valid` e includes dedicados para `r-agent2` e `oly-fit-balance`

Conclusão para a migração:

- o edge novo só replica com fidelidade o domínio principal se a raiz `/` apontar para um backend Joomla/PHP equivalente ou, temporariamente, para o servidor antigo
- manter apenas um `DocumentRoot` estático com conteúdo sincronizado não reproduz o comportamento real de `results.com.br`
- o `10.10.2.55` não é a origem autoritativa desses arquivos; ele recebe réplica via `lsyncd`
- a origem autoritativa observada está no `10.10.2.7`, em `/var/www/html/results/`
- o `10.10.2.7` replica esse conteúdo para `srvhttp0.results.intranet:/var/www/html_tra/results/` e `srvhttp1.results.intranet:/var/www/html_tra/results/`
- no ambiente novo, o padrão correto é reproduzir o mesmo modelo: o `10.10.2.7` deve empurrar os arquivos para `10.10.2.30:/opt/results/infra/joomla-site/` via `lsyncd`
- dentro da stack Docker do `10.10.2.30`, o serviço `joomla-lsyncd` consome `./joomla-site` e replica para o volume interno do container Joomla

Impacto na migração:

- se o Joomla ainda for necessário, deve virar um workload separado:
  - `php-fpm` ou `apache+php` dedicado;
  - volume persistente para `images`, `logs`, eventuais uploads;
  - limpeza de cache fora do container antes da migração.

### 4. Certificados TLS

Papel:

- vários domínios usam certificados em `/etc/letsencrypt/live`.

Certificados observados:

- `asp.lianja.com.br`
- `asp.results.com.br`
- `chatgpt.results.com.br`
- `colaboracao.results.com.br`
- `diskbot.results.com.br`
- `olimpicshape.com.br`
- `play-dev.results.com.br`
- `rdelivery.results.com.br`
- `repo.results.com.br`
- `ripabx.results.com.br`
- `rvpn.results.com.br`
- `valid.results.com.br`
- `webhook.results.com.br`
- `www.results.com.br`

Estado atual:

- `snap.certbot.renew.service` falhando;
- `snapd` parado há meses;
- renovação também impactada por falta de espaço temporário.

Impacto na migração:

- a gestão TLS deve sair desse host legado;
- ideal mover para um edge novo com ACME funcional;
- não reutilizar o pipeline atual baseado em snap quebrado.

## VHosts e Rotas Relevantes

### Conteúdo local ou redirect simples

Domínios apontando para conteúdo local ou redirect para HTTPS:

- `results.com.br`
- `www.results.com.br`
- `asp2.results.com.br`
- `repo.results.com.br`
- `fabiogomes.results.com.br`
- `asp.results.com.br`
- `asp.lianja.com.br`
- `chatGPT.results.com.br`
- `colaboracao.results.com.br`
- `comporte.results.com.br`
- `olimpicshape.com.br`
- `play-dev.results.com.br`
- `play.results.com.br`
- `rbi.results.com.br`
- `rdelivery.results.com.br`
- `ripabx.results.com.br`
- `terminal.results.com.br`
- `valid.results.com.br`
- `webhook.results.com.br`

Observação:

- há bastante sobreposição, redirects e arquivos de configuração duplicados.
- existe um diretório `bkp2` enorme em `/etc/httpd/conf.d`, indicando alta chance de drift de configuração.

### Rotas e backends em 10.10.2.30

Dependências já observadas para o host novo/containerizado:

- `results-le-ssl.conf`
  - `/n8n` -> `10.10.2.30:5678`
  - `/egroupware` -> `10.10.2.30/egroupware/`
  - `/colaboracao` -> `10.10.2.30/egroupware/`
  - include `apache-r-agent2.conf`
  - include `apache-oly-fit-balance.conf`

- `apache-r-agent2.conf`
  - `/r-agent2` -> `10.10.2.30:8086`
  - WebSocket em `/r-agent2/ws` e `/r-agent2/api/ws`

- `apache-oly-fit-balance.conf`
  - `/oly-fit-balance/api` -> `10.10.2.30:3000/api`
  - `/oly-fit-balance/health` -> `10.10.2.30:3000/health`
  - `/oly-fit-balance/` -> `10.10.2.30:8087/`
  - `/oly-fit-balance-admin/` -> `10.10.2.30:8089/`

- `apache-aprende-ai.conf`
  - `/aprende-ai` -> `10.10.2.30:8088`
  - `/aprende-ai/api` -> `10.10.2.30:8088/api`
  - `/aprende-ai/auth` -> `10.10.2.30:8088/auth`
  - `/aprende-ai/health` -> `10.10.2.30:8088/health`

- `ripabx-le-ssl.conf`
  - `/` -> `10.10.2.30:8082`
  - `/ripabx-ws` -> `10.10.2.30:8088/ws`
  - `/asr-ws` -> `10.10.2.30:5001`
  - `/face` -> `10.10.2.30:5002`
  - `/tts-ws` -> `10.10.2.30:5003`
  - `/praia` -> `10.10.2.30:5004`

- `rvpn.conf`
  - `rvpn.results.com.br` -> `https://10.10.2.30:443/`

### Dependências adicionais fora de 10.10.2.30

- `10.10.2.22`
  - `repo`, `barcode`, `valid`, `rconf`, várias rotas do `ripabx`
- `10.10.2.7`
  - `/suporte`
- `10.10.2.6`
  - `asp.lianja.com.br`, `wa`, `whatsapp-ws`, `col_magno`

## Serviços que Devem Ficar Fora do Container

Esses serviços não devem ser colocados no mesmo container da aplicação web:

- `sshd`
- `firewalld`
- `fail2ban`
- `chronyd`
- `NetworkManager`
- `rsyslog`
- `bacula-fd`
- `auditd`

Podem até ser redesenhados depois, mas não pertencem ao container do Apache/Joomla.

## Proteção Atual Contra Ataques

### Controles existentes

Controles efetivamente observados no host:

- `firewalld` ativo
- `fail2ban` ativo
- `mod_security` carregado no Apache
- `reqtimeout_module` carregado no Apache
- `limitipconn_module` carregado no Apache

### Firewall

Serviços liberados na zona pública:

- `http`
- `https`
- `ssh`

Portas adicionais abertas:

- `7400/tcp`
- `7400/udp`
- `554/tcp`
- `3702/tcp`
- `3702/udp`

Leitura prática:

- existe filtro de borda e política padrão de rejeição para o restante;
- porém há portas extras expostas que precisam de justificativa operacional antes da migração.

### Fail2ban

Jails ativas observadas:

- `apache-auth`
- `apache-badbots`
- `apache-botsearch`
- `apache-fakegooglebot`
- `apache-modsecurity`
- `apache-nohome`
- `apache-noscript`
- `apache-overflows`
- `apache-shellshock`

Evidências de atuação:

- `apache-noscript`: `7692` falhas totais, `96` bans históricos
- `apache-botsearch`: `24` falhas totais, `2` bans históricos
- `apache-fakegooglebot`: `2` bans históricos

Leitura prática:

- existe proteção reativa contra scanners e bots triviais;
- ela ajuda, mas não compensa a exposição do stack legado.

### WAF e endurecimento HTTP

- `security2_module` está carregado
- existe diretório `modsecurity.d/activated_rules`
- há indícios de `RequestReadTimeout` em configurações antigas/derivadas
- `KeepAlive` e `Timeout` estão configurados em múltiplos arquivos, com sinais de drift de configuração

Limitações observadas:

- não apareceu evidência de política WAF consolidada e bem governada;
- o servidor continua com `TRACE` habilitado e funcional;
- não há evidência clara de hardening consistente com `ServerTokens`, `ServerSignature` e cabeçalhos de segurança aplicados globalmente.

### Proteção SSH

Estado observado:

- `PasswordAuthentication yes`
- login `root` por senha aceito
- algoritmos legados ainda expostos no SSH

Leitura prática:

- o host tem proteção fraca para acesso administrativo;
- o maior risco aqui não é brute force simples, e sim a combinação de `root` remoto por senha com stack antigo.

### Fragilidades estruturais da proteção atual

1. `SELinux` está desabilitado.
2. `TRACE` está ativo.
3. a renovação de certificados falha.
4. o host está sem espaço em disco, o que degrada componentes de proteção e manutenção.
5. `snapd` está morto e `rpm` já apresentou `Bus error`.

### Implicação para o ambiente novo

Na migração, a proteção não deve ser copiada como está. O recomendado é:

1. manter firewall no host de borda
2. manter `fail2ban` fora do container
3. remover login `root` por senha
4. usar TLS e SSH modernos
5. desabilitar `TRACE`
6. reduzir módulos Apache ao mínimo necessário
7. decidir conscientemente se `mod_security` continua no edge novo ou será substituído

## Serviços que Podem Ser Removidos ou Revisados

Itens que precisam de revisão antes ou durante a migração:

- `rpcbind`: exposto, sem benefício claro para a função web observada
- `postfix`: escuta só em `localhost:25`; avaliar se é apenas entrega local de notificações
- `snapd`: habilitado, mas morto; atualmente só agrava o problema de renovação
- `sshd` em `2222`: confirmar se é necessário ou sobra operacional

## Automações Atuais

Agendamentos observados:

- `root crontab`
  - `00 00 * * * /usr/bin/certbot renew`
- timer systemd
  - `snap.certbot.renew.timer`

Observação:

- há redundância e falha nos dois caminhos de renovação.

## Riscos de Migração e Bloqueios Atuais

### Bloqueios imediatos

- `/` em `100%`
- `/var/www/html` em `100%`
- `certbot` quebrado
- `snapd` parado
- `rpm` apresentando `Bus error`

### Riscos arquiteturais

- configuração Apache muito espalhada e com duplicidade
- dependências em múltiplos hosts internos
- TLS acoplado a `sslh` + `localhost:443`
- mistura de frontend legado, proxy reverso, VPN ingress e CMS local no mesmo host

## Recomendação de Migração por Blocos

### Bloco A: edge HTTP/HTTPS

Migrar para container dedicado:

- reverse proxy moderno
- roteamento por domínio e path
- ACME/TLS funcional

Pode absorver:

- `results-le-ssl.conf`
- `rvpn.conf` se o desenho de borda continuar igual
- `apache-r-agent2.conf`
- `apache-oly-fit-balance.conf`
- `apache-aprende-ai.conf`

### Bloco B: Joomla legado

Migrar separadamente, se ainda for necessário:

- `Joomla 3.9.11`
- `PHP 7.2` legado, idealmente com plano de upgrade
- volume persistente para `images`, `logs`, eventuais uploads
- limpeza obrigatória de `cache`

### Bloco C: VPN/TLS multiplexer

Não misturar com o container do site.

Opções:

- manter no host de borda dedicado;
- ou migrar para um container separado e explícito.

### Bloco D: sistema/base

Manter fora de container:

- `sshd`
- `firewalld`
- `fail2ban`
- `chronyd`
- `bacula-fd`

## Ordem Recomendada

1. Inventariar quais domínios realmente ainda têm tráfego e quais são só legado/config sobrando.
2. Liberar espaço em disco para estabilizar o host atual.
3. Mover primeiro as rotas que já dependem de `10.10.2.30` para um novo edge único.
4. Separar a decisão do Joomla local: migrar, congelar ou desativar.
5. Descomissionar `sslh` e o modelo `127.0.0.1:443` somente depois que os domínios HTTPS estiverem validados no novo edge.

## Resposta Curta para a Migração

Para migrar este servidor para container, o que realmente precisa ser mapeado como workload é:

- Apache reverse proxy multi-domínio
- SSL/TLS e certificados
- sslh/VPN ingress em `10.10.2.60:443`
- Joomla 3.9.11 local
- integrações com `10.10.2.30`, `10.10.2.22`, `10.10.2.7` e `10.10.2.6`

O restante é serviço de sistema e não deve entrar no mesmo container da aplicação.

## Matriz de Proteção Atual, Risco e Ação Corretiva

| Controle atual | Estado observado | Risco principal | Ação corretiva recomendada |
| --- | --- | --- | --- |
| `firewalld` | Ativo, com `http`, `https`, `ssh` e portas extras `7400`, `554`, `3702` | Exposição desnecessária de portas não justificadas | Revisar regra por regra, fechar portas que não fizerem parte do desenho final |
| `fail2ban` | Ativo, 9 jails Apache, bans históricos reais | Proteção apenas reativa, sem cobrir fragilidade estrutural do host | Manter no host de borda novo, com jails para Apache/Nginx e SSH |
| `mod_security` | Módulo carregado, sem evidência clara de política madura | Sensação de proteção sem governança efetiva | Decidir explicitamente se haverá WAF no edge novo e padronizar regras |
| `reqtimeout_module` | Disponível, mas configuração espalhada | Defesa inconsistente contra slowloris e abuso de conexão | Aplicar `RequestReadTimeout` global e versionado no edge novo |
| `limitipconn_module` | Carregado e usado em alguns vhosts | Pode haver drift e política desigual por domínio | Reavaliar necessidade; preferir limites centralizados no edge/proxy |
| `firewalld` + `fail2ban` | Ativos | Não compensam `root` remoto por senha | Desabilitar login `root` por senha e migrar para chave SSH |
| SSH | `PasswordAuthentication yes`, root aceito | Comprometimento administrativo por credencial | `PermitRootLogin no`, `PasswordAuthentication no`, chaves e grupo administrativo dedicado |
| SSH legacy ciphers | Ativos | Downgrade criptográfico e hardening fraco | Restringir KEX, MACs e cifras modernas no novo host |
| Apache `TRACE` | Ativo e funcional | XST e exposição desnecessária | `TraceEnable Off` no edge novo |
| SELinux | Desabilitado | Ausência de contenção local | Reativar SELinux ou controle equivalente no host novo |
| Certbot/snap | Falhando | Expiração de certificados e indisponibilidade HTTPS | Migrar ACME para pipeline novo e suportado |
| Espaço em disco | `/` e `/var/www/html` em `100%` | Quebra de TLS, logs, pacote, tmp e serviços base | Liberar espaço antes da migração e monitorar capacidade no novo ambiente |

## Checklist de Hardening do Novo Host e do Novo Edge

### Host de borda

- Atualizar para distribuição com suporte vigente.
- Habilitar atualizações de segurança com processo controlado.
- Usar `SELinux` em modo `enforcing` ou política equivalente.
- Permitir acesso SSH apenas por chave.
- Desabilitar `root` por senha.
- Restringir `AllowUsers` ou `AllowGroups` no SSH.
- Remover algoritmos SSH legados.
- Habilitar auditoria e rotação de logs.
- Monitorar disco, inode, memória e validade de certificados.
- Manter `firewalld` com política mínima de portas.
- Manter `fail2ban` no host, não no container.

### Edge HTTP/HTTPS containerizado

- Expor apenas `80` e `443` necessários ao edge.
- Desabilitar `TRACE`.
- Configurar `ServerTokens Prod` e `ServerSignature Off`.
- Aplicar `RequestReadTimeout`.
- Aplicar cabeçalhos de segurança globais.
- Usar TLS 1.2 e 1.3 apenas.
- Remover módulos Apache não essenciais.
- Publicar healthcheck claro por serviço.
- Centralizar configuração versionada por domínio/path.
- Definir limites de upload e timeouts por rota crítica.

### Aplicações atrás do edge

- Rodar cada workload em container separado.
- Não embutir SSH, firewall ou fail2ban dentro dos containers.
- Montar apenas volumes persistentes necessários.
- Externalizar segredos via arquivo ou secret manager.
- Separar logs de aplicação de logs do edge.
- Definir readiness e liveness checks.

## O que Fica no Host e o que Vai para Container

### Deve ficar no host

- `sshd`
- `firewalld`
- `fail2ban`
- `auditd`
- `rsyslog`
- `chronyd`
- `bacula-fd`
- eventual componente de VPN/multiplexação, se continuar existindo como função de borda

Motivo:

- são controles de administração, proteção, tempo, auditoria, backup e rede do nó;
- misturar isso com o container web reduz isolamento e aumenta impacto de falha.

### Deve ir para containers separados

- edge HTTP/HTTPS reverso
- Joomla legado, se ainda permanecer ativo
- `r-agent2`
- `oly-fit-balance` web/admin/api
- `aprende-ai`
- `n8n`
- `ripabx` e seus componentes que já estão atrás de proxies específicos

Motivo:

- esses são workloads de aplicação e entrega;
- precisam de ciclo de deploy e rollback próprio;
- devem escalar e falhar de forma independente.

### Deve ser redesenhado antes de migrar

- `sslh` em `10.10.2.60:443`
- modelo atual `127.0.0.1:443` no Apache
- pipeline de certificados via `certbot`/snap
- organização de vhosts duplicados e `bkp2` dentro de `conf.d`

Motivo:

- esses pontos representam acoplamento histórico do host legado;
- migrar sem redesenhar só transporta complexidade e fragilidade para o novo ambiente.

## Plano de Execução por Fases

### Fase 0: estabilização do legado

Objetivo:

- reduzir risco operacional antes de qualquer corte.

Ações:

- liberar espaço em `/` e em `/var/www/html`;
- remover cache obsoleto do Joomla e artefatos antigos que não entram na migração;
- confirmar quais arquivos de `conf.d` são ativos e quais são apenas histórico em `bkp2`;
- validar expiração real dos certificados ainda em uso;
- congelar alterações manuais no Apache legado durante a janela de migração.

Critério de saída:

- host volta a ter margem operacional mínima para logs, tmp e inspeção;
- lista de vhosts ativos fica fechada.

### Fase 1: consolidação do edge novo em `10.10.2.30`

Objetivo:

- transformar o container novo em ponto único de entrada HTTP/HTTPS para os domínios que hoje passam pelo Apache legado.

Ações:

- substituir o modelo de site único por configuração multi-vhost e multi-path no edge novo;
- manter TLS/ACME no host novo, sem dependência de `snapd`;
- criar arquivos de rota por domínio, versionados no repositório;
- separar claramente rotas HTTP comuns, WebSocket e backends internos.

Critério de saída:

- edge novo consegue publicar os mesmos domínios e paths críticos hoje ativos;
- healthchecks e logs de acesso/erro ficam padronizados.

### Fase 2: migração das rotas que já apontam para `10.10.2.30`

Objetivo:

- migrar primeiro o que já depende do host novo, reduzindo risco de integração.

Escopo prioritário:

- `results.com.br` para rotas `/n8n`, `/egroupware`, `/colaboracao`, `/r-agent2`, `/aprende-ai`, `/oly-fit-balance`;
- `olimpicshape.com.br`;
- `play.results.com.br`;
- `play-dev.results.com.br`;
- `ripabx.results.com.br` nas rotas que já apontam para `10.10.2.30`;
- `rvpn.results.com.br` se a topologia final continuar usando proxy HTTPs para `rvpn`.

Critério de saída:

- esses domínios deixam de depender do Apache legado para publicação;
- rollback por DNS ou por publicação no edge fica documentado.

### Fase 3: migração das rotas que dependem de `10.10.2.22`, `10.10.2.7` e `10.10.2.6`

Objetivo:

- mover a função de publicação sem necessariamente mover os backends ainda.

Ações:

- replicar no edge novo apenas o proxy reverso para `10.10.2.22`, `10.10.2.7` e `10.10.2.6`;
- padronizar timeouts, headers e WebSockets;
- confirmar conectividade do `10.10.2.30` até cada backend interno.

Critério de saída:

- o Apache velho deixa de ser dependência para publicação desses backends.

### Fase 4: decisão sobre o Joomla legado

Objetivo:

- decidir se o Joomla será migrado, encapsulado temporariamente ou desativado.

Opções válidas:

- migrar para container dedicado e isolado;
- manter temporariamente fora do edge novo, atrás de um proxy específico;
- desativar se o domínio estiver sem uso real.

Critério de saída:

- o Joomla deixa de ser uma incógnita arquitetural.

### Fase 5: retirada do desenho antigo de TLS e de `sslh`

Objetivo:

- eliminar o acoplamento `10.10.2.60:443` -> `sslh` -> `localhost:443`.

Ações:

- publicar HTTPS diretamente pelo edge novo;
- decidir se VPN e multiplexação continuam no host com outro componente dedicado;
- remover dependência de `certbot` via `snap`;
- desativar `TRACE`, root SSH por senha e configs obsoletas restantes.

Critério de saída:

- `10.10.2.55` deixa de ser edge ativo;
- corte final vira apenas descomissionamento controlado.

## Matriz de Domínios, Backends e Destino no Novo Edge

| Domínio | Papel atual | Backend atual | Destino no novo edge | Observações |
| --- | --- | --- | --- | --- |
| `results.com.br` | site principal + agregador de rotas | Joomla local em `/var/www/html/results` e proxies por path para `10.10.2.30`, `10.10.2.22`, `10.10.2.7` | vhost principal do edge | domínio mais crítico; precisa separar conteúdo local de rotas proxied |
| `www.results.com.br` | alias do principal | mesmo vhost de `results.com.br` | alias do vhost principal | manter redirect e certificado no mesmo bloco |
| `repo.results.com.br` | conteúdo local com proxy `/repo` | Joomla local + `10.10.2.22/repo/` | vhost dedicado ou alias com rota própria | decidir se continua com conteúdo local ou vira apenas proxy |
| `fabiogomes.results.com.br` | conteúdo local | Joomla local | vhost dedicado ou alias consolidado | confirmar se ainda possui tráfego real |
| `asp2.results.com.br` | conteúdo local | Joomla local | vhost dedicado temporário | candidato forte a desativação se não houver uso |
| `colaboracao.results.com.br` | proxy dedicado | `10.10.2.30:8080` | rota ou vhost no edge novo | coexistem também rotas `/colaboracao` em `results.com.br` |
| `comporte.results.com.br` | proxy por path | `10.10.2.22/comportezendesk` | vhost dedicado | dependência externa a `10.10.2.30` |
| `rbi.results.com.br` | proxy dedicado | `rbi.results.intranet:8080` | vhost dedicado | backend interno legado |
| `rconf.results.com.br` | proxy dedicado | `10.10.2.22:3009` | vhost dedicado | simples de mover para o edge novo |
| `rdelivery.results.com.br` | proxy dedicado | `10.10.2.22/rdelivery/` e em HTTP `rdelivery.results.intranet` | vhost dedicado | padronizar backend final antes do corte |
| `rvpn.results.com.br` | proxy HTTPS | `https://10.10.2.30:443/` | vhost dedicado no edge novo ou publicação direta | hoje conflita com ocupação da porta `443` no host novo |
| `terminal.results.com.br` | proxy dedicado | `10.10.2.30:2222` | vhost dedicado | expõe serviço sensível; revisar necessidade e controles |
| `valid.results.com.br` | proxy por path `/valid` | `10.10.2.22/valid` | vhost dedicado | simples de migrar |
| `webhook.results.com.br` | proxy dedicado | `10.10.2.6:3002` | vhost dedicado | revisar autenticação e limite de origem |
| `asp.lianja.com.br` | proxy dedicado com HTTP e WebSocket | `10.10.2.6`, `10.10.2.6:5000`, `10.10.2.6:3002` | vhost dedicado | depende de múltiplas rotas e WebSocket |
| `chatGPT.results.com.br` | proxy dedicado | `10.10.2.22/chatGPT/` | vhost dedicado | preservar alias `chatgpt.results.com.br` |
| `chatgpt.results.com.br` | alias | mesmo backend de `chatGPT.results.com.br` | alias do mesmo vhost | padronizar grafia no edge novo |
| `play.results.com.br` | proxy dedicado | `10.10.2.30:3001` | vhost dedicado | já depende do host novo |
| `play-dev.results.com.br` | proxy dedicado | `10.10.2.30:80` | vhost dedicado | validar se backend final não conflita com edge do próprio host |
| `olimpicshape.com.br` | proxy dedicado | `10.10.2.30:8081` | vhost dedicado | já depende do host novo |
| `ripabx.results.com.br` | agregador de múltiplas rotas HTTP e WS | `10.10.2.30:8082`, `10.10.2.30:8088/ws`, `10.10.2.30:5001-5004`, `10.10.2.22`, `ripabx.results.intranet` | vhost complexo dedicado | requer migração cuidadosa de ordem das regras |
| `asp.results.com.br` | vhost HTTP legado | não apareceu backend ativo no dump | avaliar desativação ou mapeamento posterior | existe em `*:80`, mas sem proxy evidente ativo |

### Paths críticos dentro de `results.com.br`

| Path | Backend atual | Destino no novo edge | Observações |
| --- | --- | --- | --- |
| `/n8n/` | `10.10.2.30:5678` | rota no vhost `results.com.br` | exige HTTP e WebSocket em `/n8n/socket` |
| `/suporte` | `10.10.2.7:8001/newcentral` | rota no vhost `results.com.br` | dependência fora do host novo |
| `/egroupware` | `10.10.2.30/egroupware/` | rota no vhost `results.com.br` | pode compartilhar backend com `/colaboracao` |
| `/colaboracao` | `10.10.2.30/egroupware/` | rota no vhost `results.com.br` | validar se deve coexistir com `colaboracao.results.com.br` |
| `/barcode` | `10.10.2.22:3030` | rota no vhost `results.com.br` | simples de migrar |
| `/valid` | `10.10.2.22:3010` | rota no vhost `results.com.br` | revisar coexistência com `valid.results.com.br` |
| `/r-agent2` | `10.10.2.30:8086` | rota no vhost `results.com.br` | inclui WebSocket e assets |
| `/aprende-ai` | `10.10.2.30:8088` | rota no vhost `results.com.br` | inclui auth, api, health e assets |
| `/oly-fit-balance/` | `10.10.2.30:8087/` | rota no vhost `results.com.br` | depende também da API em `:3000` |
| `/oly-fit-balance-admin/` | `10.10.2.30:8089/` | rota no vhost `results.com.br` | manter separado do frontend web |

## Desenho Alvo do Novo Edge em `10.10.2.30`

### Estrutura recomendada

Separar em quatro camadas lógicas:

1. host de borda
2. container de edge HTTP/HTTPS
3. rede interna Docker para workloads locais em `10.10.2.30`
4. rotas de saída para backends externos em `10.10.2.22`, `10.10.2.7`, `10.10.2.6` e intranet

### Responsabilidade de cada camada

Host de borda:

- `firewalld`
- `fail2ban`
- SSH endurecido
- observabilidade do nó
- eventual componente VPN, se continuar fora do Docker

Container de edge:

- TLS/ACME
- virtual hosts por domínio
- roteamento por path
- WebSocket proxy
- headers, limits, timeouts e logs de acesso

Workloads locais em `10.10.2.30`:

- `n8n`
- `r-agent2`
- `aprende-ai`
- `oly-fit-balance`
- `ripabx` e serviços auxiliares
- eventual Joomla legado, se realmente for mantido

Backends externos preservados temporariamente:

- `10.10.2.22`
- `10.10.2.7`
- `10.10.2.6`
- nomes internos como `rbi.results.intranet` e `ripabx.results.intranet`

### Ajuste recomendado na stack já existente

A stack atual em `/opt/results/infra` já resolve a base de segurança, mas ainda está modelada como um site único. Para virar edge real, a evolução mínima é:

- trocar `DocumentRoot` único por um diretório de `conf.d` ou template por vhost;
- carregar módulos de proxy e WebSocket no container novo;
- externalizar arquivos de rota por domínio e por aplicação;
- manter o Joomla fora do mesmo container do edge;
- criar volumes separados para certificados, configs e logs.

### Composição alvo sugerida

- `edge-apache` ou `edge-nginx`: terminador TLS e reverse proxy
- `edge-config`: diretório versionado com vhosts e rotas
- `md-data`: persistência ACME/certificados
- rede `edge-public`
- rede `edge-backend`

### Regras de desenho para evitar repetir o problema atual

- um domínio não deve depender de arquivos duplicados em múltiplos diretórios;
- cada backend deve ter dono claro e healthcheck verificável;
- rotas WebSocket devem ficar explícitas e testáveis;
- aliases devem viver no mesmo vhost do domínio principal;
- conteúdo local legado não deve ficar misturado com proxy reverso multiaplicação;
- publicar `443` padrão é requisito para produção; `4443` é apenas workaround transitório.

## Melhor Uso do IP 10.10.2.60 no Novo Host

No legado, `10.10.2.60` não é uma interface separada. Ele existe como IP adicional em `eth0` junto com `10.10.2.55`, `10.10.2.61` e `10.10.2.62`.

No host novo `10.10.2.30`, a melhor forma de reproduzir isso é:

1. adicionar `10.10.2.60/24` como IP secundário em `eth0`;
2. prender o `rvpn` em `10.10.2.30`;
3. prender o edge novo em `10.10.2.60`.

Por que esse é o melhor desenho:

- mantém compatibilidade com o modelo já usado no legado;
- evita `macvlan`, bridge extra e troubleshooting desnecessário;
- permite separar porta `443` por IP sem separar fisicamente a máquina;
- simplifica o corte de DNS, porque o IP histórico da borda HTTPS continua sendo `10.10.2.60`.

### Situação atual que impede isso hoje

No `10.10.2.30`, o container `rvpn` publica `443`, `992`, `5555`, `500/udp`, `4500/udp`, `1701/udp` e `1194/udp` em `0.0.0.0`.

Isso significa:

- se apenas adicionarmos `10.10.2.60` no host, o `rvpn` continuará ocupando `443` também nesse IP;
- portanto o edge HTTP/HTTPS não conseguirá usar `10.10.2.60:443` até o `rvpn` ser rebindado para `10.10.2.30`.

### Desenho recomendado final

- `10.10.2.30:443` -> `rvpn`
- `10.10.2.30:992` -> `rvpn`
- `10.10.2.30:5555` -> `rvpn`
- `10.10.2.60:80` -> edge Apache novo
- `10.10.2.60:443` -> edge Apache novo

### Persistência no host Alpine

O host `10.10.2.30` roda Alpine e usa `/etc/network/interfaces`. O caminho mais direto é manter a configuração principal de `eth0` e acrescentar comandos `up` e `down` para o IP secundário.

### Implicação prática para o corte

O caminho correto não é trocar DNS primeiro.

O caminho correto é:

1. adicionar `10.10.2.60` no host novo;
2. mover o bind do `rvpn` para `10.10.2.30`;
3. subir o edge novo em `10.10.2.60:80/443`;
4. validar por `curl --resolve`;
5. só depois mudar DNS público.

### Ordem prática de implementação no repositório

1. criar estrutura `apache/vhosts/` ou equivalente para domínios e rotas;
2. adaptar o `httpd.conf.template` para incluir configs fragmentadas;
3. migrar primeiro `results.com.br` e domínios que já apontam para `10.10.2.30`;
4. adicionar depois os vhosts que dependem de `10.10.2.22`, `10.10.2.7` e `10.10.2.6`;
5. por último tratar Joomla e descomissionamento do legado.