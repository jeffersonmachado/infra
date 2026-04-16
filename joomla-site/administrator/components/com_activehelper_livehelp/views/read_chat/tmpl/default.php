<?php

/**
 * @package   ActiveHelper_LiveHelp
 * @contact   www.activehelper.com, support@activehelper.com
 * @copyright 2010 - 2017 by ActiveHelper Inc. All rights reserved
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

 
defined( '_JEXEC' ) or die( 'Restricted access' );

jimport( 'joomla.html.html.grid' );

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
<form action="<?php echo JRoute::_('index.php?option=com_activehelper_livehelp&view=reports'); ?>" method="post" name="adminForm" id="adminForm">
  
  <table class="adminlist table table-striped" id="itemsList">
  <thead>
    <tr>                    
      <caption> <?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_FULL_CHATE') ?></caption>                 
      <td width="50%" valign="top"><!-- --><table cellspacing="1"  border="0" width="100%" class="fbstat"></td>  
   </tr>       
  </thead>    
  <?php
  foreach ($this->chat as $row )
  {
    ?>
    <tr class="<?php echo "row$k"; ?>">
      <td>
        <?php echo $row["username"];?>
      </td>
      <td>
        <?php echo  $row["message"]; ?></a>
      </td>
       <td>
        <?php echo  $row["time"]; ?></a>
      </td>
    </tr>
    <?php
  }
  ?>
</table>
<!-- BEGIN: STATS -->
<div class="fbstatscover">
  <?php
    ?>
      </td>
       <td width="1%">&nbsp;</td>
      <td width="25%" valign="top"><!--  -->
        <table cellspacing="1"  border="0" width="100%" class="fbstat">
    <caption>
    <?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_CHAT_STATS') ?>
    </caption>
    <col class="col1">
    <col class="col2">
    <thead>
      <tr>
        <th><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_STATISTIC') ?></th>
        <th><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_VALUE') ?></th>
      </tr>
    </thead>
    <?php
	$yesterday = mktime(0, 0, 0, date("m")  , date("d")-1, date("Y"));
	?>
    <tbody>
      <tr>
        <td><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_DOMAIN_NAME') ?> </td>
        <td><strong><?php echo $this->server; ?></strong></td>
      </tr>
      <tr>
        <td><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_DEPT_NAME') ?></td>
        <td><strong><?php echo $this->department; ?></strong></td>
      </tr>
      <tr>
        <td><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_AGENT_NAME') ?></td>
        <td><strong><?php echo $this->agent; ?></strong></td>
      </tr>
      <tr>
        <td><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_VISITOR_NAME') ?></td>
        <td><strong><?php echo $this->visitor; ?></strong></td>
      </tr>
      <tr>
        <td><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_COMPANY') ?></td>
        <td><strong><?php echo $this->company; ?></strong></td>
      </tr>
      <tr>
        <td><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_PHONE') ?></td>
        <td><strong><?php echo $this->phone; ?></strong></td>
      </tr>
      <tr>
        <td><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_COUNTRY') ?></td>
        <td><strong><?php echo $this->country; ?></strong></td>
      </tr>
      <tr>
        <td><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_CITY') ?></td>
        <td><strong><?php echo $this->city; ?></strong></td>
      </tr>
       <tr>
        <td><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_EMAIL') ?></td>
        <td><strong><?php echo $this->email; ?></strong></td>
      </tr>
         <tr>
        <td><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_DATE') ?></td>
        <td><strong><?php echo $this->date; ?></strong></td>
      </tr>
         <tr>
        <td><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_RAITING') ?></td>
        <td><strong><?php echo $this->rating; ?></strong></td>
      </tr>
    </tbody>
  </table>
<?php echo JHTML::_( 'form.token' ); ?>
<input type="hidden" name="SQL" value="<?php echo $this->ExecSQL; ?>" />
<input type="hidden" name="visitor" value="<?php echo $this->visitor;?>" />
<input type="hidden" name="email" value="<?php echo $this->email;?>" />
<input type="hidden" name="task" value="" />
</form>