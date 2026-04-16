<?php

/**
 * @Product     ActiveHelper LiveHelp component for Joomla
 * @package		Module
 * @subpackage	View
 * @copyright	(C) 2010- 2017 ActiveHelper Inc.
 * @author		ActiveHelper Inc.
 * @version     5.0
 * @Joomla      3.7  
 * @license   : GNU/GPL http://www.gnu.org/copyleft/gpl.html  
*/


defined( '_JEXEC' ) or die( 'Restricted access' );

 jimport('joomla.application.component.view');
 jimport('joomla.filter.output');
 jimport('joomla.filesystem.file');
 jimport('joomla.filesystem.archive');


include_once (JPATH_COMPONENT_ADMINISTRATOR .'/language/english.php');

class activehelper_livehelpViewModule extends JViewLegacy
{
	function display($tpl = null)
	{
        jimport('joomla.filesystem.folder');
        jimport('joomla.filesystem.file');
    

		$jinput = JFactory::getApplication()->input;

        $id    = $jinput->get('Domain_id');
        $jversion    = $jinput->get('j_ver');
		
              
    # dinamic URL base
    $server_path = JURI::root() . 'components/com_activehelper_livehelp/server'  . '/' . 'import' . '/' . 'javascript.php';


    $path = JPATH_SITE .DIRECTORY_SEPARATOR. 'components' .DIRECTORY_SEPARATOR. 'com_activehelper_livehelp'.DIRECTORY_SEPARATOR. 'files' ;

    $download_path  =  JURI::root() . 'components/com_activehelper_livehelp/' . 'files' ;

    if (JFolder::exists($path) == false) {
        JFolder::create($path);
       }


    $path_module_file = $path .DIRECTORY_SEPARATOR. 'mod_activehelper_livehelp.php';
    $path_mo_xml_file = $path .DIRECTORY_SEPARATOR. 'mod_activehelper_livehelp.xml';
    $path_helper_file = $path .DIRECTORY_SEPARATOR. 'helper.php';
    $path_module_zip  = $path .DIRECTORY_SEPARATOR. 'mod_activehelper_livehelp.zip' ;
    $path_download_module_zip  = $download_path . '/' . 'mod_activehelper_livehelp.zip' ;



    if ($jversion == 'j15') {
         $script_mod = $this->get_mod_script_j15($id,$server_path,$languaje);
        $xml_mod    = $this->get_xml_file_15();}
    else  
    if ($jversion == 'j25') {
          $script_mod = $this->get_mod_script_j25($id,$server_path,$languaje);          
          $xml_mod    = $this->get_xml_file_j25();
          $xml_helper = $this->get_helper_file_j25();            
         }
   else  
    if ($jversion == 'j30') {             
          $script_mod = $this->get_mod_script_j25($id,$server_path,$languaje);          
          $xml_mod    = $this->get_xml_file_j25();
          $xml_helper = $this->get_helper_file_j25();            
      }


    $filesforzip = array();
    $filesforzip[] = array('name' => 'mod_activehelper_livehelp.php','data' => $script_mod);
    $filesforzip[] = array('name' => 'mod_activehelper_livehelp.xml','data' => $xml_mod);
    $filesforzip[] = array('name' => 'helper.php','data' => $xml_helper);


    // create zip module

    $zip =& JArchive::getAdapter('zip');
    $zip->create($path_module_zip , $filesforzip);

    $this->assignRef('path_module',$path_module_zip);
    $this->assignRef('path_download_module',$path_download_module_zip);
   
    // Set the toolbar
    $this->addToolBar($item);
        
   parent::display($tpl);
	}

