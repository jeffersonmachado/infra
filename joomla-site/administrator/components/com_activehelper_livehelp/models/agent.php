<?php

/**
 * @Product     ActiveHelper LiveHelp component for Joomla
 * @package		agent
 * @subpackage	Model
 * @copyright	(C) 2010- 2017 ActiveHelper Inc.
 * @author		ActiveHelper Inc.
 * @version     5.0
 * @Joomla      3.7
 * @license   : GNU/GPL http://www.gnu.org/copyleft/gpl.html
*/
// No direct access to this file
defined('_JEXEC') or die('Restricted access');

// import Joomla modelform library
jimport('joomla.application.component.modeladmin');
jimport('joomla.log.log');

/**
 * Domain Model
 */
class activehelper_livehelpModelAgent extends JModelAdmin
{
    
    /**
	 * Returns a reference to the a Table object, always creating it.
	 *
	 * @param	type	The table type to instantiate
	 * @param	string	A prefix for the table class name. Optional.
	 * @param	array	Configuration array for model. Optional.
	 * @return	JTable	A database object
	 * @since	1.6
	 */
	public function getTable($type = 'Agent', $prefix = 'Table', $config = array()) 
	{
         
	 	return JTable::getInstance($type, $prefix, $config);
        
	}
	/**
	 * Method to get the record form.
	 *
	 * @param	array	$data		Data for the form.
	 * @param	boolean	$loadData	True if the form is to load its own data (default case), false if not.
	 * @return	mixed	A JForm object on success, false on failure
	 * @since	1.6
	 */
	public function getForm($data = array(), $loadData = true) 
	{  
                        
        $app	= JFactory::getApplication();
                                 
		// Get the form.          
		$form = $this->loadForm('com_activehelper_livehelp.agent', 'agent', array('control' => 'jform', 'load_data' => $loadData));
	
	 if (empty($form)) 
		{
			return false;
		}
		return $form;
	}
	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return	mixed	The data for the form.
	 * @since	1.6
	 */
	protected function loadFormData() 
	{
         
		// Check the session for previously entered form data.
		$data = JFactory::getApplication()->getUserState('com_activehelper_livehelp.agent.data', array());
		if (empty($data)) 
		{
			$data = $this->getItem();
		}
		return $data;
	}
    
 	/**
	 * Method to get the data.
	 *
	 * @return	Record
	 * @since	1.6
	 */   
	function getData()
	{		
		$jinput = JFactory::getApplication()->input;
		$id     = $jinput->get('cid');
		                       
		$row = $this->getTable('user');
		$row->load($id[0]);
		return $row;
	}
        
  
	function store($data,$domains,$domains_current)
	{	
		// New record
        $isnew =0;
        
        $row =& $this->getTable('user');

		if (!$row->bind($data)) {
			return false;
		}
        
        
        //  new agent           
	   if ($row->id ==0) {
				$isnew =1;
                $password ='';}
        else
        { $password = $this->read_encrypted_pass($row->id);}      
                
                  
               
		if (!$row->check()) {
			return false;
		}

		if (!$row->store()) {
			return false;
		}


      if (!$row->password ==''){
			 $this->SetAgentPassowrd($row->id,$row->password);
             $password ='';
             
             // fixed mariaDB issue
             $this->update_dates($row->id);
            }

      if (!$password ==''){
        $this->Update_encrypted_passowrd($row->id,$password);
        }


          // create agent status indicator base folder                             
             $this->createSettingBase($row->id);           
          // load the agent image      
             $this->uploadfile('a'. $row->id , $row->id);    
          
        // Assign domains  
        $domains_status = array();   
        
		// Log
		    /* JLog::addLogger(array('text_file' => 'livechat.log'));
		     JLog::add("domains ==>: " . $domains); 
			 JLog::add("domains_current ==>: " . $domains_current); 
			 */
			 
		
        foreach ($domains as $id => $value)
        {
			   //JLog::add("domain ==>: " . $domains[$id]); 
			   //JLog::add("value ==>: " . $value); 
			   
            if ($domains[$id] != $domains_current[$id])
                $domains_status[$id] = $value;
         }
        
       $this->assign_agent($row->id,$domains_status);  
                 
		return $row->id;
	}	
	  
      
/************************************************************ agent photo *****************************************************************/

