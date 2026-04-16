<?php

/**
 * @Product     ActiveHelper LiveHelp component for Joomla
 * @package		Agents
 * @subpackage	Viewes
 * @copyright	(C) 2010- 2017 ActiveHelper Inc.
 * @author		ActiveHelper Inc.
 * @version     5.0
 * @Joomla      3.7 
 * @license   : GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */


defined( '_JEXEC' ) or die( 'Restricted access' );

jimport( 'joomla.application.component.view');

class activehelper_livehelpViewagents extends JViewLegacy
{
	function display($tpl = null)
	{

	   $jinput = JFactory::getApplication();
	   
    // Get data from the model
        $items = $this->get('Items');
        $pagination = $this->get('Pagination');
        
         if (count($errors = $this->get('Errors'))) 
          {
             $jinput->enqueueMessage(JText::_('500'), $errors);
             return false;
          }

       // Assign data to the view
        $this->items = $items;
        $this->pagination = $pagination;
       

	    // Set the toolbar
		$this->addToolBar();
        
    parent::display($tpl);

	}
    
 	protected function addToolBar() 
	{
		JToolBarHelper::title(JText::_('COM_ACTIVEHELPER_LIVEHELP_M_OPT_AGENTS'));	
        JToolBarHelper::addNew('agent.add');
        JToolBarHelper::editList('agent.edit');
        JToolBarHelper::deleteList( JText::_( 'COM_ACTIVEHELPER_LIVEHELP_WARNING_DELETE_ITEMS' ), 'agents.delete');
        JToolBarHelper::divider(); 	
        JToolBarHelper::custom('agent.settings', 'publish.png', 'publish_f2.png',JText::_( 'COM_ACTIVEHELPER_LIVEHELP_EDIT_SETTINGS' ), true);
        JToolBarHelper::custom('agent.client_info', 'publish.png', 'publish_f2.png',JText::_( 'COM_ACTIVEHELPER_LIVEHELP_CLIENT_INFO'), true);               
	}   
}