   function get_mod_script_j25($domain,$path,$lan)
  {
     $CR = "\r\n";

     return  '<?php'.$CR.
             " defined('_JEXEC') or die('Restricted access');".$CR.
             "require_once dirname(__FILE__) . '/helper.php';".$CR.
             '$class_sfx = htmlspecialchars($params->get('. " 'class_sfx'));".$CR.                      
             " ".$CR.             
             '$language  = $params->get("languages","");'.$CR.
             '$track     = $params->get("tracking","");'.$CR.
             '$indicator = $params->get("status_indicator","");'.$CR.    
             '$agent     = $params->get("agent_id","");'.$CR.
             '$footer    = $params->get("footer","");'.$CR.
			 '$direction = $params->get("textdir","");'.$CR.
              " ".$CR.  
              'if ($footer ==1)'.$CR.
                    '$footer_string ='. "'". '<p class="pin"><span style="font-size: 10pt;"><div style="position: fixed; bottom: 0px; right:0px; z-index:999999999999; display:block;">'. "';". $CR.            
                 ' else'   .$CR.
             'if ($footer ==2)'.$CR.
                    '$footer_string ='. "'". '<p class="pin"><span style="font-size: 10pt;"><div style="position: fixed; center: 0px; right:0px; z-index:999999999999; display:block;">'. "';". $CR.                         
                 ' else'   .$CR.
             'if ($footer ==3)'.$CR.
                    '$footer_string ='. "'". '<p class="pin"><span style="font-size: 10pt;"><div style="position: fixed; top: 0px; right:0px; z-index:999999999999; display:block;">'. "';". $CR.                         
                 ' else'   .$CR.
             'if ($footer ==4)'.$CR.
                    '$footer_string ='. "'". '<p class="pin"><span style="font-size: 10pt;"><div style="position: fixed; bottom: 0px; center:0px; z-index:999999999999; display:block;">'. "';". $CR.                       
                 ' else'   .$CR.
             'if ($footer ==5)'.$CR.
                    '$footer_string ='. "'". '<p class="pin"><span style="font-size: 10pt;"><div style="position: fixed; top: 0px; center:0px; z-index:999999999999; display:block;">'. "';". $CR.                                        
                 ' else'   .$CR.
             'if ($footer ==6)'.$CR.
                    '$footer_string ='. "'". '<p class="pin"><span style="font-size: 10pt;"><div style="position: fixed; bottom: 0px; left:0px; z-index:999999999999; display:block;">'. "';". $CR.                                          
                 ' else'   .$CR.
             'if ($footer ==7)'.$CR.
                    '$footer_string ='. "'". '<p class="pin"><span style="font-size: 10pt;"><div style="position: fixed; Center: 0px; left:0px; z-index:999999999999; display:block;">'. "';". $CR.                                         
                 ' else'   .$CR.
             'if ($footer ==8)'.$CR.
                    '$footer_string ='. "'". '<p class="pin"><span style="font-size: 10pt;"><div style="position: fixed; top: 0px; left:0px; z-index:999999999999; display:block;">'. "';". $CR.                                        
              ' else'   .$CR.                                                     
                 '$footer_string ='."''".';'.$CR.             
             " ".$CR.              
             '$tracking_script =$footer_string.'. "'<script language=" .'"JavaScript" type="text/JavaScript"'. ' src="'. $path . '">'.$CR.
             " ".$CR.
             '</script> <script type="text/javascript"> '.$CR. 
             '_vlDomain ='.$domain. ';'.$CR. 
             "_vlAgent ='" . ". " . '$agent' . ".'" . ';'.$CR.              
             "_vlService = 1; ".$CR. 
             "_vlLanguage =" . '"' . "'" . '.$language' . ".'" . '";' .$CR.
             "_vlTracking ='". "." . '$track' . ".'" . ';' .$CR.  
             " _vlStatus_indicator ='" . ". " . '$indicator' . ".'" . ';'.$CR.
			 " _vldirection ='" . ". " . '$direction' . ".'" . ';'.$CR.
             " startLivehelp();" .$CR. 
             "</script>';" .$CR.  
             " ".$CR.
             'echo ($tracking_script);'; 
     }
     
  function get_helper_file_j25()
  {
     $CR = "\r\n";

     return  '<?php'.$CR.
             "defined('_JEXEC') or die;".$CR.
             'class mod_activehelper_livehelpHelper {'.$CR.
             '}';
     }
     
