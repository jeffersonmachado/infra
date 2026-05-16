/**
 * Feature: API listener
 * Template — substituído pelo entrypoint.sh via envsubst.
 */
object ApiListener "api" {
  accept_config   = false
  accept_commands = true
  ticket_salt     = GenerateTicketSalt()
}
