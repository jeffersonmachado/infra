<?php

/**
 * @Product     ActiveHelper LiveHelp component for Joomla
 * @package		domain
 * @subpackage	Model
 * @copyright	(C) 2010- 2017 ActiveHelper Inc.
 * @author		ActiveHelper Inc.
 * @version     5.0
 * @Joomla      3.7     
 * @license   : GNU/GPL http://www.gnu.org/copyleft/gpl.html
*/
// No direct access to this file
defined('_JEXEC') or die('Restricted access');

// import Joomla modelform library
jimport('joomla.application.component.modeladmin');
jimport('joomla.filesystem.file');
jimport('joomla.filesystem.folder');
jimport('joomla.string');
jimport('joomla.log.log');
    	
/**
 * Domain Model
 */
class activehelper_livehelpModelDomain extends JModelAdmin
{
    
    /**
	 * Returns a reference to the a Table object, always creating it.
	 *
	 * @param	type	The table type to instantiate
	 * @param	string	A prefix for the table class name. Optional.
	 * @param	array	Configuration array for model. Optional.
	 * @return	JTable	A database object
	 * @since	1.6
	 */
	public function getTable($type = 'Domain', $prefix = 'Table', $config = array()) 
	{
         
	 	return JTable::getInstance($type, $prefix, $config);
        
	}
	/**
	 * Method to get the record form.
	 *
	 * @param	array	$data		Data for the form.
	 * @param	boolean	$loadData	True if the form is to load its own data (default case), false if not.
	 * @return	mixed	A JForm object on success, false on failure
	 * @since	1.6
	 */
	public function getForm($data = array(), $loadData = true) 
	{
        $app	= JFactory::getApplication();
                                 
		// Get the form.          
		$form = $this->loadForm('com_activehelper_livehelp.domain', 'domain', array('control' => 'jform', 'load_data' => $loadData));
	
	 if (empty($form)) 
		{
			return false;
		}
		return $form;
	}
	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return	mixed	The data for the form.
	 * @since	1.6
	 */
	protected function loadFormData() 
	{
         
	 // Check the session for previously entered form data.
		$data = JFactory::getApplication()->getUserState('com_activehelper_livehelp.domain.data', array());
		if (empty($data)) 
		{
			$data = $this->getItem();
		}
		return $data;
	}
    
 	/**
	 * Method to get the data.
	 *
	 * @return	Record
	 * @since	1.6
	 */   
	function getData()
	{
	    $jinput = JFactory::getApplication()->input;
	    $id    = $jinput->get('cid');
	    	
		$row = $this->getTable('domain');
		$row->load($id[0]);
		return $row;
	}
        
  
	function store($data)
	{	
		// New record
        $isnew =0;
        
        $row =& $this->getTable('domain');

		if (!$row->bind($data)) {
			return false;
		}

	   if ($row->id_domain ==0) {
				$isnew =1;}
               
		if (!$row->check()) {
			return false;
		}

		if (!$row->store()) {
			return false;
		}

       // Create domain base configuration
   		if ($isnew ==1) {
             $this->createSettings ($row->id_domain);
             $this-> add_account_domain('1',$row->id_domain);
             $this-> add_domain_languages($row->id_domain);
            // add images to new domain
			
		   // Log
		  /* JLog::addLogger(array('text_file' => 'livechat.log'));
		   JLog::add("SQL ==>: " . $row->id_domain ); 
           */
		  
             $this-> createSettingBase ($row->id_domain);
         }
                
		return true;
	}	
	  
      
/************************************************************ add new domain options *****************************************************************/


   	function createSettings($domain)
	   {
		
        $app = JFactory::getApplication();   
        $db = JFactory::getDbo();
                            
        $setSQL = "insert into #__livehelp_settings (name,value,id_domain) "." select name,value, ".$domain. " FROM #__livehelp_settings  WHERE id_domain = 0  ORDER BY id";
        $db->setQuery($setSQL);
		
		   // Log
		 /*  JLog::addLogger(array('text_file' => 'livechat.log'));
		     JLog::add("SQL ==>: " . $setSQL ); 
           */
        try {
       	     $db->execute();
			return false;
		    }
       catch (Exception $e)
         {
		    $app->enqueueMessage(JText::_($e->getMessage()), 'error'); 
            return true;	     
             }
	   }
             
             
   	function add_account_domain($id_account,$id_domain)
	{
      $app = JFactory::getApplication(); 
	  $db  = JFactory::getDbo();
      
      $setSQL = "insert into #__livehelp_accounts_domain (id_account, id_domain, status) values (" .
                $id_account . "," . $id_domain . "," . "1" . ")" ;

      $db->setQuery($setSQL);
      
        try {
       	     $db->execute();
			return false;
		    }
       catch (Exception $e)
         {
		    $app->enqueueMessage(JText::_($e->getMessage()), 'error'); 
           return true;	     
             }
    }

 	function add_domain_languages($id_domain)
	{
      $app = JFactory::getApplication();
	  $db = JFactory::getDbo();
      
      $setSQL = "insert into #__livehelp_languages_domain (id_domain, code, name,welcome_message) values (" .
                $id_domain . "," . "'en'" . "," . "'english'" . "," . "'Welcome to our LiveHelp, one moment please.'". ")" ;

      $db->setQuery($setSQL);
      
         try {
       	     $db->execute();
			return false;
		    }
       catch (Exception $e)
         {
		    $app->enqueueMessage(JText::_($e->getMessage()), 'error'); 
           return true;	     
             }
    }
       
       
  function createSettingBase($domain)
      {

         $jinput = JFactory::getApplication();
         $option 	= 'com_activehelper_livehelp';
         
         if(!defined('DIRECTORY_SEPARATOR')) define('DIRECTORY_SEPARATOR', '/');

         $mode = 0755;

       $source_folder = JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . $option . DIRECTORY_SEPARATOR . 'server' . DIRECTORY_SEPARATOR . 'domains' . DIRECTORY_SEPARATOR . '0'. DIRECTORY_SEPARATOR . 'i18n';
       $path = JPATH_SITE . DIRECTORY_SEPARATOR  . 'components' . DIRECTORY_SEPARATOR . $option . DIRECTORY_SEPARATOR . 'server' . DIRECTORY_SEPARATOR . 'domains' . DIRECTORY_SEPARATOR . $domain ;


       JFolder::create($path, $mode);

        if (!JFolder::copy($source_folder, $path . DIRECTORY_SEPARATOR . 'i18n')) {
           $msg = "Failed to create a base configuration";
           $jinput->enqueueMessage(JText::_('500'), $msg);
             }

       return $msg;
	   }
       
 /************************************************************************* Domain Settings *******************************************************************************************/
 
