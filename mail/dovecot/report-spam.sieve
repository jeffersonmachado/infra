require ["vnd.dovecot.pipe", "copy", "imapsieve", "environment", "variables"];

# Triggered when a user moves a message into Spam via IMAP.
if environment :matches "imap.mailbox" "*" {
  set "mailbox" "${1}";
}

pipe :copy "rspamc-learn-spam.sh";
