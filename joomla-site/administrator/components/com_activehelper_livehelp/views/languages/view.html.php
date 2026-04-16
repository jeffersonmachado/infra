<?php

/**
 * @Product     ActiveHelper LiveHelp component for Joomla
 * @package		Server Settings
 * @subpackage	Views
 * @copyright	(C) 2010- 2017 ActiveHelper Inc.
 * @author		ActiveHelper Inc.
 * @version     5.0
 * @Joomla      3.7
 * @license   : GNU/GPL http://www.gnu.org/copyleft/gpl.html 
*/

defined( '_JEXEC' ) or die( 'Restricted access' );

jimport( 'joomla.application.component.view');
jimport('joomla.filter.output');
jimport('joomla.filesystem.folder');
jimport('joomla.filesystem.file');

class activehelper_livehelpViewLanguages extends JViewLegacy
{
	function display($tpl = null)
	{
	   
      if(!defined('DIRECTORY_SEPARATOR')) define('DIRECTORY_SEPARATOR', '/');  
	   
     include_once (JPATH_ROOT .DIRECTORY_SEPARATOR. 'components'.DIRECTORY_SEPARATOR. 'com_activehelper_livehelp' .DIRECTORY_SEPARATOR. 'server' .DIRECTORY_SEPARATOR. 'import' .DIRECTORY_SEPARATOR. 'constants.php');
      
	 $languages =$this->check_installed_languages();	
     $available_languages=$this->check_available_languages();	 
	 
		  
	 $this->assign('languages', JHTML::_('select.genericList', $languages, 'languages', 'class="inputbox" '. '', 'value', 'text', ''));
	 $this->assign('available_languages', JHTML::_('select.genericList', $available_languages, 'available_languages', 'class="inputbox" '. '', 'value', 'text', ''));
      
      // Set the toolbar
      $this->addToolBar(); 
    
      parent::display($tpl);
	}
    
   	protected function addToolBar() 
	{
             
        JToolBarHelper::title(JText::_('COM_ACTIVEHELPER_LIVEHELP_SERVER_LANGUAGES_EDIT'));
		JToolBarHelper::Apply('domain.install_language',JText::_( 'COM_ACTIVEHELPER_LIVEHELP_UPLOAD_LANG'));
        JToolBarHelper::Apply('domain.edit_language',JText::_( 'COM_ACTIVEHELPER_LIVEHELP_SERVER_LANGUAGES_EDIT_FILE'));		
        JToolBarHelper::cancel();
	}  
       

  function check_installed_languages()
   {
		  $db = JFactory::getDbo();		
		  $query = $db->getQuery(true);
		  
		   $query->select('code, name')
            ->from('#__livehelp_languages')
            ->where('installed = 1')
            ->order('1');	   
	   
         $db->setQuery($query);  	  		               		
		 $rows = $db->loadObjectList();

        // Populate an array with a query list  		 
		 foreach ( $rows as $row ) {
            $langs[] = array( 'text' => $row->name, 'value' => $row->code );
           }
        return $langs; 
		    
     }
	 
 function check_available_languages()
   {
		  $db = JFactory::getDbo();		
		  $query = $db->getQuery(true);
		  
		   $query->select('code, name')
            ->from('#__livehelp_languages')
            ->where('installed = 0')
            ->order('1');	   
	   
         $db->setQuery($query);  	  		               		
		 $rows = $db->loadObjectList();

        // Populate an array with a query list  		 
		 foreach ( $rows as $row ) {
            $langs[] = array( 'text' => $row->name, 'value' => $row->code );
           }
        return $langs; 
		    
     }	 
}