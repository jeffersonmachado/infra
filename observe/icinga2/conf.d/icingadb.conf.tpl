/**
 * Feature: IcingaDB
 * Conecta Icinga2 ao Redis dedicado do IcingaDB.
 * Template — substituído pelo entrypoint.sh via envsubst.
 */
object IcingaDB "icingadb" {
  host = "${ICINGA_REDIS_HOST}"
  port = ${ICINGA_REDIS_PORT}
}
