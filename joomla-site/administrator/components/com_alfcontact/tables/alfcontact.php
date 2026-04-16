<?php
// No direct access
defined('_JEXEC') or die('Restricted access');

// AlfContact Table class
class AlfcontactTableAlfcontact	extends JTable
{
    function __construct(&$db) 
    {
		parent::__construct('#__alfcontact', 'id', $db);
    }
}
