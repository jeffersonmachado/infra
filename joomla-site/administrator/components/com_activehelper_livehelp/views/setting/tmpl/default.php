<?php

/**
 * @package   ActiveHelper_LiveHelp
 * @contact   www.activehelper.com, support@activehelper.com
 * @copyright 2010 - 2017 by ActiveHelper Inc. All rights reserved
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

 
defined( '_JEXEC' ) or die( 'Restricted access' );

JHTML::_('behavior.calendar');

$editor = JFactory::getEditor();
jimport('joomla.html.html.bootstrap');

include_once (JPATH_COMPONENT_ADMINISTRATOR .'/language/english.php');

?>
<script language="javascript" type="text/javascript">
function submitbutton(pressbutton) {
		submitform(pressbutton);
	}
</script>
<form action="index.php" method="post" name="adminForm" id="adminForm" enctype="multipart/form-data">
<fieldset class="adminform">
 <ul class="nav nav-tabs" id="myTab">
  <li class="active"><a data-toggle="tab" href="#home"><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_GENERAL') ?></a></li>
  <li><a data-toggle="tab" href="#display"><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_DISPLAY') ?></a></li>
  <li><a data-toggle="tab" href="#proactive"><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_PROA') ?></a></li>
  <li><a data-toggle="tab" href="#fonts"><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_FONTS') ?></a></li> 
  <li><a data-toggle="tab" href="#chat"><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_CHAT') ?></a></li>   
  <li><a data-toggle="tab" href="#email"><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_EMAIL') ?></a></li>    
  <li><a data-toggle="tab" href="#images"><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_IMAGES') ?></a></li>
  <li><a data-toggle="tab" href="#welcome"><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_LANG_WEL') ?></a></li> 
  <li><a data-toggle="tab" href="#copyright"><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_RB') ?></a></li>  
  <li><a data-toggle="tab" href="#google"><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_GO_ANL') ?></a></li>                 
 </ul>     
  <?php echo JHtml::_('bootstrap.startPane', 'myTab', array('active' => 'home'));  ?>
  <?php echo JHtml::_('bootstrap.addPanel', 'myTab', 'home');  ?>
  <table class="admintable">
    <tr>
       <td width="200" class="key> <label for="livehelp name"> <?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_LHPN');?></td>
      <td>
        <input class="text_area" type="text" name="livehelp_name" id="livehelp_name" size="50" maxlength="50" value="<?php echo $this->livehelp_name;?>" />
      </td>
    </tr>
        <tr>
         <td width="200" class="key> <label for="site name"> <?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_SITE') ?></td>
      <td>
        <input class="text_area" type="text" name="site_name" id="site_name" size="50" maxlength="50" value="<?php echo $this->site_name;?>" />
      </td>
    </tr>
    <tr>
        <td width="200" class="key> <label for="site link"> <?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_SITE_AD') ?></td>
      <td>
        <input class="text_area" type="text" name="site_address" id="site_address" size="50" maxlength="50" value="<?php echo $this->site_address;?>" />
      </td>
    </tr>
       <tr>
        <td width="200" class="key> <label for="department"> <?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_STATS_TOTAL_DEPARTMENTS') ?></td>
      <td>
        <?php echo $this->departments; ?>
      </td>
    </tr>
     <tr>
       <td width="200" class="key> <label for="geolocation"> <?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_D_GEO') ?></td>
      <td>
        <?php echo $this->disable_geolocation; ?>
      </td>
    </tr>
     <tr>
      <td width="200" class="key> <label for="Status indicator"> <?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_D_STATUS') ?></td>
      <td>
        <?php echo $this->disable_status_indicator; ?>
      </td>
    </tr>
     <tr>
      <td width="200" class="key> <label for="captcha"> <?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_CAPTCHA') ?></td>
      <td>
        <?php echo $this->captcha; ?>
      </td>
    </tr>
     <tr>
      <td width="200" class="key> <label for="phone"> <?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_PHONE') ?></td>
      <td>
        <?php echo $this->phone; ?>
      </td>
    </tr>
     <tr>
      <td width="200" class="key> <label for="company"> <?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_COMPANY') ?></td>
      <td>
        <?php echo $this->company; ?>
      </td>
    </tr>
    </table>
    <?php echo JHtml::_('bootstrap.endPanel'); ?>
  <?php echo JHtml::_('bootstrap.addPanel', 'myTab', 'display'); ?>
  <table class="admintable">
    <tr>
      <td width="200" class="key> <label for="background"> <?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_BC') ?></td>
      <td>
        <input class="text_area" type="text" name="background_color" id="background_color" size="50" maxlength="25" value="<?php echo $this->background_color;?>" />
      </td>
    </tr>
   <tr>
      <td width="200" class="key> <label for="chat font"> <?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_CHAT_FONT_T') ?></td>
      <td>
        <input class="text_area" type="text" name="chat_font_type" id="chat_font_type" size="50" maxlength="25" value="<?php echo $this->chat_font_type;?>" />
      </td>
    </tr>
   <tr>
    <tr>
      <td width="200" class="key> <label for="font"> <?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_G_CHAT_FONT') ?></td>
      <td>
        <input class="text_area" type="text" name="guest_chat_font_size" id="guest_chat_font_size" size="50" maxlength="25" value="<?php echo $this->guest_chat_font_size;?>" />
      </td>
    </tr>
    <tr>
      <td width="200" class="key> <label for="chat font a"> <?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_A_CHAT_FONT') ?></td>
      <td>
        <input class="text_area" type="text" name="admin_chat_font_size" id="admin_chat_font_size" size="50" maxlength="25" value="<?php echo $this->admin_chat_font_size;?>" />
      </td>
    </tr>
   <tr>
      <td width="200" class="key> <label for="livehelp dph"> <?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_D_P_H') ?></td>
      <td>
        <?php echo $this->disable_popup_help; ?>
      </td>
    </tr>
    <tr>
      <td width="200" class="key> <label for="chat bc"> <?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_CHAT_BC') ?></td>
      <td>
        <?php echo $this->chat_background_img; ?>
      </td>
    </tr>
   <tr>
      <td width="200" class="key> <label for="imgage link"> <?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_CHAT_IMG_LINK') ?></td>
      <td>
        <input class="text_area" type="text" name="campaign_link" id="campaign_link" size="50" maxlength="50" value="<?php echo $this->campaign_link;?>" />
      </td>
    </tr>
     <tr>
      <td width="200" class="key> <label for="disable agent banner"> <?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_DISABLE_AGENT_BANNER') ?></td>
      <td>
        <?php echo $this->disable_agent_bannner; ?>
      </td>
    </tr>
    <tr>
      <td width="200" class="key> <label for="livehelp image"> <?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_IMG') ?></td>
      <td class="domain_campaign_image" width="60" align="left" >
        <?php echo JHTML::_('image', $this->img_path . $this->campaign_image , 'campaign image'); ?> <input type="file" name="campaign_image" value="" />                 
      </td>
    </tr>
    <tr>
      <td width="200" class="key> <label for="send button"> <?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_SEND_BTN') ?></td>
      <td class="domain_chat_button_img" width="60" align="left" >
       <?php echo JHTML::_('image', $this->img_path . $this->chat_button_img, 'chat button' ); ?> <input type="file" name="chat_button_img" value="" />
      </td>
    </tr>
        <tr>
      <td width="200" class="key> <label for="send button hover"> <?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_SEND_BTN_OVER') ?></td>
      <td class="domain_chat_button_hover_img" width="60" align="left" >
       <?php echo JHTML::_('image', $this->img_path . $this->chat_button_hover_img, 'chat button hover' ); ?> <input type="file" name="chat_button_hover_img" value="" />
      </td>
    </tr>
    </table>
    <?php echo JHtml::_('bootstrap.endPanel'); ?>
   <?php echo JHtml::_('bootstrap.addPanel', 'myTab', 'proactive'); ?>
  <table class="admintable">
   <tr>
      <td width="200" class="key> <label for="invitation"> <?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_INV_IMG') ?></td>
      <td class="domain_chat_invitation_img" width="60" align="left" >
       <?php echo JHTML::_('image', $this->img_path . $this->chat_invitation_img, 'chat invitation' ); ?> <input type="file" name="chat_invitation_img" value="" />
      </td>
    </tr>
     <tr>
      <td width="200" class="key> <label for="auto start"> <?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_INV_AUTO_START') ?></td>
      <td>
        <input class="text_area" type="text" name="chat_invitation_auto_refresh" id="chat_invitation_auto_refresh" size="10" maxlength="4" value="<?php echo $this->chat_invitation_auto_refresh;?>" />
      </td>
    </tr>
       <tr>
      <td width="200" class="key> <label for="secs"> <?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_D_INV') ?></td>
      <td>
        <?php echo $this->disable_invitation; ?>
      </td>
    </tr>
    </table>
    <?php echo JHtml::_('bootstrap.endPanel'); ?>
 <?php echo JHtml::_('bootstrap.addPanel', 'myTab', 'fonts'); ?>
  <table class="admintable">
    <tr>
      <td width="200" class="key> <label for="ft"><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_FT') ?></td>
      <td>
        <input class="text_area" type="text" name="font_type" id="font_type" size="50" maxlength="25" value="<?php echo $this->font_type;?>" />
      </td>
    </tr>
   <tr>
      <td width="200" class="key> <label for="fs"><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_FS') ?></td>
      <td>
        <input class="text_area" type="text" name="font_size" id="font_size" size="50" maxlength="25" value="<?php echo $this->font_size;?>" />
      </td>
    </tr>
   <tr>
    <tr>
      <td width="200" class="key> <label for="fc"><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_FC') ?></td>
      <td>
        <input class="text_area" type="text" name="font_color" id="font_color" size="50" maxlength="25" value="<?php echo $this->font_color;?>" />
      </td>
    </tr>
    <tr>
      <td width="200" class="key> <label for="flc"><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_FLC') ?></td>
      <td>
        <input class="text_area" type="text" name="font_link_color" id="font_link_color" size="50" maxlength="25" value="<?php echo $this->font_link_color;?>" />
      </td>
    </tr>
    <tr>
      <td width="200" class="key> <label for="sfc"><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_SFC') ?></td>
      <td>
        <input class="text_area" type="text" name="sent_font_color" id="sent_font_color" size="50" maxlength="25" value="<?php echo $this->sent_font_color;?>" />
      </td>
    </tr>
    <tr>
      <td width="200" class="key> <label for="rfc"><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_RFC') ?></td>
      <td>
        <input class="text_area" type="text" name="received_font_color" id="received_font_color" size="50" maxlength="25" value="<?php echo $this->received_font_color;?>" />
      </td>
    </tr>
    </table>
    <?php echo JHtml::_('bootstrap.endPanel'); ?>
  <?php echo JHtml::_('bootstrap.addPanel', 'myTab', 'chat'); ?>
  <table class="admintable">
   <tr>
      <td width="200" class="key> <label for="login"> <?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_D_LOGIN') ?></td>
      <td>
        <?php echo $this->disable_login_details; ?>
      </td>
    </tr>
   <tr>
      <td width="200" class="key> <label for="chat username"><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_D_CHAT_USN') ?></td>
      <td>
        <?php echo $this->disable_chat_username; ?>
      </td>
    </tr>
    <tr>
      <td width="200" class="key> <label for="login"> <?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_R_G_DETAILS') ?></td>
      <td>
        <?php echo $this->require_guest_details; ?>
      </td>
    </tr>
    <tr>
      <td width="200" class="key> <label for="language"> <?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_D_LANG_SEL') ?></td>
      <td>
        <?php echo $this->disable_language; ?>
      </td>
    </tr>
    </table>
    <?php echo JHtml::_('bootstrap.endPanel'); ?>
    <?php echo JHtml::_('bootstrap.addPanel', 'myTab', 'email'); ?>
  <table class="admintable">
  <tr>
      <td width="200" class="key> <label for="offline email"> <?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_OFF_EMAIL') ?></td>
      <td>
        <input class="text_area" type="text" name="offline_email" id="offline_email" size="50" maxlength="50" value="<?php echo $this->offline_email;?>" />
      </td>
    </tr>
    <tr>
      <td width="200" class="key> <label for="from email"> <?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_FROM_EMAIL') ?></td>
      <td>
        <input class="text_area" type="text" name="from_email" id="from_email" size="50" maxlength="50" value="<?php echo $this->from_email;?>" />
      </td>
    </tr>
   <tr>
      <td width="200" class="key> <label for="disable offline email"> <?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_D_OFF_EMAIL') ?></td>
      <td>
        <?php echo $this->disable_offline_email; ?>
      </td>
    </tr>
    <tr>
      <td width="200" class="key> <label for="custom form"><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_C_OFF_FORM') ?></td>
      <td>
        <input class="text_area" type="text" name="custom_offline_form" id="custom_offline_form" size="50" maxlength="200" value="<?php echo $this->custom_offline_form;?>" />
      </td>
    </tr>
     <tr>
      <td width="200" class="key> <label for="log messages"><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_LOG_OFF_MSG') ?></td>
      <td>
        <?php echo $this->log_offline_email; ?>
      </td>
    </tr>
     <tr>
      <td width="200" class="key> <label for="smtp"> <?php echo JText::_('SMTP') ?></td>
      <td>
        <?php echo $this->configure_smtp; ?>
      </td>
    </tr>
     <tr>
      <td width="200" class="key> <label for="server"> <?php echo JText::_('SMTP Server') ?></td>
      <td>
        <input class="text_area" type="text" name="smtp_server" id="smtp_server" size="50" maxlength="50" value="<?php echo $this->smtp_server;?>" />
      </td>
    </tr>
         <tr>
      <td width="200" class="key> <label for="port"> <?php echo JText::_('SMTP Port') ?></td>
      <td>
        <input class="text_area" type="text" name="smtp_port" id="smtp_port" size="50" maxlength="50" value="<?php echo $this->smtp_port;?>" />
      </td>
    </tr>
    </table>
    <?php echo JHtml::_('bootstrap.endPanel'); ?>
     <?php echo JHtml::_('bootstrap.addPanel', 'myTab', 'images'); ?>
  <table class="admintable">
     <tr>
      <td width="200" class="key> <label for="image languages"> <?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_LANGUAGE') ?></td>
      <td>
        <?php echo $this->languajes; ?>
      </td>
    </tr>
     <tr>
      <td width="200" class="key> <label for="enctype="multipart/form-data"> <?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_ONLINE_IMG')  . ' ( ' . $this->status_default_img_type . ' )' ?></td>
      <td class="domain_image_online" >
       <?php echo JHTML::_('image', $this->img_path . $this->online_img , 'online' ); ?> <input type="file" name="online" value="" />       
      </td>
    </tr>
     <tr>
      <td width="200" class="key> <label for="offline"> <?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_OFFLINE_IMG')  . ' ( ' . $this->status_default_img_type . ' )' ?> </td>
      <td class="domain_image_offline" >
       <?php echo JHTML::_('image',  $this->img_path . $this->offline_img , 'offline'); ?>  <input type="file" name="offline" value="" />
      </td>
    </tr>
     <tr>
      <td width="200" class="key> <label for="away"> <?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_AWAY_IMG')   . ' ( ' . $this->status_default_img_type . ' )' ?> </td>
      <td class="domain_image_away" >
       <?php echo JHTML::_('image', $this->img_path . $this->away_img , 'away' ); ?>  <input type="file" name="away" value="" />
      </td>
    </tr>
   <tr>
      <td width="200" class="key> <label for="brb"> <?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_BRB_IMG')  . ' ( ' . $this->status_default_img_type . ' )' ?> </td>
      <td class="domain_image_brb" >
        <?php echo JHTML::_('image', $this->img_path . $this->brb_img, 'brb' ); ?> <input type="file" name="brb" value="" />
      </td>
    </tr>
    </table>
    <?php echo JHtml::_('bootstrap.endPanel'); ?>
    <?php echo JHtml::_('bootstrap.addPanel', 'myTab', 'welcome'); ?>
  <table class="admintable">
   <tr>
      <td width="200" class="key> <label for="en"> <?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_LANG_EN') ?></td>
      <td>
        <?php echo $this->languaje_en; ?>  <input class="text_area" type="text" name="lan_en_wm" id="lan_en_wm" size="100" maxlength="200" value="<?php echo $this->lan_en_wm;?>" />
      </td>
      <td> <a target="_blank" href="<?php echo _EXT_LINK_ICON_STORE; ?>"><?php echo JText::_('Get more chat buttons, themes and invitations here') ?></a></td>
    </tr>
       <tr>
      <td width="200" class="key> <label for="sp"> <?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_LANG_SP') ?></td>
      <td>
        <?php echo $this->languaje_sp; ?> <input class="text_area" type="text" name="lan_sp_wm" id="lan_sp_wm" size="100" maxlength="200" value="<?php echo $this->lan_sp_wm;?>" />
      </td>
    </tr>
     <tr>
      <td width="200" class="key> <label for="de"><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_LANG_DE') ?></td>
      <td>
        <?php echo $this->languaje_de; ?>  <input class="text_area" type="text" name="lan_de_wm" id="lan_de_wm" size="100" maxlength="200" value="<?php echo $this->lan_de_wm;?>" />
      </td>
    </tr>
    <tr>
      <td width="200" class="key> <label for="pt"> <?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_LANG_PT') ?></td>
      <td>
        <?php echo $this->languaje_pt; ?>  <input class="text_area" type="text" name="lan_pt_wm" id="lan_pt_wm" size="100" maxlength="200" value="<?php echo $this->lan_pt_wm;?>" />
      </td>
    </tr>
    <tr>
      <td width="200" class="key> <label for="it"> <?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_LANG_IT') ?></td>
      <td>
        <?php echo $this->languaje_it; ?>  <input class="text_area" type="text" name="lan_it_wm" id="lan_it_wm" size="100" maxlength="200" value="<?php echo $this->lan_it_wm;?>" />
      </td>
    </tr>
    <tr>
      <td width="200" class="key> <label for="fr"> <?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_LANG_FR') ?></td>
      <td>
        <?php echo $this->languaje_fr; ?>  <input class="text_area" type="text" name="lan_fr_wm" id="lan_fr_wm" size="100" maxlength="200" value="<?php echo $this->lan_fr_wm;?>" />
      </td>
    </tr>
    <tr>
      <td width="200" class="key> <label for="cz"> <?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_LANG_CZ') ?></td>
      <td>
        <?php echo $this->languaje_cz; ?>  <input class="text_area" type="text" name="lan_cz_wm" id="lan_cz_wm" size="100" maxlength="200" value="<?php echo $this->lan_cz_wm;?>" />
      </td>
    </tr>
    <tr>
      <td width="200" class="key> <label for="se"><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_LANG_SE') ?></td>
      <td>
        <?php echo $this->languaje_se; ?>  <input class="text_area" type="text" name="lan_se_wm" id="lan_se_wm" size="100" maxlength="200" value="<?php echo $this->lan_se_wm;?>" />
      </td>
    </tr>
    <tr>
      <td width="200" class="key> <label for="no"> <?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_LANG_NO') ?></td>
      <td>
        <?php echo $this->languaje_no; ?>  <input class="text_area" type="text" name="lan_no_wm" id="lan_no_wm" size="100" maxlength="200" value="<?php echo $this->lan_no_wm;?>" />
      </td>
    </tr>
    <tr>
      <td width="200" class="key> <label for="tr"> <?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_LANG_TR') ?></td>
      <td>
        <?php echo $this->languaje_tr; ?>  <input class="text_area" type="text" name="lan_tr_wm" id="lan_tr_wm" size="100" maxlength="200" value="<?php echo $this->lan_tr_wm;?>" />
      </td>
    </tr>
    <tr>
      <td width="200" class="key> <label for="gr"> <?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_LANG_GR') ?></td>
      <td>
        <?php echo $this->languaje_gr; ?>  <input class="text_area" type="text" name="lan_gr_wm" id="lan_gr_wm" size="100" maxlength="200" value="<?php echo $this->lan_gr_wm;?>" />
      </td>
    </tr>
     <tr>
      <td width="200" class="key> <label for="he"><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_LANG_HE') ?></td>
      <td>
        <?php echo $this->languaje_he; ?>  <input class="text_area" type="text" name="lan_he_wm"  id="lan_he_wm" dir="RTL" size="100" maxlength="200" value="<?php echo $this->lan_he_wm;?>" />
      </td>
    </tr>
    <tr>
      <td width="200" class="key> <label for="fa"> <?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_LANG_FA') ?></td>
      <td>
        <?php echo $this->languaje_fa; ?>  <input class="text_area" type="text" name="lan_fa_wm" id="lan_fa_wm" dir="RTL"  size="100" maxlength="200" value="<?php echo $this->lan_fa_wm;?>" />
      </td>
    </tr>
    <tr>
      <td width="200" class="key> <label for="sr"> <?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_LANG_SR') ?></td>
      <td>
        <?php echo $this->languaje_sr; ?>  <input class="text_area" type="text" name="lan_sr_wm" id="lan_sr_wm" size="100" maxlength="200" value="<?php echo $this->lan_sr_wm;?>" />
      </td>
    </tr>
    <tr>
      <td width="200" class="key> <label for="ru"> <?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_LANG_RU') ?></td>
      <td>
        <?php echo $this->languaje_ru; ?>  <input class="text_area" type="text" name="lan_ru_wm" id="lan_ru_wm" size="100" maxlength="200" value="<?php echo $this->lan_ru_wm;?>" />
      </td>
    </tr>
    <tr>
      <td width="200" class="key> <label for="hu"> <?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_LANG_HU') ?></td>
      <td>
        <?php echo $this->languaje_hu; ?>  <input class="text_area" type="text" name="lan_hu_wm" id="lan_hu_wm" size="100" maxlength="200" value="<?php echo $this->lan_hu_wm;?>" />
      </td>
    </tr>
    <tr>
      <td width="200" class="key> <label for="zh"> <?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_LANG_ZH') ?></td>
      <td>
        <?php echo $this->languaje_zh; ?>  <input class="text_area" type="text" name="lan_zh_wm" id="lan_zh_wm" size="100" maxlength="200" value="<?php echo $this->lan_zh_wm;?>" />
      </td>
    </tr>
    <tr>
     <td width="200" class="key> <label for="cn"> <?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_LANG_CN') ?></td>
      <td>
        <?php echo $this->languaje_cn; ?>  <input class="text_area" type="text" name="lan_cn_wm" id="lan_cn_wm" size="100" maxlength="200" value="<?php echo $this->lan_cn_wm;?>" />
      </td>
    </tr>
    <tr>
      <td width="200" class="key> <label for="ar"> <?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_LANG_AR') ?></td>
      <td>
        <?php echo $this->languaje_ar; ?>  <input class="text_area" type="text" name="lan_ar_wm" id="lan_ar_wm" dir="RTL" size="100" maxlength="200" value="<?php echo $this->lan_ar_wm;?>" />
      </td>
    </tr>
    <tr>
      <td width="200" class="key> <label for="nl"> <?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_LANG_NL') ?></td>
      <td>
        <?php echo $this->languaje_nl; ?>  <input class="text_area" type="text" name="lan_nl_wm" id="lan_nl_wm" size="100" maxlength="200" value="<?php echo $this->lan_nl_wm;?>" />
      </td>
    </tr>
    <tr>
      <td width="200" class="key> <label for="fi"> <?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_LANG_FI') ?></td>
      <td>
        <?php echo $this->languaje_fi; ?>  <input class="text_area" type="text" name="lan_fi_wm" id="lan_fi_wm" size="100" maxlength="200" value="<?php echo $this->lan_fi_wm;?>" />
      </td>
    </tr>
    <tr>
      <td width="200" class="key> <label for="dk"> <?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_LANG_DK') ?>
      </td>
      <td>
        <?php echo $this->languaje_dk; ?>  <input class="text_area" type="text" name="lan_dk_wm" id="lan_dk_wm" size="100" maxlength="200" value="<?php echo $this->lan_dk_wm;?>" />
      </td>
    </tr>
    <tr>
      <td width="200" class="key> <label for="pl"> <?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_LANG_PL') ?></td>
      <td>
        <?php echo $this->languaje_pl; ?>  <input class="text_area" type="text" name="lan_pl_wm" id="lan_pl_wm" size="100" maxlength="200" value="<?php echo $this->lan_pl_wm;?>" />
      </td>
    </tr>
     <tr>
      <td width="200" class="key> <label for="bg"> <?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_LANG_BG') ?></td>
      <td>
        <?php echo $this->languaje_bg; ?>  <input class="text_area" type="text" name="lan_bg_wm" id="lan_bg_wm" size="100" maxlength="200" value="<?php echo $this->lan_bg_wm;?>" />
      </td>
    </tr>
    <tr>
      <td width="200" class="key> <label for="sk"> <?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_LANG_SK') ?></td>
      <td>
        <?php echo $this->languaje_sk; ?>  <input class="text_area" type="text" name="lan_sk_wm" id="lan_sk_wm" size="100" maxlength="200" value="<?php echo $this->lan_sk_wm;?>" />
      </td>
    </tr>
    <tr>
      <td width="200" class="key> <label for="cr"> <?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_LANG_CR') ?></td>
      <td>
        <?php echo $this->languaje_cr; ?>  <input class="text_area" type="text" name="lan_cr_wm" id="lan_cr_wm" size="100" maxlength="200" value="<?php echo $this->lan_cr_wm;?>" />
      </td>
    </tr>
    <tr>
      <td width="200" class="key> <label for="id"><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_LANG_ID') ?></td>
      <td>
        <?php echo $this->languaje_id; ?>  <input class="text_area" type="text" name="lan_id_wm" id="lan_id_wm" size="100" maxlength="200" value="<?php echo $this->lan_id_wm;?>" />
      </td>
    </tr>
    <tr>
      <td width="200" class="key> <label for="lt"> <?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_LANG_LT') ?></td>
      <td>
        <?php echo $this->languaje_lt; ?>  <input class="text_area" type="text" name="lan_lt_wm" id="lan_lt_wm" size="100" maxlength="200" value="<?php echo $this->lan_lt_wm;?>" />
      </td>
    </tr>
    <tr>
      <td width="200" class="key> <label for="ro"> <?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_LANG_RO') ?></td>
      <td>
        <?php echo $this->languaje_ro; ?>  <input class="text_area" type="text" name="lan_ro_wm" id="lan_ro_wm" size="100" maxlength="200" value="<?php echo $this->lan_ro_wm;?>" />
      </td>
    </tr>
     <tr>
      <td width="200" class="key> <label for="sl"> <?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_LANG_SL') ?></td>
      <td>
        <?php echo $this->languaje_sl; ?>  <input class="text_area" type="text" name="lan_sl_wm" id="lan_sl_wm" size="100" maxlength="200" value="<?php echo $this->lan_sl_wm;?>" />
      </td>
    </tr>
     <tr>
      <td width="200" class="key> <label for="et"> <?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_LANG_ET') ?></td>
      <td>
        <?php echo $this->languaje_et; ?>  <input class="text_area" type="text" name="lan_et_wm" id="lan_et_wm" size="100" maxlength="200" value="<?php echo $this->lan_et_wm;?>" />
      </td>
    </tr>
    <tr>
      <td width="200" class="key> <label for="lv"> <?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_LANG_LV') ?></td>
      <td>
        <?php echo $this->languaje_lv; ?>  <input class="text_area" type="text" name="lan_lv_wm" id="lan_lv_wm" size="100" maxlength="200" value="<?php echo $this->lan_lv_wm;?>" />
      </td>
    </tr>
      <tr>
      <td width="200" class="key> <label for="ge"> <?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_LANG_GE') ?></td>
      <td>
        <?php echo $this->languaje_ge; ?>  <input class="text_area" type="text" name="lan_ge_wm" id="lan_ge_wm" size="100" maxlength="200" value="<?php echo $this->lan_ge_wm;?>" />
      </td>
    </tr>
	  <tr>
      <td width="200" class="key> <label for="jp"> <?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_LANG_JP') ?></td>
      <td>
        <?php echo $this->languaje_jp; ?>  <input class="text_area" type="text" name="lan_jp_wm" id="lan_jp_wm" size="100" maxlength="200" value="<?php echo $this->lan_jp_wm;?>" />
      </td>
    </tr>
    </table>
    <?php echo JHtml::_('bootstrap.endPanel'); ?>
      <?php  echo JHtml::_('bootstrap.addPanel', 'myTab', 'copyright'); ?>
  <table class="admintable">
   <tr>
      <td width="200" class="key> <label for="disable copyright"> <?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_CP') ?></td>
      <td>
        <?php echo $this->disable_copyright; ?>
      </td>
      </tr>
       <tr>
      <td width="200" class="key> <label for="copyright banner"> <?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_IMG_BANNER') ?></td>
      <td>
        <?php echo $this->copyright_image; ?>
      </td>
      </tr>
      <tr>
      <td width="200" class="key> <label for="company image"> <?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_COMPANY_IMG') ?></td>
      <td class="domain_company_logo" width="120" align="left" > 
       <?php echo JHTML::_('image', $this->img_path . $this->company_logo, 'company logo'); ?> <input type="file" name="company_logo" value="" />
      </td>
    </tr>
     <tr>
      <td width="200" class="key> <label for="company link"> <?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_COMPANY_IMG_LNK') ?></td>
      <td>
        <input class="text_area" type="text" name="company_link" id="company_link" size="50" maxlength="50" value="<?php echo $this->company_link;  ?>" />
      </td>
    </tr>
    <tr>
      <td width="200" class="key> <label for="company slogan"> <?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_COMPANY_SL') ?></td>
      <td>
        <input class="text_area" type="text" name="company_slogan" id="company_slogan" size="50" maxlength="50" value="<?php echo $this->company_slogan;  ?>" />
      </td>
    </tr>
    </table>
    <?php echo JHtml::_('bootstrap.endPanel'); ?>
      <?php echo JHtml::_('bootstrap.addPanel', 'myTab', 'google'); ?>
  <table class="admintable">   
     <tr>
      <td width="200" class="key> <label for="analytics"> <?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_ANL') ?></td>
      <td>
        <input class="text_area" type="text" name="analytics_account" id="analytics_account" size="50" maxlength="50" value="<?php echo $this->analytics_account;?>" />
      </td>
    </tr>
    </table>
    <?php echo JHtml::_('bootstrap.endPanel'); ?>
   <?php echo JHtml::_('bootstrap.endPane', 'myTab'); ?>
  </fieldset> 
    <input type="hidden" name="option" value="com_activehelper_livehelp" />
    <input type="hidden" name="id_domain" value="<?php echo $this->id_domain; ?>" />
    <input type="hidden" name="task" value="save" />
    <input type="hidden" name="boxchecked" value="" />
    <input type="hidden" name="controller" value="domain" />  
    <input type="hidden" name="view" value="domains" />           
  <?php echo JHTML::_( 'form.token' ); ?>
</form>

<?php $random = rand(100, 999); ?>
<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
<script type="text/javascript">
  $jq = jQuery.noConflict();
  jQuery(document).ready(function($) {
    $("#languajes.inputbox").change(function(){
      var lang = $(this).val();

      var src = "<?php echo $this->img_path_lang; ?>";
      var src = src.replace(/__lang__/g, lang);

      $(".domain_image_online img").attr("src", src + "online.gif?cache=<?php echo $random; ?>");
      $(".domain_image_offline img").attr("src", src + "offline.gif?cache=<?php echo $random; ?>");
      $(".domain_image_away img").attr("src", src + "away.gif?cache=<?php echo $random; ?>");
      $(".domain_image_brb img").attr("src", src + "brb.gif?cache=<?php echo $random; ?>");
      $(".domain_campaign_image img").attr("src", src + "<?php echo $this->campaign_image; ?>?cache=<?php echo $random; ?>");
      $(".domain_chat_button_img img").attr("src", src + "<?php echo $this->chat_button_img; ?>?cache=<?php echo $random; ?>");
      $(".domain_chat_button_hover_img img").attr("src", src + "<?php echo $this->chat_button_hover_img; ?>?cache=<?php echo $random; ?>");
      $(".domain_chat_invitation_img img").attr("src", src + "<?php echo $this->chat_invitation_img; ?>?cache=<?php echo $random; ?>");
      $(".domain_company_logo img").attr("src", src + "<?php echo $this->company_logo; ?>?cache=<?php echo $random; ?>");
    });
  });
</script>