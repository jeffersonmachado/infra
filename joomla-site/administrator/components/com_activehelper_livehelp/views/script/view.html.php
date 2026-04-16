<?php

/**
 * @Product     ActiveHelper LiveHelp component for Joomla
 * @package		Script
 * @subpackage	Views
 * @copyright	(C) 2010- 2017 ActiveHelper Inc.
 * @author		ActiveHelper Inc.
 * @version     5.0
 * @Joomla      3.7     
 * @license   : GNU/GPL http://www.gnu.org/copyleft/gpl.html
*/

defined( '_JEXEC' ) or die( 'Restricted access' );

jimport( 'joomla.application.component.view');
jimport('joomla.filter.output');

include_once (JPATH_COMPONENT_ADMINISTRATOR .'/language/english.php');

class activehelper_livehelpViewscript extends JViewLegacy
{
	function display($tpl = null)
	{   
    global $option;
	
	 $jinput = JFactory::getApplication()->input;
	 
	 $id           = $jinput->get('Domain_id');
     $status_type  = $jinput->get('status_type');
     $id_agent     = $jinput->get('agents');
     $languaje     = $jinput->get('languajes');
     $tracking     = $jinput->get('tracking');
     $indicator    = $jinput->get('status_indicator');
     $footer       = $jinput->get('footer');

    $editor_html =JFactory::getEditor('none');
  
    if ($status_type ==0) {
        $id_agent =0;
        }  

    # dinamic URL base
     $server_path = JURI::root() . 'components/com_activehelper_livehelp/server'  . '/' . 'import' . '/' . 'javascript.php';

  if ($footer == 1) 
           $footer_script ='<p class="pin"><span style="font-size: 10pt;"><div style="position: fixed; bottom: 0px; right:0px; z-index:999999999999; display:block;"> ';
          else
    if ($footer == 2) 
           $footer_script ='<p class="pin"><span style="font-size: 10pt;"><div style="position: fixed; bottom: 0px; left:0px; z-index:999999999999; display:block;"> ';

     if ($footer == 0)
       $script_html = $this->get_html_script($id,$id_agent,$server_path,$languaje,$tracking,$indicator);
      else
        $script_html = $this->get_html_footer_script($id,$id_agent,$server_path,$languaje,$tracking,$indicator,$footer_script);

    $this->assignRef('script_html', $script_html);

    $this->assignRef('editor_html',$editor_html);

    // Set the toolbar
    $this->addToolBar(); 
           
    parent::display($tpl);
    
	}

  function get_html_script($domain,$agent,$path,$lan,$tracking,$indicator)
  {
     $CR = "\r\n";

    return '<script language="JavaScript" type="text/JavaScript"'.' src="'. $path . '">'.$CR.
             " ".$CR. 
             '</script>'.$CR. 
             " ".$CR. 
             '<script type="text/javascript">'.$CR. 
             '_vlDomain ='.$domain. ';'.$CR.
             '_vlAgent ='.$agent. ';'.$CR.
             '_vlService = 1; '.$CR.
             '_vlLanguage ='. '"' .  $lan. '";' .$CR.
             '_vlTracking = '.  $tracking. ';' .$CR. 
             '_vlStatus_indicator ='.  $indicator. ';'.$CR.
             ' startLivehelp();'.$CR.
             ' </script>';


     }
     
 function get_html_footer_script($domain,$agent,$path,$lan,$tracking,$indicator,$footer)
  {
     $CR = "\r\n";

    return   $footer .$CR.
             '<script language="JavaScript" type="text/JavaScript"'.' src="'. $path . '">'.$CR.
             " ".$CR. 
             '</script>'.$CR. 
             " ".$CR. 
             '<script type="text/javascript">'.$CR. 
             '_vlDomain ='.$domain. ';'.$CR.
             '_vlAgent ='.$agent. ';'.$CR.
             '_vlService = 1; '.$CR.
             '_vlLanguage ='. '"' .  $lan. '";' .$CR.
             '_vlTracking = '.  $tracking. ';' .$CR. 
             '_vlStatus_indicator ='.  $indicator. ';'.$CR.
             ' startLivehelp();'.$CR.
             ' </script>';


     }        
     
  	protected function addToolBar() 
	{
             
        JToolBarHelper::title(JText::_('COM_ACTIVEHELPER_LIVEHELP_LIVEHELP_SCRIPT_EN')); 
        JToolBarHelper::cancel('cancel', $alt = 'JTOOLBAR_CLOSE' );
	}      
}