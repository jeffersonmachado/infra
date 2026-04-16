<?php

/**
 * @Product     ActiveHelper LiveHelp component for Joomla! 
 * @package		Domain
 * @subpackage	Viewes
 * @copyright	(C) 2010- 2017 ActiveHelper Inc.
 * @author		ActiveHelper Inc.
 * @version     5.0
 * @Joomla      3.7
 * @license   : GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */


defined( '_JEXEC' ) or die( 'Restricted access' );

jimport( 'joomla.application.component.view');
jimport('joomla.log.log');

class activehelper_livehelpViewDomain extends JViewLegacy
{
   protected $form = null;
        
	function display($tpl = null)
	{        
  		// get the Data
		
        $jinput = JFactory::getApplication();
        $item	= $this->get('Data');        
  		
		// Check for errors.
		if (count($errors = $this->get('Errors'))) 
		{
			$jinput->enqueueMessage(JText::_('500'), $errors);	
			return false;
		}
                   
		$this->assignRef('row', $item);

		$editor =JFactory::getEditor('none');
		$this->assignRef('editor', $editor);

		$status = array(
			array('value' => '0', 'text' => 'Disable'),
			array('value' => '1', 'text' => 'Enable'));

		$this->assign('status', JHTML::_('select.genericList', $status, 'status', 'class="inputbox" '. '', 'value', 'text', $item->status ));
  
  		// Set the toolbar
		$this->addToolBar($item);
        
		parent::display($tpl);
	}
  
  	protected function addToolBar($item) 
	{
          
          /*JLog::addLogger(array());
          JLog::add('Adiciona menu');   
          */
          
	     if ($item->id_domain >0) {
	       JToolBarHelper::title(JText::_('COM_ACTIVEHELPER_LIVEHELP_EDIT_DOMAIN'));} 
           else {
           JToolBarHelper::title(JText::_('COM_ACTIVEHELPER_LIVEHELP_ADD_DOMAIN'));}
                  		
		   JToolBarHelper::save('domain.save' , $alt = 'JTOOLBAR_SAVE');
           JToolBarHelper::save('cancel', $alt = 'JTOOLBAR_CLOSE');      		 
	
	}  
}