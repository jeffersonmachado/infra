<?php

/**
 * @Product     ActiveHelper LiveHelp component for Joomla
 * @package		Script
 * @subpackage	Views
 * @copyright	(C) 2010- 2017 ActiveHelper Inc.
 * @author		ActiveHelper Inc.
 * @version     5.0
 * @Joomla      3.7     
 * @license   : GNU/GPL http://www.gnu.org/copyleft/gpl.html
*/

defined( '_JEXEC' ) or die( 'Restricted access' );

jimport( 'joomla.application.component.view');
jimport('joomla.filesystem.file');
jimport('joomla.log.log');
jimport('joomla.filesystem.folder');
jimport('joomla.string');

include_once (JPATH_COMPONENT_ADMINISTRATOR .'/language/english.php');

class activehelper_livehelpViewedit_language_file extends JViewLegacy
{
	function display($tpl = null)
	{   
    global $option;
	
	 $jinput        = JFactory::getApplication()->input;	
     $editor_script = JFactory::getEditor('none');	 
	 
     $lang     = $jinput->get('languages');
	 
	 if(!defined('DIRECTORY_SEPARATOR')) define('DIRECTORY_SEPARATOR', '/');	 
	 
	 $file_name = "lang_guest_" . $lang. ".php";
	 
     $file_path = JPATH_SITE . DIRECTORY_SEPARATOR . 'components/com_activehelper_livehelp/server'  . '/' . 'i18n' . '/' . $lang ;
     
	 $text_file = JFile::read($file_path.'/'. $file_name);
	 
	     // Log
		 /* JLog::addLogger(array('text_file' => 'livechat.log'));
		   JLog::add("path ==>: ".$file_path); 
		   JLog::add("text  ==>: " .$text_file ); 
		  */
	         	
	     $this->assignRef('language_file', $text_file);
         $this->assignRef('editor_script',$editor_script);
		 $this->assignRef('file_name',$file_name);
		 $this->assignRef('file_path',$file_path );
		 

			         
   
		  
    // Set the toolbar
    $this->addToolBar(); 
           
    parent::display($tpl);
    
	}   
     
  	protected function addToolBar() 
	{
             
        JToolBarHelper::title(JText::_('COM_ACTIVEHELPER_LIVEHELP_SERVER_LANGUAGES_EDIT_FILE')); 
		JToolBarHelper::Apply('domain.save_language_changes',JText::_( 'COM_ACTIVEHELPER_LIVEHELP_SERVER_LANGUAGES_FILE_SAVE'));	
        JToolBarHelper::cancel('cancel', $alt = 'JTOOLBAR_CLOSE' );
	}      
}