 function updateDomainSettings(){

 // bind settings
     
   $jinput = JFactory::getApplication()->input;

 
   $id_domain             = $jinput->getInt('id_domain');              			
   $livehelp_name         = $jinput->getString('livehelp_name');
   $site_name             = $jinput->getString('site_name');
   $site_address          = $jinput->getString('site_address');
   $departments           = $jinput->getInt('departments');
   $background_color      = $jinput->getString('background_color');
   $chat_font_type        = $jinput->getString('chat_font_type');
   $guest_chat_font_size  = $jinput->getString('guest_chat_font_size');
   $admin_chat_font_size  = $jinput->getString('admin_chat_font_size');
   $disable_popup_help    = $jinput->getInt('disable_popup_help');
   $campaign_link         = $jinput->getString('campaign_link');
   $disable_agent_bannner = $jinput->getInt('disable_agent_bannner'); 
   $font_type             = $jinput->getString('font_type');
   $font_size             = $jinput->getString('font_size');
   $font_color            = $jinput->getString('font_color');
   $font_link_color       = $jinput->getString('font_link_color');
   $sent_font_color       = $jinput->getString('sent_font_color');
   $received_font_color   = $jinput->getString('received_font_color');
   $disable_login_details = $jinput->getInt('disable_login_details'); 
   $disable_chat_username = $jinput->getInt('disable_chat_username');   
   $require_guest_details = $jinput->getInt('require_guest_details');   
   $disable_language      = $jinput->getInt('disable_language');   
   $disable_offline_email = $jinput->getInt('disable_offline_email');
   $custom_offline_form   = $jinput->getString('custom_offline_form');
   $log_offline_email     = $jinput->getString('log_offline_email');
   
   $from_email            = $jinput->getString('from_email');
   $offline_email         = $jinput->getString('offline_email');
   $chat_background_img   = $jinput->getString('chat_background_img');


   $languajes             = $jinput->getInt('languajes');

   $configure_smtp        = $jinput->getInt('configure_smtp');
   $smtp_server           = $jinput->getString('smtp_server');
   $smtp_port             = $jinput->getString('smtp_port');
   
   $disable_copyright     = $jinput->getInt('disable_copyright');
   $company_link          = $jinput->getString('company_link'); 
   $company_slogan        = $jinput->getString('company_slogan'); 
   $copyright_image       = $jinput->getString('copyright_image');

   # languajes
   $languaje_en           = $jinput->getInt('languaje_en');
   $languaje_sp           = $jinput->getInt('languaje_sp');
   $languaje_de           = $jinput->getInt('languaje_de');
   $languaje_pt           = $jinput->getInt('languaje_pt');
   $languaje_it           = $jinput->getInt('languaje_it');
   $languaje_fr           = $jinput->getInt('languaje_fr');
   $languaje_cz           = $jinput->getInt('languaje_cz');
   $languaje_se           = $jinput->getInt('languaje_se');
   $languaje_no           = $jinput->getInt('languaje_no');
   $languaje_tr           = $jinput->getInt('languaje_tr');
   $languaje_gr           = $jinput->getInt('languaje_gr');
   $languaje_he           = $jinput->getInt('languaje_he');
   $languaje_fa           = $jinput->getInt('languaje_fa');
   $languaje_sr           = $jinput->getInt('languaje_sr');
   $languaje_ru           = $jinput->getInt('languaje_ru');
   $languaje_hu           = $jinput->getInt('languaje_hu');
   $languaje_zh           = $jinput->getInt('languaje_zh');
   $languaje_cn           = $jinput->getInt('languaje_cn');
   $languaje_ar           = $jinput->getInt('languaje_ar');
   $languaje_nl           = $jinput->getInt('languaje_nl');
   $languaje_fi           = $jinput->getInt('languaje_fi');
   $languaje_dk           = $jinput->getInt('languaje_dk');
   $languaje_pl           = $jinput->getInt('languaje_pl');
   $languaje_bg           = $jinput->getInt('languaje_bg');
   $languaje_cr           = $jinput->getInt('languaje_cr');
   $languaje_id           = $jinput->getInt('languaje_id');
   $languaje_lt           = $jinput->getInt('languaje_lt');
   $languaje_ro           = $jinput->getInt('languaje_ro');
   $languaje_sl           = $jinput->getInt('languaje_sl');
   $languaje_et           = $jinput->getInt('languaje_et');
   $languaje_lv           = $jinput->getInt('languaje_lv');
   $languaje_ge           = $jinput->getInt('languaje_ge');
   $languaje_jp           = $jinput->getInt('languaje_jp');

   $languaje_en_wm        = $jinput->getString('lan_en_wm');
   $languaje_sp_wm        = $jinput->getString('lan_sp_wm');
   $languaje_de_wm        = $jinput->getString('lan_de_wm');
   $languaje_pt_wm        = $jinput->getString('lan_pt_wm');
   $languaje_it_wm        = $jinput->getString('lan_it_wm');
   $languaje_fr_wm        = $jinput->getString('lan_fr_wm');
   $languaje_cz_wm        = $jinput->getString('lan_cz_wm');
   $languaje_se_wm        = $jinput->getString('lan_se_wm');
   $languaje_no_wm        = $jinput->getString('lan_no_wm');
   $languaje_tr_wm        = $jinput->getString('lan_tr_wm');
   $languaje_gr_wm        = $jinput->getString('lan_gr_wm');
   $languaje_he_wm        = $jinput->getString('lan_he_wm');
   $languaje_fa_wm        = $jinput->getString('lan_fa_wm');
   $languaje_sr_wm        = $jinput->getString('lan_sr_wm');
   $languaje_ru_wm        = $jinput->getString('lan_ru_wm');
   $languaje_hu_wm        = $jinput->getString('lan_hu_wm');
   $languaje_zh_wm        = $jinput->getString('lan_zh_wm');
   $languaje_cn_wm        = $jinput->getString('lan_cn_wm');
   $languaje_ar_wm        = $jinput->getString('lan_ar_wm');
   $languaje_nl_wm        = $jinput->getString('lan_nl_wm');
   $languaje_fi_wm        = $jinput->getString('lan_fi_wm');
   $languaje_dk_wm        = $jinput->getString('lan_dk_wm');
   $languaje_pl_wm        = $jinput->getString('lan_pl_wm');
   $languaje_bg_wm        = $jinput->getString('lan_bg_wm');
   $languaje_cr_wm        = $jinput->getString('lan_cr_wm');
   $languaje_id_wm        = $jinput->getString('lan_id_wm');
   $languaje_lt_wm        = $jinput->getString('lan_lt_wm');
   $languaje_ro_wm        = $jinput->getString('lan_ro_wm');
   $languaje_sl_wm        = $jinput->getString('lan_sl_wm');
   $languaje_et_wm        = $jinput->getString('lan_et_wm');
   $languaje_lv_wm        = $jinput->getString('lan_lv_wm');
   $languaje_ge_wm        = $jinput->getString('lan_ge_wm');
   $languaje_jp_wm        = $jinput->getString('lan_jp_wm');
   
   $chat_invitation_auto_refresh = $jinput->getInt('chat_invitation_auto_refresh');
   $analytics_account            = $jinput->getString('analytics_account');
   $disable_invitation           = $jinput->getInt('disable_invitation');    
   $disable_geolocation          = $jinput->getInt('disable_geolocation');
   $disable_status_indicator     = $jinput->getInt('disable_status_indicator'); 
   $captcha                      = $jinput->getInt('captcha'); 
   $phone                        = $jinput->getInt('phone');
   $company                      = $jinput->getInt('company');




   // seting update
   $this-> updateSetting($id_domain,"'livehelp_name'",$livehelp_name);
   $this-> updateSetting($id_domain,"'site_name'",$site_name);
   $this-> updateSetting($id_domain,"'site_address'",$site_address);
   $this-> updateSetting($id_domain,"'departments'",$departments );
   $this-> updateSetting($id_domain,"'background_color'",$background_color);
   $this-> updateSetting($id_domain,"'chat_font_type'",$chat_font_type);
   $this-> updateSetting($id_domain,"'guest_chat_font_size'",$guest_chat_font_size);
   $this-> updateSetting($id_domain,"'admin_chat_font_size'",$admin_chat_font_size);
   $this-> updateSetting($id_domain,"'disable_popup_help'",$disable_popup_help);
   $this-> updateSetting($id_domain,"'chat_background_img'",$chat_background_img);
   $this-> updateSetting($id_domain,"'campaign_link'",$campaign_link);
   $this-> updateSetting($id_domain,"'disable_agent_bannner'",$disable_agent_bannner);  
   $this-> updateSetting($id_domain,"'font_type'",$font_type);
   $this-> updateSetting($id_domain,"'font_size'",$font_size);
   $this-> updateSetting($id_domain,"'font_color'",$font_color);
   $this-> updateSetting($id_domain,"'font_link_color'",$font_link_color);
   $this-> updateSetting($id_domain,"'sent_font_color'",$sent_font_color);
   $this-> updateSetting($id_domain,"'received_font_color'",$received_font_color);
   $this-> updateSetting($id_domain,"'disable_login_details'",$disable_login_details);
   $this-> updateSetting($id_domain,"'disable_chat_username'",$disable_chat_username);
   $this-> updateSetting($id_domain,"'require_guest_details'",$require_guest_details);
   $this-> updateSetting($id_domain,"'disable_language'",$disable_language);   
   $this-> updateSetting($id_domain,"'disable_offline_email'",$disable_offline_email);
   $this-> updateSetting($id_domain,"'from_email'",$from_email);
   $this-> updateSetting($id_domain,"'offline_email'",$offline_email);   
   $this-> updateSetting($id_domain,"'custom_offline_form_link'",$custom_offline_form);
   $this-> updateSetting($id_domain,"'log_offline_email'",$log_offline_email); 
   $this-> updateSetting($id_domain,"'invitation_refresh'",$chat_invitation_auto_refresh); 
   $this-> updateSetting($id_domain,"'disable_invitation'",$disable_invitation);
   $this-> updateSetting($id_domain,"'disable_geolocation'",$disable_geolocation);
   $this-> updateSetting($id_domain,"'disable_tracking_offline'",$disable_status_indicator);    
   $this-> updateSetting($id_domain,"'captcha'",$captcha); 
   $this-> updateSetting($id_domain,"'phone'",$phone); 
   $this-> updateSetting($id_domain,"'company'",$company);
 
   $this-> updateSetting($id_domain,"'analytics_account'",$analytics_account);
   
 
   $this-> updateSetting($id_domain,"'configure_smtp'",$configure_smtp);
   $this-> updateSetting($id_domain,"'smtp_server'",$smtp_server);
   $this-> updateSetting($id_domain,"'smtp_port'",$smtp_port);    
    
    
   $msg = $this-> uploadfile('online',$option,$id_domain,$languajes);  	
   $msg = $this-> uploadfile('offline',$option,$id_domain,$languajes );
   $msg = $this-> uploadfile('away',$option,$id_domain,$languajes );
   $msg = $this-> uploadfile('brb',$option,$id_domain,$languajes );

   // upload custom chat options

   $msg = $this-> uploadChatfile($option,$id_domain,$languajes);     
   $msg = $this-> uploadChatInvitationfile($option,$id_domain,$languajes);
   $msg = $this-> uploadChatSendButtonfile($option,$id_domain,$languajes);
   $msg = $this-> uploadChatSendHoverButtonfile($option,$id_domain,$languajes);

   // rebranding
    $this-> updateSetting($id_domain,"'disable_copyright'",$disable_copyright);   
    $this-> updateSetting($id_domain,"'company_link'",$company_link); 
    $this-> updateSetting($id_domain,"'company_slogan'",$company_slogan);
    $this-> updateSetting($id_domain,"'copyright_image'",$copyright_image);             
    $msg = $this-> uploadLogofile($option,$id_domain,$languajes); 
   
   # languajes
   $this->updateLanguaje($id_domain,'en',$languaje_en,'english');
   $this->updateLanguaje($id_domain,'sp',$languaje_sp,'spanish');
   $this->updateLanguaje($id_domain,'de',$languaje_de,'deutsch');
   $this->updateLanguaje($id_domain,'pt',$languaje_pt,'portuguese');
   $this->updateLanguaje($id_domain,'it',$languaje_it,'italian');
   $this->updateLanguaje($id_domain,'fr',$languaje_fr,'french');
   $this->updateLanguaje($id_domain,'cz',$languaje_cz,'czech');
   $this->updateLanguaje($id_domain,'se',$languaje_se,'swedish');
   $this->updateLanguaje($id_domain,'no',$languaje_no,'norwegian');
   $this->updateLanguaje($id_domain,'tr',$languaje_tr,'turkey');
   $this->updateLanguaje($id_domain,'gr',$languaje_gr,'greek');
   $this->updateLanguaje($id_domain,'he',$languaje_he,'hebrew');
   $this->updateLanguaje($id_domain,'fa',$languaje_fa,'farsi');
   $this->updateLanguaje($id_domain,'sr',$languaje_sr,'Serbian');
   $this->updateLanguaje($id_domain,'ru',$languaje_ru,'Russian');
   $this->updateLanguaje($id_domain,'hu',$languaje_hu,'Hungarian');
   $this->updateLanguaje($id_domain,'zh',$languaje_zh,'Traditional Chinese');
   $this->updateLanguaje($id_domain,'cn',$languaje_cn,'Simplified Chinese');
   $this->updateLanguaje($id_domain,'ar',$languaje_ar,'Arab');
   $this->updateLanguaje($id_domain,'nl',$languaje_nl,'Dutch');
   $this->updateLanguaje($id_domain,'fi',$languaje_fi,'Finnish');
   $this->updateLanguaje($id_domain,'dk',$languaje_dk,'Danish');
   $this->updateLanguaje($id_domain,'pl',$languaje_pl,'Polish');
   $this->updateLanguaje($id_domain,'bg',$languaje_bg,'Bulgarian');
   $this->updateLanguaje($id_domain,'cr',$languaje_cr,'Croatian');
   $this->updateLanguaje($id_domain,'id',$languaje_id,'Indonesian');
   $this->updateLanguaje($id_domain,'lt',$languaje_lt,'Lithuanian');
   $this->updateLanguaje($id_domain,'ro',$languaje_ro,'Romanian');
   $this->updateLanguaje($id_domain,'sl',$languaje_sl,'Slovenian');
   $this->updateLanguaje($id_domain,'et',$languaje_et,'Estonian');
   $this->updateLanguaje($id_domain,'lv',$languaje_lv,'Latvian');
   $this->updateLanguaje($id_domain,'ge',$languaje_ge,'Georgian');
   $this->updateLanguaje($id_domain,'jp',$languaje_jp,'Japanese');

  if (!$languaje_en_wm ==''){
    $this->updateLanguaje_wm($id_domain,'en' , $languaje_en_wm);}

   if (!$languaje_sp_wm ==''){
    $this->updateLanguaje_wm($id_domain,'sp' , $languaje_sp_wm);}

   if (!$languaje_de_wm ==''){
     $this->updateLanguaje_wm($id_domain,'de' , $languaje_de_wm);}

   if (!$languaje_pt_wm ==''){
     $this->updateLanguaje_wm($id_domain,'pt' , $languaje_pt_wm);}

   if (!$languaje_it_wm ==''){
     $this->updateLanguaje_wm($id_domain,'it' , $languaje_it_wm);}

   if (!$languaje_fr_wm ==''){
     $this->updateLanguaje_wm($id_domain,'fr' , $languaje_fr_wm);}

   if (!$languaje_cz_wm ==''){
     $this->updateLanguaje_wm($id_domain,'cz' , $languaje_cz_wm);}

   if (!$languaje_se_wm ==''){
     $this->updateLanguaje_wm($id_domain,'se' , $languaje_se_wm);}

   if (!$languaje_no_wm ==''){
     $this->updateLanguaje_wm($id_domain,'no' , $languaje_no_wm);}

   if (!$languaje_tr_wm ==''){
     $this->updateLanguaje_wm($id_domain,'tr' , $languaje_tr_wm);}

   if (!$languaje_gr_wm ==''){
     $this->updateLanguaje_wm($id_domain,'gr' , $languaje_gr_wm);}

   if (!$languaje_he_wm ==''){
     $this->updateLanguaje_wm($id_domain,'he' , $languaje_he_wm);}

   if (!$languaje_fa_wm ==''){
     $this->updateLanguaje_wm($id_domain,'fa' , $languaje_fa_wm);}

   if (!$languaje_sr_wm ==''){
     $this->updateLanguaje_wm($id_domain,'sr' , $languaje_sr_wm);}

   if (!$languaje_ru_wm ==''){
     $this->updateLanguaje_wm($id_domain,'ru' , $languaje_ru_wm);}

   if (!$languaje_hu_wm ==''){
     $this->updateLanguaje_wm($id_domain,'hu' , $languaje_hu_wm);}

   if (!$languaje_zh_wm ==''){
     $this->updateLanguaje_wm($id_domain,'zh' , $languaje_zh_wm);}
     
   if (!$languaje_cn_wm ==''){
     $this->updateLanguaje_wm($id_domain,'cn' , $languaje_cn_wm);}     

   if (!$languaje_ar_wm ==''){
     $this->updateLanguaje_wm($id_domain,'ar' , $languaje_ar_wm);}
     
   if (!$languaje_nl_wm ==''){
     $this->updateLanguaje_wm($id_domain,'nl' , $languaje_nl_wm);}

   if (!$languaje_fi_wm ==''){
     $this->updateLanguaje_wm($id_domain,'fi' , $languaje_fi_wm);}
     
   if (!$languaje_dk_wm ==''){
     $this->updateLanguaje_wm($id_domain,'dk' , $languaje_dk_wm);}
     
   if (!$languaje_pl_wm ==''){
     $this->updateLanguaje_wm($id_domain,'pl' , $languaje_pl_wm);}    
     
   if (!$languaje_bg_wm ==''){
     $this->updateLanguaje_wm($id_domain,'bg' , $languaje_bg_wm);}         
     
   if (!$languaje_cr_wm ==''){
     $this->updateLanguaje_wm($id_domain,'cr' , $languaje_cr_wm);}    
     
   if (!$languaje_id_wm ==''){
     $this->updateLanguaje_wm($id_domain,'id' , $languaje_id_wm);}   
     
   if (!$languaje_lt_wm ==''){
     $this->updateLanguaje_wm($id_domain,'lt' , $languaje_lt_wm);}      
     
   if (!$languaje_ro_wm ==''){
     $this->updateLanguaje_wm($id_domain,'ro' , $languaje_ro_wm);}   
     
 if (!$languaje_sl_wm ==''){
     $this->updateLanguaje_wm($id_domain,'sl' , $languaje_sl_wm);}          
            
 if (!$languaje_et_wm ==''){
     $this->updateLanguaje_wm($id_domain,'et' , $languaje_et_wm);}   
     
 if (!$languaje_lv_wm ==''){
     $this->updateLanguaje_wm($id_domain,'lv' , $languaje_lv_wm);}    
     
 if (!$languaje_ge_wm ==''){
     $this->updateLanguaje_wm($id_domain,'ge' , $languaje_ge_wm);}         

 if (!$languaje_jp_wm ==''){
     $this->updateLanguaje_wm($id_domain,'jp' , $languaje_jp_wm);}      	 
                    

   return true;

  }
  

