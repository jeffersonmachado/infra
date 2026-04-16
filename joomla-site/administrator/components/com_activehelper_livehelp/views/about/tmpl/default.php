<?php 

/**
 * @package   ActiveHelper_LiveHelp
 * @contact   www.activehelper.com, support@activehelper.com
 * @copyright 2010 - 2017 by ActiveHelper Inc. All rights reserved
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */


 defined('_JEXEC') or die('Restricted access');

 JHTML::_('behavior.tooltip');
 jimport('joomla.html.html.bootstrap');

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
<form action="<?php echo JRoute::_('index.php?option=com_activehelper_livehelp&view=reports'); ?>" method="post" name="adminForm" id="domain-form">
<!-- BEGIN: STATS -->
<div class="fbstatscover">
  <?php
    ?>
        <table class="adminlist table table-striped" id="itemsList">
    <caption>
    <?php echo  JText::_('COM_ACTIVEHELPER_LIVEHELP_ABOUT');?>
    </caption>
    <col class="col1">
    <col class="col2">
    <thead>
      <tr>
        <th><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_LIVEHELP');?></th>
        <th><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_INFO'); ?></th>
      </tr>
    </thead>
    <?php
      
    # images
    $uri =JURI::getInstance();
    $img_path =  JURI::root().'administrator/components/com_activehelper_livehelp/images/';
    
   	?>

    <tbody>
      <tr>
        <td><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_ABOUT_NAME');?> </td>
        <td><?php echo _AT_VAL_NAME;?></td>
      </tr>
      <tr>
        <td><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_ABOUT_VERSION');?></td>
        <td><?php echo _AT_VAL_VERSION;?></td>
      </tr>
      <tr>
        <td><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_ABOUT_UPDATE');?></td>
        <td> <a target="_blank" href="<?php echo _AT_VAL_UPDATE ?>"><?php echo _AT_VAL_UPDATE ?></a></td>
      </tr>
      <tr>
        <td><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_ABOUT_HELP') ?></td>
        <td> <a target="_blank" href="<?php echo _AT_VAL_HELP;?>"><?php echo _AT_VAL_HELP;?></a></td>
      </tr>
       <tr>
        <td><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_ABOUT_SUPPORT') ?></td>
        <td> <a target="_blank" href="<?php echo _AT_VAL_SUPPORT; ?>"><?php echo _AT_VAL_SUPPORT; ?></a></td>
      </tr>
         <tr>
        <td><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_ABOUT_TWITTER') ?></td>
        <td> <a target="_blank" href="<?php echo _AT_VAL_TWITTER; ?>"><?php echo JHTML::_('image', $img_path . 'twitter.png', 'twitter' ); ?></a></td>        
      </tr>
      <tr>
        <td><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_ABOUT_FACEBOOK') ?></td>
        <td> <a target="_blank" href="<?php echo _EXT_LINK_FACEBOOK; ?>"><?php echo _EXT_LINK_FACEBOOK; ?></a></td>   
      </tr>
         <tr>
        <td><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_ABOUT_LICENSE') ?></td>
       <td> <a href="<?php echo _AT_VAL_LICENSE_LINK; ?>"><?php echo _AT_VAL_LICENSE;?></a></td>
      </tr>
       </tr>
         <tr>
        <td><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_ABOUT_COPYRIGHT'); ?></td>
        <td><?php echo _AT_VAL_COPYRIGHT; ?></td>
      </tr>
    </tbody>
  </table>
<?php echo JHTML::_( 'form.token' ); ?>
<input type="hidden" name="task" value="" />
</form>