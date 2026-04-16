<?php

/**
 * @Product     ActiveHelper LiveHelp component for Joomla
 * @package		Agent_domains
 * @subpackage	Viewes
 * @copyright	(C) 2010- 2017 ActiveHelper Inc.
 * @author		ActiveHelper Inc.
 * @version     5.0
 * @Joomla      3.7 
 * @license   : GNU/GPL http://www.gnu.org/copyleft/gpl.html 
 */


defined( '_JEXEC' ) or die( 'Restricted access' );

jimport( 'joomla.application.component.view');


class activehelper_livehelpViewAgent_domains extends JViewLegacy
{
	function display($tpl = null)
	{

	 $jinput   = JFactory::getApplication();
     $row      = $jinput->input->post->getArray();	
	 
    $id          = $jinput->input->get('cid');   
	
    $rows = $this->ReadDomains($id);

    $this->assignRef('rows', $rows);
    $this->assignRef('id_user', $id);
    
    // Set the toolbar
    $this->addToolBar();
        
    parent::display($tpl);

	}
    
 	protected function addToolBar() 
	{
		JToolBarHelper::title(JText::_('COM_ACTIVEHELPER_LIVEHELP_EDIT_AGENT_GD'));
        JToolBarHelper::cancel( 'cancel', JTOOLBAR_CLOSE);
        JToolBarHelper::divider();
        JToolBarHelper::help('domain.help', true );		       
	}  
    
  function ReadDomains($id_user)
       {
         $db = JFactory::getDbo();
           $query = $db->getQuery(true);
       
           $setSQL = "SELECT  di.id_domain_user , d.id_domain,  d.name ,If(di.id_domain is null, 'enable' , 'disable') enabled " .
                   "FROM #__livehelp_users AS i JOIN #__livehelp_domains AS d " .
                   " LEFT JOIN #__livehelp_domain_user AS di ON di.id_user = i.id AND di.id_domain     = d.id_domain ".
                   " WHERE   d.status =1 and i.id =". $id_user;
 
         
         $db->setQuery($setSQL);
      
       	if (!$db->query()) {
			$this->setError($this->_db->getErrorMsg());
			return false;
		  } 

         $rows = $db->loadAssocList();
         
         return $rows;
      
     }        
}
