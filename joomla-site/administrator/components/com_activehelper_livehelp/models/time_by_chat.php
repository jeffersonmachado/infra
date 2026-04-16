<?php

/**
 * @Product     ActiveHelper LiveHelp component for Joomla
 * @package		Time_by_chat
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
jimport('joomla.error.log');

class activehelper_livehelpModelTime_by_chat extends JModelList
{
    var $_search_start = null;
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
        
        $start_date = $app->getUserStateFromRequest($this->context.'.start_date', 'start_date');
        $end_date   = $app->getUserStateFromRequest($this->context.'.end_date', 'end_date');
        
        if ($start_date ==''){
            
            $this->_search_start = $this->get_start_date();
            $this->_search_end   = $this->get_end_date();          
           }  else
         {
            $this->_search_start  = $start_date;
            $this->_search_end    = $end_date;   
         }                   
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
                  
          $query= " select  jls.id session, CONCAT(jlu.firstname, ' ',jlu.lastname) name, jld.name domain, ".
                  " jls.username visitor, jls.email email, if(jls.rating =-1,'Not rate',jls.rating) rating, ".
                  " (TIMEDIFF(jls.refresh, jls.datetime)) time, DATE_FORMAT(jls.datetime,'%m/%d/%Y') date ".
                  " from   #__livehelp_sessions jls, #__livehelp_users jlu , #__livehelp_domains jld ".
                  " WHERE DATE_FORMAT(jls.datetime,'%Y%m%d') >=DATE_FORMAT(". "'" .$this->_search_start. "'" . ",'%Y%m%d')".
                  " and DATE_FORMAT(jls.datetime,'%Y%m%d') <=DATE_FORMAT( ". "'" .$this->_search_end. "'" . ",'%Y%m%d')".
                  " and jls.id_user = jlu.id and jls.id_domain =jld.id_domain ".
                  " GROUP BY jls.id, jls.username ".
                  " ORDER BY 1 desc ,2 , 3, 4";
                  
               
             $this->_sql = $query;            
                 
             return $query;
        }
        
 
       /**
         * Method get the Current  SQL script
         *
         * @return      string  An SQL script
         */
           
        
   function buildSearch()
   {

    return $this->_sql;

     }
     

       /**
         * Method set the date range
         *
         * 
         */
           
                
   function myFunction($dateStart,$datehEnd){

      if ($dateStart !=0){
          $this->_search_start = $dateStart;
          $this->_search_end   = $datehEnd;
        }
     }

   /**
         * Method get start report default date
         *
         * @return      string  date
         */
           
 function get_start_date()
     {                        
       $db = JFactory::getDbo();
       $query = $db->getQuery(true);
      
       $query->select('DATE_SUB(CURDATE(), INTERVAL 1 MONTH) start_date');
            
      $db->setQuery($query);
      return $db->loadResult(); 
        
     }


       /**
         * Method get end report default date
         *
         * @return      string  date
         */
         
      function get_end_date()
       {        
         
       $db = JFactory::getDbo();
       $query = $db->getQuery(true);
      
       $query->select(' CURDATE() end_date');
            
      $db->setQuery($query);
      return $db->loadResult(); 
        
     }
     
 
       /**
         * Method get the start  date report
         *
         * @return      string  An date report
         */
           
        
   function start_date_value()
   {

    return $this->_search_start;

     }
     
 
       /**
         *  Method get the start  date report
         *
         * @return      string  An date report
         */
           
        
   function end_date_value()
   {

    return $this->_search_end;

     }        
}