<?php

/**
 * @Product     ActiveHelper LiveHelp component for Joomla
 * @package		Chats_by_country
 * @subpackage	View
 * @copyright	(C) 2010- 2017 ActiveHelper Inc.
 * @author		ActiveHelper Inc.
 * @version     5.0
 * @Joomla      3.7
 * @license   : GNU/GPL http://www.gnu.org/copyleft/gpl.html
*/


defined( '_JEXEC' ) or die( 'Restricted access' );

jimport( 'joomla.application.component.view');


class activehelper_livehelpViewChats_by_country extends JViewLegacy
{
		function display($tpl = null)
	{
        $jinput = JFactory::getApplication();
        $model  = $this->getModel();      
      
      	// Get data from the model
		$items = $this->get('Items');
		$pagination = $this->get('Pagination');

		// Check for errors.
		if (count($errors = $this->get('Errors'))) 
		{
			$jinput->enqueueMessage(JText::_('500'), $errors);
			return false;
		}
      
       $this->assignRef('rows', $items);
       $this->assignRef('pagination', $pagination);
       
       $this->assign('start_date', $model->start_date_value());
       $this->assign('end_date', $model->end_date_value());  
       $this->assign('ExecSQL', $model->buildSearch());
      
       // Set the toolbar
      $this->addToolBar();
      
      parent::display($tpl);

	}
    
        
	protected function addToolBar() 
	{
		JToolBarHelper::title(JText::_('COM_ACTIVEHELPER_LIVEHELP_R_CHATS_BY_COUNTRY'));
        JToolBarHelper::custom('reports.tocsv', 'publish.png', 'publish_f2.png',JText::_('COM_ACTIVEHELPER_LIVEHELP_B_TOCSV'), false); 
        
	}
    
}
