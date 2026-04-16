<?php
/**
 * @version $Id: layoutbuilder.php 163 2017-10-17 12:48:27Z szymon $
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

// close control-group, etc. divs.
?>
</div>
</div>

<div id="jm_layoutbuilder_container">

	<div class="control-group">
		<div class="control-label">
			<label for="jform_params_layout" title="" data-original-title="<?php echo JText::_('PLG_SYSTEM_JMFRAMEWORK_LAYOUTBUILDER_LAYOUT_LABEL'); ?>" data-content="<?php echo JText::_('PLG_SYSTEM_JMFRAMEWORK_LAYOUTBUILDER_LAYOUT_DESC'); ?>" class="hasPopover"><?php echo JText::_('PLG_SYSTEM_JMFRAMEWORK_LAYOUTBUILDER_LAYOUT_LABEL') ?></label>
		</div>
		<div class="controls toolbar">
			<?php echo $loadOptions ?> <span class="loader hide" id="<?php echo $this->id ?>_loader"></span>
			<button class="btn btn-success hasTooltipBottom" id="jm_layoutbuilder_save" title="<?php echo JText::_('PLG_SYSTEM_JMFRAMEWORK_LAYOUTBUILDER_LAYOUT_SAVE_DESC') ?>"><i class="icon-save"></i>&nbsp;&nbsp;<?php echo JText::_('PLG_SYSTEM_JMFRAMEWORK_LAYOUTBUILDER_LAYOUT_SAVE') ?></button>
			<button class="btn btn-smaller hasTooltipBottom" id="jm_layoutbuilder_assigns" title="<?php echo JText::_('PLG_SYSTEM_JMFRAMEWORK_LAYOUTBUILDER_LAYOUT_ASSIGN_TO_MENU_ITEMS') ?>"><i class="icon-remove"></i><i class="icon-menu"></i></button>
			<button class="btn btn-smaller hasTooltipBottom" id="jm_layoutbuilder_copy" title="<?php echo JText::_('PLG_SYSTEM_JMFRAMEWORK_LAYOUTBUILDER_LAYOUT_COPY') ?>"><i class="icon-copy"></i></button>
			<button class="btn btn-smaller hasTooltipBottom" id="jm_layoutbuilder_remove" title="<?php echo JText::_('PLG_SYSTEM_JMFRAMEWORK_LAYOUTBUILDER_LAYOUT_REMOVE') ?>"><i class="icon-trash"></i></button>
			<button class="btn btn-smaller hasTooltipBottom" id="jm_layoutbuilder_setdefault" title="<?php echo JText::_('PLG_SYSTEM_JMFRAMEWORK_LAYOUTBUILDER_LAYOUT_SET_AS_DEFAULT') ?>"><i class="icon-default"></i></button>
			<button class="jm_layoutbuilder_full_restore btn btn-smaller hasTooltipBottom" title="<?php echo JText::_('PLG_SYSTEM_JMFRAMEWORK_LAYOUTBUILDER_RESTORE_LAYOUT') ?>"><i class="icon-refresh"></i></button>
		</div>

	</div>

	<div id="layoutbuilder_msg"></div>

	<div class="jm_layoutbuilder_params control-group">
		<div class="control-group ">
			<div class="control-label"><label title="" data-original-title="<?php echo JText::_('PLG_SYSTEM_JMFRAMEWORK_CONFIG_TEMPLATE_WIDTH_PERCENT_LABEL'); ?>" data-content="<?php echo JText::_('PLG_SYSTEM_JMFRAMEWORK_CONFIG_TEMPLATE_WIDTH_PERCENT_DESC'); ?>" class="hasPopover" for="layoutbuilder_tmpl_width" id="layoutbuilder_tmpl_width-lbl">
			<?php echo JText::_('PLG_SYSTEM_JMFRAMEWORK_CONFIG_TEMPLATE_WIDTH_PERCENT_LABEL'); ?></label><label title="" data-original-title="<?php echo JText::_('PLG_SYSTEM_JMFRAMEWORK_CONFIG_GUTTER_WIDTH_LABEL'); ?>" data-content="<?php echo JText::_('PLG_SYSTEM_JMFRAMEWORK_CONFIG_GUTTER_WIDTH_DESC'); ?>" class="hasPopover" for="layoutbuilder_tmpl_space" id="layoutbuilder_tmpl_space-lbl">
			<?php echo JText::_('PLG_SYSTEM_JMFRAMEWORK_CONFIG_GUTTER_WIDTH_LABEL'); ?></label></div>
				<div class="controls"><input type="text" class="unit-remember" value="" id="layoutbuilder_tmpl_width" name="layoutbuilder_tmpl_width" placeholder="<?php echo JText::_('JDEFAULT') ?>" /> <input type="text" value="" id="layoutbuilder_tmpl_space" name="layoutbuilder_tmpl_space" placeholder="<?php echo JText::_('JDEFAULT') ?>" /></div>
		</div>
	</div>

	<div class="jm_layoutbuilder_tabs">
		<div class="jm_layoutbuilder_build_res_tab active">
			<ul class="nav nav-tabs jm_layoutbuilder_screen">
				<li><a class="jm_layoutbuilder_build_positions hasTooltip" title="<?php echo JText::_('PLG_SYSTEM_JMFRAMEWORK_LAYOUTBUILDER_CUSTOM_MODULE_POS_DESC') ?>"><?php echo JText::_('PLG_SYSTEM_JMFRAMEWORK_LAYOUTBUILDER_CUSTOM_MODULE_POS') ?></a></li>
				<li><a class="jm_layoutbuilder_device jm_admin_screen_wide hasTooltip" data-screen="wide" title="<?php echo JText::_('PLG_SYSTEM_JMFRAMEWORK_LAYOUTBUILDER_SCREEN_WIDE_DESC') ?>"><?php echo JText::_('PLG_SYSTEM_JMFRAMEWORK_LAYOUTBUILDER_SCREEN_WIDE') ?></a></li>
				<li><a class="jm_layoutbuilder_device jm_admin_screen_normal hasTooltip" data-screen="normal" title="<?php echo JText::_('PLG_SYSTEM_JMFRAMEWORK_LAYOUTBUILDER_SCREEN_NORMAL_DESC') ?>"><?php echo JText::_('PLG_SYSTEM_JMFRAMEWORK_LAYOUTBUILDER_SCREEN_NORMAL') ?></a></li>
				<li><a class="jm_layoutbuilder_device jm_admin_screen_xtablet hasTooltip" data-screen="xtablet" title="<?php echo JText::_('PLG_SYSTEM_JMFRAMEWORK_LAYOUTBUILDER_SCREEN_XTABLET_DESC') ?>"><?php echo JText::_('PLG_SYSTEM_JMFRAMEWORK_LAYOUTBUILDER_SCREEN_XTABLET') ?></a></li>
				<li><a class="jm_layoutbuilder_device jm_admin_screen_tablet hasTooltip" data-screen="tablet" title="<?php echo JText::_('PLG_SYSTEM_JMFRAMEWORK_LAYOUTBUILDER_SCREEN_TABLET_DESC') ?>"><?php echo JText::_('PLG_SYSTEM_JMFRAMEWORK_LAYOUTBUILDER_SCREEN_TABLET') ?></a></li>
				<li><a class="jm_layoutbuilder_device jm_admin_screen_mobile hasTooltip" data-screen="mobile" title="<?php echo JText::_('PLG_SYSTEM_JMFRAMEWORK_LAYOUTBUILDER_SCREEN_MOBILE_DESC') ?>"><?php echo JText::_('PLG_SYSTEM_JMFRAMEWORK_LAYOUTBUILDER_SCREEN_MOBILE') ?></a></li>
			</ul>

			<p class="alert alert-error responsive-notice" style="display: none;"><?php echo JText::_('PLG_SYSTEM_JMFRAMEWORK_RESPONSIVE_DISABLED_MESSAGE') ?></p>

			<div id="jm_layoutbuilder_preview" class="jm_layoutbuilder_preview jm_layoutbuilder_build_pos"></div>

			<div id="jm_layoutbuilder_buttons">
				<button class="btn jm_layoutbuilder_restore_screen">
					<i class="icon-refresh"></i>&nbsp;&nbsp;<?php echo JText::_('PLG_SYSTEM_JMFRAMEWORK_LAYOUTBUILDER_RESTORE_SCREEN') ?>
				</button>
				<button class="btn jm_layoutbuilder_restore_positions">
					<i class="icon-refresh"></i>&nbsp;&nbsp;<?php echo JText::_('PLG_SYSTEM_JMFRAMEWORK_LAYOUTBUILDER_RESTORE_MODULE_POS') ?>
				</button>
				<button class="btn jm_layoutbuilder_restore_order">
					<i class="icon-refresh"></i>&nbsp;&nbsp;<?php echo JText::_('PLG_SYSTEM_JMFRAMEWORK_LAYOUTBUILDER_RESTORE_ORDER') ?>
				</button>
			</div>

		</div>

		<div class="jm_layoutbuilder_assigns_tab">
			<div id="layout_assigns"></div>
		</div>

	</div>

</div>

<!-- POPOVER POSITIONS -->
<div id="jm_layoutbuilder_module_positions" class="popover top hide">
	<div class="arrow"></div>
	<h3 class="popover-title"><?php echo JText::_('PLG_SYSTEM_JMFRAMEWORK_SELECT_MODULE_POSITION') ?></h3>
	<div class="popover-content">
		<?php echo JMFAdminTemplate::getModulePositions(); ?>
		<div class="jm_layoutbuilder_module_positions_buttons">
			<button class="jm_layoutbuilder_remove_pos btn btn-warning"><i class="icon-remove"></i>&nbsp;&nbsp;<?php echo JText::_('JNONE') ?></button>
			<button class="jm_layoutbuilder_default_pos btn btn-primary"><i class="icon-refresh"></i>&nbsp;&nbsp;<?php echo JText::_('JDEFAULT') ?></button>
		</div>
	</div>
</div>

<!-- MODAL COPY LAYOUT -->
<div id="jm_layoutbuilder_copy_modal" class="modal hide">
	<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
		<h4><?php echo JText::_('PLG_SYSTEM_JMFRAMEWORK_LAYOUTBUILDER_LAYOUT_COPY_HEAD') ?></h4>
	</div>
	<div class="modal-body">
		<p><?php echo JText::_('PLG_SYSTEM_JMFRAMEWORK_LAYOUTBUILDER_LAYOUT_COPY_DESC') ?></p>
		<div class="input-prepend input-append">
			<span class="add-on"><i class="icon-copy"></i></span>
			<input type="text" class="input-xlarge" id="jm_layoutbuilder_layout_copy_name" />
			<button class="btn btn-success"><?php echo JText::_('PLG_SYSTEM_JMFRAMEWORK_LAYOUTBUILDER_LAYOUT_COPY') ?></button>
		</div>
	</div>
	<div class="modal-footer">
		<button class="btn cancel" data-dismiss="modal"><?php echo JText::_('JCANCEL') ?></button>
	</div>
</div>

<!-- MODAL REMOVE LAYOUT -->
<div id="jm_layoutbuilder_remove_modal" class="modal hide">
	<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
		<h4><?php echo JText::_('PLG_SYSTEM_JMFRAMEWORK_LAYOUTBUILDER_LAYOUT_REMOVE_HEAD') ?></h4>
	</div>
	<div class="modal-body">
		<p><?php echo JText::_('PLG_SYSTEM_JMFRAMEWORK_LAYOUTBUILDER_LAYOUT_REMOVE_DESC') ?></p>
	</div>
	<div class="modal-footer">
		<button class="btn cancel" data-dismiss="modal"><?php echo JText::_('JCANCEL') ?></button>
		<button class="btn btn-danger"><?php echo JText::_('PLG_SYSTEM_JMFRAMEWORK_LAYOUTBUILDER_LAYOUT_REMOVE') ?></button>
	</div>
</div>

<?php // re-open control-group ?>
<div>
<div>

<?php
