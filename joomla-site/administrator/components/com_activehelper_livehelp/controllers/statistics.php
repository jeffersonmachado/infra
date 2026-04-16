<?php

/**
 * @Product     ActiveHelper LiveHelp component for Joomla
 * @package		statistics
 * @subpackage	Controller
 * @copyright	(C) 2010- 2017 ActiveHelper Inc.
 * @author		ActiveHelper Inc.
 * @version     5.0
 * @Joomla      3.7
 * @license   : GNU/GPL http://www.gnu.org/copyleft/gpl.html
*/


defined( '_JEXEC' ) or die( 'Restricted access' );

jimport('joomla.application.component.controllerform');
jimport('joomla.log.log');

class activehelper_livehelpControllerstatistics extends JControllerForm
{
  	function __construct($config = array())
	{
		parent::__construct($config);
		
		$jinput = JFactory::getApplication()->input;	       
        $task   = $jinput->get('task');	 
	                     
	}

	function display()
	{
    
	    $jinput = JFactory::getApplication()->input;	       
        $view= $jinput->get('view');	 
		

		if (!$view) {
			$jinput->set('view', 'statistics'); 
		}
		parent::display();
	}
}