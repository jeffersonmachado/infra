<?php

/* Local configuration for Roundcube Webmail */

// ----------------------------------
// SQL DATABASE
// ----------------------------------
$roundcubeDbHost = getenv('ROUNDCUBE_DB_HOST') ?: 'srvmysql.results.intranet';
$config['db_dsnw'] = 'mysql://roundcube:' . (getenv('ROUNDCUBE_DB_PASSWORD') ?: 'CHANGE_ME') . '@' . $roundcubeDbHost . '/roundcubemail';

// ----------------------------------
// IMAP
// ----------------------------------
$config['default_host'] = getenv('ROUNDCUBE_IMAP_HOST') ?: 'ssl://results-mail-dovecot';
$imapPort = getenv('ROUNDCUBE_IMAP_PORT');
if ($imapPort !== false && $imapPort !== '') {
	$config['default_port'] = (int) $imapPort;
} elseif (strpos($config['default_host'], 'tls://') === 0) {
	$config['default_port'] = 143;
} else {
	$config['default_port'] = 993;
}
$config['imap_conn_options'] = [
	'ssl' => [
		'verify_peer' => false,
		'verify_peer_name' => false,
		'allow_self_signed' => true,
	],
];

// ----------------------------------
// SMTP
// ----------------------------------
$config['smtp_server'] = getenv('ROUNDCUBE_SMTP_SERVER') ?: 'tls://results-mail-postfix';
$config['smtp_user'] = '%u';
$config['smtp_conn_options'] = [
	'ssl' => [
		'verify_peer' => false,
		'verify_peer_name' => false,
		'allow_self_signed' => true,
	],
];
$config['username_domain'] = 'results.com.br';

$config['support_url'] = 'https://www.results.com.br/index.php/suporte/suporte-webmail';
$config['skin_logo'] = 'https://www.results.com.br/images/results/home_results_29.png';
$config['log_dir'] = 'logs/';
$config['temp_dir'] = 'temp/';
$config['login_lc'] = 0;
$config['des_key'] = 'C467AkSmh%QlCJHG+0zhR?8m';
$config['mail_domain'] = 'results.com.br';
$config['product_name'] = 'Result`s Webmail';
$config['mime_magic'] = '/usr/share/misc/magic';

// ----------------------------------
// PLUGINS
// ----------------------------------
$config['plugins'] = array('markasjunk', 'managesieve');

$config['managesieve_host'] = getenv('ROUNDCUBE_MANAGESIEVE_HOST') ?: 'results-mail-dovecot';
$config['managesieve_port'] = 4190;
$config['managesieve_usetls'] = true;
$config['managesieve_conn_options'] = [
	'ssl' => [
		'verify_peer' => false,
		'verify_peer_name' => false,
		'allow_self_signed' => true,
	],
];
$config['managesieve_default'] = '/var/www/html/results/webmail/config/managesieve-default.sieve';
$config['managesieve_script_name'] = 'managesieve';
$config['managesieve_mbox_encoding'] = 'UTF-8';
$config['managesieve_raw_editor'] = true;

$config['drafts_mbox'] = 'Drafts';
$config['sent_mbox'] = 'Sent';
$config['junk_mbox'] = 'Spam';
$config['trash_mbox'] = 'Trash';
$config['default_folders'] = array('INBOX', 'Drafts', 'Sent', 'Spam', 'Trash');

$config['language'] = 'pt_BR';
$config['date_formats'] = array('Y-m-d', 'd-m-Y', 'Y/m/d', 'm/d/Y', 'd/m/Y', 'd.m.Y', 'j.n.Y');
$config['mime_param_folding'] = 0;
$config['display_next'] = false;
$config['default_font'] = '';
$config['message_cache_lifetime'] = '10d';