   function updateSetting($domain,$varname,$varvalue)
    {
        $app    = JFactory::getApplication();
		$db     = JFactory::getDbo();        
        $query  = $db->getQuery(true);
		
		  /* // Log
		     JLog::addLogger(array('text_file' => 'livechat.log'));
		     JLog::add("varname ==>: " . $varname ); 
			 JLog::add("value ==>: " . $varvalue ); 
           */
		   
	      	
		$query->update($db->quoteName('#__livehelp_settings'))   
		      ->set($db->quoteName('value') . "=" . "'". $varvalue ."'" )
              ->where(array($db->quoteName('id_domain') . "=". "'". $domain ."'" , $db->quoteName('name') ."=" . $varname));
          
	  //  JLog::add("SQL ==>: " . $query );
	 
	   $db->setQuery($query);	  	  
      		
       	try {
       	     $db->execute();
			return false;
		    }
       catch (Exception $e)
         {
		    $app->enqueueMessage(JText::_($e->getMessage()), 'error'); 
           return true;	     
             }
        
	   }

  function uploadfile($file_name,$option,$id_domain,$languajes)
  {
     $jinput = JFactory::getApplication()->input;	 
	    
     if(!defined('DIRECTORY_SEPARATOR')) define('DIRECTORY_SEPARATOR', '/');
	 

	
     if ( $file_name =='online'){
         $file = $jinput->files->get('online');}
		 
     if ( $file_name =='offline'){
       $file  = $jinput->files->get('offline');}

     if ( $file_name =='away'){
       $file  = $jinput->files->get('away');}

      if ( $file_name =='brb'){
       $file  = $jinput->files->get('brb');}

		if(isset($file['name']) && $file['name'] != '')
		{		
			$ext = JFile::getExt($file['name']);
           $filename = JPATH_SITE . DIRECTORY_SEPARATOR . 'components\com_activehelper_livehelp\server\domains' . DIRECTORY_SEPARATOR . $id_domain . '\i18n\\' .$languajes .'\pictures\\'. $file_name . '.' . $ext;

      if (!JFile::upload($file['tmp_name'],$filename)) {
				$msg = "Upload failed, check to make sure that" . $filename . " exists and is writable.";
        return $msg;
        }
			}

   }  
  
function uploadChatfile($option,$id_domain,$languajes)
  {  
       
       $jinput = JFactory::getApplication()->input;
    
       if(!defined('DIRECTORY_SEPARATOR')) define('DIRECTORY_SEPARATOR', '/');
 
			
		$file = $jinput->files->get('campaign_image'); 		         
        $file_name ='chat_banner';
       
		if(isset($file['name']) && $file['name'] != '')
		{
			$ext = JFile::getExt($file['name']);                       
            $filename = JPATH_SITE . DIRECTORY_SEPARATOR . 'components\com_activehelper_livehelp\server\domains' . DIRECTORY_SEPARATOR . $id_domain . '\i18n\\' .$languajes .'\pictures\\'. $file_name . '.' . $ext; 

      if (!JFile::upload($file['tmp_name'],$filename)) {
				$msg = "Upload failed, check to make sure that" . $filename . " exists and is writable.";
        return $msg;
        }

      $this-> updateSetting($id_domain,"'campaign_image'", $file_name . '.' . $ext);

			}

   }

function uploadChatInvitationfile($option,$id_domain,$languajes)
  {
    
       $jinput = JFactory::getApplication()->input;
    
       if(!defined('DIRECTORY_SEPARATOR')) define('DIRECTORY_SEPARATOR', '/');

       $file = $jinput->files->get('chat_invitation_img'); 			   
       $file_name ='initiate_dialog';

		if(isset($file['name']) && $file['name'] != '')
		{
			$ext = JFile::getExt($file['name']);      
            $filename = JPATH_SITE . DIRECTORY_SEPARATOR . 'components\com_activehelper_livehelp\server\domains' . DIRECTORY_SEPARATOR . $id_domain . '\i18n\\' .$languajes .'\pictures\\'. $file_name . '.' . $ext;

      if (!JFile::upload($file['tmp_name'],$filename)) {
				$msg = "Upload failed, check to make sure that" . $filename . " exists and is writable.";
        return $msg;
        }

      $this-> updateSetting($id_domain,"'chat_invitation_img'", $file_name . '.' . $ext);

			}

   }

function uploadChatSendButtonfile($option,$id_domain,$languajes)
  {    
       $jinput = JFactory::getApplication()->input;          
       $file = $jinput->files->get('chat_button_img'); 	
       $file_name ='send';
	   
		if(isset($file['name']) && $file['name'] != '')
		 {
		    $ext = JFile::getExt($file['name']);        
			
		    if(!defined('DIRECTORY_SEPARATOR')) define('DIRECTORY_SEPARATOR', '/');
		  
		  $filename = JPATH_SITE . DIRECTORY_SEPARATOR . 'components\com_activehelper_livehelp\server\domains' . DIRECTORY_SEPARATOR . $id_domain . '\i18n\\' .$languajes .'\pictures\\'. $file_name . '.' . $ext;
    		  
      if (!JFile::upload($file['tmp_name'],$filename)) {
				$msg = "Upload failed, check to make sure that" . $filename . " exists and is writable.";
        return $msg;
        }

      $this-> updateSetting($id_domain,"'chat_button_img'", $file_name . '.' . $ext);

			}
   }

function uploadChatSendHoverButtonfile($option,$id_domain,$languajes)
  {
       $jinput = JFactory::getApplication()->input;          
       $file = $jinput->files->get('chat_button_hover_img'); 
       $file_name ='send_hover';
	   	             		         
		if(isset($file['name']) && $file['name'] != '')
		{
			$ext = JFile::getExt($file['name']);      
						
			if(!defined('DIRECTORY_SEPARATOR')) define('DIRECTORY_SEPARATOR', '/');
              $filename = JPATH_SITE . DIRECTORY_SEPARATOR . 'components\com_activehelper_livehelp\server\domains' . DIRECTORY_SEPARATOR . $id_domain . '\i18n\\' .$languajes .'\pictures\\'. $file_name . '.' . $ext;
              		
			
      if (!JFile::upload($file['tmp_name'],$filename)) {
				$msg = "Upload failed, check to make sure that" . $filename . " exists and is writable.";				
        return $msg;
        }

      $this-> updateSetting($id_domain,"'chat_button_hover_img'", $file_name . '.' . $ext);

			}

   }
   
   
 function uploadLogofile($option,$id_domain,$languajes)
  {
  
    
        $jinput = JFactory::getApplication()->input;
    
       if(!defined('DIRECTORY_SEPARATOR')) define('DIRECTORY_SEPARATOR', '/');

	   $file = $jinput->files->get('company_logo'); 
       $file_name ='logo';
       
		if(isset($file['name']) && $file['name'] != '')
		{
			$ext = JFile::getExt($file['name']);         
           $filename = JPATH_SITE . DIRECTORY_SEPARATOR . 'components\com_activehelper_livehelp\server\domains' . DIRECTORY_SEPARATOR . $id_domain . '\i18n\\'  .$languajes . '\pictures\\' . $file_name . '.' . $ext;

      if (!JFile::upload($file['tmp_name'],$filename)) {
				$msg = "Upload failed, check to make sure that" . $filename . " exists and is writable.";
        return $msg;
        }

      $this-> updateSetting($id_domain,"'company_logo'", $file_name . '.' . $ext);

			}

   }  
   
