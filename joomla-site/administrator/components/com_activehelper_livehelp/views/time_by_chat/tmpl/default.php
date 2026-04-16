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
<form action="<?php echo JRoute::_('index.php?option=com_activehelper_livehelp&view=time_by_chat'); ?>" method="post" name="adminForm" id="adminForm">
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
    <tr>                  
      <th width="10%"><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_CHAT_ID') ?></th>
      <th class="title"><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_AGENT_NAME') ?></th>
      <th class="title"><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_DOMAIN_NAME') ?></th>
      <th class="title"><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_VISITOR_NAME') ?></th>
      <th class="title"><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_EMAIL') ?></th>
      <th width="10%"><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_RAITING') ?></th>
      <th width="10%"><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_DUR_AGENTS') ?></th>
      <th width="20%"><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_DATE') ?></th>
      <th width="5%"><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_ES_DELETE') ?></th>
    </tr>
  </thead>
  <?php
  jimport('joomla.filter.output');
   $k = 0;
  for ($i=0, $n=count( $this->rows ); $i < $n; $i++)
  {
    $row = &$this->rows[$i];

     $link_delete = JFilterOutput::ampReplace('index.php?option=com_activehelper_livehelp&task=reports.delete&cid='. $row->session);
     $link        = JFilterOutput::ampReplace('index.php?option=com_activehelper_livehelp&view=read_chat&cid='. $row->session);  
                   
    ?>
    <tr class="<?php echo "row$k"; ?>">
    <td>
      <a href="<?php echo $link; ?>"><?php echo $row->session;?></a>
    </td>
     <td>
        <?php echo $row->name;?>
      </td>
      <td>
        <?php echo $row->domain; ?></a>
      </td>
         <td>
        <?php echo $row->visitor; ?></a>
      </td>
      <td>
        <?php echo $row->email; ?></a>
      </td>
      <td>
        <?php echo $row->rating; ?></a>
      </td>
      <td>
        <?php echo $row->time; ?></a>
      </td>
      <td>
        <?php echo $row->date; ?></a>
      </td>
      <td>
     <a href="<?php echo $link_delete; ?>"><?php echo JText::_('Delete') ?> </a>
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
  <input type="hidden" name="reportname" value="time_by_chat" />
  <input type="hidden" name="boxchecked" value="0" />
</form>