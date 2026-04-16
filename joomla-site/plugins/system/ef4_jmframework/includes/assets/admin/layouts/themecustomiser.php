<?php
/**
 * @version $Id: themecustomiser.php 157 2017-03-30 14:48:16Z michal $
 * @package JMFramework
 * @copyright Copyright (C) 2012 DJ-Extensions.com LTD, All rights reserved.
 * @license http://www.gnu.org/licenses GNU/GPL
 * @author url: http://dj-extensions.com
 * @author email contact@dj-extensions.com
 * @developer Michal Olczyk - michal.olczyk@design-joomla.eu
 *
 * JMFramework is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * JMFramework is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with JMFramework. If not, see <http://www.gnu.org/licenses/>.
 *
 */

defined('_JEXEC') or die('Restricted access');

$user   = JFactory::getUser();
$result = new JObject;
$actions = JAccess::getActionsFromFile(JPATH_ADMINISTRATOR . '/components/com_templates/access.xml', "/access/section[@name='component']/");
foreach ($actions as $action) {
	$result->set($action->name, $user->authorise($action->name, 'com_templates'));
}

$hasAccess = false;
$isLoggedIn = $user->guest ? false : true;

if ($result->get('core.edit')) {
	$hasAccess = true;
}

$display_login_form = JFactory::getApplication()->input->getInt('jmthemerlogin', 0);

?>
<span id="jmtheme-logo">Theme Customizer (<a href="http://djex.co/jm-theme-customizer" target="_blank" title="<?php echo JText::_('PLG_SYSTEM_JMFRAMEWORK_THEME_CUSTOMIZER_LINK'); ?>">?</a>)</span>
<div class="inside" id="jmtheme-inside">
<?php 
if ($display_login_form == 1) {
	if (!$isLoggedIn) {?>
	<?php 
	
	require_once JPATH_ADMINISTRATOR . '/components/com_users/helpers/users.php';
	
	$twofactormethods = UsersHelper::getTwoFactorMethods();
	
	?>
	<form id="jmtheme-login-form" action="<?php echo JRoute::_('index.php');?>" method="post" class="form-vertical">
		<fieldset>
			<legend><?php echo JText::_('PLG_SYSTEM_JMFRAMEWORK_THEME_LOGIN_LEGEND'); ?></legend>
		</fieldset>
		<div class="control-group">
			<div class="control-label"><label for="jmtheme-username"><?php echo JText::_('PLG_SYSTEM_JMFRAMEWORK_THEME_USERNAME'); ?></label></div>
			<div class="controls"><input type="text" id="jmtheme-username" name="username" class="input input-medium" required="required" placeholder="<?php echo JText::_('PLG_SYSTEM_JMFRAMEWORK_THEME_USERNAME'); ?>"/></div>
		</div>
		<div class="control-group">
			<div class="control-label"><label for="jmtheme-password"><?php echo JText::_('PLG_SYSTEM_JMFRAMEWORK_THEME_PASSWORD'); ?></label></div>
			<div class="controls"><input type="password" id="jmtheme-password" name="password" class="input input-medium" required="required" placeholder="<?php echo JText::_('PLG_SYSTEM_JMFRAMEWORK_THEME_PASSWORD'); ?>" /></div>
		</div>
		
		<?php if (count($twofactormethods) > 1) { ?>
		<div class="control-group">
			<div class="control-label">
				<label for="modlgn-secretkey"><?php echo JText::_('JGLOBAL_SECRETKEY') ?></label>
			</div>
			<div class="controls">
				<input id="modlgn-secretkey" autocomplete="off" type="text" name="secretkey" class="input input-medium" tabindex="0" size="18" placeholder="<?php echo JText::_('JGLOBAL_SECRETKEY') ?>" />
			</div>
		</div>
		<?php } ?>
		
		<div class="control-group">
			<div class="controls submit">
				<input id="jmtheme-login" class="jmtheme-btn" type="submit" value="<?php echo JText::_('PLG_SYSTEM_JMFRAMEWORK_THEME_LOGIN'); ?>" />
				<input type="hidden" name="return" value="<?php echo  base64_encode(JUri::getInstance()->current()); ?>"/>
				<input type="hidden" name="option" value="com_users" />
				<input type="hidden" name="task" value="user.login" />
				<?php echo JHtml::_('form.token'); ?>
			</div>
		</div>
	</form>
<?php } else {?>
	<form id="jmtheme-logout-form" action="<?php echo JRoute::_('index.php');?>" method="post" class="form-vertical">
		<div class="control-group">
			<div class="controls submit">
				<button type="submit" class="jmtheme-btn"><?php echo JText::_('PLG_SYSTEM_JMFRAMEWORK_THEME_LOGOUT'); ?></button>
				<input type="hidden" name="option" value="com_users" />
				<input type="hidden" name="task" value="user.logout" />
				<input type="hidden" name="return" value="<?php echo  base64_encode(JUri::getInstance()->current()); ?>"/>
			</div>
		</div>
		<?php echo JHtml::_('form.token'); ?>
	</form>
<?php }
} ?>
<form name="jmtheme" id="jmtheme" action="" method="post">
	<div class="control-group jmtheme-form-submit">
	<div class="controls submit"><input class="jmtheme-btn" type="submit" id="jmtheme-submit" value="<?php echo JText::_('PLG_SYSTEM_JMFRAMEWORK_THEME_PREVIEW') ?>" /></div>
	<div class="controls submit"><button class="jmtheme-btn" id="jmtheme-reset"><?php echo JText::_('PLG_SYSTEM_JMFRAMEWORK_THEME_RESET') ?></button></div>
	<?php if ($hasAccess && $display_login_form == 1) {?>
		<div class="controls submit"><button class="jmtheme-btn" id="jmtheme-save"><?php echo JText::_('PLG_SYSTEM_JMFRAMEWORK_THEME_SAVE'); ?></button></div>
		<div class="controls submit"><button class="jmtheme-btn" id="jmtheme-save_file"><?php echo JText::_('PLG_SYSTEM_JMFRAMEWORK_THEME_SAVE_TO_FILE'); ?></button></div>
	<?php } ?>
	</div>
<?php 
foreach($fieldSets as $fieldset) {
	$fields = $form->getFieldset($fieldset->name);
	if (empty($fields)){
		continue;
	}
	?>
	<h3 class="jmtheme-set-toggler"><?php echo JText::_($fieldset->label) ?></h3>
	<div id="jmtheme-set-<?php echo htmlspecialchars($fieldset->name) ?>" class="jmtheme-set">
	<div class="jmtheme-set-inside">
	<?php 
	$subset_open = false;
	$fields_no = count($fields);
	$i = 0;
	foreach ($fields as $field_name => $field){
		if ($field->__get('type') == 'Jmacchelper') {
			if ($subset_open) {
				?>
				</div>
				</div>
				<?php
			}
			$subset_open = true;
			?>
			<h4 class="jmtheme-subset-toggler"><?php echo $field->label ?></h4>
			<div class="jmtheme-subset">
			<div class="jmtheme-subset-inside">
			<?php 
			$i++;
			continue;
		}
		?>
		<div class="control-group">
			<div class="control-label"><?php echo $field->label ?></div>
			<div class="controls"><?php echo $field->input ?></div>
		</div>
		<?php 
		$i++;
		if ($i >= $fields_no && $subset_open) {
			?>
			</div>
			</div>
			<?php
		}
	}
	?>
	</div>
	</div>
	<?php 
}
?>
</form>
</div>

