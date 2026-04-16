<?php

 /**
 * @ ActiveHelper LiveHelp component for Joomla
 * @author    : ActiveHelper Inc.
 * @copyright : (C) 2010- 2017 ActiveHelper Inc.
 * @license   : GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @version     5.0
 * @Joomla      3.7  
*/

defined( '_JEXEC' ) or die( 'Restricted access' );

jimport( 'joomla.application.component.view');

class activehelper_livehelpViewRestriction extends JViewLegacy
{
	function display($tpl = null)
	{
  
       $countries = $this-> ReadCountries();
       $domains = $this-> ReadDomains();    
       
           $block= array(
			array('value' => '0', 'text' => 'Disable'),
			array('value' => '1', 'text' => 'Enable'));   
       
       $this->assignRef('countries', JHTML::_('select.genericList', $countries, 'countries', 'class="inputbox" '. '', 'code', 'name',$countries));
       $this->assignRef('domains', JHTML::_('select.genericList', $domains, 'domains', 'class="inputbox" '. '', 'id_domain', 'name',$domains));
       $this->assignRef('block', JHTML::_('select.genericList', $block, 'block', 'class="inputbox" '. '', 'value', 'text', 0));
       
		// Set the toolbar
		$this->addToolBar($item);
  
		parent::display($tpl);
	}
    
  function ReadCountries()
       {
          $db = JFactory::getDbo();
          $query = $db->getQuery(true);
           
         $setSQL = "select code, name  from #__livehelp_countries order by name ";                    
         
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
          
         $setSQL = "select id_domain, name  from #__livehelp_domains order by name ";                    
         
         $db->setQuery($setSQL);
      
       	if (!$db->query()) {
			$this->setError($this->_db->getErrorMsg());
			return false;
		  } 

         $rows = $db->loadAssocList();
         
         return $rows;
     }    
     
 protected function addToolBar($item) 
	{
             
        JToolBarHelper::title(JText::_('COM_ACTIVEHELPER_LIVEHELP_ADD_COUNTRY_RESTRICTION'));                  		
        JToolBarHelper::save('domain.saveCountryRestriction','JTOOLBAR_SAVE');
        JToolBarHelper::cancel( 'cancel', 'Close','JTOOLBAR_CLOSE' );
	}  
             
}