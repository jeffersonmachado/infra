<?php

/**
 * @Product     ActiveHelper LiveHelp component for Joomla
 * @package		About
 * @subpackage	Viewes
 * @copyright	(C) 2010- 2017 ActiveHelper Inc.
 * @author		ActiveHelper Inc.
 * @version     5.0
 * @Joomla      3.7
 * @license   : GNU/GPL http://www.gnu.org/copyleft/gpl.html 
 */

defined( '_JEXEC' ) or die( 'Restricted access' );

jimport( 'joomla.application.component.view');


class activehelper_livehelpViewAbout extends JViewLegacy
{
	function display($tpl = null)
	{
	         
       // Set the toolbar
      $this->addToolBar();
      
   	   parent::display($tpl);
	}
    
    
	protected function addToolBar() 
	{
		JToolBarHelper::title(JText::_('COM_ACTIVEHELPER_LIVEHELP_ABOUT'));
        
	}    
}
