<?php

/**
 * @Product     ActiveHelper LiveHelp component for Joomla
 * @package		restrictions
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
jimport('joomla.error.log');
/**
 * HelloWorldList Model
 */
class activehelper_livehelpModelrestrictions extends JModelList
{
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
                  
          $query= "select jlnac.id, jld.id_domain domain_id, jld.name domain_name  ,  jlc.name country  ".
                  " from #__livehelp_not_allowed_countries jlnac, ".
                  " #__livehelp_countries jlc, ".
                  " #__livehelp_domains jld ".
                  " where  jlnac.id_domain = jld.id_domain and jlnac.code = jlc.code ".
                  " group by 1 desc ";
               
              $this->_sql = $query;                          
                  
             return $query;
        }



/**************************************************************************************************************************************************************************************/
}

