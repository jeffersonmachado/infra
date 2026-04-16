<?php

/**
 * @Product     ActiveHelper LiveHelp component for Joomla
 * @package		Reports
 * @subpackage	Controller
 * @copyright	(C) 2010- 2017 ActiveHelper Inc.
 * @author		ActiveHelper Inc.
 * @version     5.0
 * @Joomla      3.7
 * @license   : GNU/GPL http://www.gnu.org/copyleft/gpl.html
*/


defined( '_JEXEC' ) or die( 'Restricted access' );

jimport('joomla.application.component.controllerform');
jimport('joomla.log.log');

include_once (JPATH_COMPONENT_ADMINISTRATOR .'/lib/csvcreation.php');

class activehelper_livehelpControllerreports extends JControllerForm
{
  	function __construct($config = array())
	{
		parent::__construct($config);
		
		$jinput = JFactory::getApplication()->input;	       
        $task   = $jinput->get('task');	 
	                     
	}

	function edit()
	{
		
		 $jinput = JFactory::getApplication()->input;		 
	     $jinput->set('view', 'Agent'); 

		$this->display();
	}

	function display()
	{
    
	    $jinput = JFactory::getApplication()->input;	       
        $view= $jinput->get('view');	 
		

		if (!$view) {
			$jinput->set('view', 'reports'); 
		}
		parent::display();
	}

  function monthly_chats()
  {
      $jinput = JFactory::getApplication()->input;		 
	  $jinput->set('view', 'monthly_chats'); 
		          
      $this->display();
  }

  function offline_messages()
  {
	  $jinput = JFactory::getApplication()->input;		 
	  $jinput->set('view', 'offline_messages'); 
	  
      $this->display();
  }
  
   function time_by_chat()
  {
      $jinput = JFactory::getApplication()->input;		 
	  $jinput->set('view', 'time_by_chat');
	  
      $this->display();
  }

   function failed_chats()
  {
	  
	  $jinput = JFactory::getApplication()->input;		 
	  $jinput->set('view', 'failed_chats');
	  
      $this->display();
  }
  function chats_by_dept()
  {
      $jinput = JFactory::getApplication()->input;		 
	  $jinput->set('view', 'chats_by_dept');
	  
      $this->display();
  }

  function chats_by_country()
  {
      $jinput = JFactory::getApplication()->input;		 
	  $jinput->set('view', 'chats_by_country');
	 
      $this->display();
  }

function chat_by_keyword()
  {
      $jinput = JFactory::getApplication()->input;		 
	  $jinput->set('view', 'chat_by_keyword');

      $this->display();
  }

  function read_chat()
  {
      $jinput = JFactory::getApplication()->input;		 
	  $jinput->set('view', 'read_chat');
	  
      $this->display();
  }
  
    function unanswered_chats()
  {
      $jinput = JFactory::getApplication()->input;		 
	  $jinput->set('view', 'unanswered_chats');
	  
      $this->display();
  }

  function aboutbox()
  {
      $jinput = JFactory::getApplication()->input;		 
	  $jinput->set('view', 'about');

      $this->display();
  }


   function tocsv()
  {

     $jinput = JFactory::getApplication()->input;		 
	 $jinput->set('view', 'csv');
	  
    $this->display();

  }

function server_settings()
  {

     $jinput = JFactory::getApplication()->input;		 
	 $jinput->set('view', 'server_settings');
	
    $this->display();

  }
  
  function record_count($query)
  {
   $db =& JFactory::getDBO();
   $db->execute($query);

   return $db->getAffectedRows();

  }

  function send_chat()
  {
      $jinput = JFactory::getApplication()->input;		 
	  $jinput->set('view', 'email');
	 
      $this->display();
  }



	function sendemail()
	{
	
 
    	jimport( 'joomla.mail.mail' );
		jimport( 'joomla.mail.helper' );
		
		$jinput = JFactory::getApplication()->input;
            
			
		$sender_email  = $jinput->getString('sender_email');		
		$recipient     = $jinput->getString('recipient');
		$sender_name   = $jinput->getString('sender_name');
		$message       = $jinput->getString('mail');
				
        $subject ='LiveChat Internal chat session';
		$body =$message;

		$sender_name  = JMailHelper::cleanAddress($sender_name);
		$subject      = JMailHelper::cleanSubject($subject);
		$body         = JMailHelper::cleanBody($body);
        
                     
       $mail = JFactory::getMailer();
	   
	  
            $mail->addRecipient($recipient);
            $mail->setSubject($subject);
            $mail->setBody($body);

			if ($mail->Send()) 
          { echo "Mail sent successfully.";
            } 
         else 
          {  echo "An error occurred.  Mail was not sent.";
             }
					       
    $this->setRedirect('index.php?option=com_activehelper_livehelp&view=reports');
        
	}

	function delete()
	{
		   
		$jinput = JFactory::getApplication()->input;
		$cid    = $jinput->get('cid');
		               
        $model = $this->getModel( 'reports' );
                       
		if ($model->remove_chat($cid)) {
			$msg = JText::_( 'Chat deleted : ' );
		} else {
			$msg = JText::_( 'Error Deleting the chat : ' );
            $jinput->enqueueMessage(JText::_('500'), $msg);
		}  
        
       $this->setRedirect('index.php?option=com_activehelper_livehelp&view=time_by_chat', $msg . $cid );
	}
 
 function delete_offline_message()
	{
		$jinput = JFactory::getApplication()->input;
		$cid    = $jinput->get('cid');
        
        $model = $this->getModel( 'reports' );
                       
		if ($model->remove_offline_message($cid)) {
			$msg = JText::_( 'Offline message deleted : ' );
		} else {
			$msg = JText::_( 'Error deleting the offline message : ' );
            $jinput->enqueueMessage(JText::_('500'), $msg);
		}  
        
       $this->setRedirect('index.php?option=com_activehelper_livehelp&view=offline_messages' ,  $msg . $cid );
	}
    

 	function answer_message()
	{
	
		$jinput  = JFactory::getApplication()->input;
		$cid     = $jinput->get('cid');
		$cstatus = $jinput->get('cstatus');
		
        $model = $this->getModel( 'reports' );
                               
		if ($model->change_offline_message_status($cid,$cstatus)) {
			$msg = JText::_( 'Message status changed : ' );
		} else {
			$msg = JText::_( 'Message status cannot change : ' );
            $jinput->enqueueMessage(JText::_('500'), $msg);
		}  
        
        $this->setRedirect('index.php?option=com_activehelper_livehelp&view=offline_messages' ,  $msg . $cid );
	}   
}