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
.button-container {
    width: 1100px;
    background: #FBFBFB;
    overflow-y: auto;
}

.button-container > a {
    width: 160px;
    height: 120px;
    float: left;
    background: #FBFBFB;   
    margin: 10px;
}

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
</style>

 <div class="fbwelcome">
    <table width="100%"  border="0" cellspacing="1" class="fbstat">
      <tbody>
        <tr>
          <td width="94%"><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_TOP4')?></td>
        </tr>
      </tbody>
    </table>       
</div>


<div  style="border:1px solid #ddd; background:#FBFBFB;">
  <table>
    <tr >
          <?php
     	       $link_monthly_chats    = JFilterOutput::ampReplace( 'index.php?option=com_activehelper_livehelp&view=monthly_chats');
               $link_time_by_chat     = JFilterOutput::ampReplace( 'index.php?option=com_activehelper_livehelp&view=time_by_chat');
               $link_failed_chats     = JFilterOutput::ampReplace( 'index.php?option=com_activehelper_livehelp&view=failed_chats');
               $link_unanswered_chats = JFilterOutput::ampReplace( 'index.php?option=com_activehelper_livehelp&view=unanswered_chats');			   			                 
			   $link_chats_by_dept    = JFilterOutput::ampReplace( 'index.php?option=com_activehelper_livehelp&view=chats_by_dept');
			   $link_chats_by_country = JFilterOutput::ampReplace( 'index.php?option=com_activehelper_livehelp&view=chats_by_country');
               $link_chat_by_keyword  = JFilterOutput::ampReplace( 'index.php?option=com_activehelper_livehelp&view=chat_by_keyword');
               $link_offline_messages = JFilterOutput::ampReplace( 'index.php?option=com_activehelper_livehelp&view=offline_messages');           
               $link_restrictions     = JFilterOutput::ampReplace( 'index.php?option=com_activehelper_livehelp&view=restrictions');

          	?>
		<div class="button-container">	
           <a href = <?php echo $link_monthly_chats;?>  style = "text-decoration:none;" title = "<?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_R_MONTHLY_CHATS') ?>"> <img src = "components/com_activehelper_livehelp/images/1.png"  align = "top" border = "1"/><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_R_MONTHLY_CHATS') ?></a>        
           <a href = <?php echo $link_time_by_chat;?>  style = "text-decoration:none;" title = "<?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_R_TIME_BY_CHAT') ?>"> <img src = "components/com_activehelper_livehelp/images/2.png" align = "top" border = "1"/><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_R_TIME_BY_CHAT') ?></a>            
           <a href = <?php echo $link_failed_chats;?> style = "text-decoration:none;" title = "<?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_R_FAILED_CHATS') ?>"> <img src = "components/com_activehelper_livehelp/images/3.png"  align = "top" border = "1"/><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_R_FAILED_CHATS') ?> </a>      	  		
           <a href = <?php echo $link_unanswered_chats;?>  style = "text-decoration:none;" title = "<?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_R_UNANSWERED_CHATS') ?>"> <img src = "components/com_activehelper_livehelp/images/4.png" align = "top" border = "1"/><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_R_UNANSWERED_CHATS') ?></a>      		  		   
           <a href = <?php echo $link_chats_by_dept;?>  style = "text-decoration:none;" title = "<?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_CHATS_BY_DEPT') ?>"> <img src = "components/com_activehelper_livehelp/images/5.png" align = "top" border = "1"/><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_CHATS_BY_DEPT') ?></a>              
           <a href = <?php echo $link_chats_by_country;?>  style = "text-decoration:none;" title = "<?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_CHATS_BY_COUNTRY') ?>"> <img src = "components/com_activehelper_livehelp/images/6.png" align = "top" border = "1"/><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_CHATS_BY_COUNTRY') ?></a>           
           <a href = <?php echo $link_chat_by_keyword;?>  style = "text-decoration:none;" title = "<?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_R_CHAT_BY_KEYWORD') ?>"> <img src = "components/com_activehelper_livehelp/images/7.png" align = "top" border = "1"/><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_CHAT_BY_KEYWORDS') ?></a>     
           <a href = <?php echo $link_offline_messages;?>  style = "text-decoration:none;" title = "<?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_R_OFFLINE_MSG') ?>"> <img src = "components/com_activehelper_livehelp/images/8.png" align = "top" border = "1"/><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_OFFLINE_MSGS') ?></a>    
           <a href = <?php echo $link_restrictions;?>  style = "text-decoration:none;" title = "<?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_R_SERVER_RESTRICTION') ?>"> <img src = "components/com_activehelper_livehelp/images/9.png" align = "top" border = "1"/><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_SERVER_RESTRICTIONS') ?></a>
        </div>           
    </tr>
  </table>
 </div>	
 
<div  style="border:1px solid #ddd; background:#F1F3F5;">
  <table>
	   <div class="lc-more-stats">		
	  <?php foreach ($this->rowsdomains as $rdomain) { ?>
	        <div class="lc-stats-row">	
            <div class="lc-counter"><a href="<?php echo "index.php?option=com_activehelper_livehelp&view=monthly_chats" ?>">		
			<span class="lc-counter-number"> <?php echo $rdomain["name"] ?> </span>
			<span class="lc-counter-title"><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_CHATS') ?> : <?php echo $rdomain["chats"] ?></span>
            </div> 			
            <?php } ?>     
	    </div>		
    </table>       
</div>

 <div  style="border:1px solid #ddd; background:#F1F3F5;">
  <table> 
     <div class="lc-more-stats">		
	  <?php foreach ($this->rowsagents_rating as $ragentrating) { ?>
	        <div class="lc-stats-row">	
            <div class="lc-counter"><a href="<?php echo "index.php?option=com_activehelper_livehelp&view=monthly_chats" ?>">		
			<span class="lc-counter-number"> <?php echo $ragentrating["name"] ?> </span>
			<span class="lc-counter-title"> <?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_AVG_RAITINGS') ?> : <?php echo $ragentrating["avg_rating"] ?></span>
            </div> 			
            <?php } ?>     
	</div>	
  </table>       
</div>

 <div  style="border:1px solid #ddd; background:#F1F3F5;">
  <table> 
  <div class="lc-more-stats">		
	  <?php foreach ($this->rowsagents as $ragent) { ?>
	        <div class="lc-stats-row">	
            <div class="lc-counter"><a href="<?php echo "index.php?option=com_activehelper_livehelp&view=monthly_chats" ?>">		
			<span class="lc-counter-number"> <?php echo $ragent["name"] ?> </span>
			<span class="lc-counter-title"> <?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_CHATS') ?> : <?php echo $ragent["chats"] ?></span>
            </div> 			
            <?php } ?>     
	</div>	
    </table>       
</div>