<?php 
 $protocol = isset($_SERVER['HTTPS']) ? 'https' : 'http';
 $protocol = 'http';
 $ssl = 0;
 
  if ( isset( $_SERVER['HTTPS'] ) && strtolower( $_SERVER['HTTPS'] ) == 'on' ) { 
     $protocol = 'https';
     $ssl = 1;
      }
 
 
 
 define("J_HOST",$protocol.'://'.'asp.results.com.br');
 define("J_DOMAIN_SET_PATH",'/var/www/html/results/components/com_activehelper_livehelp/server/domains');
 define("J_DIR_PATH",'/results/components/com_activehelper_livehelp');
 define("J_CONF_PATH",'/var/www/html/results');
 define("J_CONF_SSL",$ssl);
 
 
?>