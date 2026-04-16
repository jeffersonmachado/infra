<?php

/**
 * @package   ActiveHelper_LiveHelp
 * @contact   www.activehelper.com, support@activehelper.com
 * @copyright 2010 - 2017 by ActiveHelper Inc. All rights reserved
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

 
defined( '_JEXEC' ) or die( 'Restricted access' );

jimport( 'joomla.html.html.grid' );
JHTML::_('behavior.tooltip');

?>

<form action="<?php echo JRoute::_('index.php?option=com_activehelper_livehelp&view=domains'); ?>" method="post" name="adminForm" id="adminForm">
<table>
		<div class="filter-search fltlft">
			<label class="filter-search-lbl" for="filter_search"><?php echo  JText::_('Filter') ?></label>
			<input type="text" name="filter_search" id="filter_search"  title="<?php echo JText::_('Search'); ?>" />
			<button type="submit"><?php echo  JText::_('Search')?></button>
			<button type="button" onclick="document.id('filter_search').value='';this.form.submit();"><?php echo  JText::_('Clear')?></button>
		</div>
        
</table>
<table class="adminlist table table-striped" id="itemsList">
         <thead>
			<tr>                    
                <th width="1%"><input type="checkbox" name="toggle" value="" onclick="checkAll(<?php echo count( $this->rows ); ?>);" /></th>
				<th width="15%"><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_DOMAIN_ID'); ?></th>
                <th class="title"><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_ABOUT_NAME'); ?></th>                
                <th width="10%"><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_TM'); ?></th>
                <th width="10%"><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_TS'); ?></th>
                <th width="1%"><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_STATUS'); ?></th>                				
			</tr>
		</thead>
  <?php
  jimport('joomla.filter.output');
  $k = 0;
  for ($i=0, $n=count( $this->items ); $i < $n; $i++)
  {
    $row = &$this->items[$i];
    $checked = JHTML::_('grid.id', $i, $row->id_domain );
    //$published = JHTML::_('grid.published', $row, $i );
	
        
     
     $link            = JRoute::_('index.php?option=com_activehelper_livehelp&task=domain.edit&cid[]='. $row->id_domain);                           
     $link_mod        = JRoute::_('index.php?option=com_activehelper_livehelp&task=domain.script_mod&cid='. $row->id_domain.'&clan=en&clver[]=1');     
     $link_tracking   = JRoute::_('index.php?option=com_activehelper_livehelp&task=domain.tracking_module&cid='. $row->id_domain);

    
    $img_path ='../administrator/components/com_activehelper_livehelp/images/';
    ?>
    <tr class="<?php echo "row$k"; ?>">
      <td>
        <?php echo $checked;?>
      </td>
      <td>
        <?php echo $row->id_domain;?>
      </td>
      <td>
        <a href="<?php echo $link; ?>"><?php echo $row->name; ?></a>
      </td>   
       <td>     
         <li><a href="<?php echo $link_mod ?>"> <?php echo JText::_('Module') ?></a></li>
      </td>   
      <td>             
        <a href="<?php echo $link_tracking; ?>"><?php echo JText::_('Javascript') ?></a>
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