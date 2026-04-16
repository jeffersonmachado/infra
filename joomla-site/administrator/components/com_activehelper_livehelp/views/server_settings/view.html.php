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

class activehelper_livehelpViewServer_Settings extends JViewLegacy
{
	function display($tpl = null)
	{
	   
        if(!defined('DIRECTORY_SEPARATOR')) define('DIRECTORY_SEPARATOR', '/');  
	   
     include_once (JPATH_ROOT .DIRECTORY_SEPARATOR. 'components'.DIRECTORY_SEPARATOR. 'com_activehelper_livehelp' .DIRECTORY_SEPARATOR. 'server' .DIRECTORY_SEPARATOR. 'import' .DIRECTORY_SEPARATOR. 'constants.php');
       
	  $this->assignRef('connection_timeout', $connection_timeout);
      $this->assignRef('keep_alive_timeout', $keep_alive_timeout);
      $this->assignRef('guest_login_timeout', $guest_login_timeout);
      $this->assignRef('chat_refresh_rate', $chat_refresh_rate);
      $this->assignRef('status_indicator_img_type', $status_indicator_img_type);
      $this->assignRef('invitation_position', $invitation_position);
      
      $sound_alert = array(
			array('value' => '0', 'text' => 'Disable'),
			array('value' => '1', 'text' => 'Enable'));
            
    $this->assign('sound_alert', JHTML::_('select.genericList', $sound_alert, 'sound_alert', 'class="inputbox" '. '', 'value', 'text', $sound_alert_new_message));
    
    
    $sound_alert_pop = array(
			array('value' => '0', 'text' => 'Disable'),
			array('value' => '1', 'text' => 'Enable'));
            
    $this->assign('sound_alert_pop', JHTML::_('select.genericList', $sound_alert_pop, 'sound_alert_pop', 'class="inputbox" '. '', 'value', 'text', $sound_alert_new_pro_msg));

    
    if ($status_indicator_img_type == "gif")
          $status_indicator_img_type=0 ; 
        else     
      if ($status_indicator_img_type =="png" )
          $status_indicator_img_type=1;
        else
      if ($status_indicator_img_type == "jpg")
          $status_indicator_img_type=2;
        else  
      if ($status_indicator_img_type == "jpeg" )
          $status_indicator_img_type=3;
        else  
      if ($status_indicator_img_type =="bmp" )
          $status_indicator_img_type=4;
           
          $img_type = array(
			array('value' => '0', 'text' => 'gif'),
			array('value' => '1', 'text' => 'png'),
            array('value' => '2', 'text' => 'jpg'),
            array('value' => '3', 'text' => 'jpeg'),
            array('value' => '4', 'text' => 'bmp'));
            
    $this->assign('img_type', JHTML::_('select.genericList', $img_type, 'img_type', 'class="inputbox" '. '', 'value', 'text', $status_indicator_img_type));
     

     if ($invitation_position == "right")
          $invitation_position=0 ; 
        else     
      if ($invitation_position =="center" )
          $invitation_position=1;
        else
      if ($invitation_position == "left")
          $invitation_position=2;

  $inv_position = array(
			array('value' => '0', 'text' => 'right'),
			array('value' => '1', 'text' => 'center'),
            array('value' => '2', 'text' => 'left'));
            
    $this->assign('inv_position', JHTML::_('select.genericList', $inv_position, 'inv_position', 'class="inputbox" '. '', 'value', 'text', $invitation_position));
        
      
       # Server statistic
    $request  = $this-> get_request_number();
    $chats    = $this-> get_chat_sessions_number();
    $messages = $this-> get_total_chat_messages();
	$core_ver = $this-> get_core_setting("1");
	
    
    $this->assignRef('request', $request);
    $this->assignRef('chats', $chats);
    $this->assignRef('messages', $messages);
	$this->assignRef('core_ver', $core_ver);
      
      
      // Set the toolbar
      $this->addToolBar(); 
    
      parent::display($tpl);
	}
    
   	protected function addToolBar() 
	{
             
        JToolBarHelper::title(JText::_('COM_ACTIVEHELPER_LIVEHELP_SERVER_SETTINGS'));
        JToolBarHelper::save('domain.updateServer'); 
        JToolBarHelper::Apply('domain.reset',JText::_( 'COM_ACTIVEHELPER_LIVEHELP_RESET_SETTINGS'));
        JToolBarHelper::Apply('domain.clearup',JText::_( 'COM_ACTIVEHELPER_LIVEHELP_CLEARUP')); 
        JToolBarHelper::Apply('domain.install_package',JText::_( 'COM_ACTIVEHELPER_LIVEHELP_SERVER_INSTALL'));			
        JToolBarHelper::Apply('domain.export_chats',JText::_( 'COM_ACTIVEHELPER_LIVEHELP_EXPORT_CHATS'));
        JToolBarHelper::Apply('domain.delete_chats',JText::_( 'COM_ACTIVEHELPER_LIVEHELP_DELETE_CHATS'));        
        
        JToolBarHelper::cancel();
	}  
    
    function get_request_number()
       {
         
         $db = JFactory::getDbo();
         $query = $db->getQuery(true);         
         $setSQL = "select count(*) from #__livehelp_requests";
       
         $db->setQuery($setSQL);
       
      	if (!$db->query()) {
			$this->setError($this->_db->getErrorMsg());
			return false;
		  } 
          
       return $db->loadResult();        
     }
     
  function get_chat_sessions_number()
       {
          $db = JFactory::getDbo();
          $query = $db->getQuery(true);         
          $setSQL = "select count(*) from #__livehelp_sessions";
        
         $db->setQuery($setSQL);
       
      	if (!$db->query()) {
			$this->setError($this->_db->getErrorMsg());
			return false;
		  } 
          
       return $db->loadResult();      
     }
     
  function get_total_chat_messages()
       {
          $db = JFactory::getDbo();
          $query = $db->getQuery(true);
          $setSQL = "select count(*) from #__livehelp_messages";
         
         $db->execute($setSQL);
       
      	if (!$db->query()) {
			$this->setError($this->_db->getErrorMsg());
			return false;
		  } 
          
       return $db->loadResult();      
     }    

  
    function get_core_setting($id)
      {     
         
       $db = JFactory::getDbo();
       $query = $db->getQuery(true);
      
      $query->select('value')
       ->from('#__livehelp_core_settings')
	   ->where("id =" . $id); 

      
      $db->setQuery($query);
      return $db->loadResult(); 
          
     } 	 
}