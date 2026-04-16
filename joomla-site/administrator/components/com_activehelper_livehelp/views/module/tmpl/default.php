<?php

/**
 * @package   ActiveHelper_LiveHelp
 * @contact   www.activehelper.com, support@activehelper.com
 * @copyright 2010 - 2017 by ActiveHelper Inc. All rights reserved
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

 
defined( '_JEXEC' ) or die( 'Restricted access' );

jimport('joomla.html.html.bootstrap');

include_once (JPATH_COMPONENT_ADMINISTRATOR .'/language/english.php');

$editor =& JFactory::getEditor();

$img_path ='../administrator/components/com_activehelper_livehelp/images/';

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
</style>
<div class="fbwelcome">
 <?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_FLW') ?> <?php echo $this->path_module ?> 
 <h3><a href = <?php echo $this->path_download_module;?> <span> <?php echo $this->path_download_module ?> </span></a> </div></h3>
</div>
<form action="index.php" method="post" name="adminForm" id="adminForm">
    <input type="hidden" name="option" value="com_activehelper_livehelp" />
    <input type="hidden" name="task" value="" />  
    <input type="hidden" name="controller" value="domain" />   
    <input type="hidden" name="view" value="domains" />       
  <?php echo JHTML::_( 'form.token' ); ?>
</form>