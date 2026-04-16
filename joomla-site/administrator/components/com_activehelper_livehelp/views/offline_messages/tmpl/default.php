<?php

/**
 * @package   ActiveHelper_LiveHelp
 * @contact   www.activehelper.com, support@activehelper.com
 * @copyright 2010 - 2017 by ActiveHelper Inc. All rights reserved
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

 
defined( '_JEXEC' ) or die( 'Restricted access' );
jimport( 'joomla.html.html.grid' );
jimport('joomla.html.html.bootstrap');
jimport('joomla.filesystem.folder');
jimport('joomla.filesystem.file');

include_once (JPATH_COMPONENT_ADMINISTRATOR .'/language/english.php');

?>

<form action="<?php echo JRoute::_('index.php?option=com_activehelper_livehelp&view=offline_messages'); ?>" method="post" name="adminForm" id="adminForm">
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
      <th class="title"><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_ABOUT_NAME') ?></th>
      <th class="title"><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_COMPANY') ?></th>
      <th class="title"><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_EMAIL') ?></th>
      <th class="title"><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_PHONE') ?></th>
      <th class="title"><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_DOMAIN_NAME') ?></th>
      <th class="title"><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_DATE') ?></th>
      <th class="title"><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_MSG') ?></th>
      <th class="title"><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_ANW') ?></th>
      <th class="title"><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_DELETE') ?></th>
    </tr>
  </thead>
  <?php

  jimport('joomla.filter.output');

   $k = 0;
  for ($i=0, $n=count( $this->rows ); $i < $n; $i++)
  {
    $row = &$this->rows[$i];
   
   
     $link_delete = JFilterOutput::ampReplace('index.php?option=com_activehelper_livehelp&task=reports.delete_offline_message&cid='. $row->id);
     $link        = JFilterOutput::ampReplace('index.php?option=com_activehelper_livehelp&task=reports.answer_message&cid='. $row->id .'&cstatus='. $row->answered);  
     
     //$link = JRoute::_('index.php?option=com_activehelper_livehelp&task=reports.answer_message&cid='. $row->id .'&cstatus='. $row->answered) ;          
     //$link_delete = JRoute::_('index.php?option=com_activehelper_livehelp&task=reports.delete_offline_message&cid='. $row->id);  
     
     $uri =& JURI::getInstance();
     $img_path =  JURI::root().'administrator/components/com_activehelper_livehelp/images/';
     $img = $row->answered .'.gif';
                                              
    ?>
    <tr class="<?php echo "row$k"; ?>">
      <td><?php echo $row->name;?></td>
      <td><?php echo $row->company;?></td>
      <td><?php echo $row->email; ?></td>
      <td><?php echo $row->phone; ?></td>
      <td><?php echo $row->domain; ?></td>
       <td><?php echo $row->datetime; ?></td>
      <td><?php echo $row->message; ?></td>       
      <td align="center" > <a href="<?php echo $link; ?>"><?php echo JHTML::_('image', $img_path . $img ,'status image'); ?> </a></td>
      <td><a href="<?php echo $link_delete; ?>"><?php echo JText::_('Delete') ?> </a></td>
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
<input type="hidden" name="reportname" value="offline_messages" />
<input type="hidden" name="boxchecked" value="0" />
</form>


