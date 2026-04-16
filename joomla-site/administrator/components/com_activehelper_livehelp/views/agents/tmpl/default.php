<?php

/**
 * @package   ActiveHelper_LiveHelp
 * @contact   www.activehelper.com, support@activehelper.com
 * @copyright 2010 - 2017 by ActiveHelper Inc. All rights reserved
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
 
defined( '_JEXEC' ) or die( 'Restricted access' );
JHTML::_('behavior.tooltip');

?>
<form action="<?php echo JRoute::_('index.php?option=com_activehelper_livehelp&view=agents'); ?>" method="post" name="adminForm" id="adminForm">
<table>
	<div class="filter-search fltlft">
			<label class="filter-search-lbl" for="filter_search"><?php echo  JText::_('Filter') ?></label>
			<input type="text" name="filter_search" id="filter_search"  title="<?php echo JText::_('Search'); ?>" />
			<button type="submit"><?php echo  JText::_('Search')?></button>
			<button type="button" onclick="document.id('filter_search').value='';this.form.submit();"><?php echo  JText::_('Clear')?></button>
		</div>
		</div>
</table>
<table class="adminlist table table-striped" id="itemsList">
  <thead>
    <tr>
      <th width="20">
        <input type="checkbox" name="toggle"
             value="" onclick="checkAll(<?php echo count( $this->rows ); ?>);" />
      </th>
      <th width="15%"><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_AGENT_ID'); ?></th>
      <th class="title"><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_USERNAME'); ?></th>
      <th class="title"><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_EMAIL'); ?></th>
      <th class="title"><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_DEPT_NAME'); ?></th>
      <th width="5%" nowrap="nowrap"><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_STATUS');?></th>
    </tr>
  </thead>

  <?php
  jimport('joomla.filter.output');
  $k = 0;

  for ($i=0, $n=count( $this->items ); $i < $n; $i++)
  {
    $row = &$this->items[$i];
    $checked = JHTML::_('grid.id', $i, $row->id);
    //$published = JHTML::_('grid.published', $row, $i );
  	
    $link =  JRoute::_('index.php?option=com_activehelper_livehelp&task=agent.edit&cid[]='. $row->id );
    ?>
    <tr class="<?php echo "row$k"; ?>">
      <td>
        <?php echo $checked;?>
      </td>
      <td>
        <?php echo $row->id;?>
      </td>
      <td>
        <a href="<?php echo $link; ?>"><?php echo $row->username; ?></a>
      </td>
      <td>
        <?php echo $row->email; ?></a>
      </td>
      <td>
        <?php echo $row->department; ?></a>
      </td>
      <td>
        <?php echo $row->status; ?>
      </td>
    </tr>
    <?php
    $k = 1 - $k;
  }
  ?>
<tfoot>
	<tr>
		<td colspan="7"><?php echo $this->pagination->getListFooter(); ?></td>
	</tr>
</tfoot>
</table>
<?php echo JHTML::_( 'form.token' ); ?>
<input type="hidden" name="task" value="" />
<input type="hidden" name="boxchecked" value="0" />
</form>