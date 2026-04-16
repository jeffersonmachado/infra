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

<form action="<?php echo JRoute::_('index.php?option=com_activehelper_livehelp&view=monthly_chats'); ?>" method="post" name="adminForm" id="adminForm"> 
<table>
  <tr>
   <td><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_SDATE') ?><?php echo JHTML::_('calendar', $this->start_date, 'start_date', 'start_date', "%Y-%m-%d"); ?></td>
   <td><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_EDATE') ?><?php echo JHTML::_('calendar', $this->end_date, 'end_date', 'end_date', "%Y-%m-%d"); ?></td>
   <td><button type="submit">Search</button></td>
  </tr>       
</table>
         
<table class="adminlist table table-striped" id="itemsList">
	<thead>
		<tr>
            <th><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_AGENT_NAME'); ?></th>    
            <th><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_DOMAIN_NAME'); ?></th>  
            <th><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_STATS_TOTAL_CHATS'); ?></th>      	 
        </tr>
  </thead>
  <?php

  jimport('joomla.filter.output');

   $k = 0;
  for ($i=0, $n=count( $this->rows ); $i < $n; $i++)
  {
    $row = &$this->rows[$i];
    ?>
    <tr class="<?php echo "row$k"; ?>">
      <td>
        <?php echo $row->name;?>
      </td>
      <td>
        <?php echo $row->domain; ?></a>
      </td>
      <td>
        <?php echo $row->chats; ?></a>
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
 <input type="hidden" name="SQL" value="<?php echo urlencode($this->ExecSQL);?>" />
 <input type="hidden" name="reportname" value="monthly_chats"/>
 <input type="hidden" name="boxchecked" value="0" />    
</form>