   function updateLanguaje($id_domain,$langname,$langvalue,$languaje)
   {
       $lanoption = $this->languajeStatus($id_domain,$langname);
       
	   $app    = JFactory::getApplication();	  
	   $db     = JFactory::getDbo(); 
       
       $setSQL ='';
         
       if ($lanoption == 1 && $langvalue ==0) {
         $setSQL ="delete from #__livehelp_languages_domain where id_domain = ".$id_domain .
                  " and code =". "'" .$langname ."'"; };

       if ($lanoption == 0 && $langvalue ==1) {
        $setSQL ="insert into #__livehelp_languages_domain (id_domain, code, name) values (" . $id_domain.
         ",".  "'". $langname . "'" . ","  . "'". $languaje . "'" . ")";
         };
         				
		 
         if ($setSQL !='') {  
            
           $db->setQuery($setSQL);
           
      
      try {
       	     $db->execute();
			return false;
		    }
       catch (Exception $e)
         {
		    $app->enqueueMessage(JText::_($e->getMessage()), 'error'); 
           return true;	     
             }
       }   
   }
  
  function languajeStatus($domain,$langname)
    {
       $app    = JFactory::getApplication();
	   $db     = JFactory::getDbo(); 
       $query  = $db->getQuery(true);
                    
        $query->select('count(*) enabled ')
        ->from('#__livehelp_languages_domain')
        ->where("id_domain =" . $domain)      
        ->where("code =" . "'". $langname . "'" );
                     
      
        $db->setQuery($query);    
		
       	if (!$db->execute()) {			
			return false;
		  } 

        return $db->loadResult();
 }
  

