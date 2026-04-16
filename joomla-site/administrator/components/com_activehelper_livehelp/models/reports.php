<?php

/**
 * @Product     ActiveHelper LiveHelp component for Joomla
 * @package		Reports
 * @subpackage	Model
 * @copyright	(C) 2010- 2017 ActiveHelper Inc.
 * @author		ActiveHelper Inc.
 * @version     5.0
 * @Joomla      3.7
 * @license   : GNU/GPL http://www.gnu.org/copyleft/gpl.html 
*/

// No direct access to this file
defined('_JEXEC') or die('Restricted access');
// import the Joomla modellist library
jimport('joomla.application.component.modellist');
jimport('joomla.error.log');

/**
 * HelloWorldList Model
 */

class activehelper_livehelpModelReports extends JModelList

{                		        
    
function remove_chat($id_chat)
	{
       $db = JFactory::getDbo();

      $setSQL = "delete from #__livehelp_sessions  , #__livehelp_messages " .
                " USING  #__livehelp_sessions INNER JOIN #__livehelp_messages " .
                " where #__livehelp_sessions.id =" . $id_chat .
                " and #__livehelp_messages.session = #__livehelp_sessions.id";
                  
      $db->setQuery($setSQL);
      
       	if (!$db->query()) {
			$this->setError($this->_db->getErrorMsg());
			return false;
		  } 
          
       return true;

	}
    
    
function remove_offline_message($id)
	{
       $db = JFactory::getDbo();

      $setSQL = "delete from #__livehelp_offline_messages " .                
                " where id = ". $id ;
                  
      $db->setQuery($setSQL);
      
       	if (!$db->query()) {
			$this->setError($this->_db->getErrorMsg());
			return false;
		  } 
          
       return true;

	}    
    
 
function change_offline_message_status($id,$status)
	{
       $db = JFactory::getDbo();

       if ($status ==0) {
				$status =1;}
      else
        { $status =0;}


      $setSQL = "update #__livehelp_offline_messages " .
                "set answered =" . $status .                  
                " where id = ". $id ;
                  
      $db->setQuery($setSQL);
      
       	if (!$db->query()) {
			$this->setError($this->_db->getErrorMsg());
			return false;
		  } 
          
       return true;

	}     
/******************************************************************************** Stats ***************************************************************************************************/    
 
  function agent_statistic()
  {

         
      $db = JFactory::getDbo();
      $query = $db->getQuery(true);
      
      $query->select('count(*) users')
       ->from('#__livehelp_users');
      
      $db->setQuery($query);
      return $db->loadResult(); 
         
         
     }

  function department_statistic()
  {
       $db = JFactory::getDbo();
       $query = $db->getQuery(true);
      
      $query->select('count(*)departments')
       ->from('( select department from #__livehelp_users group by department ) b');
             
      $db->setQuery($query);
      return $db->loadResult(); 
         
     }

  function chats_statistic()
    {         
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);
      
      $query->select('count(*) chats')
       ->from('#__livehelp_sessions');
             
