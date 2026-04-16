<?php

/**
 * @Product     ActiveHelper LiveHelp component for Joomla
 * @package		Main Controller
 * @subpackage	Contollers
 * @copyright	(C) 2010- 2017 ActiveHelper Inc.
 * @author		ActiveHelper Inc.
 * @version     5.0
 * @Joomla      3.7
 * @license   : GNU/GPL http://www.gnu.org/copyleft/gpl.html
*/

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

// import Joomla controller library
jimport('joomla.application.component.controller');
jimport('joomla.log.log');

  // Main
	$homeLink = '<i class="icon-home"></i>&nbsp;' . JText::_('COM_ACTIVEHELPER_LIVEHELP_CONTROL_PANEL');	
	JSubMenuHelper::addEntry($homeLink, 'index.php?option=com_activehelper_livehelp', true);
	
	$domains = '<i class="icon-tree-2"></i>&nbsp;' . JText::_('COM_ACTIVEHELPER_LIVEHELP_M_OPT_DOMAINS');
    JSubMenuHelper::addEntry($domains, 'index.php?option=com_activehelper_livehelp&view=domains', false);
	
	$agents = '<i class="icon-users"></i>&nbsp;' . JText::_('COM_ACTIVEHELPER_LIVEHELP_M_OPT_AGENTS');	
    JSubMenuHelper::addEntry($agents, 'index.php?option=com_activehelper_livehelp&view=agents', false);	
	
	$reports = '<i class="icon-chart"></i>&nbsp;' . JText::_('COM_ACTIVEHELPER_LIVEHELP_REPORTS');		
	JSubMenuHelper::addEntry($reports, 'index.php?option=com_activehelper_livehelp&view=statistics', false);	
   
   $server_settings = '<i class="icon-options"></i>&nbsp;' . JText::_('COM_ACTIVEHELPER_LIVEHELP_R_SERVER_SETTINGS');	
   JSubMenuHelper::addEntry($server_settings, 'index.php?option=com_activehelper_livehelp&view=server_settings', false);
   
   $server_languages = '<i class="icon-options"></i>&nbsp;' . JText::_('COM_ACTIVEHELPER_LIVEHELP_SERVER_LANGUAGES_EDIT');	
   JSubMenuHelper::addEntry($server_languages, 'index.php?option=com_activehelper_livehelp&view=languages', false);
   
   $about = '<i class="icon-help"></i>&nbsp;' . JText::_('COM_ACTIVEHELPER_LIVEHELP_M_OPT_ABOUT');	
   JSubMenuHelper::addEntry($about, 'index.php?option=com_activehelper_livehelp&view=about', false);
       
class Activehelper_LivehelpController extends JControllerLegacy {     	
    /**
	 * display task
	 *
	 * @return void
	 */
 
 function display($cachable = false, $urlparams = false) 
 
	{
		$document = JFactory::getDocument();
		
$style =  'body {'
    .'background: #FFFFFF;'
    .'left: 0;'
    .'top: 0;'
    .'bottom: 0;'
    .'position: absolute;'
    .'width: 100%;'
	.'}';
	

       $document->addStyleDeclaration($style);
		
		// set default view if not set
		 $jinput = JFactory::getApplication()->input;		 
		 $jinput->set('view', $jinput->getCmd('view', 'reports')); 
		 
		 		      
        // call parent behavior
		parent::display();
        
     
	}
    
    
    
}
