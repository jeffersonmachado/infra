require ["vnd.dovecot.pipe", "copy", "imapsieve", "environment", "variables"];

# Triggered when a user moves a message from Spam back to INBOX via IMAP.
if environment :matches "imap.mailbox" "*" {
  set "mailbox" "${1}";
}

pipe :copy "rspamc-learn-ham.sh";