      $db->setQuery($query);
      return $db->loadResult(); 
         
            
     }

   function domains_statistic()
   {                   
      $db = JFactory::getDbo();
      $query = $db->getQuery(true);
      
      $query->select('count(*) domains')
       ->from('#__livehelp_domains');
      
      $db->setQuery($query);
      return $db->loadResult();      
     }

   function today_chats_statistic()
    {               
      $db = JFactory::getDbo();
      $query = $db->getQuery(true);
      
      $query->select('count(*)')
       ->from('#__livehelp_sessions  ls')
       ->where(" DATE_FORMAT(ls.datetime, '%m/%d/%Y') = DATE_FORMAT(now() ,'%m/%d/%Y')");
      
      $db->setQuery($query);
      return $db->loadResult();      
         
     }

   function today_visitors_statistic()
   {               
      $db = JFactory::getDbo();
      $query = $db->getQuery(true);
      
      $query->select('count(*)')
       ->from('#__livehelp_requests  ls')
       ->where(" DATE_FORMAT(ls.datetime, '%m/%d/%Y')  = DATE_FORMAT(now() ,'%m/%d/%Y')");
      
      $db->setQuery($query);
      return $db->loadResult();      
           
     }

    function latest_aggent_connected()
    {                
       $db = JFactory::getDbo();
       $query = $db->getQuery(true);
      
      $query->select('username')
       ->from('#__livehelp_users')
       ->order('refresh desc limit 1');
      
      $db->setQuery($query);
      return $db->loadResult(); 
          
     }

  function oldest_aggent_connected()
     {      
       $db = JFactory::getDbo();
       $query = $db->getQuery(true);
      
      $query->select('username')
       ->from('#__livehelp_users')
       ->order('refresh asc limit 1');
      
      $db->setQuery($query);
      return $db->loadResult(); 
        
     }

    function failed_chats_statistic()
     {               
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);
      
       $query = "Select count(*) from #__livehelp_messages Right Join #__livehelp_sessions On ".
                   " #__livehelp_sessions.id = #__livehelp_messages.session where ".
                   " #__livehelp_messages.username Is Null and #__livehelp_messages.message Is Null";
                   
       $db->setQuery($query); 
             
      return $db->loadResult(); 
         
     }

     function avg_chat_rating()
      {     
         
       $db = JFactory::getDbo();
       $query = $db->getQuery(true);
      
      $query->select('avg(rating)')
       ->from('#__livehelp_sessions');

      
      $db->setQuery($query);
      return $db->loadResult(); 
          
     }

     function most_active_domains_statistic()
     {      
       $db = JFactory::getDbo();
       $query = $db->getQuery(true);
      
      $query->select('jld.name name , count( jls.id) chats ')
       ->from('#__livehelp_sessions jls, #__livehelp_domains jld')
       ->where('jls.id_domain = jld.id_domain')
       ->group('jls.id_domain limit 4');
       
      $db->setQuery($query);
      
      $rows = $db->loadAssocList();
      return $rows;
         
     }

     function most_active_agents_statistic()
     {       
       $db = JFactory::getDbo();
       $query = $db->getQuery(true);
      
      $query->select('jlu.username name, count( jls.id) chats ')
       ->from('#__livehelp_sessions jls, #__livehelp_users jlu')
       ->where('jls.id_user = jlu.id')
       ->group('jlu.username limit 4');
       
      $db->setQuery($query);
      
      $rows = $db->loadAssocList();
      return $rows;
         
     }

     function avg_agents_statistic()
     {
            
       $db = JFactory::getDbo();
       $query = $db->getQuery(true);
      
      $query->select('jlu.username  name, avg( jls.rating) avg_rating ')
       ->from('#__livehelp_sessions jls, #__livehelp_users jlu')
       ->where('jls.id_user = jlu.id ')
       ->group('jlu.username  limit 4');
       
      $db->setQuery($query);
      
      $rows = $db->loadAssocList();
      return $rows;
         
     }

     function most_active_agents_by_duration()
       {                        
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);
      
        $query = " select t1.name name, SEC_TO_TIME(sum(TIME_TO_SEC(Time))) time ".
                   " from (SELECT b.username name , TIMEDIFF(c.refresh, c.datetime) Time ".
                   " FROM  #__livehelp_users b, #__livehelp_sessions c ".
                   " WHERE c.id_user = b.id and DATE_FORMAT(c.datetime,'%Y%m%d') >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH) ".
                   " AND DATE_FORMAT(c.datetime,'%Y%m%d') <= CURDATE() ".
                   " GROUP BY c.id ".
                   " ORDER BY CONCAT(b.firstname, ' ', b.lastname) ) as t1 ".
                   " group by t1.name limit 5 ";

                   
       $db->setQuery($query); 
             
      $rows = $db->loadAssocList();
      return $rows;
           
     }


     function avg_user_statistic()
     {               
       $db = JFactory::getDbo();
       $query = $db->getQuery(true);
      
      $query->select("concat(jls.username , '  ( ' , jls.email , ' )') name, count(jls.id) chats")
       ->from('#__livehelp_sessions jls')
       ->group('jls.email limit 5');
       
      $db->setQuery($query);
      
      $rows = $db->loadAssocList();
      return $rows;
        
     }

    function get_start_date()
     {                        
       $db = JFactory::getDbo();
       $query = $db->getQuery(true);
      
       $query->select('DATE_SUB(CURDATE(), INTERVAL 1 MONTH) start_date');
            
      $db->setQuery($query);
      return $db->loadResult(); 
        
     }

      function get_end_date()
       {        
         
       $db = JFactory::getDbo();
       $query = $db->getQuery(true);
      
       $query->select(' CURDATE() end_date');
            
      $db->setQuery($query);
      return $db->loadResult(); 
        
     }

  /**************************************** new reports ****************************************/ 
 function montly_chats()
  {
         
          $db = JFactory::getDbo();
          $query = $db->getQuery(true);
         
         $query  ="select count(*) chats ".
                  " from #__livehelp_sessions ".
                  " where datetime between DATE_FORMAT(CURDATE(), '%Y-%m-01') and LAST_DAY(DATE_FORMAT(CURDATE(), '%Y-%m-%d'))";


        $db->setQuery($query);
        return $db->loadResult(); 
     } 
     
 function last_month_chats()
       {
          $db = JFactory::getDbo();
          $query = $db->getQuery(true);
         
         $query =" select count(*) chats ".
                  " from #__livehelp_sessions ".
                  " WHERE datetime >= DATE_FORMAT( CURRENT_DATE - INTERVAL 1 MONTH, '%Y/%m/01' ) AND ".
                  " datetime < DATE_FORMAT( CURRENT_DATE, '%Y/%m/01' )";


         $db->setQuery($query);
         return $db->loadResult();
     }  
     
 function last_week_chats()
       {
         $db = JFactory::getDbo();
         $query = $db->getQuery(true);
         
         $query ="SELECT COUNT(*) chats ".
                  " FROM #__livehelp_sessions ".
                  " WHERE datetime >= DATE_SUB(DATE(NOW()), INTERVAL DAYOFWEEK(NOW())+6 DAY) AND datetime <  DATE_SUB(DATE(NOW()), INTERVAL DAYOFWEEK(NOW())-1 DAY)";



         $db->setQuery($query);
         return $db->loadResult();
     } 
     
  function current_week_chats()
       {
         $db = JFactory::getDbo();
         $query = $db->getQuery(true);
         
         $query ="SELECT  COUNT(*) chats " .
                  " FROM #__livehelp_sessions ".
                  " WHERE WEEK(datetime) = WEEK(CURRENT_DATE()) AND DAYOFWEEK(datetime) IN (1,2,3,4,5,6,7) "; 
                  
         $db->setQuery($query);
         return $db->loadResult();
     }  
     
