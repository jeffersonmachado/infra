<?php

/**
 * @Product     ActiveHelper LiveHelp component for Joomla
 * @package		Agent
 * @subpackage	Contollers
 * @copyright	(C) 2010- 2017 ActiveHelper Inc.
 * @author		ActiveHelper Inc.
 * @version     5.0
 * @Joomla      3.7    
 * @license   : GNU/GPL http://www.gnu.org/copyleft/gpl.html
*/

// No direct access to this file
defined('_JEXEC') or die('Restricted access');


jimport('joomla.application.component.controllerform');
jimport('joomla.log.log');


/**
 * Domain Controller
 */
class activehelper_livehelpControllerAgent extends JControllerForm
{  
     
 
function __construct($config = array())
	{
		parent::__construct($config);                
      
	    $jinput = JFactory::getApplication()->input;	       
        $task  = $jinput->get('task');	  

        $this->registerTask('apply', 'save');
	
	}
    
	function add()
	{
		             
     $jinput = JFactory::getApplication()->input;		 
	 $jinput->set('view', 'agent'); 
	 	 
	  $this->display();
    
	}
    
   function edit($key = NULL, $urlVar = NULL)
	{
		$jinput = JFactory::getApplication()->input;		 
	    $jinput->set('view', 'agent'); 
            
		$this->display();		
	}   
    
 	function save($key = NULL, $urlVar = NULL)
	{
	
	    $jinput = JFactory::getApplication();
	    $row      = $jinput->input->post->getArray();	
			
		$domains         = $jinput->input->get('domains_selected', 'default_value', 'array');       
         //$domains_current = $jinput->input->get('domains_selected_default');   	
				
		 // Error log
		/* JLog::addLogger(array('text_file' => 'livechat.log'));
		 JLog::add("domains ==>: " . print_r($domains,1)); 
		 JLog::add("domains_c ==>: " . print_r($domains_current,1));
		 */
        			 
		$id = $jinput->input->get('id');        
        $id  = $id[0];
       
        $model = $this->getModel( 'agent' );
                                 
		if ($id_agent = $model->store($row,$domains,$domains_current)) {
			$msg = JText::_( 'Agent Saved' );
		} else {
			$msg = JText::_( 'Error Saving Domain' );
			$jinput->enqueueMessage(JText::_('500'), $msg);			
		}
               
		if ($this->getTask() == 'apply') {              
			$this->setRedirect('index.php?option=com_activehelper_livehelp&task=edit&controller=agent&cid[]=' . $id[0], 'Changes Applied'); 
            }
          else   
        if ($id !=0) 
        {
			$this->setRedirect('index.php?option=com_activehelper_livehelp&view=agents', $msg);
		
	      } 
        else
        if ($id ==0)                  
        {
			$this->setRedirect('index.php?option=com_activehelper_livehelp&view=clientinfo&cid[]=' . $id_agent, $msg);
		
	      }     
      
      
       }
       
       
   function client_info()
	{     
	   $jinput = JFactory::getApplication()->input;		 
	   $jinput->set('view', 'clientinfo'); 
	   
        $this->display();
 
	}
    
  function settings()
	{     
	   
	   $jinput = JFactory::getApplication()->input;		 
	   $jinput->set('view', 'agent_settings'); 
	  
        $this->display();

	} 
    
    
 function domains()
	{
     $jinput = JFactory::getApplication();
     $token  = JSession::getFormToken();
	 
    if (!$token || !$jinput->input->get($token, null, 'alnum'))		
        $this->setError('Invalid (or expired) request token.');
	 
        $jinput->set('view','Agent_domains'); 	   	 
        $this->display();
     
	}    
       
       
  function edit_domain()
	{
      
	  $jinput = JFactory::getApplication();

      $model = $this->getModel( 'agent' );  


   if ($model->edit_domain_status()) {
			$msg = JText::_( 'Agent status changed' );
		} else {
			$msg = JText::_( 'Error changed the status' );			
			$jinput->enqueueMessage(JText::_('500'), $msg);
			           
		}
        
     $this->setRedirect('index.php?option=com_activehelper_livehelp&view=agents');
	}
    
function updatesettings()
	{
       
     $jinput = JFactory::getApplication();
	 $model = $this->getModel('agent');  


   if ($model->updatesettings()) {
			$msg = JText::_( 'Agent status indicator changed' );
		} else {
			$msg = JText::_( 'Error changed the status' );            
			$jinput->enqueueMessage(JText::_('500'), $msg);
		}
        
     $this->setRedirect('index.php?option=com_activehelper_livehelp&view=agents');
	}        
                          	                 	
}