   function updateLanguaje_wm($id_domain,$langname,$wm)
    {
       $app = JFactory::getApplication();
	   $db  = JFactory::getDbo(); 
        
        $setSQL ="update #__livehelp_languages_domain set `welcome_message` =".  "'". $wm . "'"  .
         "where `id_domain` = ". $id_domain . " and  `code` = "  . "'". $langname . "'";

        $db->setQuery($setSQL);
		

       	 	try {
       	     $db->execute();
			return false;
		    }
       catch (Exception $e)
         {
		    $app->enqueueMessage(JText::_($e->getMessage()), 'error'); 
           return true;	     
             }
	   }
  
/************************************************************************************* Server Settings ****************************************************************************************/

 function updateServerSettings()
	{
       
	  $jinput = JFactory::getApplication()->input;
	   
      $connection_timeout  = $jinput->get('connection_timeout');
      $keep_alive_timeout  = $jinput->get('keep_alive_timeout');
      $guest_login_timeout = $jinput->get('guest_login_timeout');
      $chat_refresh_rate   = $jinput->get('chat_refresh_rate');
      $sound_alert         = $jinput->get('sound_alert');
      $img_type            = $jinput->get('img_type');
      $sound_alert_pop     = $jinput->get('sound_alert_pop');
      $inv_position        = $jinput->get('inv_position');
	  $connection_timeout_mobile = 43200;
      
      
       if ($img_type == 0)
          $img_type="gif"; 
        else     
      if ($img_type == 1)
          $img_type="png";
        else
      if ($img_type == 2)
          $img_type="jpg";
        else  
      if ($img_type == 3)
          $img_type="jpeg";
        else  
      if ($img_type == 4)
          $img_type="bmp"; 
          
      if ($inv_position == 0)
          $inv_position="right"; 
        else     
      if ($inv_position == 1)
          $inv_position="center";
        else
      if ($inv_position == 2)
          $inv_position="left";
      
     return $this->save_settings($connection_timeout,$keep_alive_timeout,$guest_login_timeout,$chat_refresh_rate,$sound_alert,$img_type,$sound_alert_pop,$inv_position,$connection_timeout_mobile);
      
	}
    
