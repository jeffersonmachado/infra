<?php

 /**
 * @ ActiveHelper LiveHelp component for Joomla! 1.5
 * @version   : 5.0
 * @author    : ActiveHelper Inc.
 * @copyright : (C) 2010- 2017 ActiveHelper Inc.
 * @license   : GNU/GPL http://www.gnu.org/copyleft/gpl.html
**/


defined( '_JEXEC' ) or defined ('_VALID_MOS') or die('Restricted access');

$config =& JFactory::getConfig();

  $host= $config->getValue('host');
  $username = $config->getValue('user');
  $password = $config->getValue('password');
  $database = $config->getValue('database');

 DEFINE('_livehelp_hostname', $host);
 DEFINE('_livehelp_mysql_username',$username);
 DEFINE('_livehelp_mysql_password',$password);
 DEFINE('_livehelp_mysql_database',$database);


?>
