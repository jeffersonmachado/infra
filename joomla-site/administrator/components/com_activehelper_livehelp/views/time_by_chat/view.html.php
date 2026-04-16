<?php


/**
 * @Product     ActiveHelper LiveHelp component for Joomla
 * @package		Time_by_chat
 * @subpackage	View
 * @copyright	(C) 2010- 2017 ActiveHelper Inc.
 * @author		ActiveHelper Inc.
 * @version     5.0
 * @Joomla      3.7
 * @license   : GNU/GPL http://www.gnu.org/copyleft/gpl.html 
*/

defined( '_JEXEC' ) or die( 'Restricted access' );

jimport( 'joomla.application.component.view');
jimport('joomla.log.log');


class activehelper_livehelpViewTime_by_chat extends JViewLegacy
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
		JToolBarHelper::title(JText::_('COM_ACTIVEHELPER_LIVEHELP_R_TIME_BY_CHAT'));
        JToolBarHelper::custom('reports.tocsv', 'publish.png', 'publish_f2.png',JText::_('COM_ACTIVEHELPER_LIVEHELP_B_TOCSV'), false); 
       //JToolBarHelper::cancel(JTOOLBAR_CLOSE); 
	}
       
}