  function uploadfile($file_name,$id_agent)
  {
       jimport('joomla.filesystem.file');
       jimport('joomla.filesystem.folder');
       
       $option 	= 'com_activehelper_livehelp';
       $mode = 0755; 
      
	  if(!defined('DIRECTORY_SEPARATOR')) define('DIRECTORY_SEPARATOR', '/');
	  
      
            $jinput = JFactory::getApplication()->input;
    		$file   = $jinput->get('photo', '', 'files', 'array' );
	 
		if( $file['name'] != '')
		{
			$ext = JFile::getExt($file['name']);          
            $filename = JPATH_SITE . DIRECTORY_SEPARATOR . 'components\com_activehelper_livehelp\server\agents'. DIRECTORY_SEPARATOR . $id_agent .DIRECTORY_SEPARATOR . $file_name . '.' . $ext;
		 
          
		   
      if (!JFile::upload($file['tmp_name'],$filename)) {
				$msg = "Upload failed, check to make sure that" . $filename . " exists and is writable.";
        return $msg;
        } else
          {
        $this->Update_agent_photo($id_agent,$file_name . '.' . $ext);
        }
        
	}
   }
   
	function Update_agent_photo($id,$file_name)
	   {
       
        $db = JFactory::getDbo();
       
        $setSQL = "update #__livehelp_users set photo =". "'". $file_name . "'" . " where id =". $id;

       $db->setQuery($setSQL);
      
       	if (!$db->query()) {
			$this->setError($this->_db->getErrorMsg());
			return false;
		  } 

      return true;

	   }

       
 /************************************************************************Agent Password**********************************************************************************************/
 
 	function read_encrypted_pass($id_user)
	{
       $db = JFactory::getDbo();

       $setSQL = "select password from #__livehelp_users where  id =". "'". $id_user . "'";
 
       $db->setQuery($setSQL);
                      
       	if (!$db->query()) {
			$this->setError($this->_db->getErrorMsg());
			return false;
		  } 


       return $db->loadResult();
      
	}

   	function Update_encrypted_passowrd($id,$password)
	   {
       $db = JFactory::getDbo();
       
       $setSQL = "update #__livehelp_users set password =". "'". $password . "'" . " where id =". $id;

       $db->setQuery($setSQL);
      
       	if (!$db->query()) {
			$this->setError($this->_db->getErrorMsg());
			return false;
		  } 

      return true;

	   }

   	function SetAgentPassowrd($id,$password)
	   {
       $db = JFactory::getDbo();
       $setSQL = "update #__livehelp_users set password = md5('".$password."') where id =".$id;
       
       $db->setQuery($setSQL);
      
       	if (!$db->query()) {
			$this->setError($this->_db->getErrorMsg());
			return false;
		  } 

        return true;

	   }
       
 /********************************************************************************** agent_domains ***************************************************************************************/      
  
  function assign_agent($id_user,$domains_status)   
   {
     foreach ($domains_status as $id => $value){
           
          if ($value =='0') {
              $this->remove_agent_from_domain($id_user,$id);
            }
           else
           {
              $this->assign_agent_to_domain($id_user,$id);
           }                   
       }              
    } 
    