  function save_settings($connection_timeout,$keep_alive_timeout,$guest_login_timeout,$chat_refresh_rate,$sound_alert,$img_type,$sound_alert_pop,$inv_position,$connection_timeout_mobile)
	{
      
        if(!defined('DIRECTORY_SEPARATOR')) define('DIRECTORY_SEPARATOR', '/');
            
      $CR = "\r\n\r\n";
      $path_file = JPATH_ROOT . DIRECTORY_SEPARATOR . 'components'. DIRECTORY_SEPARATOR . 'com_activehelper_livehelp' . DIRECTORY_SEPARATOR . 'server' . DIRECTORY_SEPARATOR . 'import' . DIRECTORY_SEPARATOR . 'constants.php';

     $script = "<?php ".$CR.
               " ".$CR.
               "if (!defined('__CONSTANTS_INC')) {  ".$CR.
               "define('__CONSTANTS_INC', 1);  ".$CR.
               " ".$CR.
               "include_once('jlhconst.php');  ".$CR.
               " ".$CR.
               '$eserverHostname = J_HOST;  '.$CR.
               '$eserverName = "server";'.$CR.
               '$domainSettings =J_DOMAIN_SET_PATH;  '.$CR.
               '$server_directory =J_DIR_PATH;  '.$CR.
               '$ssl =J_CONF_SSL;  '.$CR.
               " ".$CR.
               '$install_directory = $server_directory."/".$eserverName;'.$CR.
               " ".$CR.
               '$push_api_path = "http://s99.velaio.com/pushapi/";'.$CR.
               " ".$CR.
               "// Set advanced settings, ie. timers  ".$CR.
               " ".$CR.
               '$connection_timeout = ' . $connection_timeout . ';'.$CR.
			   '$connection_timeout_mobile  = ' . $connection_timeout_mobile . ';'.$CR.
               '$keep_alive_timeout = ' . $keep_alive_timeout . ';'.$CR.
               '$guest_login_timeout= ' . $guest_login_timeout. ';'.$CR.
               '$chat_refresh_rate = '  . $chat_refresh_rate .  ';'.$CR.
               '$user_panel_refresh_rate = 10;'.$CR.
               '$sound_alert_new_message = '  . $sound_alert .  ';'.$CR.
               '$status_indicator_img_type = '  . '"'.  $img_type .'"' .  ';'.$CR.
               '$sound_alert_new_pro_msg = '  . $sound_alert_pop .  ';'.$CR.
               '$invitation_position = '  . '"'.  $inv_position .'"' .  ';'.$CR.
               " ".$CR.
               '} /* __CONSTANTS_INC */'.$CR.
               " ".$CR.
               '?>';
               
               
      JFile::write ($path_file,$script);

             
      return true;
    }  
    
    
 function resetSettings()
	{
     
      # Make default settings : 
      $connection_timeout  = 60;	  
      $keep_alive_timeout  = 30;
      $guest_login_timeout = 60;
      $chat_refresh_rate   = 6;
      $sound_alert         = 1;
      $img_type            ="gif";
      $sound_alert_pop     =1;
      $inv_position        ="right";
	  $connection_timeout_mobile =43200; 
               
      
      $this->save_settings($connection_timeout,$keep_alive_timeout,$guest_login_timeout,$chat_refresh_rate,$sound_alert,$img_type,$sound_alert_pop,$inv_position,$connection_timeout_mobile);
     
     $this->rebuildsettingsfile();
      
	 return true;
     
    }    
        
 function rebuildsettingsfile()
  {
    
       $CR = "\r\n\r\n";

        if(!defined('DIRECTORY_SEPARATOR')) define('DIRECTORY_SEPARATOR', '/');
            
   $uri =& JURI::getInstance();
   // echo

    $domain_path = JPATH_ROOT . DIRECTORY_SEPARATOR . 'components' .DIRECTORY_SEPARATOR .'com_activehelper_livehelp'. DIRECTORY_SEPARATOR .'server' . DIRECTORY_SEPARATOR .'domains';
    $j_path      =  JPATH_ROOT;

    if ($uri->isSSL() == true)
     {$protocole = 'https://';
      $ssl =1; }
    else
      {$protocole = 'http://';
       $ssl =0; }

      
      $host        =  $uri->getHost();
      $j_dir       = JURI::root(true) . '/' . 'components/com_activehelper_livehelp';
      
      $path_conf = JPATH_SITE . DIRECTORY_SEPARATOR . 'components' .DIRECTORY_SEPARATOR. 'com_activehelper_livehelp'.DIRECTORY_SEPARATOR. 'server'.DIRECTORY_SEPARATOR. 'import' .DIRECTORY_SEPARATOR. 'jlhconst.php' ;
    
          $db_file =  "<?php ".$CR.
		
		     ' $protocol = isset($_SERVER['. "'" .HTTPS. "'".']) ? '.  "'" .https. "'".' : '. "'" .http. "'".';'.$CR.  
             ' $protocol = '. "'" .http. "'".';'.$CR. 			             
             ' $ssl = 0;'.$CR.
             ' '.$CR.
             '  if ( isset( $_SERVER['. "'" .HTTPS. "'".'] ) && strtolower( $_SERVER['. "'" .HTTPS. "'".'] ) == '. "'" .on."'".' ) { '.$CR.
             '     $protocol = '."'" .https."'".';'.$CR.
             '     $ssl = 1;'.$CR.
             '      }'.$CR.
             ' '.$CR.
             ' '.$CR.
             ' '.$CR.
             ' define("J_HOST",' . '$protocol'. '.' . "'://'" . '.' . "'" . $host . "');".$CR.
             ' define("J_DOMAIN_SET_PATH",' . "'" . $domain_path . "');".$CR.
             ' define("J_DIR_PATH",' . "'" . $j_dir . "');".$CR.
             ' define("J_CONF_PATH",' . "'" . $j_path . "');".$CR.
             ' define("J_CONF_SSL",'. '$ssl' .  ");".$CR.
             ' '.$CR.
             ' '.$CR.
             "?>" ;
                     
                     
    JFile::write ($path_conf,$db_file);  
    
    
 } 
    
    
/******************************************************************************** Country Restriction ***********************************************************************************/

