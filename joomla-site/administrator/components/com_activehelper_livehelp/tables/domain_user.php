<?php

 /**
 * @ ActiveHelper LiveHelp component for Joomla
 * @version   : 3.9
 * @author    : ActiveHelper Inc.
 * @copyright : (C) 2010- 2017 ActiveHelper Inc.
 * @license   : GNU/GPL http://www.gnu.org/copyleft/gpl.html 
**/


defined('_JEXEC') or die('Restricted access');

class TableDomainUser extends JTable
{
  var $id_domain = null;
  var $name = null;
  var $status = null;

  function __construct(&$db)
  {
    parent::__construct( '#__livehelp_domain_user', 'id_domain', $db );
  }
}