# RVPN na porta 443 via HAProxy SNI

**Data:** 2026-08-04  
**Status:** Em vigor

## Objetivo

Compartilhar a porta HTTPS publica entre o SoftEther VPN e os sites servidos
pelo Apache sem terminar o TLS da VPN no Apache.

## Topologia

```text
Internet TCP/443 -> DNAT 10.10.2.60:443 -> edge-sni (HAProxy TCP/SNI)
  SNI web presente   -> 10.10.2.60:18443 (secure-httpd/Apache)
  SNI rvpn ou ausente -> 10.10.2.30:443 (SoftEther, TCP passthrough)
```

O Apache permanece publicado diretamente em `10.10.2.60:80` para HTTP e
ACME. A porta HTTPS interna `18443` existe apenas para receber o backend do
HAProxy. A porta externa anunciada e redirecionada continua sendo `443` por
meio de `HTTPS_EXTERNAL_PORT=443`.

## Por que nao usar mod_proxy_connect

`mod_proxy_connect` exige configurar um proxy HTTP explicito em cada cliente
e aumenta o risco de criar um proxy aberto. O HAProxy inspeciona somente o SNI
do ClientHello e preserva o fluxo TLS original ate o SoftEther ou o Apache.

## Arquivos operacionais

- `docker-compose.edge-sni.yml`: publica `10.10.2.60:443`.
- `edge-sni/haproxy.cfg`: faz bind em `10.10.2.60:443` e roteia a VPN para `10.10.2.30:443`.
- `docker-compose.yml`: publica o Apache em `10.10.2.60:18443`.
- `docker-compose.edge-sni.yml`: usa rede do host para alcançar as duas VIPs sem hairpin NAT.

## Aplicacao

Antes do deploy, executar `./scripts/check-file-integrity.sh`. Depois:

```bash
docker compose --env-file .env up -d secure-httpd
docker compose -f docker-compose.edge-sni.yml up -d
```

O cliente SoftEther nao apresenta SNI TLS reconhecivel durante sua negociacao
inicial. Por isso o fallback sem SNI precisa apontar para o SoftEther; clientes
HTTPS modernos, que apresentam SNI, seguem ao Apache. O firewall deve manter o
DNAT TCP `443` para `10.10.2.60:443`.

Os timeouts TCP de cliente, servidor e tunel sao de 24 horas, com keepalive
habilitado. Timeouts curtos deixam o SoftEther aparentar `Connection Completed`
no cliente enquanto o caminho de dados ja foi encerrado pelo HAProxy.

## Validacao

```bash
docker compose -f docker-compose.edge-sni.yml config -q
docker exec edge-sni haproxy -c -f /usr/local/etc/haproxy/haproxy.cfg
openssl s_client -connect 10.10.2.60:443 -servername results.com.br </dev/null
vpncmd localhost /CLIENT /CMD AccountStatusGet results-443
```

Validar tambem um site HTTPS comum e a conectividade VPN para
`10.10.2.30`, sem perda das rotas `10.10.2.0/24` e `192.168.1.0/24`.

## Bootstrap e renovacao de r-observe

O dominio de certificado e o vhost sao controlados separadamente:

- `R_OBSERVE_CERT_DOMAIN=r-observe.results.com.br` mantem o dominio no
  `mod_md` e permite emitir/renovar por HTTP-01.
- `R_OBSERVE_SERVER_NAME=r-observe.results.com.br` publica o vhost TLS apenas
  depois que `pubcert.pem` e `privkey.pem` forem certificados validos.

Essa separacao impede que a ausencia de um certificado utilizavel para
`r-observe` bloqueie a inicializacao de todos os vhosts do Apache. Antes de
habilitar `R_OBSERVE_SERVER_NAME`, validar emissor, SAN e validade com
`openssl x509` e executar `httpd -t` dentro do container.

O DNS `127.0.0.11` e fornecido automaticamente pelo Docker. Nao configura-lo
explicitamente como upstream no Compose, pois isso cria um loop e impede o
`mod_md` de resolver `acme-v02.api.letsencrypt.org`.

## Versao do SoftEther

O Compose fixa o digest da imagem SoftEther 5.02. Nao substituir por
`softethervpn/vpnserver:stable`: em 2026-08-04 essa tag apontava para a versao
4.41, que regravou o volume em formato incompatível, removeu os usuarios do
hub e desativou o SecureNAT. Antes de atualizar o digest, validar a versao em
ambiente separado e preservar um backup de `vpn_server.config`.