 function assign_agent_to_domain($id_user,$id_domain)   
   {
      
        // check domain assigned
        $checked = $this->check_domain_by_user($id_user,$id_domain);
        
        if ($checked ==0) {
            $id_user_role =  $this->add_domain_user($id_user,$id_domain);
            $this->add_sa_domain_user_role($id_user_role);
          }        
    } 
    
    
 function remove_agent_from_domain($id_user,$id_domain)   
   {
        
        $id_domain_user = $this->get_id_domain_user($id_user,$id_domain);        
        $this->remove_domain_user($id_user,$id_domain);
        $this->remove_sa_domain_user_role ($id_domain_user);    
    }     
    
    
  function edit_domain_status()
	{
   
    $jinput = JFactory::getApplication()->input;
		
   
    $id_domain      = $jinput->get('cid' , array(0)); 
    $id_user        = $jinput->get('cuser', array(1)); 
    $domain_status  = $jinput->get('cstatus', array(2)); 
    $id_domain_user = $jinput->get('cid_domain_user', array(3)); 
	
		/* // Error log
		 JLog::addLogger(array('text_file' => 'livechat.log'));
		 JLog::add("id_domain ==>: " . $id_domain); 
		 JLog::add("id_user ==>: " . $id_user);
		 JLog::add("domain_status ==>: " . $domain_status);
		 JLog::add("id_domain_user ==>: " . $id_domain_user);
		 */		 
		 
    if ($domain_status =='disable') {
        $this->remove_domain_user($id_user,$id_domain);
        $this->remove_sa_domain_user_role ($id_domain_user);
       }

      if ($domain_status =='enable') {
        $id_user_role =  $this->add_domain_user($id_user,$id_domain);
        $this->add_sa_domain_user_role($id_user_role);
       }

    return true;
 
	}
    
    
	function remove_domain_user($id_user,$id_domain)
	{
      $db = JFactory::getDbo();
      
      $setSQL = "delete from #__livehelp_domain_user where id_user =   " . $id_user .
                   " and id_domain =". $id_domain;

      $db->setQuery($setSQL);
      
       	if (!$db->query()) {
			$this->setError($this->_db->getErrorMsg());
			return false;
		  } 

     return true;
	}
        
	function update_dates($id)
     {
         $db = JFactory::getDbo();         
         $setSQL = "update #__livehelp_users set refresh = now() where id =". $id;

         $db->setQuery($setSQL);
      
       	if (!$db->query()) {
			$this->setError($this->_db->getErrorMsg());
			return false;
		  } 

        return true;

	   }
        

function remove_sa_domain_user_role($id)
	{
         
       if ($id !=''){
      
      $db = JFactory::getDbo();
      
      $setSQL = "delete from #__livehelp_sa_domain_user_role where id_domain_user =" .$id;

      $db->setQuery($setSQL);
      
       	if (!$db->query()) {
			$this->setError($this->_db->getErrorMsg());
			return false;
		  } 

     return true;
      }
    else
      return false; 
   	}      
 
 	function add_domain_user($id_user,$id_domain)
	{
      $db = JFactory::getDbo();
      
      $setSQL = "insert into #__livehelp_domain_user (id_domain, id_user, status) values (" .
                $id_domain . "," . $id_user . "," . "1" . ")" ;

      $db->setQuery($setSQL);
      
       	if (!$db->query()) {
			$this->setError($this->_db->getErrorMsg());
			return false;
		  } 

      return $db->insertid() ;
	}

	function add_sa_domain_user_role($id)
	{
      $db = JFactory::getDbo();
      
      $setSQL = "insert into #__livehelp_sa_domain_user_role (id_domain_user, id_role) values (" .
                $id. "," . "1" . ")" ;

      $db->setQuery($setSQL);
      
       	if (!$db->query()) {
			$this->setError($this->_db->getErrorMsg());
			return false;
		  } 

     return true;

	}
       
       
  	function get_id_domain_user($id_user,$id_domain)
	{
	   
       $db = JFactory::getDbo();
      
       $setSQL = "select id_domain_user from #__livehelp_domain_user where id_user =   " . $id_user .
                   " and id_domain =". $id_domain;

      $db->setQuery($setSQL);
      
       	if (!$db->query()) {
			$this->setError($this->_db->getErrorMsg());
			return false;
		  } 

       return $db->loadResult();     
	} 
    
function createSettingBase($agent)
	   {
       
         jimport('joomla.filesystem.folder');
         jimport('joomla.filesystem.file');
         
		 $jinput = JFactory::getApplication();
		 
         $option 	= 'com_activehelper_livehelp';
         $mode = 0755;
         
        if(!defined('DIRECTORY_SEPARATOR')) define('DIRECTORY_SEPARATOR', '/');
      
       $source_folder = JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . $option . DIRECTORY_SEPARATOR . 'server' . DIRECTORY_SEPARATOR . 'agents' . DIRECTORY_SEPARATOR . '0'. DIRECTORY_SEPARATOR . 'i18n';
       $path = JPATH_SITE . DIRECTORY_SEPARATOR  . 'components' . DIRECTORY_SEPARATOR . $option . DIRECTORY_SEPARATOR . 'server' . DIRECTORY_SEPARATOR . 'agents' . DIRECTORY_SEPARATOR . $agent ;

      if (!JFolder::exists($path))
       {
          JFolder::create($path, $mode);

        if (!JFolder::copy($source_folder, $path . DIRECTORY_SEPARATOR . 'i18n')) {
           $msg = "Failed to create a base configuration";
           $jinput->enqueueMessage(JText::_('500'), $msg);
             }
       }
       return $msg;
	   } 
       
