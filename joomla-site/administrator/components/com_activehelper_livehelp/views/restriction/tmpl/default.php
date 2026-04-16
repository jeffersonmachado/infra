<?php

/**
 * @package   ActiveHelper_LiveHelp
 * @contact   www.activehelper.com, support@activehelper.com
 * @copyright 2010 - 2017 by ActiveHelper Inc. All rights reserved
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

 
defined( '_JEXEC' ) or die( 'Restricted access' );
jimport( 'joomla.html.html.grid' );

?>
</script>
<form action="<?php echo JRoute::_('index.php?option=com_activehelper_livehelp&view=restrictions'); ?>" method="post" name="adminForm" id="adminForm">
   <fieldset class="adminform">
    <legend><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_ES_DETAILS') ?></legend>   
   <table class="adminlist table table-striped" id="itemsList">
    <tr>
      <td width="100" align="right" class="key">
        <?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_DOMAIN_NAME'); ?>
      </td>
      <td>
        <?php echo $this->domains; ?>
      </td>
    </tr>
    <tr>
      <td width="100" align="right" class="key">
        <?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_COUNTRY'); ?>
      </td>
      <td>
        <?php echo $this->countries; ?>
      </td>
    </tr> 
     <tr>
      <td width="100" align="right" class="key">
        <?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_BLOCK_ALL_COUNTRY') ?>
      </td>
      <td>
        <?php echo $this->block; ?>
      </td>
      </tr>           
</table> 
</fieldset>  
  <input type="hidden" name="id" value="" />
  <input type="hidden" name="task" value="" />
  <?php echo JHTML::_( 'form.token' ); ?>
</form>