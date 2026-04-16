<?php 

/**
 * @package   ActiveHelper_LiveHelp
 * @contact   www.activehelper.com, support@activehelper.com
 * @copyright 2010 - 2017 by ActiveHelper Inc. All rights reserved
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */


defined( '_JEXEC' ) or die( 'Restricted access' ); ?>

<form action="<?php echo JRoute::_('index.php?option=com_activehelper_livehelp&view=reports'); ?>" method="post" name="adminForm" id="adminForm">

	<div class="contentheading">
	<div>
		<strong>Visitor Name:</strong>
	</div>
	<p>
		<input type="text" name="sender_name" value=<?php echo $this->name; ?> />
	</p>

	<div>
		<strong>Visitor Email :</strong>
	</div>
	<p>
		<input type="text" name="sender_email" value=<?php echo $this->email;?> />
	</p>

	<div><strong>Recipient Email :</strong></div>
	<p>
		<input type="text" name="recipient" value="" />
	</p>

	<div><strong>Message:</strong></div>
	<p>
  <?php echo $this->editor->display( 'chat', $this->chat, '60%', '100', '40', '10' ) ;?>
	</p>
  <br>
  <br>
  <br>
	<p>
	<input type="submit" value="Send" class="button" />
	</p>
	<?php echo JHTML::_( 'form.token' ); ?>
    <input type="hidden" name="mail" value="<?php echo urlencode($this->chat);?>" />
    <input type="hidden" name="task" value="reports.sendemail" />
</form>