 function updateSettings(){

   $option 	 = 'com_activehelper_livehelp';
   
   $jinput = JFactory::getApplication()->input;
   
   $agent         = $jinput->get('id_agent');
   $languajes     = $jinput->get('languajes');
   
       
   // Time schedule
   $schedule          = $jinput->get('schedule');   
   $initial_time      = $jinput->get('int_time');  
   $final_time        = $jinput->get('end_time');   
   
   $msg = $this-> uploadfiles('online',$option,$agent,$languajes);
   $msg = $this-> uploadfiles('offline',$option,$agent,$languajes);
   $msg = $this-> uploadfiles('away',$option,$agent,$languajes);
   $msg = $this-> uploadfiles('brb',$option,$agent,$languajes);
   
    // time  
   $this->update_schedule($agent,$schedule,$initial_time,$final_time);
   
   return true;
   
   } 
           
            
 function uploadfiles($file_name,$option,$agent,$languajes)
  {
    
         $option 	= 'com_activehelper_livehelp';
         $mode = 0755;
         
        if(!defined('DIRECTORY_SEPARATOR')) define('DIRECTORY_SEPARATOR', '/');
    
    jimport('joomla.filesystem.file');

	$jinput = JFactory::getApplication()->input;
	
     if ( $file_name =='online'){
       $file = $jinput->get( 'online', '', 'files', 'array' ); }

     if ( $file_name =='offline'){
       $file = $jinput->get( 'offline', '', 'files', 'array' ); }

     if ( $file_name =='away'){
       $file = $jinput->get( 'away', '', 'files', 'array' ); }

      if ( $file_name =='brb'){
       $file = $jinput->get( 'brb', '', 'files', 'array' );}

		if(isset($file['name']) && $file['name'] != '')
		{
			$ext = JFile::getExt($file['name']);
      $filename = JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . $option . DIRECTORY_SEPARATOR . 'server\agents' . DIRECTORY_SEPARATOR . $agent . '\i18n\\' .$languajes .'\\'. $file_name . '.' . $ext;

      if (!JFile::upload($file['tmp_name'],$filename)) {
				$msg = "Upload failed, check to make sure that" . $filename . " exists and is writable.";
        return $msg;
        }
			}

   }  
   
   function update_schedule ($id,$schedule,$initial_time,$final_time)
	{
      
      $db = JFactory::getDbo(); 
      
      $setSQL = "update #__livehelp_users set schedule =". $schedule . " , initial_time =" . "'". $initial_time ."'" . ",  final_time = ". "'". $final_time ."'" .  "  where id =" .$id;
       
                     
      $db->setQuery($setSQL);

 	  if (!$db->query()) {
			$this->setError($this->_db->getErrorMsg());	
            return false;
		  } 
          
      return true;

	}                                            

	function check_domain_by_user($id_user,$id_domain)
	{
      $db = JFactory::getDbo(); 
      $setSQL = "SELECT If(id_domain is null, '0' , '1') enabled from #__livehelp_domain_user where id_user = " . "'". $id_user ."'". " and id_domain = "  . "'" . $id_domain ."'";

        $db->setQuery($setSQL);

 	  if (!$db->query()) {
			$this->setError($this->_db->getErrorMsg());	
            return false;
		  } 

      return $db->loadResult();
	}


}
