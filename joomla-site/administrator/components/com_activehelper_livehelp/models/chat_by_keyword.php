<?php

/**
 * @Product     ActiveHelper LiveHelp component for Joomla
 * @package		Chat_by_keyword
 * @subpackage	Model
 * @copyright	(C) 2010- 2017 ActiveHelper Inc.
 * @author		ActiveHelper Inc.
 * @version     5.0
 * @Joomla      3.7    
 * @license   : GNU/GPL http://www.gnu.org/copyleft/gpl.html
*/


defined( '_JEXEC' ) or die( 'Restricted access' );

// import the Joomla modellist library
jimport('joomla.application.component.modellist');

class activehelper_livehelpModelChat_by_keyword extends JModelList
{

  var $_search_keyword = null;
  var $_search_end =  null;
  var $_sql =  null;


 
   	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @since	1.6
	 */
           
	protected function populateState($ordering = NULL, $direction = NULL)
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
             
         $search = $this->getState('filter.search');
                  
          $query=  "select  jls.id session, CONCAT(jlu.firstname, ' ',jlu.lastname) name, jld.name domain,".
                   " jls.username visitor, jls.email email, if(jls.rating =-1,'Not rate',jls.rating) rating,".
                   " (TIMEDIFF(jls.refresh, jls.datetime)) time, DATE_FORMAT(jls.datetime,'%m/%d/%Y') date ".
                   " from #__livehelp_messages jlm , #__livehelp_sessions jls,  #__livehelp_users jlu , #__livehelp_domains jld ".
                   " where lower(jlm.message)  like lower('%" .$search . "%') and ".
                   " jls.id =jlm.session  ".
                   " group by 1 desc ";
                  
            $this->_sql = $query;     

                  
          return $query;
        }
        
        
   function buildSearch(){

     return $this->_sql;

     }

}