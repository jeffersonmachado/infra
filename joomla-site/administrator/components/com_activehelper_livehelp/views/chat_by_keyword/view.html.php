<?php

/**
 * @Product     ActiveHelper LiveHelp component for Joomla
 * @package		Chat_by_keyword
 * @subpackage	View
 * @copyright	(C) 2010- 2017 ActiveHelper Inc.
 * @author		ActiveHelper Inc.
 * @version     5.0
 * @Joomla      3.7
 * @license   : GNU/GPL http://www.gnu.org/copyleft/gpl.html
*/


defined( '_JEXEC' ) or die( 'Restricted access' );

jimport( 'joomla.application.component.view');
jimport('joomla.error.log');

class activehelper_livehelpViewChat_by_keyword extends JViewLegacy
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


          // Get the current SQL
          $ExecSQL = $model ->buildSearch();
        
		  $this->assignRef('rows', $items);
          $this->assignRef('pagination', $pagination);
          $this->assignRef('ExecSQL', $ExecSQL);
         
  		// Set the toolbar
		$this->addToolBar();

        parent::display($tpl);

	}
    
    
	protected function addToolBar() 
	{
		JToolBarHelper::title(JText::_('COM_ACTIVEHELPER_LIVEHELP_R_CHAT_BY_KEYWORD'));
        
	} 

}
