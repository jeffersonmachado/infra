<?php

/**
 * @Product     ActiveHelper LiveHelp component for Joomla
 * @package		all - domains  
 * @subpackage	Model
 * @copyright	(C) 2010- 2017 ActiveHelper Inc.
 * @author		ActiveHelper Inc.
 * @version     5.0
 * @Joomla      3.7   
 * @license   : GNU/GPL http://www.gnu.org/copyleft/gpl.html
*/

// No direct access to this #__
defined('_JEXEC') or die('Restricted access');
// import the Joomla modellist library
jimport('joomla.application.component.modellist');
jimport('joomla.log.log');

/**
 * HelloWorldList Model
 */
//class activehelper_livehelpModelAll extends JModelList
class activehelper_livehelpModelDomains extends JModelList

{                		        
   
   	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @since	1.6
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
		parent::populateState('name', 'asc');
	}
	
	/**
	 * Method to get a store id based on model configuration state.
	 *
	 * This is necessary because the model is used by the component and
	 * different modules that might need different sets of data or different
	 * ordering requirements.
	 *
	 * @param	string		$id	A prefix for the store id.
	 * @return	string		A store id.
	 * @since	1.6
	 */
	protected function getStoreId($id = '')
	{
		// Compile the store id.
		$id.= ':' . $this->getState('filter.search');

		return parent::getStoreId($id);
	}
       
       
       /**
         * Method to build an SQL query to load the list data.
         *
         * @return      string  An SQL query
         */
   
        protected function getListQuery()
        {
                // Create a new query object.         
                $db = JFactory::getDBO();
                $query = $db->getQuery(true);
                // Select some fields
                $query->select('id_domain, name, status');
                // From the hello table
                $query->from('#__livehelp_domains');
                
                
           /*  $log = &JLog::getInstance('session.log');
             $log->addEntry(array('comment' => $query ));     
             */       
        	// Filter by search in title
                              
		     $search = $this->getState('filter.search');
             //$log->addEntry(array('comment' => $search ));
             
		if (!empty($search)) {
			if (stripos($search, 'id:') === 0) {
				$query->where('id_domain = '.(int) substr($search, 3));
			} else {
				$search = $db->Quote('%'.$db->escape($search, true).'%');
				$query->where('(name LIKE '.$search.')');
			}
		}

	
             return $query;
        }
        
 /***************************************************************** remove domain *********************************************************************************************/
  function remove($id_domain)
	{
        $row =& $this->getTable('domain');


      	if (!$row->delete($id_domain)) {
			$jinput->enqueueMessage(JText::_('500'), $row->getError());			      	   
			return false;
		  }
       
            $this->deleteSettings ($id_domain);            
            $this->remove_domain_languages($id_domain);
            $this->remove_account_domain($id_domain);
            // no funciona, genera error borrando directorio
			$this->deleteFolder($id_domain);
		
        return true;
	}
 
        
    function deleteSettings($domain)
     {
       $db = JFactory::getDbo();
       
       $setSQL = "delete from #__livehelp_settings where id_domain =".$domain;
       
       $db->setQuery($setSQL);
      
       	if (!$db->query()) {
			$this->setError($this->_db->getErrorMsg());
			return false;
		  } 
          
       return true;
	   }
       
      
 	function remove_domain_languages($id_domain)
	{
      $db = JFactory::getDbo();
      
      $setSQL = "delete from #__livehelp_languages_domain where id_domain = " . $id_domain ;

      $db->setQuery($setSQL);
      
       	if (!$db->query()) {
			$this->setError($this->_db->getErrorMsg());
			return false;
		  } 
          
       return true;

	}

 	function remove_account_domain($id_domain)
	{
      $db = JFactory::getDbo();
      
      $setSQL = "delete from #__livehelp_accounts_domain where id_domain = " . $id_domain ;

      $db->setQuery($setSQL);
      
       	if (!$db->query()) {
			$this->setError($this->_db->getErrorMsg());
			return false;
	    } 
                    
      return true;

	}     
    
  function deleteFolder($domain)
   {
        jimport('joomla.filesystem.folder');
    
        if(!defined('DIRECTORY_SEPARATOR')){ 
            define('DIRECTORY_SEPARATOR',DIRECTORY_SEPARATOR); 
            }   
	       
       $option 	= 'com_activehelper_livehelp';       

       $path = JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . $option . DIRECTORY_SEPARATOR . 'server\domains' . DIRECTORY_SEPARATOR . $domain ;

       if ( JFolder::exists($path) && !JFolder::delete($path)) {
           $msg = "Failed to delete the base configuration"; }

       return $msg ;
	   }
       
  	function delete_restriction($id)
	{
      $db = JFactory::getDbo();
      
      $setSQL = "delete from #__livehelp_not_allowed_countries where id= " . $id ;

      $db->setQuery($setSQL);
      
       	if (!$db->query()) {
			$this->setError($this->_db->getErrorMsg());
			return false;
		  } 
          
       return true;

	}
     
       
 /****************************************************************************************************************************************************************************/                    
}



