<?php

/**
-------------------------------------------------------------------------
blogfactory - Blog Factory 4.3.0
-------------------------------------------------------------------------
 * @author thePHPfactory
 * @copyright Copyright (C) 2011 SKEPSIS Consult SRL. All Rights Reserved.
 * @license - http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * Websites: http://www.thePHPfactory.com
 * Technical Support: Forum - http://www.thePHPfactory.com/forum/
-------------------------------------------------------------------------
*/

defined('_JEXEC') or die;

?>

<div class="quickpost-save-result"></div>

<form>
    <input id="quickpost_title" name="quickpost[title]" type="text"
           placeholder="<?php echo BlogFactoryText::_('dashboard_portlet_quickpost_title_placeholder'); ?>"/>
    <textarea id="quickpost_content" name="quickpost[content]"
              placeholder="<?php echo BlogFactoryText::_('dashboard_portlet_quickpost_content_placeholder'); ?>"
              rows="5"></textarea>
</form>

<div class="quickpost-actions">
    <a
        href="<?php echo BlogFactoryRoute::task('post.quickpost&mode=draft&format=raw'); ?>"
        class="btn btn-small quickpost-action-save">
        <i class="factory-icon-loader" style="display: none;"></i>
        <span><?php echo BlogFactoryText::_('dashboard_portlet_quickpost_action_save'); ?></span>
    </a>

    <a
        href="#"
        class="btn btn-small quickpost-action-reset">
        <?php echo BlogFactoryText::_('dashboard_portlet_quickpost_action_reset'); ?>
    </a>

    <a
        href="<?php echo BlogFactoryRoute::task('post.quickpost&mode=publish&format=raw'); ?>"
        class="btn btn-small btn-primary quickpost-action-save"
        style="float: right;">
        <i class="factory-icon-loader" style="display: none;"></i>
        <span><?php echo BlogFactoryText::_('dashboard_portlet_quickpost_action_publish'); ?></span>
    </a>
</div>
