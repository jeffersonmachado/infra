<?php

/**
 * @Product     ActiveHelper LiveHelp component for Joomla
 * @package		setting 
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

class activehelper_livehelpModelsetting extends JModelList

{                		        
      
   function ReadDomainSettings($domain)
   {           
      $db = JFactory::getDbo();         
      $query = $db->getQuery(true);
   
      
      $query->select('name, value')
        ->from('#__livehelp_settings')
        ->where("id_domain =" . $domain);      
        
        $db->setQuery($query);
      
       	if (!$db->query()) {
			$this->setError($this->_db->getErrorMsg());
			return false;
		  } 

      
         $rows = $db->loadAssocList();
         

        foreach ( $rows as $row ) {
                   
         $sett[$row["name"]] = $row["value"]; }

        return $sett;
     }
     
 function imgExits($name,$path,$default_type)
  {
     jimport('joomla.filesystem.file');

    if (($default_type == "gif") && ( JFile::exists($path .$name .'.gif'))) {
          return $name. '.gif';
        }

  if   (($default_type == "jpg") && ( JFile::exists($path .$name .'.jpg'))) {
        return $name. '.jpg';
        }

   if   (($default_type == "jpeg") && ( JFile::exists($path .$name .'.jpeg'))) {
        return $name. '.jpeg';
        }

  if  (($default_type == "png") && ( JFile::exists($path .$name .'.png'))) {
        return $name. '.png';
        }
   }

  function ReadDomainLanguages($domain)
       {
         $db = JFactory::getDbo(); 
         
         $setSQL = "SELECT d.code, d.name ,If(di.code is null,0,1) enabled FROM #__livehelp_domains AS i ".
                   " JOIN #__livehelp_languages AS d  LEFT JOIN #__livehelp_languages_domain AS di ON ".
                   " di.id_domain = i.id_domain AND di.code  = d.code WHERE i.id_domain = " . $domain;

         $db->setQuery($setSQL);
      
       	if (!$db->query()) {
			$this->setError($this->_db->getErrorMsg());
			return false;
		  } 

        $rows = $db->loadAssocList();

        foreach ( $rows as $row ) {
         $languages[$row["code"]] = $row["enabled"]; }

        return $languages;
     }


  function ReadDomainLanguagesWM($domain)
     {
         $db = JFactory::getDbo();         
         $query = $db->getQuery(true);
  
      $query->select('code, welcome_message')
        ->from('#__livehelp_languages_domain')
        ->where("id_domain =" . $domain);  
        
        $db->setQuery($query);

       	if (!$db->query()) {
			$this->setError($this->_db->getErrorMsg());
			return false;
		  } 
          
         $rows = $db->loadAssocList();

        foreach ( $rows as $row ) {
         $languages_wm[$row["code"]] = $row["welcome_message"]; }


        return $languages_wm;
     }
     
                   
}