   function get_xml_file_j25()
  {
     $CR = "\r\n";

     return  '<?xml version="1.0" encoding="utf-8"?>'.$CR.
             '<extension type="module" version="3.0" client="site" method="upgrade">'.$CR.
             '<name>ActiveHelper LiveHelp Module</name>'.$CR.
             '<author>ActiveHelper</author>'.$CR.
             '<creationDate>August 2016</creationDate>'.$CR.
             '<copyright>Copyright 2011 - 2016 by ActiveHelper Inc. All rights reserved</copyright>' .$CR.
             '<license>GNU/GPLv3 http://www.gnu.org/copyleft/gpl.html</license>'.$CR.
             '<authorEmail>support@activehelper.com</authorEmail>'.$CR.
             '<authorUrl>www.activehelper.com</authorUrl>'.$CR.
             '<version>4.5</version>'.$CR.
             '<description>A module for livehelp system tracking</description>'.$CR.
             '<files>'.$CR.
             '<filename module="mod_activehelper_livehelp">mod_activehelper_livehelp.php</filename>'.$CR.
             '<filename>mod_activehelper_livehelp.xml</filename>'.$CR.
             '<filename>helper.php</filename>'.$CR.
             '</files>'.$CR.             
             '<config>'.$CR.
             '<fields name="params">'.$CR.
             '<fieldset name="basic">'.$CR.
             '<field name="languages" type="list" default="English" label="language" description="Tracking module language">'.$CR.
             '<option value="en">English</option>'.$CR.
             '<option value="sp">Spanish</option>'.$CR.
             '<option value="de">German</option>'.$CR.
             '<option value="pt">Portuguese</option>'.$CR.
             '<option value="it">Italian</option>'.$CR.
             '<option value="fr">French</option>'.$CR.
             '<option value="cz">Czech</option>'.$CR.
             '<option value="se">Swedish</option>'.$CR.
             '<option value="no">Norwegian</option>'.$CR.
             '<option value="tr">Turkey</option>'.$CR.
             '<option value="gr">Greek</option>'.$CR.
             '<option value="he">Hebrew</option>'.$CR.
             '<option value="fa">Farsi</option>'.$CR.
             '<option value="sr">Serbian</option>'.$CR.
             '<option value="ru">Russian</option>'.$CR.
             '<option value="hu">Hungarian</option>'.$CR.
             '<option value="zh">Traditional Chinese</option>'.$CR.
             '<option value="cn">Simplified Chinese</option>'.$CR.
             '<option value="ar">Arab</option>'.$CR.
             '<option value="nl">Dutch</option>'.$CR.
             '<option value="fi">Finnish</option>'.$CR.
             '<option value="dk">Danish</option>'.$CR.
             '<option value="pl">Polish</option>'.$CR.
             '<option value="bg">Bulgarian</option>'.$CR.
             '<option value="sk">Slovak</option>'.$CR.
             '<option value="cr">Croatian</option>'.$CR.
             '<option value="id">Indonesian</option>'.$CR.
             '<option value="lt">Lithuanian</option>'.$CR.
             '<option value="ro">Romanian</option>'.$CR.
             '<option value="sl">Slovenian</option>'.$CR.
             '<option value="et">Estonian</option>'.$CR.
             '<option value="lv">Latvian</option>'.$CR.
             '<option value="ge">Georgian</option>'.$CR.
			 '<option value="jp">Japanese</option>'.$CR.
             '</field>'.$CR.
              '<field name="footer" type="list" default="None" label="Absolute Position" description="Position">'.$CR.     
             '<option value="0">None</option>'.$CR.
             '<option value="1">Right_Bottom</option>'.$CR.
             '<option value="2">Right_Center</option>'.$CR.
             '<option value="3">Right_Top</option>'.$CR.
             '<option value="4">Center_Bottom</option>'.$CR.
             '<option value="5">Center_Top</option>'.$CR.
             '<option value="6">Left_Bottom</option>'.$CR.
             '<option value="7">Left_Center</option>'.$CR.
             '<option value="8">Left_Top</option>'.$CR.
             '</field>'.$CR.
             '<field name="tracking" type="radio" default="1" label="Tracking" description="Use Trackin">'.$CR.
             '<option value="1">Enable</option>'.$CR.
             '<option value="0">Disable</option>'.$CR. 
             '</field>'.$CR.
             '<field name="status_indicator" type="radio" default="1" label="Status Indicator" description="Use Status Indicator">'.$CR.
             '<option value="1">Enable</option>'.$CR.
             '<option value="0">Disable</option>'.$CR.
             '</field>'.$CR.
			 '<field name="textdir" type="radio" default="0" label="Text Direction" description="Text Language Direction">'.$CR.
             '<option value="0">Left to Right</option>'.$CR.
             '<option value="1">Right to Left</option>'.$CR.
             '</field>'.$CR.
             '<field name="agent_id" type="text" default="0" label="Agent ID" description="Use only for agent status indicator, the general status indicator is 0" />'.$CR.
             '</fieldset>'.$CR. 
             '<fieldset name="advanced"> '.$CR.   
             '<field '.$CR.
             'name="moduleclass_sfx"'.$CR.
             'type="text"'.$CR.
             'label="COM_MODULES_FIELD_MODULECLASS_SFX_LABEL"'.$CR.
             'description="COM_MODULES_FIELD_MODULECLASS_SFX_DESC"'.$CR.
             '/>'.$CR.
             '</fieldset> '.$CR.            
             '</fields>'.$CR. 
             '</config>'.$CR.           
             '</extension>';
     }
     
     

function get_mod_script_j15($domain,$path,$lan)
  {
     $CR = "\r\n";

     return  '<?php'.$CR.
             " defined('_JEXEC') or die('Restricted access');".$CR.
             " ".$CR.
             '$language  = $params->get("languages","");'.$CR.
             '$track     = $params->get("tracking","");'.$CR.
             '$indicator = $params->get("status_indicator","");'.$CR.
             '$agent     = $params->get("agent_id","");'.$CR.
             '$footer    = $params->get("footer","");'.$CR.
			 '$direction = $params->get("textdir","");'.$CR.
              " ".$CR.  
              'if ($footer ==1)'.$CR.
                    '$footer_string ='. "'". '<p class="pin"><span style="font-size: 10pt;"><div style="position: fixed; bottom: 0px; right:0px; z-index:999999999999; display:block;">'. "';". $CR.            
                 ' else'   .$CR.
             'if ($footer ==2)'.$CR.
                    '$footer_string ='. "'". '<p class="pin"><span style="font-size: 10pt;"><div style="position: fixed; center: 0px; right:0px; z-index:999999999999; display:block;">'. "';". $CR.                         
                 ' else'   .$CR.
             'if ($footer ==3)'.$CR.
                    '$footer_string ='. "'". '<p class="pin"><span style="font-size: 10pt;"><div style="position: fixed; top: 0px; right:0px; z-index:999999999999; display:block;">'. "';". $CR.                         
                 ' else'   .$CR.
             'if ($footer ==4)'.$CR.
                    '$footer_string ='. "'". '<p class="pin"><span style="font-size: 10pt;"><div style="position: fixed; bottom: 0px; center:0px; z-index:999999999999; display:block;">'. "';". $CR.                       
                 ' else'   .$CR.
             'if ($footer ==5)'.$CR.
                    '$footer_string ='. "'". '<p class="pin"><span style="font-size: 10pt;"><div style="position: fixed; top: 0px; center:0px; z-index:999999999999; display:block;">'. "';". $CR.                                        
                 ' else'   .$CR.
             'if ($footer ==6)'.$CR.
                    '$footer_string ='. "'". '<p class="pin"><span style="font-size: 10pt;"><div style="position: fixed; bottom: 0px; left:0px; z-index:999999999999; display:block;">'. "';". $CR.                                          
                 ' else'   .$CR.
             'if ($footer ==7)'.$CR.
                    '$footer_string ='. "'". '<p class="pin"><span style="font-size: 10pt;"><div style="position: fixed; Center: 0px; left:0px; z-index:999999999999; display:block;">'. "';". $CR.                                         
                 ' else'   .$CR.
             'if ($footer ==8)'.$CR.
                    '$footer_string ='. "'". '<p class="pin"><span style="font-size: 10pt;"><div style="position: fixed; top: 0px; left:0px; z-index:999999999999; display:block;">'. "';". $CR.                                        
              ' else'   .$CR.                                                     
                 '$footer_string ='."''".';'.$CR.             
             " ".$CR.              
             '$tracking_script =$footer_string.'. "'<script language=" .'"JavaScript" type="text/JavaScript"'. ' src="'. $path . '">'.$CR.
             " ".$CR.
             '</script> <script type="text/javascript"> '.$CR. 
             '_vlDomain ='.$domain. ';'.$CR.   
             "_vlAgent ='" . ". " . '$agent' . ".'" . ';'.$CR.        
             "_vlService = 1; ".$CR. 
             "_vlLanguage =" . '"' . "'" . '.$language' . ".'" . '";' .$CR.
             "_vlTracking ='". "." . '$track' . ".'" . ';' .$CR.  
             " _vlStatus_indicator ='" . ". " . '$indicator' . ".'" . ';'.$CR.
			 " _vldirection ='" . ". " . '$direction' . ".'" . ';'.$CR.
             " startLivehelp();" .$CR. 
             "</script>';" .$CR.  
             " ".$CR.
             'echo ($tracking_script);'; 
     }