function current_week_offline_messages()
       {
         $db = JFactory::getDbo();
         $query = $db->getQuery(true);
         
         $query ="SELECT  COUNT(*) messages ".
                  " FROM #__livehelp_offline_messages ".
                  " WHERE WEEK(datetime) = WEEK(CURRENT_DATE()) AND DAYOFWEEK(datetime) IN (1,2,3,4,5,6,7)"; 
                                     
         $db->setQuery($query);
         return $db->loadResult();
     }   
     
   function last_month_offline_messages()
       {
         $db = JFactory::getDbo();
         $query = $db->getQuery(true);
         
         
         $query ="SELECT  COUNT(*) messages ".
                  " FROM #__livehelp_offline_messages ".
                  " WHERE datetime >= DATE_FORMAT( CURRENT_DATE - INTERVAL 1 MONTH, '%Y/%m/01' ) AND datetime < DATE_FORMAT( CURRENT_DATE, '%Y/%m/01' )"; 
                                     
         $db->setQuery($query);
         return $db->loadResult();
     }    
     
function weekly_failed_chats()
       {
         $db = JFactory::getDbo();
         $query = $db->getQuery(true);
         
         $query ="Select count(jls.id) ".       
                   " from  #__livehelp_sessions jls ".
                   " WHERE WEEK(jls.datetime) = WEEK(CURRENT_DATE()) AND DAYOFWEEK(jls.datetime) IN (2,3,4,5,6) and ". 
                   " jls.active <> 0 and jls.id not in (select jlm.session  from #__livehelp_messages  jlm ".
                   " where  WEEK(jlm.datetime) = WEEK(CURRENT_DATE()) AND DAYOFWEEK(jlm.datetime) IN (2,3,4,5,6))";
                                     
         $db->setQuery($query);
         return $db->loadResult();
     }       
     
function weekly_unanswred_chats()
       {
         $db = JFactory::getDbo();
         $query = $db->getQuery(true);
         
         $query ="Select count(jls.id) ".       
                   " from  #__livehelp_sessions jls ". 
                   " WHERE WEEK(jls.datetime) = WEEK(CURRENT_DATE()) AND DAYOFWEEK(jls.datetime) IN (2,3,4,5,6) and jls.active = 0";
                                     
         $db->setQuery($query);
         return $db->loadResult();
     }                    
       
   function live_chat_core_ver()
      {     
         
       $db = JFactory::getDbo();
       $query = $db->getQuery(true);
      
      $query->select('value')
       ->from('#__livehelp_core_settings')
	   ->where('id = 1');

      
      $db->setQuery($query);
      return $db->loadResult(); 
          
     }

	 
}



