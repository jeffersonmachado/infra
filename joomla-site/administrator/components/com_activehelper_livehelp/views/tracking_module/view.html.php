<?php

/**
 * @Product     ActiveHelper LiveHelp component for Joomla
 * @package		Tracking_module
 * @subpackage	Views
 * @copyright	(C) 2010- 2017 ActiveHelper Inc.
 * @author		ActiveHelper Inc.
 * @version     5.0
 * @Joomla      3.7    
 * @license   : GNU/GPL http://www.gnu.org/copyleft/gpl.html 
*/

defined( '_JEXEC' ) or die( 'Restricted access' );

jimport( 'joomla.application.component.view');

class activehelper_livehelpViewtracking_module extends JViewLegacy
{
	function display($tpl = null)
	{

	    $jinput        = JFactory::getApplication()->input;	 
	    $cid           = $jinput->getVar( 'cid', array(0), '', 'array' );
		$domain_id     =  $cid[0];
			    

      $languajes = array(
       array('value' => 'en', 'text' => 'English'),
       array('value' => 'sp', 'text' => 'Spanish'),
       array('value' => 'de', 'text' => 'German'),
       array('value' => 'pt', 'text' => 'Portuguese'),
       array('value' => 'it', 'text' => 'Italian'),
       array('value' => 'fr', 'text' => 'French'),
       array('value' => 'cz', 'text' => 'Czech'),
       array('value' => 'cz', 'text' => 'Swedish'),
       array('value' => 'no', 'text' => 'Norwegian'),
       array('value' => 'tr', 'text' => 'Turkey'),
       array('value' => 'gr', 'text' => 'Greek'),
       array('value' => 'he', 'text' => 'Hebrew'),
       array('value' => 'fa', 'text' => 'Farsi'),
       array('value' => 'sr', 'text' => 'Serbian'),
       array('value' => 'ru', 'text' => 'Russian'),
       array('value' => 'hu', 'text' => 'Hungarian'),
       array('value' => 'zh', 'text' => 'Traditional Chinese'),
       array('value' => 'cn', 'text' => 'Simplified Chinese'),
       array('value' => 'ar', 'text' => 'Arab'),
       array('value' => 'nl', 'text' => 'Dutch'),
       array('value' => 'fi', 'text' => 'Finnish'),
       array('value' => 'dk', 'text' => 'Danish'),
       array('value' => 'pl', 'text' => 'Polish'),
       array('value' => 'bg', 'text' => 'Bulgarian'),
       array('value' => 'sk', 'text' => 'Slovak'),
       array('value' => 'cr', 'text' => 'Croatian'),
       array('value' => 'id', 'text' => 'Indonesian'),
       array('value' => 'lt', 'text' => 'Lithuanian'),
       array('value' => 'ro', 'text' => 'Romanian'),
       array('value' => 'sl', 'text' => 'Slovenian'),
       array('value' => 'et', 'text' => 'Estonian'),
       array('value' => 'lv', 'text' => 'Latvian'),
       array('value' => 'ge', 'text' => 'Georgian'),
	   array('value' => 'jp', 'text' => 'Japanese'));

       $this->assign('languajes', JHTML::_('select.genericList', $languajes, 'languajes', 'class="inputbox" '. '', 'value', 'text', 'english'));  
       
       $status_type = array(
        array('value' => '0', 'text' => 'Domain'),
        array('value' => '1', 'text' => 'Agent'));
       
     $this->assign('status_type', JHTML::_('select.genericList', $status_type, 'status_type', 'class="inputbox" '. '', 'value', 'text', 'Domain' ));           
    
     $tracking = array(
       array('value' => '1', 'text' => 'Enable'),
       array('value' => '0', 'text' => 'Disable'));
       
     $this->assign('tracking', JHTML::_('select.genericList', $tracking, 'tracking', 'class="inputbox" '. '', 'value', 'text', 'Enable' ));  
       
    
     $status_indicator = array(
       array('value' => '1', 'text' => 'Enable'),
       array('value' => '0', 'text' => 'Disable'));
       
     $this->assign('status_indicator', JHTML::_('select.genericList', $status_indicator, 'status_indicator', 'class="inputbox" '. '', 'value', 'text', 'Enable' ));  
	 
	 $textdir= array(
       array('value' => '0', 'text' => 'Left to Right'),
       array('value' => '1', 'text' => 'Right to Left'));
       
     $this->assign('textdir', JHTML::_('select.genericList', $textdir, 'textdir', 'class="inputbox" '. '', 'value', 'text', 'Left to Right' ));  
	 
     $this->assignRef('domain_id', $domain_id);
      
     $agents = $this-> ReadAgents();  
     $this->assign('agents', JHTML::_('select.genericList', $agents, 'agents', 'class="inputbox" '. '', 'id', 'username',$agents));
     
     $footer = array(
       array('value' => '1', 'text' => 'Right'),
       array('value' => '2', 'text' => 'Left'),
       array('value' => '0', 'text' => 'None'));
       
     $this->assign('footer', JHTML::_('select.genericList', $footer, 'footer', 'class="inputbox" '. '', 'value', 'text', 0 ));    
     
    // Set the toolbar
     $this->addToolBar();      
     
	 parent::display($tpl);
     
	}
    
 	protected function addToolBar() 
	{
             
        JToolBarHelper::title(JText::_('COM_ACTIVEHELPER_LIVEHELP_JS')); 
        JToolBarHelper::custom('domain.generate_script', 'publish.png', 'publish_f2.png',JText::_('COM_ACTIVEHELPER_LIVEHELP_GEN'), true);    
        JToolBarHelper::cancel( 'cancel', $alt = 'JTOOLBAR_CLOSE' );
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