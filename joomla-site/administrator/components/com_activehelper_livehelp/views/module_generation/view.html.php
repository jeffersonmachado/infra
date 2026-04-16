<?php

/**
 * @Product     ActiveHelper LiveHelp component for Joomla
 * @package		module_generation
 * @subpackage	Views
 * @copyright	(C) 2010- 2017 ActiveHelper Inc.
 * @author		ActiveHelper Inc.
 * @version     5.0
 * @Joomla      3.7   
 * @license   : GNU/GPL http://www.gnu.org/copyleft/gpl.html 
*/

defined( '_JEXEC' ) or die( 'Restricted access' );

jimport( 'joomla.application.component.view');

class activehelper_livehelpViewmodule_generation extends JViewLegacy
{
	function display($tpl = null)
	{

	
	        $jinput = JFactory::getApplication()->input;
    		$cid   = $jinput->getVar( 'cid', array(0), '', 'array' );			        
            $domain_id =  $cid[0];

      $this->assignRef('domain_id', $domain_id);
 
    $j_ver = array(
       array('value' => 'j15', 'text' => '1.5'),
       array('value' => 'j25', 'text' => '2.5'),
       array('value' => 'j30', 'text' => '3.0'));
       
      $this->assign('j_ver', JHTML::_('select.genericList', $j_ver, 'j_ver', 'class="inputbox" '. '', 'value', 'text', 'j30'));    
     
    // Set the toolbar
     $this->addToolBar();      
     
	 parent::display($tpl);
     
	}
    
 	protected function addToolBar() 
	{
             
        JToolBarHelper::title(JText::_('COM_ACTIVEHELPER_LIVEHELP_MOD_GEN')); 
        JToolBarHelper::custom('domain.generate_module', 'publish.png', 'publish_f2.png',JText::_('COM_ACTIVEHELPER_LIVEHELP_GEN'), true);    
        JToolBarHelper::cancel( 'cancel', $alt = 'JTOOLBAR_CLOSE');
	} 
    
function ReadAgents()
   {
       $db = JFactory::getDbo();
       $query = $db->getQuery(true);
          
       $setSQL = "select id, username from #__livehelp_users where disabled =0 order by 2";                    
        
       $db->setQuery($setSQL); 
       
        if (!$db->query()) {
			$this->setError($this->_db->getErrorMsg());
			return false;
		  } 

         $rows = $db->loadAssocList();
         
         return $rows;
     }                               

}