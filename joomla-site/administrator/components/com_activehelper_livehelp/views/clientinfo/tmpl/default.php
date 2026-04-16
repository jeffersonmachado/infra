<?php

/**
 * @package   ActiveHelper_LiveHelp
 * @contact   www.activehelper.com, support@activehelper.com
 * @copyright 2010 - 2017 by ActiveHelper Inc. All rights reserved
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

 
defined( '_JEXEC' ) or die( 'Restricted access' );
include_once (JPATH_COMPONENT_ADMINISTRATOR .'/language/english.php');

?>
<style>
.fbwelcome {
  clear:both;
  margin-bottom:10px;
  padding:10px;
  font-size:12px;
  color:#536482;
  line-height:140%;
  border:1px solid #ddd;
}
.fbwelcome h3 {
  margin:0;
  padding:0;
}
table.thisform {
  width: 100%;
  padding: 10px;
  border-collapse: collapse;
}
table.thisform tr.row0 {
  background-color: #F7F8F9;
}
table.thisform tr.row1 {
  background-color: #eeeeee;
}
table.thisform th {
  font-size: 15px;
  font-weight: normal;
  font-variant: small-caps;
  padding-top: 6px;
  padding-bottom: 2px;
  padding-left: 4px;
  padding-right: 4px;
  text-align: left;
  height: 25px;
  color: #666666;
  background: url(../images/background.gif);
  background-repeat: repeat;
}
table.thisform td {
  padding: 3px;
  text-align: left;
}
.fbstatscover {
  padding:0px;
}
table.fbstat {
  background-color:#FFFFFF;
  border:1px solid #ddd;
  padding:1px;
  width:100%;
}
table.fbstat th {
  background:#EEE;
  border-bottom:1px solid #CCC;
  border-top:1px solid #EEE;
  color:#666;
  font-size:11px;
  padding:3px 4px;
  text-align:left;
}
table.fbstat td {
  font-size:11px;
  line-height:140%;
  padding:4px;
  text-align:left;
}
table.fbstat caption {
  clear:both;
  font-size:14px;
  font-weight:bold;
  margin:10px 0 2px 0;
  padding:2px;
  text-align:left;
}
table.fbstat .col1 {
  background-color:#F1F3F5;
}
table.fbstat .col2 {
	background-color: #FBFBFB;
}
</style>
<div style="border:1px solid #ddd; background:#FBFBFB;">
<form action="index.php" method="post" name="adminForm" id="adminForm">
  <fieldset class="adminform">
    <legend><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_ES_DETAILS') ?></legend>
    <table class="admintable">
    <tr>
      <td width="120" class="key">
       <label for="server">
          <?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_SERVER') ?>
        </label>
      </td>
      <td>
        <input class="text_area" type="text" name="server" id="server" size="50" maxlength="25" value="<?php echo $this->server;?>" />
      </td>
    </tr>
      <tr>
      <td width="120" class="key">
         <label for="path">
        <?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_PATH') ?>
         </label>
      </td>
      <td>
        <input class="text_area" type="text" name="server_path" id="server_path" size="100" maxlength="100" value="<?php echo $this->server_path;?>" />
      </td>
    </tr>
      <tr>
      <td width="120" class="key">
        <label for="account">
        <?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_ACCOUNT') ?>
         </label>
      </td>
      <td>
        <input class="text_area" type="text" name="account" id="account" size="100" maxlength="100" value="<?php echo $this->account;?>" />
      </td>
    </tr>
   <tr>
      <td width="120" class="key">
      <label for="login">
        <?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_LOGIN') ?>
        </label>
      </td>
      <td>
        <input class="text_area" type="text" name="login" id="login" size="50" maxlength="25" value="<?php echo $this->login;?>" />
      </td>
    </tr>
   <tr>
      <td width="120" class="key">
       <label for="ssl">
         <?php echo JText::_('SSL') ?>
        </label>
      </td>
      <td>
        <input class="text_area" type="text" name="SSL" id="SSL" size="50" maxlength="25" value="<?php echo $this->ssl;?>" />
      </td>
    </tr>
    </table>
    <div class="fbstatscover">
  <?php
    ?>
  <table class="adminlist table table-striped" id="itemsList">
    <caption>
    <?php echo  JText::_('COM_ACTIVEHELPER_LIVEHELP_DOWNLOAD');?>
    </caption>
    <col class="col1">
    <col class="col2">
    <thead>
      <tr>
        <th><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_PRODUCT');?></th>
        <th><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_LNK'); ?></th>
        <th><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_USER_GUIDE'); ?></th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_SPD_WIN');?> </td>
        <td> <a target="_blank" href="<?php echo _DW_LNK_WIN;?>"><?php echo _DW_LNK_WIN;?></a></td>
        <td> <a target="_blank" href="<?php echo _DW_LNK_GUIDE_DESKTOP;?>"><?php echo _DW_LNK_GUIDE_DESKTOP;?></a></td>
      </tr>
      <tr>
        <td><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_SPD_MAC');?></td>
        <td> <a target="_blank" href="<?php echo _DW_LNK_MAC;?>"><?php echo _DW_LNK_MAC;?></a></td>
        <td> <a target="_blank" href="<?php echo _DW_LNK_GUIDE_DESKTOP;?>"><?php echo _DW_LNK_GUIDE_DESKTOP;?></a></td>
      </tr>
      <tr>
        <td><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_SPM_IOS');?></td>
        <td> <a target="_blank" href="<?php echo _DW_LNK_IOS;?>"><?php echo _DW_LNK_IOS;?></a></td>
        <td> <a target="_blank" href="<?php echo _DW_LNK_GUIDE_MOBILE_IOS;?>"><?php echo _DW_LNK_GUIDE_MOBILE_IOS;?></a></td>
      </tr>
      <tr>
        <td><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_SPM_ANDROID') ?></td>
        <td> <a target="_blank" href="<?php echo _DW_LNK_ANDROID;?>"><?php echo _DW_LNK_ANDROID;?></a></td>
        <td> <a target="_blank" href="<?php echo _DW_LNK_GUIDE_MOBILE_ANDROID;?>"><?php echo _DW_LNK_GUIDE_MOBILE_ANDROID;?></a></td>
      </tr>      
    </tbody>
  </table>
  </fieldset>
  <input type="hidden" name="option" value="com_activehelper_livehelp" />
  <input type="hidden" name="id" value="<?php echo $this->row->id; ?>" />
  <input type="hidden" name="task" value="" />
  <input type="hidden" name="controller" value="agent" />  
  <input type="hidden" name="view" value="agents" />  
  <?php echo JHTML::_( 'form.token' ); ?>
</form>