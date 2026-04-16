<?php

/**
 * @Product     ActiveHelper LiveHelp component for Joomla 
 * @package		All
 * @subpackage	Viewes
 * @copyright	(C) 2010- 2017 ActiveHelper Inc.
 * @author		ActiveHelper Inc.
 * @version     5.0
 * @Joomla      3.7
 * @license   : GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */



// No direct access to this file
defined('_JEXEC') or die('Restricted access');

// import Joomla view library
jimport('joomla.application.component.view');
jimport('joomla.log.log');
jimport('joomla.filesystem.file');
jimport('joomla.filesystem.folder');

/**
 * Manage domains View
 */

class activehelper_livehelpViewDomains extends JViewLegacy
{

	function display($tpl = null)
	{
		
        $jinput   = JFactory::getApplication();  
		// Get data from the model
		$items = $this->get('Items');
		$pagination = $this->get('Pagination');

		// Check for errors.
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
  
		// Display the template
		parent::display($tpl);
      
	}
    
	protected function addToolBar() 
	{
		JToolBarHelper::title(JText::_('COM_ACTIVEHELPER_LIVEHELP_M_OPT_DOMAINS'));               
        JToolBarHelper::addNew('domain.add');
        JToolBarHelper::editList('domain.edit');         
        JToolBarHelper::deleteList( JText::_( 'COM_ACTIVEHELPER_LIVEHELP_WARNING_DELETE_ITEMS' ), 'domains.delete');
        JToolBarHelper::divider();
        JToolBarHelper::custom('domain.settings', 'publish.png', 'publish_f2.png',JText::_( 'COM_ACTIVEHELPER_LIVEHELP_EDIT_SETTINGS' ), true);  

	}
 }  