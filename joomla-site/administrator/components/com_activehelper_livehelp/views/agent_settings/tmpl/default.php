<?php

/**
 * @package   ActiveHelper_LiveHelp
 * @contact   www.activehelper.com, support@activehelper.com
 * @copyright 2010 - 2017 by ActiveHelper Inc. All rights reserved
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */


defined( '_JEXEC' ) or die( 'Restricted access' );

jimport('joomla.html.html.bootstrap');

?>
<script language="javascript" type="text/javascript">
function submitbutton(pressbutton) {
		submitform(pressbutton);
	}
</script> 
<form action="index.php" method="post" name="adminForm" id="adminForm" enctype="multipart/form-data">  
  <fieldset class="adminform">  
   <ul class="nav nav-tabs" id="myTab">
    <li class="active"><a data-toggle="tab" href="#home"><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_AGENT_STATUS') ?></a></li>
    <li><a data-toggle="tab" href="#schedule"><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_AGENT_TIME') ?></a></li>
  </ul>     
  <?php echo JHtml::_('bootstrap.startPane', 'myTab', array('active' => 'home'));  ?>
  <?php echo JHtml::_('bootstrap.addPanel', 'myTab', 'home');  ?>
  <table class="admintable">
     <tr>     
      <td width="200" class="key> <label for="languages"> <?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_LANGUAGES');?></td>
      <td>
        <?php echo $this->languajes; ?>
      </td>
    </tr>
     <tr>
      <td width="200" class="key> <label for="online"> <?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_ONLINE_IMG') . ' ( ' . $this->status_default_img_type . ' )'  ?> </td>
      <td class="agent_image_online">       
        <?php echo JHTML::_('image', $this->img_path . $this->online_img , 'online' ); ?> <input type="file" name="online" value="" /> 
      </td>
    </tr>
     <tr>
     <td width="200" class="key> <label for="offline"> <?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_OFFLINE_IMG') . ' ( ' . $this->status_default_img_type . ' )'  ?> </td>
      <td class="agent_image_offline">
        <?php echo JHTML::_('image',  $this->img_path . $this->offline_img , 'offline'); ?>  <input type="file" name="offline" value="" />
      </td>
    </tr>
     <tr>
     <td width="200" class="key> <label for="away"> <?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_AWAY_IMG') . ' ( ' . $this->status_default_img_type . ' )'  ?> </td>
      <td class="agent_image_away">
       <?php echo JHTML::_('image', $this->img_path . $this->away_img , 'away' ); ?>  <input type="file" name="away" value="" />       
      </td>
    </tr>
   <tr>
      <td width="200" class="key> <label for="brb"> <?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_BRB_IMG') . ' ( ' . $this->status_default_img_type . ' )'  ?> </td> 
      <td class="agent_image_brb">
        <?php echo JHTML::_('image', $this->img_path . $this->brb_img, 'brb' ); ?> <input type="file" name="brb" value="" />
      </td>
    </tr>
    </table>
   <?php echo JHtml::_('bootstrap.endPanel'); ?>    
   <?php echo JHtml::_('bootstrap.addPanel', 'myTab', 'schedule');  ?>
    <table class="admintable">
     <tr>     
      <td width="250" class="key> <label for="schedule"> <?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_ALLOW_TIME');?></td>
      <td>
        <?php echo $this->schedule; ?>
      </td>
    </tr>
    <tr>
       <td width="250" class="key> <label for="initial_time"> <?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_INITIAL_TIME');?></td>
      <td>
        <input class="text_area" type="text" name="int_time" id="livehelp_name" size="8" maxlength="8" value="<?php echo $this->int_time;?>" />
      </td>
    </tr>
    <tr>
       <td width="250" class="key> <label for="end_time"> <?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_END_TIME');?></td>
      <td>
        <input class="text_area" type="text" name="end_time" id="end_time" size="8" maxlength="8" value="<?php echo $this->end_time;?>" />
      </td>
    </tr>
    </table>
   <?php echo JHtml::_('bootstrap.endPanel'); ?>                
   <?php echo JHtml::_('bootstrap.endPane', 'myTab'); ?>
  </fieldset>
  <input type="hidden" name="option" value="com_activehelper_livehelp" />
  <input type="hidden" name="id_agent" value="<?php echo $this->id_agent; ?>" />
  <input type="hidden" name="task" value="" />
  <input type="hidden" name="controller" value="agent" />  
  <input type="hidden" name="view" value="agents" />     
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

      $(".agent_image_online img").attr("src", src + "online.gif?cache=<?php echo $random; ?>");
      $(".agent_image_offline img").attr("src", src + "offline.gif?cache=<?php echo $random; ?>");
      $(".agent_image_away img").attr("src", src + "away.gif?cache=<?php echo $random; ?>");
      $(".agent_image_brb img").attr("src", src + "brb.gif?cache=<?php echo $random; ?>");      
    });
  });
</script>