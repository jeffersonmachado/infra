# Checklist de Desanuncio dos IPs Legados de Mail

Este checklist existe para liberar com seguranca os IPs historicos `10.10.2.3` e `10.10.2.23` antes do corte da stack nova no host `10.10.2.30`.

O objetivo nao e desmontar todo o legado primeiro. O objetivo minimo e parar de anunciar esses IPs na rede para evitar conflito de ARP quando o host novo os assumir.

## Criterio de avancar

So avance para o `cutover` no `10.10.2.30` quando estas duas condicoes forem verdadeiras ao mesmo tempo:

1. `10.10.2.3` nao responde mais a ping nem a `tcp/25`
2. `10.10.2.23` nao responde mais a ping nem a `tcp/25`

## Sequencia recomendada

1. identificar em quais hosts os IPs `10.10.2.3` e `10.10.2.23` estao configurados
2. remover primeiro o IP secundario `10.10.2.23`
3. validar que `10.10.2.23` sumiu da rede
4. remover o IP principal `10.10.2.3`
5. validar que `10.10.2.3` sumiu da rede
6. executar imediatamente o cutover no `10.10.2.30`

## Verificacao antes de mexer

No host legado de `10.10.2.3`, confirmar interface e enderecos:

```bash
hostname
ip -4 addr show
ip -4 route show
netstat -ltn | egrep ':(25|110|143|465|587|993|995) '
```

Se o host ainda usar as ferramentas antigas do CentOS 6.5, estes comandos tambem ajudam:

```bash
ifconfig -a
service postfix status
service courier-imap status
service courier-pop status
```

Se o IP `10.10.2.23` estiver em outro host, repetir a verificacao nele tambem.

## Desanuncio em runtime

### Remover `10.10.2.23`

Use a interface real observada no host. Se for `eth0`, o comando fica:

```bash
ip addr del 10.10.2.23/24 dev eth0
```

Confirmar que o IP saiu:

```bash
ip -4 addr show dev eth0
```

### Remover `10.10.2.3`

Depois de liberar o secundario, remover o principal:

```bash
ip addr del 10.10.2.3/24 dev eth0
```

Confirmar que o IP saiu:

```bash
ip -4 addr show dev eth0
```

## Persistencia da remocao

Se o legado subir novamente com os IPs antigos apos reboot ou restart de rede, remover tambem a configuracao persistente.

Em hosts no estilo CentOS 6.5, verificar principalmente:

```bash
grep -R "10.10.2.3\|10.10.2.23" /etc/sysconfig/network-scripts 2>/dev/null
```

Arquivos tipicos:

- `/etc/sysconfig/network-scripts/ifcfg-eth0`
- `/etc/sysconfig/network-scripts/ifcfg-eth0:0`
- `/etc/sysconfig/network-scripts/ifcfg-eth0:1`

Se houver alias persistente, remover ou comentar as entradas referentes a `10.10.2.3` e `10.10.2.23` antes de qualquer reinicio de rede.

## Validacao externa a partir da estacao de operacao

Depois de cada remocao, validar de fora do host legado:

```bash
ping -c 2 -W 2 10.10.2.23
nc -vz -w 5 10.10.2.23 25
ping -c 2 -W 2 10.10.2.3
nc -vz -w 5 10.10.2.3 25
```

O resultado esperado e falha de ping e falha de conexao SMTP.

## Passo seguinte imediato

Assim que os dois IPs pararem de responder, executar sem demora no host de operacao:

```bash
cd /opt/results/infra
DEPLOY_SSH_PASSWORD='resu100gabao' ./scripts/mail-cutover-10.10.2.30.sh cutover
```

Em seguida, continuar a validacao descrita em [MAIL_IP_CUTOVER_10.10.2.30.md](MAIL_IP_CUTOVER_10.10.2.30.md).

## Rollback do desanuncio

Se for necessario devolver temporariamente o IP ao legado antes do cutover novo, o comando de runtime e o inverso:

```bash
ip addr add 10.10.2.23/24 dev eth0
ip addr add 10.10.2.3/24 dev eth0
```

So faca isso se o `10.10.2.30` ainda nao tiver assumido esses IPs.