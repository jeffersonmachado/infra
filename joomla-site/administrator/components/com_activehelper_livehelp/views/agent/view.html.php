<?php

/**
 * @Product     ActiveHelper LiveHelp component for Joomla
 * @package		Agent
 * @subpackage	Viewes
 * @copyright	(C) 2010- 2017 ActiveHelper Inc.
 * @author		ActiveHelper Inc.
 * @version     5.0
 * @Joomla      3.7
 * @license   : GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */


defined( '_JEXEC' ) or die( 'Restricted access' );

jimport( 'joomla.application.component.view');
jimport('joomla.filesystem.folder');
jimport('joomla.filesystem.file');
jimport('joomla.log.log');

class activehelper_livehelpViewAgent extends JViewLegacy
{
	function display($tpl = null)
	{
        $jinput   = JFactory::getApplication();
  		// get the Data        
        $item	=$this->get('Data');
                 	
		// Check for errors.
		if (count($errors = $this->get('Errors'))) 
		{
			$jinput->enqueueMessage(JText::_('500'), $errors);		
			return false;
		}
	     
         $id = $item->id;
         
        if ($id !=0)
        {
           $domains = $this-> ReadDomainsbyUser($id);
		   		   		
		   // Log
		   /*  JLog::addLogger(array('text_file' => 'livechat.log'));
		     JLog::add("domains ==>: " . print_r($domains)); 
			 */           		   
           $this->assignRef('domains', $domains);
        } 
         else        
       {
           $domains = $this-> ReadDomains();
           $this->assignRef('domains', $domains);
        }       
          

		$this->assignRef('row', $item);

		$status = array(
			array('value' => '0', 'text' => 'Disable'),
			array('value' => '1', 'text' => 'Enable'));

		$this->assign('status', JHTML::_('select.genericList', $status, 'status', 'class="inputbox" '. '', 'value', 'text', $item->status ));
        
		$privilege = array(
			array('value' => '0', 'text' => 'Disable'),
			array('value' => '1', 'text' => 'Enable'));

		$this->assign('privilege', JHTML::_('select.genericList', $privilege, 'privilege', 'class="inputbox" '. '', 'value', 'text', $item->privilege ));

        
       	$agent_domain_status = array(    
             JHTML::_('select.option', '1', 'Enable'), 
             JHTML::_('select.option', '0', 'Disable'));
                                     
                  
        $this->assignRef('agent_domain_status', $agent_domain_status);       
        

        $answers = array(    
             JHTML::_('select.option', '1', 'Domain'), 
             JHTML::_('select.option', '2', 'Agent'));
                                     
                  
        $this->assign('answers', JHTML::_('select.genericList', $answers, 'answers', 'class="inputbox" '. '', 'value', 'text', $item->answers ));    
        
      
        $uri =JURI::getInstance();
           
        # image
        $img_path =  JURI::root().'components/com_activehelper_livehelp/server/agents/'. $item->id  . '/';
        $img_file_path ='../components/com_activehelper_livehelp/server/agents/'. $item->id . '/';
        $this->assignRef('img_path', $img_path);
  
        
        /*JLog::addLogger(array());
        JLog::add($img_file_path . $item->photo);*/ 
           
        $photo =$this->imgExits($item->photo,$img_file_path);
        $this->assignRef('photo',$photo);
          
  		// Set the toolbar
		$this->addToolBar($item);        
                  
        parent::display($tpl);
        
	}
    
    	protected function addToolBar($item) 
	{
             
	     if ($item->id >0) {
	       JToolBarHelper::title(JText::_('COM_ACTIVEHELPER_LIVEHELP_EDIT_AGENT'));} 
           else {
           JToolBarHelper::title(JText::_('COM_ACTIVEHELPER_LIVEHELP_ADD_AGENT'));}
                  		
        JToolBarHelper::save('agent.save','JTOOLBAR_SAVE');
        JToolBarHelper::cancel( 'cancel', 'Close','JTOOLBAR_CLOSE' );
	}  
    
 function imgExits($name,$path)
  {
     jimport('joomla.filesystem.file');

    if ( JFile::exists($path .$name)){
       return $name;
        }
   } 
   
 function ReadDomainsbyUser($id_user)
       {
                
           $db    = JFactory::getDbo();
           $query = $db->getQuery(true);
       
           $setSQL = "SELECT  d.id_domain id,  d.name name ,If(di.id_domain is null, '0' , '1') enabled  , di.id_domain_user " .
                     "FROM #__livehelp_users AS i JOIN #__livehelp_domains AS d " .
                     " LEFT JOIN #__livehelp_domain_user AS di ON di.id_user = i.id AND di.id_domain     = d.id_domain ".
                     " WHERE   d.status =1 and i.id =". $id_user;
 
         
           $db->setQuery($setSQL);
		   
		   
      
       	if (!$db->query()) {
			$this->setError($this->_db->getErrorMsg());
			return false;
		  } 

         $rows = $db->loadAssocList();
         
         return $rows;
                  
     }
     
     
 function ReadDomains()
       {
        
           $db = JFactory::getDbo();
           $query = $db->getQuery(true);
       
           $setSQL = "SELECT id_domain id , name ,  '0' enabled " . 
                     "FROM #__livehelp_domains ".
                     " where status = 1";
         
         $db->setQuery($setSQL);
      
       	if (!$db->query()) {
			$this->setError($this->_db->getErrorMsg());
			return false;
		  } 

         $rows = $db->loadAssocList();
         
         return $rows;         
     }     
            
}