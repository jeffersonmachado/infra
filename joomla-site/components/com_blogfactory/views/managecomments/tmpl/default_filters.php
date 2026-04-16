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

<ul class="blogfactory-list-filters">
    <li>
        <?php echo JHtml::_('BlogFactoryList.filter', 'status', $this->status, 'managecomments_filter_all', null, $this->filterTotals); ?>
        <span class="muted">|</span>
    </li><?php if (1 == $this->approval): ?>
        <li>
        <?php echo JHtml::_('BlogFactoryList.filter', 'status', $this->status, 'managecomments_filter_approved', 'approved', $this->filterTotals); ?>
        <span class="muted">|</span>
        </li>
        <li>
        <?php echo JHtml::_('BlogFactoryList.filter', 'status', $this->status, 'managecomments_filter_pending', 'pending', $this->filterTotals); ?>
        <span class="muted">|</span>
        </li><?php endif; ?>
    <li>
        <?php echo JHtml::_('BlogFactoryList.filter', 'status', $this->status, 'managecomments_filter_reported', 'reported', $this->filterTotals); ?>
    </li>
</ul>
