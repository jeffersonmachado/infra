<?php

/**
 * @package   ActiveHelper_LiveHelp
 * @contact   www.activehelper.com, support@activehelper.com
 * @copyright 2010 - 2017 by ActiveHelper Inc. All rights reserved
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

 
defined( '_JEXEC' ) or die( 'Restricted access' );

JHTML::_('behavior.calendar');

?>

<style>

.button-container {
    width: 1100px;
    background: #FBFBFB;
    overflow-y: auto;
}

.button-container > a {
    width: 160px;
    height: 120px;
    float: left;
    background: #FBFBFB;   
    margin: 10px;
}

.lc-stats-row {
    padding: 0;
    text-align: center;
    width: 100%;
    display: table;
    box-sizing: border-box;
}

.lc-button:focus {
    outline: none;
}

.wbl-sh404sef-cp-more-stats + .wbl-sh404sef-cp-more-button:after {
    content: "\2304";
}

.wbl-sh404sef-cp-more-stats.in + .wbl-sh404sef-cp-more-button:after {
    content: "\2303";
}

.lc-more-stats {
    margin: 0;
    padding: 0;
    width: 100%;
}

.lc-stats-row {
    padding: 0;
    text-align: center;
    width: 100%;
    display: table;
    box-sizing: border-box;
}

.lc-counter {
    margin: 0 !important;
    width: 25%;
    display: table-cell;
    background: #FFFFFF;
    border: 1em solid #F5F5F5;
}

.lc-counter-inner {
    padding: 1.5em 2em;
}

span.lc-counter-number,
span.lc-counter-title {
    display: block;
    text-align: center;
}

span.lc-counter-title {
    font-size: 1em;
    color: #BBBBBB;
    margin-top: 0.5em;
}

.fbwelcome {
  clear:both;
  margin-bottom:10px;
  padding:10px;
  font-size:14px;
  color:#536482;
  line-height:140%;
  border:1px solid #ddd;
  background-color: #728fbd;
}

table.fbstat {
  background-color:#FFFFFF;
  border:1px solid #ddd;
  padding:1px;
  width:100%;
}
table.fbstat th {
  background:#b6c0db;;
  border-bottom:1px solid #CCC;
  border-top:1px solid #EEE;
  color:#666;
  font-size:12px;
  padding:3px 4px;
  text-align:left;
}
table.fbstat td {
  font-size:12px;
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
	background-color:#F1F3F5;
}

</style>

<form action="<?php echo JRoute::_('index.php?option=com_activehelper_livehelp&view=reports'); ?>" method="post" name="adminForm" id="adminForm" enctype="multipart/form-data">
  <fieldset class="adminform">  
	<ul class="nav nav-tabs" id="myTab">
    <li class="active"><a data-toggle="tab" href="#home"><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_SERVER_LANGUAGES_INST') ?></a></li>
    <li><a data-toggle="tab" href="#Languages"><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_SERVER_LANGUAGES') ?></a></li>
  </ul>     
  <?php echo JHtml::_('bootstrap.startPane', 'myTab', array('active' => 'home'));  ?>
  <?php echo JHtml::_('bootstrap.addPanel', 'myTab', 'home');  ?>    
    
	 <table class="admintable">
     <tr>     
      <td width="200" class="key> <label for="languages"> <?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_SERVER_LANGUAGES_EDIT');?></td>
      <td>
        <?php echo $this->languages; ?>
      </td>
	 </tr> 
    </table>	
	
  <?php echo JHtml::_('bootstrap.endPanel'); ?>    
  <?php echo JHtml::_('bootstrap.addPanel', 'myTab', 'Languages');  ?>
   
     <table class="admintable">
     <tr>     
      <td width="200" class="key> <label for="available_languages"> <?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_SERVER_LANGUAGES_EDIT');?></td>
      <td>
        <?php echo $this->available_languages; ?>
      </td>
	 </tr> 
       <tr>
      <td width="200" class="key> <label for="enctype="multipart/form-data"></td> 
        <input type="file" name="upload_file" value="" />       
    </tr>
    </table>	
	
 <?php echo JHtml::_('bootstrap.endPanel'); ?>   
 <?php echo JHtml::_('bootstrap.endPane', 'myTab'); ?>
 
 </fieldset>  
  <?php echo JHTML::_( 'form.token' ); ?>
  <input type="hidden" name="task" value="" />
</form>