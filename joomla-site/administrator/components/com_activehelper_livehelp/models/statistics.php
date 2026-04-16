<?php

/**
 * @Product     ActiveHelper LiveHelp component for Joomla
 * @package		statistics
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

class activehelper_livehelpModelstatistics extends JModelList

{                		             
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
}



