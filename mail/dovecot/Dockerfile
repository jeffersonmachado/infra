FROM dovecot/dovecot:2.3.21

RUN apt-get update \
    && DEBIAN_FRONTEND=noninteractive apt-get install -y --no-install-recommends gettext-base dovecot-mysql dovecot-ldap rspamd \
    && rm -rf /var/lib/apt/lists/*

COPY entrypoint.sh /entrypoint.sh
COPY dovecot.conf.template /templates/dovecot.conf.template
COPY dovecot-sql.conf.ext.template /templates/dovecot-sql.conf.ext.template
COPY dovecot-ldap.conf.ext.template /templates/dovecot-ldap.conf.ext.template
COPY global-after.sieve /templates/global-after.sieve
COPY report-spam.sieve /templates/report-spam.sieve
COPY report-ham.sieve /templates/report-ham.sieve
COPY rspamc-learn-spam.sh /templates/rspamc-learn-spam.sh
COPY rspamc-learn-ham.sh /templates/rspamc-learn-ham.sh

RUN chmod +x /entrypoint.sh

EXPOSE 110 143 993 995 4190 24 12345

ENTRYPOINT ["/entrypoint.sh"]