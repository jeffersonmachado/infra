<?php


/**
 * @Product     ActiveHelper LiveHelp component for Joomla
 * @package		Unanswered Chats
 * @subpackage	Views
 * @copyright	(C) 2010- 2017 ActiveHelper Inc.
 * @author		ActiveHelper Inc.
 * @version     3.9
 * @Joomla      3.3  
 * @license   : GNU/GPL http://www.gnu.org/copyleft/gpl.html 
*/



defined( '_JEXEC' ) or die( 'Restricted access' );

jimport( 'joomla.application.component.view');

class activehelper_livehelpViewUnanswered_Chats  extends JViewLegacy
{
  function display($tpl = null)  
	{
        $jinput = JFactory::getApplication()->input;
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
		JToolBarHelper::title(JText::_('COM_ACTIVEHELPER_LIVEHELP_R_UNANSWERED_CHATS'));
        JToolBarHelper::custom('reports.tocsv', 'publish.png', 'publish_f2.png',JText::_('COM_ACTIVEHELPER_LIVEHELP_B_TOCSV'), false); 
        JToolBarHelper::cancel('cancel', $alt = 'JTOOLBAR_CLOSE'); 
	}
       
}
