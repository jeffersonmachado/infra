<?php

/**
 * @package   ActiveHelper_LiveHelp
 * @contact   www.activehelper.com, support@activehelper.com
 * @copyright 2010 - 2017 by ActiveHelper Inc. All rights reserved
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
 
defined( '_JEXEC' ) or die( 'Restricted access' );

jimport('joomla.html.html.bootstrap');
JHTML::_('behavior.calendar');
jimport( 'joomla.html.editor' );

?>
<script language="javascript" type="text/javascript">
function submitbutton(pressbutton) {
	if (pressbutton == 'save' || pressbutton == 'apply') {
		if (document.adminForm.username.value == '') {
			alert("Please enter the agent username");
		} else if (document.adminForm.email.value == '') {
			alert("Please enter the email");
		} else {
			submitform(pressbutton);
		}
	} else {
		submitform(pressbutton);
	}
}
</script>
<form action="index.php" method="post" name="adminForm" id="adminForm" enctype="multipart/form-data">
  <fieldset class="adminform">
    <legend><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_ES_DETAILS') ?></legend>
    <table class="admintable">
    <tr>      
      <td width="200" class="key> <label for="username"> <?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_USERNAME'); ?></label></td>
       <td>
        <input class="text_area" type="text" name="username" id="username" size="50" maxlength="25" value="<?php echo $this->row->username;?>" />
      </td>
    </tr>
      <tr>
         <td width="200" class="key> <label for="password"> <?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_PASSWORD'); ?></label></td>
      <td>
        <input class="text_area" type="text" name="password" id="password" size="50" maxlength="25"" />
      </td>
    </tr>
   <tr>
      <td width="200" class="key> <label for="First Name"> <?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_FSNM'); ?></label></td>
      <td>
        <input class="text_area" type="text" name="firstname" id="firstname" size="50" maxlength="25" value="<?php echo $this->row->firstname;?>" />
      </td>
    </tr>
   <tr>
      <td width="200" class="key> <label for="Last Name"><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_LSNM'); ?></label></td>
      <td>
        <input class="text_area" type="text" name="lastname" id="lastname" size="50" maxlength="25" value="<?php echo $this->row->lastname;?>" />
      </td>
    </tr>
     <tr>
       <td width="200" class="key> <label for="email"> <?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_EMAIL'); ?></label></td>
      <td>
        <input class="text_area" type="text" name="email" id="email" size="50" maxlength="50" value="<?php echo $this->row->email;?>" />
      </td>
    </tr>
       <tr>
          <td width="200" class="key> <label for="Department"><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_DEPT_NAME'); ?></label></td>
      <td>
        <input class="text_area" type="text" name="department" id="department" size="50" maxlength="250" value="<?php echo $this->row->department;?>" />
      </td>
    </tr>
   <tr>
       <td width="200" class="key> <label for="Status"><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_STATUS'); ?></label></td>
      <td>
        <?php echo $this->status; ?>
      </td>
    </tr>
    <tr>
        <td width="200" class="key> <label for="privs"> <?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_PRIVS'); ?></label></td>
      <td>
        <?php echo $this->privilege; ?>
      </td>
    </tr>
     <tr>
      <td width="200" class="key> <label for="agent status type"><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_STATUS_TYPE'); ?></label></td>     
      <td>
        <?php echo $this->answers; ?>
      </td>
      <td width="300" align="left" class="hint"> <?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_AGENT_ALERT')?> </td>  
    </tr> 
    <tr>
       <td width="200" class="key> <label for="photo"> <?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_PHOTO') ?> (gif)</label></td>
      <td width="60" align="left" >
       <?php echo JHTML::_('image', $this->img_path . $this->photo, 'agent photo' ); ?> <input type="file" name="photo" value="" />
      </td>
    </tr>
    </table>
  </fieldset>  
  <ul class="nav nav-tabs" id="myTab">
  <li class="active"><a data-toggle="tab" href="#home"><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_AVA_DOMAINS') ?></a></li>
  </ul>   
  	<?php jimport( 'joomla.html.html.select' );?>
        <fieldset class="adminform">
         <?php echo JHtml::_('bootstrap.startPane', 'myTab', array('active' => 'home'));  ?>
         <?php echo JHtml::_('bootstrap.addPanel', 'myTab', 'home');  ?>             
           
            <table class="adminlist table table-striped" id="itemsList">			                    
				<thead>
					 <tr> 
                      <th></th>
                      <th class="title"><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_DOMAIN_ID'); ?></th>
                      <th class="title"><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_DOMAIN_NAME_F'); ?></th>
                      <th class="title"><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_STATUS'); ?></th>      				
					</tr>
				</thead>  
                                       
               	<?php
                  foreach ($this->domains as $row ) { ?>                  
                  <tr class="<?php echo "row$k"; ?>">                                    
                  <td> <?php echo $row["id"];?> </td>
                  <td> <?php echo  $row["name"];?> </td>                                                         
                   <td><fieldset class="radio">  
                   <?php echo  JHTML::_('select.radiolist', $this->agent_domain_status, 'domains_selected[' . $row["id"] . ']', null, 'value', 'text', $row["enabled"]); ?>      
                   </fieldset>                           
                   <input type="hidden" name="domains_selected_default[<?php echo $row["id"];?> "value="<?php echo $row["enabled"]; ?>" />                     
                 </td>                  
                 </tr>                                                          				
                 <?php } ?> 
			</table> 
     <?php echo JHtml::_('bootstrap.endPanel'); ?>
     <?php echo JHtml::_('bootstrap.endPane', 'myTab'); ?>            
       </fieldset>      
   	   <input type="hidden" name="option" value="com_activehelper_livehelp" />
       <input type="hidden" name="id" value="<?php echo $this->row->id; ?>" />
       <input type="hidden" name="task" value="" />
       <input type="hidden" name="controller" value="agent" />  
       <input type="hidden" name="view" value="agents" />  
  <?php echo JHTML::_( 'form.token' ); ?>
</form>