<?php

/**
 * @Product     ActiveHelper LiveHelp component for Joomla
 * @package		ViewEmail
 * @subpackage	View
 * @copyright	(C) 2010- 2017 ActiveHelper Inc.
 * @author		ActiveHelper Inc.
 * @version     5.0
 * @Joomla      3.7
 * @license   : GNU/GPL http://www.gnu.org/copyleft/gpl.html
*/

defined( '_JEXEC' ) or die( 'Restricted access' );

jimport( 'joomla.application.component.view');

if(!defined('DIRECTORY_SEPARATOR')) define('DIRECTORY_SEPARATOR', '/');

JTable::addIncludePath(JPATH_COMPONENT_ADMINISTRATOR . DIRECTORY_SEPARATOR . 'tables');

class activehelper_livehelpViewEmail extends JViewLegacy
{
	function display($tpl = null)
	{	   
	   $jinput    = JFactory::getApplication();
	   
	   $name        = $jinput->input->getString('visitor'));   
       $sbSQL       = $jinput->input->getString('SQL')); 
	   $email       = $jinput->input->getString('email'));
	   
	   // Error log
		/* JLog::addLogger(array('text_file' => 'livechat.log'));
		   JLog::add("Name ==>: " . $name ); 
		   JLog::add("SQL ==>: " . $sbSQL ); 
		   JLog::add("Email ==>: " . $email ); 
		 */
		 
		       
       $sbChat = $this->chat_text($sbSQL);

       $editor =& JFactory::getEditor();
       $this->assignRef('editor', $editor);
       $this->assignRef('chat', $sbChat);

       $this->assignRef('name', $name);
       $this->assignRef('email', $email);

		parent::display($tpl);
	}

  function chat_text($sbSQL)
       {
         $CR = "\r\n";
        
         $db = JFactory::getDbo();

          $db->setQuery($sbSQL);
      
       	if (!$db->query()) {
			$this->setError($this->_db->getErrorMsg());
			return false;
		  } 
          

         $rows = $db->loadAssocList();

    foreach ($rows as $row )
       {
          $sbText ='[Name] '. $row["username"] . ' [Message] ' . $row["message"] . ' [Time] ' . $row["time"];
         $sbChat = $sbChat.$CR.$sbText;

         }

         return $sbChat;
     }
}