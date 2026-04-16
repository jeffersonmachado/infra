<?php

 /**
 * @ ActiveHelper LiveHelp component for Joomla
 * @version   : 5.0
 * @author    : ActiveHelper Inc.
 * @copyright : (C) 2010- 2017 ActiveHelper Inc.
 * @license   : GNU/GPL http://www.gnu.org/copyleft/gpl.html 
**/


defined('_JEXEC') or die('Restricted access');

// import Joomla table library
jimport('joomla.database.table');

class TableDomain extends JTable
{
	public $id_domain       = null;
	public $name            = null;
	public $status          = null;
	public $configuration   = null;

	function __construct(&$db)
	{
    parent::__construct( '#__livehelp_domains', 'id_domain', $db );

	}

      	  
}