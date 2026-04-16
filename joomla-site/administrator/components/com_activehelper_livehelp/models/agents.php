<?php

/**
 * @Product     ActiveHelper LiveHelp component for Joomla
 * @package		Agents
 * @subpackage	Model
 * @copyright	(C) 2010- 2017 ActiveHelper Inc.
 * @author	    ActiveHelper Inc.
 * @version     5.0
 * @Joomla      3.7 
 * @license   : GNU/GPL http://www.gnu.org/copyleft/gpl.html 
*/

// No direct access to this file
defined('_JEXEC') or die('Restricted access');
// import the Joomla modellist library
jimport('joomla.application.component.modellist');
jimport('joomla.log.log');


class activehelper_livehelpModelAgents extends JModelList
{
        /**
         * Method to build an SQL query to load the list data.
         *
         * @return      string  An SQL query		 		 	
         */
		 
	protected function populateState( $ordering = null, $direction = null)
	{
		// Initialise variables.
		$app = JFactory::getApplication();

		// Load the filter state.
		$search = $app->getUserStateFromRequest($this->context.'.filter.search', 'filter_search');
		$this->setState('filter.search', $search);

		// Load the parameters.
		$params = JComponentHelper::getParams('com_activehelper_livehelp');
		$this->setState('params', $params);

		// List state information.
		parent::populateState('username', 'asc');
	}
	
        protected function getListQuery()
        {
                // Create a new query object.         
                $db = JFactory::getDBO();
                $query = $db->getQuery(true);
                // Select some fields
                $query->select('id, username, email, department, status ');
                // From the hello table
                $query->from('#__livehelp_users');
				
				$search = $this->getState('filter.search');
        
             
		if (!empty($search)) {
			if (stripos($search, 'id:') === 0) {
				$query->where('id = '.(int) substr($search, 3));
			} else {
				$search = $db->Quote('%'.$db->escape($search, true).'%');
				$query->where('(username LIKE '.$search.')');
			}
		}

		  
	    /* Log
		   JLog::addLogger(array('text_file' => 'livechat.log'));
		   JLog::add("SQL ==>: " . $query ); 
           */
		
                return $query;
        }


/********************************************************************* Delete Agents *****************************************************************************************************/

  function remove($id_user)
	{ 		
        $jinput = JFactory::getApplication();     
      	$row =& $this->getTable('user');


      	if (!$row->delete($id_user)) {
      	    $jinput->enqueueMessage(JText::_('500'), $msg);
			return false;
		  }

       $this->remove_sa_domain_user_role_all($id_user);
       $this->remove_domain_user_all($id_user);
       $this->deleteFolder($id_user);
      
       return true;

	}
    
    
 function remove_sa_domain_user_role_all($id_user)
	{
      $db = JFactory::getDbo();
      
      $setSQL = "delete from #__livehelp_sa_domain_user_role where id_domain_user_role in ".
               " ( select id_domain_user  from #__livehelp_domain_user where id_user =" . $id_user . ")" ;

         
       $db->setQuery($setSQL);
      
       	if (!$db->query()) {
			$this->setError($this->_db->getErrorMsg());
			return false;
		  } 

      return true;

	}   


function remove_domain_user_all($id_user)
	{
      $db = JFactory::getDbo();
      
      $setSQL = "delete from #__livehelp_domain_user where id_user =   " . $id_user ;

       $db->setQuery($setSQL);
      
       	if (!$db->query()) {
			$this->setError($this->_db->getErrorMsg());
			return false;
		  } 

      return true;
	}
    
 function deleteFolder($agent)
    {
       jimport('joomla.filesystem.folder'); 
       jimport('joomla.filesystem.file');
       
       $option 	= 'com_activehelper_livehelp';
       $mode = 0755; 
             
       $path = JPATH_SITE . DIRECTORY_SEPARATOR  . 'components' . DIRECTORY_SEPARATOR  . $option . DIRECTORY_SEPARATOR  . 'server\agents' . DIRECTORY_SEPARATOR  . $agent ;

       if ( JFolder::exists($path) && !JFolder::delete($path)) {
           $msg = "Failed to delete the base configuration"; }             
       
       return $msg;
	   }       
/**************************************************************************************************************************************************************************************/
}

