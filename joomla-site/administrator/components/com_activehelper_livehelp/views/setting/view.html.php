<?php

 /**
 * @ ActiveHelper LiveHelp component for Joomla
 * @author    : ActiveHelper Inc.
 * @copyright : (C) 2010- 2017 ActiveHelper Inc.
 * @license   : GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @version     5.0
 * @Joomla      3.7 
**/


defined( '_JEXEC' ) or die( 'Restricted access' );

jimport( 'joomla.application.component.view');
jimport('joomla.log.log');
jimport('joomla.filesystem.folder');
jimport('joomla.filesystem.file');


class activehelper_livehelpViewsetting extends JViewLegacy
{
	function display($tpl = null)
	{
	   
          
        jimport('joomla.filesystem.file');
    
        if(!defined('DIRECTORY_SEPARATOR')) define('DIRECTORY_SEPARATOR', '/');  
	     
       // Check the default status indicator type images
        include_once (JPATH_ROOT .DIRECTORY_SEPARATOR. 'components'.DIRECTORY_SEPARATOR. 'com_activehelper_livehelp' .DIRECTORY_SEPARATOR. 'server' .DIRECTORY_SEPARATOR. 'import' .DIRECTORY_SEPARATOR. 'constants.php');
        
		
		$jinput = JFactory::getApplication()->input;
	 
	    $cid           = $jinput->getVar( 'cid', array(0), '', 'array' );
	    $id            = $cid[0];
	 

      $model = $this->getModel();

      $row       = $model->ReadDomainSettings($id);
      $rowlan    = $model-> ReadDomainLanguages($id);
      $rowlan_wm = $model-> ReadDomainLanguagesWM($id);

      $this->assignRef('id_domain', $id);

      $this->assignRef('row', $row);
      $this->assignRef('rowlan', $rowlan);

   # general tab
		$departments = array(
			array('value' => '0', 'text' => 'Disable'),
			array('value' => '1', 'text' => 'Enable'));

    $this->assignRef('livehelp_name',$row["livehelp_name"]);
    $this->assignRef('site_name',$row["site_name"]);
    $this->assignRef('site_address',$row["site_address"]);

    $this->assign('departments', JHTML::_('select.genericList', $departments, 'departments', 'class="inputbox" '. '', 'value', 'text', $row["departments"]));
    
    
    $disable_geolocation= array(
			array('value' => '0', 'text' => 'Disable'),
			array('value' => '1', 'text' => 'Enable'));
            
    $this->assign('disable_geolocation', JHTML::_('select.genericList', $disable_geolocation, 'disable_geolocation', 'class="inputbox" '. '', 'value', 'text', $row["disable_geolocation"]));


   $disable_status_indicator= array(
			array('value' => '0', 'text' => 'Disable'),
			array('value' => '1', 'text' => 'Enable'));
            
    $this->assign('disable_status_indicator', JHTML::_('select.genericList', $disable_status_indicator, 'disable_status_indicator', 'class="inputbox" '. '', 'value', 'text', $row["disable_tracking_offline"]));


    $captcha= array(
			array('value' => '0', 'text' => 'Disable'),
			array('value' => '1', 'text' => 'Enable'));
            
    $this->assign('captcha', JHTML::_('select.genericList', $captcha, 'captcha', 'class="inputbox" '. '', 'value', 'text', $row["captcha"]));
    
    
    $phone= array(
			array('value' => '0', 'text' => 'Disable'),
			array('value' => '1', 'text' => 'Enable'));
            
    $this->assign('phone', JHTML::_('select.genericList', $phone, 'phone', 'class="inputbox" '. '', 'value', 'text', $row["phone"]));



    $company= array(
			array('value' => '0', 'text' => 'Disable'),
			array('value' => '1', 'text' => 'Enable'));
            
    $this->assign('company', JHTML::_('select.genericList', $company, 'company', 'class="inputbox" '. '', 'value', 'text', $row["company"]));
    

    # display
    $disable_popup_help = array(
			array('value' => '0', 'text' => 'Disable'),
			array('value' => '1', 'text' => 'Enable'));
            
   # read skins list
    
         jimport('joomla.filesystem.folder');
         jimport('joomla.filesystem.file');
    
        if(!defined('DIRECTORY_SEPARATOR')){ 
            define('DIRECTORY_SEPARATOR',DIRECTORY_SEPARATOR); 
            }   
	       
        $option 	= 'com_activehelper_livehelp';       
       
        $skins_path = JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . $option . DIRECTORY_SEPARATOR .'server' . DIRECTORY_SEPARATOR . 'pictures' . DIRECTORY_SEPARATOR . 'skins';       
            
        $chat_background_img = array();
        foreach (JFolder::folders($skins_path) as $folder){                      
        $chat_background_img[] = JHTML::_('select.option', $folder, $folder);
        }    
        
   
     $this->assignRef('background_color',$row["background_color"]);
     $this->assignRef('chat_font_type',$row["chat_font_type"]);
     $this->assignRef('guest_chat_font_size',$row["guest_chat_font_size"]);
     $this->assignRef('admin_chat_font_size',$row["admin_chat_font_size"]);
     $this->assign('disable_popup_help', JHTML::_('select.genericList', $disable_popup_help, 'disable_popup_help', 'class="inputbox" '. '', 'value', 'text', $row["disable_popup_help"]));


     $disable_agent_bannner= array(
			array('value' => '0', 'text' => 'Disable'),
			array('value' => '1', 'text' => 'Enable'));
            
     $this->assign('disable_agent_bannner', JHTML::_('select.genericList', $disable_agent_bannner, 'disable_agent_bannner', 'class="inputbox" '. '', 'value', 'text', $row["disable_agent_bannner"]));


     // Chat customize
     $this->assignRef('campaign_image',$row["campaign_image"]);
     $this->assignRef('campaign_link',$row["campaign_link"]);
     $this->assignRef('chat_invitation_img',$row["chat_invitation_img"]);
     $this->assignRef('chat_invitation_auto_refresh',$row["invitation_refresh"]);
     $this->assignRef('chat_button_img',$row["chat_button_img"]);
     $this->assignRef('chat_button_hover_img',$row["chat_button_hover_img"]);
     $this->assign('chat_background_img', JHTML::_('select.genericList', $chat_background_img, 'chat_background_img', 'class="inputbox" '. '', 'value', 'text', $row["chat_background_img"]));


    $disable_invitation= array(
			array('value' => '0', 'text' => 'Disable'),
			array('value' => '1', 'text' => 'Enable'));
            
    $this->assign('disable_invitation', JHTML::_('select.genericList', $disable_invitation, 'disable_invitation', 'class="inputbox" '. '', 'value', 'text', $row["disable_invitation"]));
                

    #fonts
    $this->assignRef('font_type',$row["font_type"]);
    $this->assignRef('font_size',$row["font_size"]);
    $this->assignRef('font_color',$row["font_color"]);
    $this->assignRef('font_link_color',$row["font_link_color"]);
    $this->assignRef('sent_font_color',$row["sent_font_color"]);
    $this->assignRef('received_font_color',$row["received_font_color"]);

    # chat
      $disable_chat_username = array(
			 array('value' => '0', 'text' => 'Disable'),
			 array('value' => '1', 'text' => 'Enable'));

      $require_guest_details = array(
			 array('value' => '0', 'text' => 'Disable'),
			 array('value' => '1', 'text' => 'Enable'));
             
      $disable_language = array(
			 array('value' => '0', 'text' => 'Disable'),
			 array('value' => '1', 'text' => 'Enable'));             

      $disable_login_details = array(
			 array('value' => '0', 'text' => 'Disable'),
			 array('value' => '1', 'text' => 'Enable'));  

     $this->assign('disable_chat_username', JHTML::_('select.genericList', $disable_chat_username, 'disable_chat_username', 'class="inputbox" '. '', 'value', 'text', $row["disable_chat_username"]));
     $this->assign('require_guest_details', JHTML::_('select.genericList', $require_guest_details, 'require_guest_details', 'class="inputbox" '. '', 'value', 'text', $row["require_guest_details"]));
     $this->assign('disable_language', JHTML::_('select.genericList', $disable_language, 'disable_language', 'class="inputbox" '. '', 'value', 'text', $row["disable_language"]));
     $this->assign('disable_login_details', JHTML::_('select.genericList', $disable_login_details, 'disable_login_details', 'class="inputbox" '. '', 'value', 'text', $row["disable_login_details"]));


    # email
     $disable_offline_email = array(
			 array('value' => '0', 'text' => 'Disable'),
			 array('value' => '1', 'text' => 'Enable'));

    $this->assign('disable_offline_email', JHTML::_('select.genericList', $disable_offline_email, 'disable_offline_email', 'class="inputbox" '. '', 'value', 'text', $row["disable_offline_email"]));

     $this->assignRef('offline_email',$row["offline_email"]);
     $this->assignRef('from_email',$row["from_email"]);

     $configure_smtp = array(
			 array('value' => '0', 'text' => 'Disable'),
			 array('value' => '1', 'text' => 'Enable'));

    $this->assign('configure_smtp', JHTML::_('select.genericList', $configure_smtp, 'configure_smtp', 'class="inputbox" '. '', 'value', 'text', $row["configure_smtp"]));


     $this->assignRef('custom_offline_form',$row["custom_offline_form_link"]);     
     $this->assignRef('smtp_server',$row["smtp_server"]);
     $this->assignRef('smtp_port',$row["smtp_port"]);

  
   // offline email log  

     $log_offline_email = array(
			 array('value' => '0', 'text' => 'Disable'),
			 array('value' => '1', 'text' => 'Enable'));

    $this->assign('log_offline_email', JHTML::_('select.genericList', $log_offline_email, 'log_offline_email', 'class="inputbox" '. '', 'value', 'text', $row["log_offline_email"]));

     
   
     
    $uri =JURI::getInstance();

    # image path lang
    $img_path_lang = JURI::root() . 'components/com_activehelper_livehelp/server/domains/'. $id . '/i18n/__lang__/pictures/';
    $this->assignRef('img_path_lang', $img_path_lang);
    
    # images
    $img_path =  JURI::root().'components/com_activehelper_livehelp/server/domains/'. $id . '/i18n/en/pictures/';
    $img_file_path ='../components/com_activehelper_livehelp/server/domains/'. $id . '/i18n/en/pictures/';
    
    $this->assignRef('img_path', $img_path);
    $this->assignRef('status_default_img_type', $status_indicator_img_type);

             
    $online_img =$model->imgExits('online',$img_file_path,$status_indicator_img_type);
    $this->assignRef('online_img',$online_img);

    $offline_img =$model->imgExits('offline',$img_file_path,$status_indicator_img_type);
    $this->assignRef('offline_img',$offline_img);

    $away_img =$model->imgExits('away',$img_file_path,$status_indicator_img_type);
    $this->assignRef('away_img',$away_img);

    $brb_img =$model->imgExits('brb',$img_file_path,$status_indicator_img_type);
    $this->assignRef('brb_img',$brb_img);

     $languajes = array(
       array('value' => 'en', 'text' => 'English'),
       array('value' => 'sp', 'text' => 'Spanish'),
       array('value' => 'de', 'text' => 'German'),
       array('value' => 'pt', 'text' => 'Portuguese'),
       array('value' => 'it', 'text' => 'Italian'),
       array('value' => 'fr', 'text' => 'French'),
       array('value' => 'cz', 'text' => 'Czech'),
       array('value' => 'se', 'text' => 'Swedish'),
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
	   array('value' => 'Jp', 'text' => 'Japanese'));

    $this->assign('languajes', JHTML::_('select.genericList', $languajes, 'languajes', 'class="inputbox" '. '', 'value', 'text', 'english'));


        # Languages
     $languaje_en = array(
			 array('value' => '0', 'text' => 'Disable'),
			 array('value' => '1', 'text' => 'Enable'));

    $this->assign('languaje_en', JHTML::_('select.genericList', $languaje_en, 'languaje_en', 'class="inputbox" '. '', 'value', 'text', $rowlan["en"]));
    $this->assignRef('lan_en_wm',$rowlan_wm["en"]);


    $languaje_sp = array(
			 array('value' => '0', 'text' => 'Disable'),
			 array('value' => '1', 'text' => 'Enable'));

    $this->assign('languaje_sp', JHTML::_('select.genericList', $languaje_sp, 'languaje_sp', 'class="inputbox" '. '', 'value', 'text', $rowlan["sp"]));
    $this->assignRef('lan_sp_wm',$rowlan_wm["sp"]);


    $languaje_de = array(
			 array('value' => '0', 'text' => 'Disable'),
			 array('value' => '1', 'text' => 'Enable'));

    $this->assign('languaje_de', JHTML::_('select.genericList', $languaje_de, 'languaje_de', 'class="inputbox" '. '', 'value', 'text', $rowlan["de"]));
    $this->assignRef('lan_de_wm',$rowlan_wm["de"]);


    $languaje_pt = array(
			 array('value' => '0', 'text' => 'Disable'),
			 array('value' => '1', 'text' => 'Enable'));

    $this->assign('languaje_pt', JHTML::_('select.genericList', $languaje_pt, 'languaje_pt', 'class="inputbox" '. '', 'value', 'text', $rowlan["pt"]));
    $this->assignRef('lan_pt_wm',$rowlan_wm["pt"]);


    $languaje_it = array(
			 array('value' => '0', 'text' => 'Disable'),
			 array('value' => '1', 'text' => 'Enable'));

    $this->assign('languaje_it', JHTML::_('select.genericList', $languaje_it, 'languaje_it', 'class="inputbox" '. '', 'value', 'text', $rowlan["it"]));
    $this->assignRef('lan_it_wm',$rowlan_wm["it"]);

    $languaje_fr = array(
			 array('value' => '0', 'text' => 'Disable'),
			 array('value' => '1', 'text' => 'Enable'));

    $this->assign('languaje_fr', JHTML::_('select.genericList', $languaje_fr, 'languaje_fr', 'class="inputbox" '. '', 'value', 'text', $rowlan["fr"]));
    $this->assignRef('lan_fr_wm',$rowlan_wm["fr"]);

   $languaje_cz = array(
			 array('value' => '0', 'text' => 'Disable'),
			 array('value' => '1', 'text' => 'Enable'));

    $this->assign('languaje_cz', JHTML::_('select.genericList', $languaje_cz, 'languaje_cz', 'class="inputbox" '. '', 'value', 'text', $rowlan["cz"]));
    $this->assignRef('lan_cz_wm',$rowlan_wm["cz"]);

   $languaje_se = array(
			 array('value' => '0', 'text' => 'Disable'),
			 array('value' => '1', 'text' => 'Enable'));

    $this->assign('languaje_se', JHTML::_('select.genericList', $languaje_se, 'languaje_se', 'class="inputbox" '. '', 'value', 'text', $rowlan["se"]));
    $this->assignRef('lan_se_wm',$rowlan_wm["se"]);

   $languaje_no = array(
			 array('value' => '0', 'text' => 'Disable'),
			 array('value' => '1', 'text' => 'Enable'));

    $this->assign('languaje_no', JHTML::_('select.genericList', $languaje_no, 'languaje_no', 'class="inputbox" '. '', 'value', 'text', $rowlan["no"]));
    $this->assignRef('lan_no_wm',$rowlan_wm["no"]);


     $languaje_tr = array(
			 array('value' => '0', 'text' => 'Disable'),
			 array('value' => '1', 'text' => 'Enable'));

    $this->assign('languaje_tr', JHTML::_('select.genericList', $languaje_tr, 'languaje_tr', 'class="inputbox" '. '', 'value', 'text', $rowlan["tr"]));
    $this->assignRef('lan_tr_wm',$rowlan_wm["tr"]);


    $languaje_gr = array(
			 array('value' => '0', 'text' => 'Disable'),
			 array('value' => '1', 'text' => 'Enable'));

    $this->assign('languaje_gr', JHTML::_('select.genericList', $languaje_gr, 'languaje_gr', 'class="inputbox" '. '', 'value', 'text', $rowlan["gr"]));
    $this->assignRef('lan_gr_wm',$rowlan_wm["gr"]);


    $languaje_he = array(
			 array('value' => '0', 'text' => 'Disable'),
			 array('value' => '1', 'text' => 'Enable'));

    $this->assign('languaje_he', JHTML::_('select.genericList', $languaje_he, 'languaje_he', 'class="inputbox" '. '', 'value', 'text', $rowlan["he"]));
    $this->assignRef('lan_he_wm',$rowlan_wm["he"]);


    $languaje_fa = array(
			 array('value' => '0', 'text' => 'Disable'),
			 array('value' => '1', 'text' => 'Enable'));

    $this->assign('languaje_fa', JHTML::_('select.genericList', $languaje_fa, 'languaje_fa', 'class="inputbox" '. '', 'value', 'text', $rowlan["fa"]));
    $this->assignRef('lan_fa_wm',$rowlan_wm["fa"]);


    $languaje_sr = array(
			 array('value' => '0', 'text' => 'Disable'),
			 array('value' => '1', 'text' => 'Enable'));

    $this->assign('languaje_sr', JHTML::_('select.genericList', $languaje_sr, 'languaje_sr', 'class="inputbox" '. '', 'value', 'text', $rowlan["sr"]));
    $this->assignRef('lan_sr_wm',$rowlan_wm["sr"]);

    $languaje_ru = array(
			 array('value' => '0', 'text' => 'Disable'),
			 array('value' => '1', 'text' => 'Enable'));

    $this->assign('languaje_ru', JHTML::_('select.genericList', $languaje_ru, 'languaje_ru', 'class="inputbox" '. '', 'value', 'text', $rowlan["ru"]));
    $this->assignRef('lan_ru_wm',$rowlan_wm["ru"]);


      $languaje_hu = array(
			 array('value' => '0', 'text' => 'Disable'),
			 array('value' => '1', 'text' => 'Enable'));

    $this->assign('languaje_hu', JHTML::_('select.genericList', $languaje_hu, 'languaje_hu', 'class="inputbox" '. '', 'value', 'text', $rowlan["hu"]));
    $this->assignRef('lan_hu_wm',$rowlan_wm["hu"]);


      $languaje_zh = array(
			 array('value' => '0', 'text' => 'Disable'),
			 array('value' => '1', 'text' => 'Enable'));

    $this->assign('languaje_zh', JHTML::_('select.genericList', $languaje_zh, 'languaje_zh', 'class="inputbox" '. '', 'value', 'text', $rowlan["zh"]));
    $this->assignRef('lan_zh_wm',$rowlan_wm["zh"]);

      $languaje_ar = array(
			 array('value' => '0', 'text' => 'Disable'),
			 array('value' => '1', 'text' => 'Enable'));

    $this->assign('languaje_ar', JHTML::_('select.genericList', $languaje_ar, 'languaje_ar', 'class="inputbox" '. '', 'value', 'text', $rowlan["ar"]));
    $this->assignRef('lan_ar_wm',$rowlan_wm["ar"]);


    $languaje_nl = array(
			 array('value' => '0', 'text' => 'Disable'),
			 array('value' => '1', 'text' => 'Enable'));

    $this->assign('languaje_nl', JHTML::_('select.genericList', $languaje_nl, 'languaje_nl', 'class="inputbox" '. '', 'value', 'text', $rowlan["nl"]));
    $this->assignRef('lan_nl_wm',$rowlan_wm["nl"]);


    $languaje_fi = array(
			 array('value' => '0', 'text' => 'Disable'),
			 array('value' => '1', 'text' => 'Enable'));

    $this->assign('languaje_fi', JHTML::_('select.genericList', $languaje_fi, 'languaje_fi', 'class="inputbox" '. '', 'value', 'text', $rowlan["fi"]));
    $this->assignRef('lan_fi_wm',$rowlan_wm["fi"]);


    $languaje_dk = array(
			 array('value' => '0', 'text' => 'Disable'),
			 array('value' => '1', 'text' => 'Enable'));

    $this->assign('languaje_dk', JHTML::_('select.genericList', $languaje_dk, 'languaje_dk', 'class="inputbox" '. '', 'value', 'text', $rowlan["dk"]));
    $this->assignRef('lan_dk_wm',$rowlan_wm["dk"]);


    $languaje_cn = array(
			 array('value' => '0', 'text' => 'Disable'),
			 array('value' => '1', 'text' => 'Enable'));

    $this->assign('languaje_cn', JHTML::_('select.genericList', $languaje_cn, 'languaje_cn', 'class="inputbox" '. '', 'value', 'text', $rowlan["cn"]));
    $this->assignRef('lan_cn_wm',$rowlan_wm["cn"]);


    $languaje_pl = array(
			 array('value' => '0', 'text' => 'Disable'),
			 array('value' => '1', 'text' => 'Enable'));

    $this->assign('languaje_pl', JHTML::_('select.genericList', $languaje_pl, 'languaje_pl', 'class="inputbox" '. '', 'value', 'text', $rowlan["pl"]));
    $this->assignRef('lan_pl_wm',$rowlan_wm["pl"]);


    $languaje_bg = array(
			 array('value' => '0', 'text' => 'Disable'),
			 array('value' => '1', 'text' => 'Enable'));

    $this->assign('languaje_bg', JHTML::_('select.genericList', $languaje_bg, 'languaje_bg', 'class="inputbox" '. '', 'value', 'text', $rowlan["bg"]));
    $this->assignRef('lan_bg_wm',$rowlan_wm["bg"]);


    $languaje_sk = array(
			 array('value' => '0', 'text' => 'Disable'),
			 array('value' => '1', 'text' => 'Enable'));

    $this->assign('languaje_sk', JHTML::_('select.genericList', $languaje_sk, 'languaje_sk', 'class="inputbox" '. '', 'value', 'text', $rowlan["sk"]));
    $this->assignRef('lan_sk_wm',$rowlan_wm["sk"]);


    $languaje_cr = array(
			 array('value' => '0', 'text' => 'Disable'),
			 array('value' => '1', 'text' => 'Enable'));

    $this->assign('languaje_cr', JHTML::_('select.genericList', $languaje_cr, 'languaje_cr', 'class="inputbox" '. '', 'value', 'text', $rowlan["cr"]));
    $this->assignRef('lan_cr_wm',$rowlan_wm["cr"]);


    $languaje_id = array(
			 array('value' => '0', 'text' => 'Disable'),
			 array('value' => '1', 'text' => 'Enable'));

    $this->assign('languaje_id', JHTML::_('select.genericList', $languaje_id, 'languaje_id', 'class="inputbox" '. '', 'value', 'text', $rowlan["id"]));
    $this->assignRef('lan_id_wm',$rowlan_wm["id"]);



    $languaje_lt = array(
			 array('value' => '0', 'text' => 'Disable'),
			 array('value' => '1', 'text' => 'Enable'));

    $this->assign('languaje_lt', JHTML::_('select.genericList', $languaje_lt, 'languaje_lt', 'class="inputbox" '. '', 'value', 'text', $rowlan["lt"]));
    $this->assignRef('lan_lt_wm',$rowlan_wm["lt"]);


    $languaje_ro = array(
			 array('value' => '0', 'text' => 'Disable'),
			 array('value' => '1', 'text' => 'Enable'));

    $this->assign('languaje_ro', JHTML::_('select.genericList', $languaje_ro, 'languaje_ro', 'class="inputbox" '. '', 'value', 'text', $rowlan["ro"]));
    $this->assignRef('lan_ro_wm',$rowlan_wm["ro"]);
    

    $languaje_sl = array(
			 array('value' => '0', 'text' => 'Disable'),
			 array('value' => '1', 'text' => 'Enable'));

    $this->assign('languaje_sl', JHTML::_('select.genericList', $languaje_sl, 'languaje_sl', 'class="inputbox" '. '', 'value', 'text', $rowlan["sl"]));
    $this->assignRef('lan_sl_wm',$rowlan_wm["sl"]);
    

    $languaje_et = array(
			 array('value' => '0', 'text' => 'Disable'),
			 array('value' => '1', 'text' => 'Enable'));

    $this->assign('languaje_et', JHTML::_('select.genericList', $languaje_et, 'languaje_et', 'class="inputbox" '. '', 'value', 'text', $rowlan["et"]));
    $this->assignRef('lan_et_wm',$rowlan_wm["et"]);
        
    $languaje_lv = array(
			 array('value' => '0', 'text' => 'Disable'),
			 array('value' => '1', 'text' => 'Enable'));

    $this->assign('languaje_lv', JHTML::_('select.genericList', $languaje_lv, 'languaje_lv', 'class="inputbox" '. '', 'value', 'text', $rowlan["lv"]));
    $this->assignRef('lan_lv_wm',$rowlan_wm["lv"]);
    
    
    $languaje_ge = array(
			 array('value' => '0', 'text' => 'Disable'),
			 array('value' => '1', 'text' => 'Enable'));

    $this->assign('languaje_ge', JHTML::_('select.genericList', $languaje_ge, 'languaje_ge', 'class="inputbox" '. '', 'value', 'text', $rowlan["ge"]));
    $this->assignRef('lan_ge_wm',$rowlan_wm["ge"]);
    
	
	$languaje_jp = array(
			 array('value' => '0', 'text' => 'Disable'),
			 array('value' => '1', 'text' => 'Enable'));

    $this->assign('languaje_jp', JHTML::_('select.genericList', $languaje_jp, 'languaje_jp', 'class="inputbox" '. '', 'value', 'text', $rowlan["jp"]));
    $this->assignRef('lan_jp_wm',$rowlan_wm["jp"]);
    
                

    // Rebranding Options
    
     $disable_copyright = array(
			 array('value' => '0', 'text' => 'Disable'),
			 array('value' => '1', 'text' => 'Enable'));


     $this->assign('disable_copyright', JHTML::_('select.genericList', $disable_copyright, 'disable_copyright', 'class="inputbox" '. '', 'value', 'text', $row["disable_copyright"]));
   
   
       
     $copyright_image = array(
			 array('value' => '0', 'text' => 'Disable'),
			 array('value' => '1', 'text' => 'Enable'));


     $this->assign('copyright_image', JHTML::_('select.genericList', $copyright_image, 'copyright_image', 'class="inputbox" '. '', 'value', 'text', $row["copyright_image"]));
   
   
     $this->assignRef('company_logo',$row["company_logo"]);
     $this->assignRef('company_link',$row["company_link"]);
     $this->assignRef('company_slogan',$row["company_slogan"]);
          

     // analytics Options
     $this->assignRef('analytics_account',$row["analytics_account"]);

     // Set the toolbar
     $this->addToolBar();
           
             
     parent::display($tpl);

	}
    
   	protected function addToolBar() 
	{
             	     
       JToolBarHelper::title(JText::_('COM_ACTIVEHELPER_LIVEHELP_SETTINGS'));                  		
       JToolBarHelper::save('domain.updateSettings',$alt = 'JTOOLBAR_SAVE');
       JToolBarHelper::cancel( 'cancel', $alt = 'JTOOLBAR_CLOSE');
	}    

 
}
