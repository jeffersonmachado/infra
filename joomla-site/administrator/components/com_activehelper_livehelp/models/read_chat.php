<?php

/**
 * @Product     ActiveHelper LiveHelp component for Joomla
 * @package		read_chat
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

class activehelper_livehelpModelread_chat extends JModelList

{                		           
  function load_chat($chat_id)
       {
         $db = JFactory::getDbo();
         
         $setSQL =" select username , message , TIME_FORMAT(jlm.datetime,'%l:%i:%s') time ".
                  " from  #__livehelp_messages jlm ".
                  " where session = ".$chat_id .
                  " order by id ";

           $db->setQuery($setSQL);
      
       	if (!$db->query()) {
			$this->setError($this->_db->getErrorMsg());
			return false;
		  }           

         $rows = $db->loadAssocList();

         $this->_query =$setSQL;

         return $rows;
     }


  function chat_statistic($chat_id)
       {
         $db = JFactory::getDbo();
       
         $setSQL ="select CONCAT(jlu.firstname, ' ',jlu.lastname) agent , jls.department , jls.server , ".
                  " jls.username,jls.email, DATE_FORMAT(jls.datetime,'%Y-%m-%d') date , ".
                  " jls.rating  , jlr.country ,  jls.phone, jls.company, jlr.city ".
                  " from #__livehelp_sessions jls , #__livehelp_users jlu , #__livehelp_requests jlr ".
                  " where jls.id =" .$chat_id .
                  " and  jls.id_user = jlu.id and jls.request = jlr.id";

             $db->setQuery($setSQL);
      
       	if (!$db->query()) {
			$this->setError($this->_db->getErrorMsg());
			return false;
		  } 
          
       
         $rows = $db->loadAssocList();

         return $rows;
     }

  /**
         * Method get the Current  SQL script
         *
         * @return      string  An SQL script
         */
           
        
   function buildSearch()
   {

    return $this->_query;

     }
     
}



