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

if (!$this->portletPosts): ?>
    <div class="portlet-no-results">
        <?php echo BlogFactoryText::_('dashboard_portlet_posts_no_items'); ?>
    </div>
<?php else: ?>
    <?php foreach ($this->portletPosts as $item): ?>
        <?php echo JHtml::_('BlogFactoryDashboard.getPortletPostsItem', $item); ?>
    <?php endforeach; ?>
<?php endif; ?>

<div class="portlet-footer" style="display: <?php echo $this->portletPosts ? '' : 'none'; ?>;">
    <a href="<?php echo BlogFactoryRoute::view('manageposts&status=draft'); ?>"
       class="btn btn-mini"><?php echo BlogFactoryText::_('dashboard_portlet_posts_button_drafts'); ?></a>
    <a href="<?php echo BlogFactoryRoute::view('manageposts&status=published'); ?>"
       class="btn btn-mini"><?php echo BlogFactoryText::_('dashboard_portlet_posts_button_published'); ?></a>
    <a href="<?php echo BlogFactoryRoute::view('manageposts'); ?>"
       class="btn btn-mini"><?php echo BlogFactoryText::_('dashboard_portlet_posts_button_all_posts'); ?></a>
</div>
