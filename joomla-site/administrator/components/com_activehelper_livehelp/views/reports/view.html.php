<?php

/**
 * @Product     ActiveHelper LiveHelp component for Joomla
 * @package		Reports
 * @subpackage	View
 * @copyright	(C) 2010- 2017 ActiveHelper Inc.
 * @author		ActiveHelper Inc.
 * @version     5.0
 * @Joomla      3.7
 * @license   : GNU/GPL http://www.gnu.org/copyleft/gpl.html
*/


defined( '_JEXEC' ) or die( 'Restricted access' );

jimport( 'joomla.application.component.view');


class activehelper_livehelpViewReports extends JViewLegacy 
{
	function display($tpl = null)
	{
	   
     $model = $this->getModel();
       
    # Panel statistic
    $domains          = $model->domains_statistic();
    $agents           = $model->agent_statistic();
    $departments      = $model->department_statistic();
    $chats            = $model->chats_statistic();
    $chats_today      = $model->today_chats_statistic();
    $visitors_today   = $model->today_visitors_statistic();
	$fail_chats       = $model->failed_chats_statistic();
    $avg_chat_rating  = $model->avg_chat_rating();
	
    $montly_chats                  = $model->montly_chats();
    $current_week_chats            = $model->current_week_chats();
    $current_week_offline_messages = $model->current_week_offline_messages();
    $weekly_failed_chats           = $model->weekly_failed_chats();
    
	$core_ver  = $model->live_chat_core_ver();
	
    if ($core_ver == '') { 
	     $live_chat_core = JText::_('COM_ACTIVEHELPER_LIVEHELP_CORE_DOWNLOAD');
		 $link = "http://www.activehelper.com/livechat/live-chat-core.html";
		 $core =false;
	     }	 
     else { 
	       $live_chat_core = JText::_('COM_ACTIVEHELPER_LIVEHELP_CORE_INSTALLED') . $core_ver; 
		   $link = "index.php?option=com_activehelper_livehelp&view=server_settings";
		   $core =true;
		 }
 
    // Asing values to the form
    
    $this->assignRef('domains', $domains);
    $this->assignRef('agents', $agents);
    $this->assignRef('departments', $departments);
    $this->assignRef('chats', $chats);
    $this->assignRef('chats_today', $chats_today);
    $this->assignRef('visitors_today', $visitors_today);
    $this->assignRef('fail_chats', $fail_chats);
    $this->assignRef('avg_chat_rating', $avg_chat_rating);
    
    $this->assignRef('montly_chats', $montly_chats);
    $this->assignRef('current_week_chats', $current_week_chats);
    $this->assignRef('current_week_offline_messages', $current_week_offline_messages);
    $this->assignRef('weekly_failed_chats', $weekly_failed_chats);
	
	$this->assignRef('live_chat_core_ver', $live_chat_core);
	$this->assignRef('link', $link);
	$this->assignRef('core', $core);
	

	// Set the toolbar
	$this->addToolBar();
		
		parent::display($tpl);
        
	}
	
  protected function addToolBar() 
	{
		JToolBarHelper::title(JText::_('COM_ACTIVEHELPER_LIVEHELP_MAIN_TITLE'));               

	}
}


