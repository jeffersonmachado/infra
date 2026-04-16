<?php

/**
 * @package   ActiveHelper_LiveHelp
 * @contact   www.activehelper.com, support@activehelper.com
 * @copyright 2010 - 2017 by ActiveHelper Inc. All rights reserved
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

 
defined( '_JEXEC' ) or die( 'Restricted access' );

JHTML::_('behavior.calendar');
include_once (JPATH_COMPONENT_ADMINISTRATOR .'/language/english.php');

?>
<style>
.lc-stats-row {
    padding: 0;
    text-align: center;
    width: 100%;
    display: table;
    box-sizing: border-box;
}

.lc-button:focus {
    outline: none;
}

.wbl-sh404sef-cp-more-stats + .wbl-sh404sef-cp-more-button:after {
    content: "\2304";
}

.wbl-sh404sef-cp-more-stats.in + .wbl-sh404sef-cp-more-button:after {
    content: "\2303";
}

.lc-more-stats {
    margin: 0;
    padding: 0;
    width: 100%;
}

.lc-stats-row {
    padding: 0;
    text-align: center;
    width: 100%;
    display: table;
    box-sizing: border-box;
}

.lc-counter {
    margin: 0 !important;
    width: 25%;
    display: table-cell;
    background: #FFFFFF;
    border: 1em solid #F5F5F5;
}

.lc-counter-inner {
    padding: 1.5em 2em;
}

span.lc-counter-number,
span.lc-counter-title {
    display: block;
    text-align: center;
}

span.lc-counter-title {
    font-size: 1em;
    color: #BBBBBB;
    margin-top: 0.5em;
}

.fbwelcome {
  clear:both;
  margin-bottom:10px;
  padding:10px;
  font-size:14px;
  color:#536482;
  line-height:140%;
  border:1px solid #ddd;
  background-color: #728fbd;
}

table.fbstat {
  background-color:#FFFFFF;
  border:1px solid #ddd;
  padding:1px;
  width:100%;
}
table.fbstat th {
  background:#b6c0db;;
  border-bottom:1px solid #CCC;
  border-top:1px solid #EEE;
  color:#666;
  font-size:12px;
  padding:3px 4px;
  text-align:left;
}
table.fbstat td {
  font-size:12px;
  line-height:140%;
  padding:4px;
  text-align:left;
}
table.fbstat caption {
  clear:both;
  font-size:14px;
  font-weight:bold;
  margin:10px 0 2px 0;
  padding:2px;
  text-align:left;
}
table.fbstat .col1 {
  background-color:#F1F3F5;
  
}
table.fbstat .col2 {
	background-color:#F1F3F5;
}

.title{  color: #7c795d; font-family: 'Trocchi', serif; font-size: 18px; font-weight: normal; line-height: 24px; margin: 0 0 24px; display: block;text-align: center;} 

</style>

<div class="fbwelcome">
    <table width="100%"  border="0" cellspacing="1" class="fbstat">
      <tbody>
        <tr>
		<?php
          if ($this->core ==true){
		 ?>	  			 
	        <td width="94%"><a href="<?php echo $this->link ?>"><span class="title"><?php echo $this->live_chat_core_ver;?></p></td>
	     <?php		
		  } else
		  {   ?> 	        
			<td width="94%"><a href="<?php echo $this->link ?>" target="_blank"><span class="title"><?php echo $this->live_chat_core_ver;?></p></td>
		   <?php  
		     }
           ?>          
          <td width="100%"><img src="http://www.activehelper.com/logo-main.png" width="300" height="84" /></td>
        </tr>
      </tbody>
    </table>       
</div>


	<div id="lc-more-stats">
		<div class="lc-stats-row">
			<div class="lc-counter"><a href="<?php echo "index.php?option=com_activehelper_livehelp&view=domains" ?>">
				<div class="lc-counter-inner "><span class="lc-counter-number"><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_STATS_TOTAL_DOMAINS') ?></span>
				<span class="lc-counter-title"><?php echo $this->domains;  ?></span></div></div>
				
			<div class="lc-counter"><a href="<?php echo "index.php?option=com_activehelper_livehelp&view=agents" ?>">
				<div class="lc-counter-inner "><span class="lc-counter-number"><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_STATS_TOTAL_AGENTS') ?></span>
				<span class="lc-counter-title"><?php echo $this->agents;  ?></span></div></div>	
				
			<div class="lc-counter"><a href="<?php echo "index.php?option=com_activehelper_livehelp&view=chats_by_dept" ?>">
				<div class="lc-counter-inner "><span class="lc-counter-number"><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_STATS_TOTAL_DEPARTMENTS') ?></span>
				<span class="lc-counter-title"><?php echo $this->departments;  ?></span></div></div>	

			<div class="lc-counter"><a href="<?php echo "index.php?option=com_activehelper_livehelp&view=time_by_chat" ?>">
				<div class="lc-counter-inner "><span class="lc-counter-number"><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_STATS_TOTAL_CHATS') ?></span>
				<span class="lc-counter-title"><?php echo $this->chats;  ?></span></div></div>				
	      </div>		  	 
		  
		<div class="lc-stats-row">
			<div class="lc-counter"><a href="<?php echo "index.php?option=com_activehelper_livehelp&view=failed_chats" ?>">
				<div class="lc-counter-inner "><span class="lc-counter-number"><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_FAIL_CHATS') ?></span>
				<span class="lc-counter-title"><?php echo $this->fail_chats;  ?></span></div></div>		
				
			<div class="lc-counter"><a href="<?php echo "index.php?option=com_activehelper_livehelp&view=time_by_chat" ?>">
				<div class="lc-counter-inner "><span class="lc-counter-number"><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_AVG_CHAT_RATING') ?></span>
				<span class="lc-counter-title"><?php echo $this->avg_chat_rating;  ?></span></div></div>	
				
			<div class="lc-counter"><a href="<?php echo "index.php?option=com_activehelper_livehelp&view=time_by_chat" ?>">
				<div class="lc-counter-inner "><span class="lc-counter-number"><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_STATS_TOTAL_CHATS_TODAY') ?></span>
				<span class="lc-counter-title"><?php echo $this->chats_today;  ?></span></div></div>		
				
			<div class="lc-counter"><a href="<?php echo "index.php?option=com_activehelper_livehelp&view=time_by_chat" ?>">
				<div class="lc-counter-inner "><span class="lc-counter-number"><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_STATS_TOTAL_VISITORS_TODAY') ?></span>
				<span class="lc-counter-title"><?php echo $this->visitors_today;  ?></span></div></div>	
										
		</div>		
		
		<div class="lc-stats-row">
			<div class="lc-counter"><a href="<?php echo "index.php?option=com_activehelper_livehelp&view=monthly_chats" ?>">
				<div class="lc-counter-inner "><span class="lc-counter-number"><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_MONTHLY_CHATS_STATS') ?></span>
				<span class="lc-counter-title"><?php echo $this->montly_chats;  ?></span></div></div>		
				
			<div class="lc-counter"><a href="<?php echo "index.php?option=com_activehelper_livehelp&view=monthly_chats" ?>">
				<div class="lc-counter-inner "><span class="lc-counter-number"><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_WEEKLY_CHATS_STATS') ?></span>
				<span class="lc-counter-title"><?php echo $this->current_week_chats;  ?></span></div></div>	
				
			<div class="lc-counter"><a href="<?php echo "index.php?option=com_activehelper_livehelp&view=offline_messages" ?>">
				<div class="lc-counter-inner "><span class="lc-counter-number"><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_WEEK_OFF_MSG_STATS') ?></span>
				<span class="lc-counter-title"><?php echo $this->current_week_offline_messages;  ?></span></div></div>		
				
			<div class="lc-counter"><a href="<?php echo "index.php?option=com_activehelper_livehelp&view=failed_chats" ?>">
				<div class="lc-counter-inner "><span class="lc-counter-number"><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_WEEK_FAILED_STATS') ?></span>
				<span class="lc-counter-title"><?php echo $this->weekly_failed_chats;  ?></span></div></div>	
										
		</div>		
	
		