   function get_xml_file_15()
  {
     $CR = "\r\n";

     return  '<?xml version="1.0" encoding="utf-8"?>'.$CR.
             '<install type="module" version="1.5">'.$CR.
             '<name>ActiveHelper LiveHelp Module</name>'.$CR.
             '<author>ActiveHelper</author>'.$CR.
             '<creationDate>August 2016</creationDate>'.$CR.
             '<copyright>Copyright 2010 - 2016 by ActiveHelper Inc. All rights reserved</copyright>' .$CR.
             '<license>GNU/GPLv3 http://www.gnu.org/copyleft/gpl.html</license>'.$CR.
             '<authorEmail>support@activehelper.com</authorEmail>'.$CR.
             '<authorUrl>www.activehelper.com</authorUrl>'.$CR.
             '<version>4.5</version>'.$CR.
             '<description>A module for livehelp system tracking</description>'.$CR.
             '<files>'.$CR.
             '<filename module="mod_activehelper_livehelp">mod_activehelper_livehelp.php</filename>'.$CR.
             '<filename>mod_activehelper_livehelp.xml</filename>'.$CR.
             '</files>'.$CR.             
             '<params>'.$CR.
             '<param name="languages" type="list" default="English" label="language" description="Tracking module language">'.$CR.
             '<option value="en">English</option>'.$CR.
             '<option value="sp">Spanish</option>'.$CR.
             '<option value="de">German</option>'.$CR.
             '<option value="pt">Portuguese</option>'.$CR.
             '<option value="it">Italian</option>'.$CR.
             '<option value="fr">French</option>'.$CR.
             '<option value="cz">Czech</option>'.$CR.
             '<option value="se">Swedish</option>'.$CR.
             '<option value="no">Norwegian</option>'.$CR.
             '<option value="tr">Turkey</option>'.$CR.
             '<option value="gr">Greek</option>'.$CR.
             '<option value="he">Hebrew</option>'.$CR.
             '<option value="fa">Farsi</option>'.$CR.
             '<option value="sr">Serbian</option>'.$CR.
             '<option value="ru">Russian</option>'.$CR.
             '<option value="hu">Hungarian</option>'.$CR.             
             '<option value="zh">Traditional Chinese</option>'.$CR.
             '<option value="cn">Simplified Chinese</option>'.$CR.
             '<option value="ar">Arab</option>'.$CR.
             '<option value="nl">Dutch</option>'.$CR.
             '<option value="fi">Finnish</option>'.$CR.
             '<option value="dk">Danish</option>'.$CR.
             '<option value="pl">Polish</option>'.$CR.             
             '<option value="bg">Bulgarian</option>'.$CR.
             '<option value="sk">Slovak</option>'.$CR.
             '<option value="cr">Croatian</option>'.$CR.
             '<option value="id">Indonesian</option>'.$CR.
             '<option value="lt">Lithuanian</option>'.$CR.
             '<option value="ro">Romanian</option>'.$CR.
             '<option value="sl">Slovenian</option>'.$CR.
             '<option value="et">Estonian</option>'.$CR.
             '<option value="lv">Latvian</option>'.$CR.
             '<option value="ge">Georgian </option>'.$CR.
             '</param>'.$CR.
             '<param name="footer" type="list" default="None" label="Absolute Position" description="Position">'.$CR.
             '<option value="0">None</option>'.$CR.
             '<option value="1">Right_Bottom</option>'.$CR.
             '<option value="2">Right_Center</option>'.$CR.
             '<option value="3">Right_Top</option>'.$CR.
             '<option value="4">Center_Bottom</option>'.$CR.
             '<option value="5">Center_Top</option>'.$CR.
             '<option value="6">Left_Bottom</option>'.$CR.
             '<option value="7">Left_Center</option>'.$CR.
             '<option value="8">Left_Top</option>'.$CR.
             '</param>'.$CR.
             '<param name="tracking" type="radio" default="1" label="Tracking" description="Use Tracking">'.$CR.
             '<option value="1">Enable</option>'.$CR.
             '<option value="0">Disable</option>'.$CR. 
             '</param>'.$CR.
             '<param name="status_indicator" type="radio" default="1" label="Status Indicator" description="Use Status Indicator">'.$CR.
             '<option value="1">Enable</option>'.$CR.
             '<option value="0">Disable</option>'.$CR.
             '</param>'.$CR.
			 '<param name="textdir" type="radio" default="1" label="Text Direction" description="Text Language Direction">'.$CR.
             '<option value="0">Left to Right</option>'.$CR.
             '<option value="1">Right to Left</option>'.$CR.
             '</param>'.$CR.
             '<param name="agent_id" type="text" default="0" label="Agent ID" description="Use only for agent status indicator, the general status indicator is 0"  size="2" />'.$CR.
             '</params>'.$CR.             
             '</install>';
     }

  	protected function addToolBar() 
	{             
	      JToolBarHelper::title(JText::_('COM_ACTIVEHELPER_LIVEHELP_TRACKING_MD')); 
          JToolBarHelper::cancel(JTOOLBAR_CLOSE);
	}   
}