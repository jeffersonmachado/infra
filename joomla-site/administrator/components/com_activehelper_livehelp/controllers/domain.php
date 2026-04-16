<?php

/**
 * @Product     ActiveHelper LiveHelp component for Joomla
 * @package		domain
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
class activehelper_livehelpControllerDomain extends JControllerForm
{  
     
     
function __construct($config = array())
	{
		parent::__construct($config);                
          		  
		$jinput = JFactory::getApplication()->input;		
        $task = $jinput->getCmd('task', '');
				          
       $this->registerTask('apply', 'save');


	}
    
	function add($key = NULL, $urlVar = NULL)
	{
		             					
       $jinput = JFactory::getApplication()->input;		 
	   $jinput->set('view', 'domain'); 
	        
		$this->display();
    
	}
    
   function edit($key = NULL, $urlVar = NULL)
	{
		
		$jinput = JFactory::getApplication()->input;		 
	    $jinput->set('view', 'domain'); 
		          
		$this->display();
	}   
    
 	function save($key = NULL, $urlVar = NULL)
	{
	
	    $jinput   = JFactory::getApplication();
        $row      = $jinput->input->post->getArray();
				
				
        $model = $this->getModel( 'domain' );
      
	  
		if ($model->store($row)) {
			$msg = JText::_( 'Domain Saved' );
		} else {
			$msg = JText::_( 'Error Saving Domain' );
            $jinput->enqueueMessage(JText::_('500'), $msg);
		}
         
		if ($this->getTask() == 'apply') {
			  $id = $jinput->input->get('id_domain');                           
			$this->setRedirect('index.php?option=com_activehelper_livehelp&task=edit&controller=domain&cid[]=' . $id[0], 'Changes Applied'); } 
        else {
			$this->setRedirect('index.php?option=com_activehelper_livehelp&view=domains', $msg);
		
	      }   
      	 
       }
        
         
 function script_mod()
	{

	    $jinput = JFactory::getApplication()->input;		 
	    $jinput->set('view', 'module_generation'); 
		     
        $this->display();
	}       
        
  function tracking_module()
	{

        $jinput = JFactory::getApplication()->input;		 
	    $jinput->set('view', 'tracking_module'); 		

        $this->display();
	}
        
        
 function generate_script()
	{

	    $jinput = JFactory::getApplication()->input;		 
	    $jinput->set('view', 'script'); 	
     
        $this->display();
	}  
    
 function generate_module()
	{

	    $jinput = JFactory::getApplication()->input;		 
	    $jinput->set('view', 'module'); 

        $this->display();
	}       
       
       
 function settings()
   {
 
        $jinput = JFactory::getApplication()->input;		 
	    $jinput->set('view', 'setting'); 
		
        $this->display();
	}   
    
    
  function restrictions()
   {
 
        $jinput = JFactory::getApplication()->input;		 
	    $jinput->set('view', 'restrictions'); 

     $this->display();
	}   
    
    
  function add_country_restriction()
	{
		             
		$jinput = JFactory::getApplication()->input;		 
	    $jinput->set('view', 'restriction'); 
		
		$this->display();
    
	}
       
 function updateSettings()
	{
	    $jinput = JFactory::getApplication();
        $model = $this->getModel( 'domain' );
      
		if ($model->updateDomainSettings()) {
			$msg = JText::_( 'Setting Updated' );
		} else {
			$msg = JText::_( 'Error Updated Setting' );
            $jinput->enqueueMessage(JText::_('500'), $msg);
		}
               

       $this->setRedirect('index.php?option=com_activehelper_livehelp&view=domains', $msg);
			        
      	 
       }
       
       
 function updateServer()
	{
	    $jinput = JFactory::getApplication();
        $model = $this->getModel( 'domain' );
      
		if ($model->updateServerSettings()) {
			$msg = JText::_( 'Setting Updated' );
		} else {
			$msg = JText::_( 'Error Updated Setting' );
            $jinput->enqueueMessage(JText::_('500'), $msg);
		}
               

       $this->setRedirect('index.php?option=com_activehelper_livehelp&view=reports', $msg);
			        
      	 
       }       
       
       
  function reset()
	{
	    $jinput = JFactory::getApplication();
        $model = $this->getModel( 'domain' );
      
		if ($model->resetSettings()) {
			$msg = JText::_( 'Setting Updated' );
		} else {
			$msg = JText::_( 'Error Updated Setting' );
            $jinput->enqueueMessage(JText::_('500'), $msg);
		}
               

       $this->setRedirect('index.php?option=com_activehelper_livehelp&view=reports', $msg);
			        
      	 
}          
       
   
     function clearup()
	{
	    $jinput = JFactory::getApplication();
        $model = $this->getModel( 'domain' );
      
		if ($model->dbclearup()) {
			$msg = JText::_( 'Request deleted' );
		} else {
			$msg = JText::_( 'Error deleted request' );
            $jinput->enqueueMessage(JText::_('500'), $msg);
		}
               

       $this->setRedirect('index.php?option=com_activehelper_livehelp&view=reports', $msg);
			              	 
}                    
               
 function saveCountryRestriction()
	{
	    $jinput = JFactory::getApplication();      
        $model = $this->getModel( 'domain' );
      
		if ($model->save_restriction()) {
			$msg = JText::_( 'Restrictions Saved' );
		} else {
			$msg = JText::_( 'Error Updated Setting' );
            $jinput->enqueueMessage(JText::_('500'), $msg);
		}
               

       $this->setRedirect('index.php?option=com_activehelper_livehelp&view=restrictions', $msg);
			        
      	 
       }   
       
function export_chats()
	{
	    $jinput = JFactory::getApplication();
        $model = $this->getModel( 'domain' );
      
		if ($model->export()) {
			$msg = JText::_( 'Request deleted' );
		} else {
			$msg = JText::_( 'Error deleted request' );
            $jinput->enqueueMessage(JText::_('500'), $msg);
		}
               

       $this->setRedirect('index.php?option=com_activehelper_livehelp&view=reports', $msg);
			              	 
       }
          
   function delete_chats()
	{
	    $jinput = JFactory::getApplication();
        $model = $this->getModel( 'domain' );
      
		if ($model->delete()) {
			$msg = JText::_( 'Request deleted' );
		} else {
			$msg = JText::_( 'Error deleted request' );
            $jinput->enqueueMessage(JText::_('500'), $msg);
		}
               

       $this->setRedirect('index.php?option=com_activehelper_livehelp&view=reports', $msg);
			              	 
   }  

   function install_language()
	{
	    $jinput = JFactory::getApplication();
        $model = $this->getModel('domain');
		      
		if ($msg = $model->install_language()) {			
		        } 
		else {
			$msg = JText::_( 'Error on the request' );
            $jinput->enqueueMessage(JText::_('500'), $msg);
		}
               
       $this->setRedirect('index.php?option=com_activehelper_livehelp&view=languages', $msg);
			              	 
   }  

   function edit_language()
	{	    	  
	    $jinput = JFactory::getApplication()->input;		 
	    $jinput->set('view', 'edit_language_file'); 		

        $this->display();
			              	 
   }  
   
   function install_package()
   {
	    $jinput = JFactory::getApplication();
        $model = $this->getModel('domain');
		      
		if ($msg = $model->install_live_chat_package()) {

		} else {
			$msg = JText::_( 'Error on the request' );
            $jinput->enqueueMessage(JText::_('500'), $msg);
		}
               

       $this->setRedirect('index.php?option=com_activehelper_livehelp&view=reports', $msg);
	   
   }
   
   function save_language_changes()
	{
	    
		$jinput = JFactory::getApplication();	
        $model = $this->getModel('domain');
			 
		      
		if ($msg = $model->save_lang()) {			
		        } 
		else {
			$msg = JText::_( 'Error on the request' );
            $jinput->enqueueMessage(JText::_('500'), $msg);
		}
               
       $this->setRedirect('index.php?option=com_activehelper_livehelp&view=languages', $msg);
			              	 
   }  
   
}
