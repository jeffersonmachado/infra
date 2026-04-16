<?php

 /**
 * @ ActiveHelper LiveHelp component for Joomla
 * @author    : ActiveHelper Inc.
 * @copyright : (C) 2010- 2017 ActiveHelper Inc.
 * @license   : GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @version     5.0
 * @Joomla      3.7
**/


defined( '_JEXEC' ) or die( 'Restricted access' );

jimport( 'joomla.application.component.view');

class activehelper_livehelpViewrestrictions extends JViewLegacy
{
	function display($tpl = null)
	{
        $jinput = JFactory::getApplication()->input;
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
		JToolBarHelper::title(JText::_('COM_ACTIVEHELPER_LIVEHELP_M_OPT_RESTRICTION'));	
        JToolBarHelper::addNew('domain.add_country_restriction', $alt = 'JTOOLBAR_NEW');
        JToolBarHelper::deleteList( JText::_( 'COM_ACTIVEHELPER_LIVEHELP_WARNING_DELETE_ITEMS' ), 'domains.removerestriction', $alt = 'JTOOLBAR_DELETE');     
        JToolBarHelper::divider();
                      		               
	}      
}