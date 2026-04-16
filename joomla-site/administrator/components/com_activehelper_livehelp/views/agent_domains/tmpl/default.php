<?php

/**
 * @package   ActiveHelper_LiveHelp
 * @contact   www.activehelper.com, support@activehelper.com
 * @copyright 2010 - 2017 by ActiveHelper Inc. All rights reserved
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
 
 
defined( '_JEXEC' ) or die( 'Restricted access' );

?>
<form action="<?php echo JRoute::_('index.php?option=com_activehelper_livehelp&view=agents'); ?>" method="post" name="adminForm" id="domain-form">
<table class="adminlist">
  <thead>
    <tr>
      <th width="15%"><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_DOMAIN_ID'); ?></th>
      <th class="title"><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_DOMAIN_NAME'); ?></th>
      <th width="5%" nowrap="nowrap"><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_ENABLED');?></th>
    </tr>
  </thead>
  <?php
  foreach ($this->rows as $row )
  {
    $checked = JHTML::_('grid.id', $i, $row["id_domain"]);     
    $link =  JRoute::_('index.php?option=com_activehelper_livehelp&task=agent.edit_domain&cid='. $row["id_domain"] .'&cuser='. $this->id_user .'&cstatus='. $row["enabled"] .'&cid_domain_user='. $row["id_domain_user"]);
    ?>
    <tr class="<?php echo "row$k"; ?>">
      <td>
        <?php echo $row["id_domain"];?>
      </td>
      <td>
        <?php echo  $row["name"]; ?></a>
      </td>
      <td>
        <a href="<?php echo $link; ?>"><?php echo  $row["enabled"]; ?></a>
      </td>
    </tr>
    <?php
  }
  ?>
</table>
  <input type="hidden" name="id_user" value="<?php echo $this->id_user; ?>" />
  <input type="hidden" name="task" value="" />
  <?php echo JHTML::_( 'form.token' ); ?>
</form>