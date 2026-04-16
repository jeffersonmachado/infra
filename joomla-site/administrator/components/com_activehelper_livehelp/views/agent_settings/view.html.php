<?php

/**
 * @Product     ActiveHelper LiveHelp component for Joomla
 * @package		Agent
 * @subpackage	Agent Settings
 * @copyright	(C) 2010- 2017 ActiveHelper Inc.
 * @author		ActiveHelper Inc.
 * @version     5.0
 * @Joomla      3.7
 * @license   : GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

defined( '_JEXEC' ) or die( 'Restricted access' );

jimport( 'joomla.application.component.view');
jimport('joomla.filesystem.folder');
jimport('joomla.filesystem.file');
jimport('joomla.log.log');

class activehelper_livehelpViewagent_settings extends JViewLegacy

{
  function display($tpl = null)
 	{


        jimport('joomla.filesystem.file');
            

       // Check the default status indicator type images
        include_once (JPATH_ROOT .DIRECTORY_SEPARATOR. 'components'.DIRECTORY_SEPARATOR. 'com_activehelper_livehelp' .DIRECTORY_SEPARATOR. 'server' .DIRECTORY_SEPARATOR. 'import' .DIRECTORY_SEPARATOR. 'constants.php');
          
		  
		 $jinput   = JFactory::getApplication()->input;		  
         $cid      = $jinput->get('cid' , array(0)); 
		 $id       = $cid[0];
		 
		/* Error log
		 JLog::addLogger(array('text_file' => 'livechat.log'));
		 JLog::add("id==>: " . $id); 
		*/
	
	
    $this->assignRef('id_agent', $id);
   
    $uri =JURI::getInstance();
    
    # image path lang
    $img_path_lang = JURI::root() . 'components/com_activehelper_livehelp/server/agents/'. $id . '/i18n/__lang__/';
    $this->assignRef('img_path_lang', $img_path_lang);
   
   
    # images
    $img_path = '../components/com_activehelper_livehelp/server/agents/'. $id . '/i18n/en/';
    
    $img_path_form =  JURI::root().'components/com_activehelper_livehelp/server/agents/'. $id . '/i18n/en/';
    
    $this->assignRef('img_path', $img_path_form);
    $this->assignRef('status_default_img_type', $status_indicator_img_type);
                
    $online_img =$this->imgExits('online',$img_path,$status_indicator_img_type);
    $this->assignRef('online_img',$online_img);

    $offline_img =$this->imgExits('offline',$img_path,$status_indicator_img_type);
    $this->assignRef('offline_img',$offline_img);

    $away_img =$this->imgExits('away',$img_path,$status_indicator_img_type);
    $this->assignRef('away_img',$away_img);

    $brb_img =$this->imgExits('brb',$img_path,$status_indicator_img_type);
    $this->assignRef('brb_img',$brb_img);

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
       array('value' => 'ch', 'text' => 'Simplified Chinese'),
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

     // Time schedule
       
       $schedule_rec = $this->Time_schedule($id);
       
           $schedule= array(
			array('value' => '0', 'text' => 'Disable'),
			array('value' => '1', 'text' => 'Enable'));
                            
      $this->assign('schedule', JHTML::_('select.genericList', $schedule, 'schedule', 'class="inputbox" '. '', 'value', 'text', $schedule_rec['0']));
           
      $this->assignRef('int_time',$schedule_rec["1"]);
      $this->assignRef('end_time',$schedule_rec["2"]);
      
    
    // Set the toolbar
     $this->addToolBar();
       
     parent::display($tpl);

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

  protected function addToolBar() 
	{
        JToolBarHelper::title(JText::_('COM_ACTIVEHELPER_LIVEHELP_EDIT_AGENT'));                  		
        JToolBarHelper::save('agent.updatesettings','JTOOLBAR_SAVE');
        JToolBarHelper::cancel('cancel', 'Close','JTOOLBAR_CLOSE' );
	} 
    
 function Time_schedule($id)
       {
        
         $db = JFactory::getDbo();
         
         $setSQL = "select schedule, initial_time, final_time from #__livehelp_users where id = ". $id .  " "; 
         
         $db->setQuery($setSQL);
      
       	if (!$db->query()) {
			$this->setError($this->_db->getErrorMsg());	
		  } 

         $row = $db->loadRow();  
         return $row;       
     }               
}