   function save_restriction()
    {
        $app    = JFactory::getApplication();
		$jinput = JFactory::getApplication()->input;		
		
        $domain     = $jinput->get('domains');
        $country    = $jinput->get('countries');
        $block_all  = $jinput->get('block');
      
       $db = JFactory::getDbo(); 
       
        if ($block_all ==0) { 
            $setSQL = "insert into #__livehelp_not_allowed_countries (id_domain, code) values (" .  $domain . "," . "'".$country."'". ")" ;
          } else
       
        {   $setSQL =" INSERT INTO #__livehelp_not_allowed_countries (id_domain,code) ". 
               " SELECT " .  $domain . "," ." code FROM #__livehelp_countries"; 
               }
       
       $db->setQuery($setSQL);
      
       	try {
       	     $db->execute();
			return false;
		    }
       catch (Exception $e)
         {
		    $app->enqueueMessage(JText::_($e->getMessage()), 'error'); 
           return true;	     
             }
        
	   }
       
function dbclearup()
   {
       $app    = JFactory::getApplication();
       $db = JFactory::getDbo(); 
       $setSQL = "delete from #__livehelp_requests";

        $db->setQuery($setSQL);
      
        try {
       	     $db->execute();
			return false;
		    }
       catch (Exception $e)
         {
		    $app->enqueueMessage(JText::_($e->getMessage()), 'error'); 
           return true;	     
             } 
       
   }
function export()
   {
   
        include_once (JPATH_COMPONENT_ADMINISTRATOR .'/lib/csvcreation.php');
        include_once (JPATH_COMPONENT_ADMINISTRATOR .'/language/english.php');

         $jinput = JFactory::getApplication();
        
		JSession::checkToken() or jexit( 'Invalid Token' );
		
		$jinput->set('view', 'reports'); 
        
     
         $filename="data_messages.csv";     
         $sql = "select * from #__livehelp_messages order by 1";
        
        ob_end_clean();

        $file_name = 'export_'.$filename.'.csv';
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Accept-Ranges: bytes');
        header('Content-Disposition: attachment; filename='.basename($file_name).';');
        header('Content-Type: text/plain; '._ISO);
        header('Expires: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Pragma: no-cache');
     
        echo ExportToCSV($sql);
	   
        die();
          
       return true;
	   }        
       
function delete(&$pks)
   {
     $app    = JFactory::getApplication();
     $jinput = JFactory::getApplication();
     JSession::checkToken() or jexit( 'Invalid Token' );
	 
     $db = JFactory::getDbo();
     $setSQL = "delete from #__livehelp_messages"; 
  
        $db->setQuery($setSQL);
      
       	try {
       	     $db->execute();
			return false;
		    }
       catch (Exception $e)
         {
		    $app->enqueueMessage(JText::_($e->getMessage()), 'error'); 
           return true;	     
             }
			 
	   }

function install_language()
   {
	
	  $jinput = JFactory::getApplication()->input;			
      $lang     = $jinput->get('available_languages');
		
	  $file = JFactory::getApplication()->input->files->get('upload_file');	  
	  $filename = JFile::makeSafe($file['name']);
	  		   
		  
	if ($lang == JFile::stripExt($filename)) 
	{	
	  if(!defined('DIRECTORY_SEPARATOR')) define('DIRECTORY_SEPARATOR', '/');
		 		  
	  if(isset($file['name']) && $file['name'] != '')
		{
            $upload_path =  JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_activehelper_livehelp' . DIRECTORY_SEPARATOR . 'server' . DIRECTORY_SEPARATOR .'uploads';
			$filename    = $upload_path . DIRECTORY_SEPARATOR . $file['name'];

			         
         /* Log
		   JLog::addLogger(array('text_file' => 'livechat.log'));
		   JLog::add("langauge ==>: ".$lang; 
		   JLog::add("file ==>: " .$file['tmp_name'] ); 
		  */ 
		  
      if (!JFile::upload($file['tmp_name'],$filename, false, true)) {
				$msg = JText::_('COM_ACTIVEHELPER_LIVEHELP_UPLOAD_FAIL_S') . $filename . JText::_('COM_ACTIVEHELPER_LIVEHELP_UPLOAD_FAIL_F') ;
           return $msg;
        }
		else
		{   // unzip main file
	           
                $exOK = $this->extract_zip_file($filename,$upload_path);
			   //JArchive::extract($filename,$upload_path);
			 
			 if ( $exOK ==true)
			     { 
			       $this->install_live_chat_lang($lang , $upload_path . DIRECTORY_SEPARATOR . 'i18n.zip', $upload_path);
			       $this->install_live_images( $lang , $upload_path . DIRECTORY_SEPARATOR . 'domains.zip', $upload_path);
				   $this->updatelang($lang );
				 } 
		  }	
	}
	return "Language installed!";
   	}
     else
     { return "Not match the file with the language!";
    }		 
  }

 function install_live_chat_lang($lang, $file, $path)
   {
       if(!defined('DIRECTORY_SEPARATOR')) define('DIRECTORY_SEPARATOR', '/');
	   
	    $this->extract_zip_file($file , $path);
		//JArchive::extract($file ,$path);	
		
	     $lang_path =  JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_activehelper_livehelp' . DIRECTORY_SEPARATOR . 'server' . DIRECTORY_SEPARATOR . 'i18n';	
		 $folder_src  = $path . DIRECTORY_SEPARATOR . $lang;
		 
		 $mode = 0755;
		JFolder::create($lang_path . DIRECTORY_SEPARATOR . $lang , $mode);
		 

		if (!JFolder::copy($folder_src , $lang_path . DIRECTORY_SEPARATOR . $lang ,'',true,false)) {
            $msg = "Failed to create a base configuration";           
          }
		
          JFolder::delete($folder_src);
          JFile::delete($path . DIRECTORY_SEPARATOR . 'i18n.zip');
		  
	    return $msg;
     }  

 function install_live_images($lang, $file, $path)
   {
       if(!defined('DIRECTORY_SEPARATOR')) define('DIRECTORY_SEPARATOR', '/');
	   
	    //JArchive::extract($file ,$path);	
		 $this->extract_zip_file($file , $path);
		
	     $img_path =  JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_activehelper_livehelp' . DIRECTORY_SEPARATOR . 'server' . DIRECTORY_SEPARATOR . 'domains';	
		 $folder_src  = $path . DIRECTORY_SEPARATOR . $lang . DIRECTORY_SEPARATOR . 'pictures';
		 
		 $mode = 0755;
		 
		 //JLog::addLogger(array('text_file' => 'livechat.log'));
		 $domains = $this->domain_list();
		 
		 // add to the static settings folder
		    
			$base_folder =0;			
            JFolder::create($img_path . DIRECTORY_SEPARATOR . $base_folder . DIRECTORY_SEPARATOR . 'i18n' . DIRECTORY_SEPARATOR . $lang , $mode);			
            
		   if (!JFolder::copy($folder_src , $img_path . DIRECTORY_SEPARATOR . $base_folder . DIRECTORY_SEPARATOR . 'i18n' . DIRECTORY_SEPARATOR . $lang  ,'',true,false)) {
             $msg = "Failed to create a base configuration";     
		   }
		   
		   foreach ($domains as $id => $value)
            {
		    	 $folder = $domains[$id];			
                 JFolder::create($img_path . DIRECTORY_SEPARATOR . $folder . DIRECTORY_SEPARATOR . 'i18n' . DIRECTORY_SEPARATOR . $lang , $mode);			
            
		       if (!JFolder::copy($folder_src , $img_path . DIRECTORY_SEPARATOR . $folder . DIRECTORY_SEPARATOR . 'i18n' . DIRECTORY_SEPARATOR . $lang  ,'',true,false)) {
                 $msg = "Failed to create a base configuration";           
                 }
            }
		 
			JFolder::delete($path . DIRECTORY_SEPARATOR . $lang);
			JFile::delete($path . DIRECTORY_SEPARATOR . 'domains.zip');
			JFile::delete($path . DIRECTORY_SEPARATOR . 'ar.zip');
		    
	    return $msg;
     }

