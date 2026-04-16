<?php

 /**
 * @ ActiveHelper LiveHelp component for Joomla
 * @version   :  5.0
 * @author    : ActiveHelper Inc.
 * @copyright : (C) 2010- 2017 ActiveHelper Inc.
 * @license   : GNU/GPL http://www.gnu.org/copyleft/gpl.html 
**/


defined('_JEXEC') or die('Restricted access');

class TableUser extends JTable
{
	var $id = null;
	var $username  = null;
	var $password  = null;
	var $firstname = null;
    var $lastname  = null;
    var $email     = null;
    var $department= null;
    var $privilege = null;  
    var $photo     = null;
    var $status    = null;
    var $answers   = null;
    
	function __construct(&$db)
	{
    parent::__construct( '#__livehelp_users', 'id', $db );
	}
}