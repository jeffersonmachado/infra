<?php

/**
 * @package   ActiveHelper_LiveHelp
 * @contact   www.activehelper.com, support@activehelper.com
 * @copyright 2010 - 2017 by ActiveHelper Inc. All rights reserved
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

 
 
// No direct access
defined('_JEXEC') or die('Restricted access');
JHtml::_('behavior.tooltip');

?>
<form action="index.php" method="post" name="adminForm" id="adminForm">
  <fieldset class="adminform">
    <legend>Details</legend>
    <table class="admintable">
    <tr>
      <td width="200" class="key> <label for="name"> <?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_ABOUT_NAME');?></td>
      <td>
        <input class="text_area" type="text" name="name" id="name" size="50" maxlength="250" value="<?php echo $this->row->name;?>" />
        <td width="300" align="left" class="hint"> <?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_DOMAIN_ALERT')?> </td> 
      </td>       
    </tr>
   <tr>
      <td width="200" class="key> <label for="Status"> <?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_STATUS');?></td>
      <td>
        <?php echo $this->status; ?>
      </td>
    </tr>
    </table>
  </fieldset>
     <input type="hidden" name="option" value="com_activehelper_livehelp" />
    <input type="hidden" name="id_domain" value="<?php echo $this->row->id_domain; ?>" />
    <input type="hidden" name="task" value="save" />
    <input type="hidden" name="boxchecked" value="0" />
    <input type="hidden" name="controller" value="domain" />  
    <input type="hidden" name="view" value="domains" />       
  <?php echo JHTML::_( 'form.token' ); ?>
</form>