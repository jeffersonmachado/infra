<?php
/**
 * @version $Id: layoutbuilder_assigns.php 71 2015-01-16 09:32:07Z michal $
 * @package JMFramework
 * @copyright Copyright (C) 2012 DJ-Extensions.com LTD, All rights reserved.
 * @license http://www.gnu.org/licenses GNU/GPL
 * @author url: http://dj-extensions.com
 * @author email contact@dj-extensions.com
 * @developer Szymon Woronowski - szymon.woronowski@design-joomla.eu
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

defined('JPATH_PLATFORM') or die;

require_once JPATH_ADMINISTRATOR.'/components/com_menus/helpers/menus.php';
$menuTypes = MenusHelper::getMenuLinks();

$default = $assigns[0];
?>

	<h3><?php echo JText::_('PLG_SYSTEM_JMFRAMEWORK_LAYOUTBUILDER_MENU_ASSIGNMENTS') ?></h3>
	
	<p class="jm-alert alert alert-info"><?php echo JText::_('PLG_SYSTEM_JMFRAMEWORK_LAYOUTBUILDER_ASSIGNED_TO_DIFFERENT_STYLE') ?></p>
	
	<ul class="menu-links thumbnails">

		<?php
		foreach ($menuTypes as &$type) : ?>
			<li class="span3">
				<div class="thumbnail">
				<button class="btn" type="button" class="jform-rightbtn" onclick="$$('.<?php echo $type->menutype; ?>').each(function(el) { el.checked = !el.checked; });">
					<i class="icon-checkbox-partial"></i>&nbsp; &nbsp; &nbsp;<?php echo JText::_('JGLOBAL_SELECTION_INVERT'); ?>
				</button>
				<h5><?php echo $type->title ? $type->title : $type->menutype; ?></h5>
				
				<?php
				$lno = array();
				foreach ($type->links as $link) :
					// disable if link is assigned to different style
					$disabled =	$link->template_style_id != $styleid;
					// disable for default template style
					if($is_default_style) $disabled = $disabled && $link->template_style_id != 0;
					// check if link is assign to different layout than current layout
					$assigned =	(array_key_exists($link->value, $assigns) && $assigns[$link->value] != $layout);
					if($layout == $default) {
						// checked if link is assigned to default layout
						$checked = !$disabled && !array_key_exists($link->value, $assigns);
						// disable also if link is assigned to default layout
						$disabled =	$disabled || $checked;
					} else {
						// checked if link is assigned to current layout
						$checked =	(array_key_exists($link->value, $assigns) && $assigns[$link->value] == $layout);
					}
					
					if($assigned && !isset($lno[$assigns[$link->value]])) $lno[$assigns[$link->value]] = count($lno);
					?>
					<label class="checkbox small <?php echo $disabled || $checked ? 'muted':''?>" for="link<?php echo (int) $link->value;?>" >
					<input type="checkbox" name="jform[layout_assigned][]" value="<?php echo (int) $link->value;?>" id="link<?php echo (int) $link->value;?>"
					<?php if ($checked):?> checked="checked"<?php endif;?>
					<?php if ($disabled):?> disabled="disabled"<?php else:?> class="<?php echo $type->menutype; ?>"<?php endif;?> />
					<?php echo $link->text; ?>
					<?php echo $assigned ? '<small class="badge hasTooltip assigned-layout'.$lno[$assigns[$link->value]].'" title="'.JText::sprintf('PLG_SYSTEM_JMFRAMEWORK_LAYOUTBUILDER_ASSIGNED_TO_LAYOUT', $assigns[$link->value]) .'"> '. $assigns[$link->value] .'</small>' : '' ?>
					<?php echo ($layout == $default && $checked) ? '<small class="badge hasTooltip assigned-layout-default" title="'.JText::sprintf('PLG_SYSTEM_JMFRAMEWORK_LAYOUTBUILDER_ASSIGNED_TO_LAYOUT', $default).'"> '. $default .'</small>' : '' ?>
					</label>
				<?php endforeach; ?>
				</div>
			</li>
		<?php endforeach; ?>
	</ul>
	