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
<form action="<?php echo JRoute::_('index.php?option=com_activehelper_livehelp&view=chat_by_keyword'); ?>" method="post" name="adminForm" id="adminForm">

<table>
  <tr>  
	 <td><input type="text" name="filter_search" id="filter_search" value="" title="<?php echo JText::_('search'); ?>" /></td>
     <td><button type="submit"><?php echo JText::_('Search'); ?></button>
     <button type="button" onclick="document.id('filter_search').value='';this.form.submit();"><?php echo JText::_('Clear'); ?></button></td>            
  </tr>       
</table>

<table class="adminlist table table-striped" id="itemsList">
  <thead>
    <tr>
      <th width="10%"><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_CHAT_ID');?></th>
      <th class="title"><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_AGENT_NAME');?></th>
      <th class="title"><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_DOMAIN_NAME');?></th>
      <th class="title"><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_VISITOR_NAME'); ?></th>
      <th class="title"><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_EMAIL'); ?></th>
      <th width="10%"><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_RAITING'); ?></th>
      <th width="10%"><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_DUR_AGENTS');?></th>
      <th width="20%"><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_DATE') ?></th>
    </tr>
  </thead>
  <?php
  jimport('joomla.filter.output');
   $k = 0;
  for ($i=0, $n=count( $this->rows ); $i < $n; $i++)
  {
      $row = &$this->rows[$i];

      $link  = JFilterOutput::ampReplace('index.php?option=com_activehelper_livehelp&view=read_chat&cid='. $row->session); 
      
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
<input type="hidden" name="reportname" value="chat_by_keyword" />
<input type="hidden" name="boxchecked" value="0" />
</form>