  function domain_list()
    {
       $app    = JFactory::getApplication();
	   $db     = JFactory::getDbo(); 
       $query  = $db->getQuery(true);
                    
        $query->select('id_domain ')
        ->from('#__livehelp_domains');
                          
        $db->setQuery($query);    
		
       	if (!$db->execute()) {			
			return false;
		  } 

        return $db->loadColumn();
    }

 function updatelang($lang)
    {
        $app    = JFactory::getApplication();
		$db     = JFactory::getDbo();        
        $query  = $db->getQuery(true);
			      
		$query->update($db->quoteName('#__livehelp_languages'))   
		      ->set($db->quoteName('installed') . "=" . "1" )
              ->where(array($db->quoteName('code') . "=". "'". $lang ."'"));
          
	   $db->setQuery($query);	  	  
      		
       	try {
       	     $db->execute();
			return false;
		    }
       catch (Exception $e)
         {
		    $app->enqueueMessage(JText::_($e->getMessage()), 'error'); 
           return true;	     
             }
        
	   }
	
	function install_live_chat_package()
	{			
	    
		$file      = JFactory::getApplication()->input->files->get("upload_installer");	  
	    $filename  = JFile::makeSafe($file['name']);
	  	$filecheck = "activehelper_livechat_core";		
		$namecheck = JFile::stripExt($filename);
	  	
		$ver           = JString::substr($namecheck,27,6);
        $filecheckname = JString::substr($namecheck,0,26); 
	 		 		
		
	    if ($filecheck == $filecheckname) 
	          {	
	            if(!defined('DIRECTORY_SEPARATOR')) define('DIRECTORY_SEPARATOR', '/');
		 		  
	            if(isset($file['name']) && $file['name'] != '')
		          {
                     $upload_path =  JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_activehelper_livehelp' . DIRECTORY_SEPARATOR .'server' . DIRECTORY_SEPARATOR . 'uploads';
			         $filename    = $upload_path . DIRECTORY_SEPARATOR . $file['name'];
                  
				            
				     if (!JFile::upload($file['tmp_name'],$filename, false, true)) {
			            	$msg = JText::_('COM_ACTIVEHELPER_LIVEHELP_UPLOAD_FAIL_S') . $filename . JText::_('COM_ACTIVEHELPER_LIVEHELP_UPLOAD_FAIL_F') ;
                           return $msg;
					      }				      
		                 else
		                 {   // unzip installer file
	                      
		                   /*  JLog::addLogger(array('text_file' => 'livechat.log'));
		                     JLog::add("filename ==>: ".$filename) ; 
		                     JLog::add("pah ==>: " .$upload_path ); 
		                     JLog::add("version ==>: " .$ver);  	  
      		                */
			
						    $msg =  $this->install_live_chat_core($filename , $upload_path); 
						    $this->update_live_chat_core_ver($ver);
						   
			             }	
			       }  
		
	            }
		return $msg;		
       }
	   
	 function install_live_chat_core($filename, $upload_path)
	 { 
          $filezip       = "activehelper_livechat_core";
	      $core_path     = JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR  . 'com_activehelper_livehelp' . DIRECTORY_SEPARATOR .'server';		  
		  $core_filename = $upload_path . DIRECTORY_SEPARATOR . 'server.zip';
		  
		   // extract main zip file
	        
			  $exOK = $this->extract_zip_file($filename,$upload_path);
			  $exOK2 = $this->extract_zip_file($core_filename,$core_path);
			
			/*  
		     JLog::addLogger(array('text_file' => 'livechat.log'));
		                     JLog::add("upload_path ==>: ".$upload_path) ; 
		                     JLog::add("filename ==>: " .$filename ); 
							 JLog::add("mensaje ==>: " .$exOK ); 							 
		                   //  JLog::add("core_path ==>: " .$core_path);  
                            // JLog::add("core_filename ==>: " .$core_filename); 							 
							 //JLog::add("mensaje ==>: " .$exOK2); 
							 
							 */
	  	     
		    // delete files
			if (( $exOK ==true) && ($exOK2 ==true)) {
		         JFile::delete($filename);
			     JFile::delete($core_filename );			
		      // setup configuration files		
			     $this->resetSettings();
			 
			    $msg = JText::_('COM_ACTIVEHELPER_LIVEHELP_INSTALL_SUCESFUL');
			    } 
			  else {
				 $msg = JText::_('COM_ACTIVEHELPER_LIVEHELP_FAIL_TO_INSTALL');  
			  }
			
			return $msg; 
	  }	 
	  
	  function extract_zip_file($filename,$path)
	  {
		                 
		 chmod($filename   , 0777);
		 
		 $msg = JArchive::extract(JPath::clean($filename), JPath::clean($path));		  
		 
		return $msg;   
	  }
	  
	 function update_live_chat_core_ver($core_ver)
	 {   
	     
		 $app    = JFactory::getApplication();	  
	     $db     = JFactory::getDbo();        
         $exits  = $this->setting_exits("1");		 
       	   	   
	     if ($exits ==0) { 
              $setSQL = "insert into #__livehelp_core_settings (id,name,value) values (1 " . "," . "'core version'" . "," . "'".$core_ver."'". ")" ;
            }  else
       
          { $setSQL =" update #__livehelp_core_settings  set value =". "'".$core_ver."'". " where id = " . "'".$exits ."'";
            
			}
			           	
          //Log
		   /*JLog::addLogger(array('text_file' => 'livechat.log'));
		   JLog::add("exits==>: ".$exits) ; 
		   JLog::add("query==>: " .$setSQL ); 
		   JLog::add("version==>: " .$core_ver);  	  
      		*/
       	  $db->setQuery($setSQL);
      
       	try {
       	     $db->execute();
			return false;
		    }
       catch (Exception $e)
         {
		    $app->enqueueMessage(JText::_($e->getMessage()), 'error'); 
           return true;	     
             }
        
	   }
		 	 
 function setting_exits($setting)
    {
       $app    = JFactory::getApplication();
	   $db     = JFactory::getDbo(); 
       $query  = $db->getQuery(true);
                    
        $query->select('count(*) setting')
        ->from('#__livehelp_core_settings')
        ->where("id =" . $setting);      
                           
        $db->setQuery($query);    
		
       	if (!$db->execute()) {			
			return false;
		  } 

        return $db->loadResult(); 
	  }
	  
  function save_lang()
    {
       
	  if(!defined('DIRECTORY_SEPARATOR')) define('DIRECTORY_SEPARATOR', '/');           
         $CR = "\r\n";
		 
		$jinput = JFactory::getApplication()->input;
		
	    $lang_text  = $jinput->getString('lang_text');
   	    $lang_path  = $jinput->getString('file_path');
	    $lang_fname = $jinput->getString('file_name');
	   
	    $path_file = $lang_path  . '/' . $lang_fname; 
      	           	          
         if( JFile::write($path_file,$lang_text) )
            {
               $msg = JText::_('COM_ACTIVEHELPER_LIVEHELP_LANG_UPDATED');
              }
			else
			{ $msg = JText::_('COM_ACTIVEHELPER_LIVEHELP_LANG_UPDATE_FAILED'); }  
		  		  					
        return $msg;
	  
   }
}
