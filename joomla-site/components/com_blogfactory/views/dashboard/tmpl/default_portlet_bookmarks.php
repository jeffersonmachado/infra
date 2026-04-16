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

if (!$this->portletBookmarks): ?>
    <div class="portlet-no-results">
        <?php echo BlogFactoryText::_('dashboard_portlet_bookmarks_no_items'); ?>
    </div>
<?php else: ?>
    <?php foreach ($this->portletBookmarks as $item): ?>
        <div class="portlet-bookmarks-bookmark">
            <a href="#"><?php echo JHtml::_('string.abridge', $item->title); ?></a>
            <span class="muted small"><?php echo JHtml::_('date', $item->created_at, 'DATE_FORMAT_LC2'); ?></span>
        </div>
    <?php endforeach; ?>

    <div class="portlet-footer">
        <a href="<?php echo BlogFactoryRoute::view('managebookmarks'); ?>"
           class="btn btn-mini"><?php echo BlogFactoryText::_('dashboard_portlet_bookmarks_all_bookmarks'); ?></a>
    </div>
<?php endif; ?>
