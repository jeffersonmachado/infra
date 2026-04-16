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

if (!$this->portletComments): ?>
    <div class="portlet-no-results">
        <?php echo BlogFactoryText::_('dashboard_portlet_comments_no_items'); ?>
    </div>
<?php else: ?>
    <?php foreach ($this->portletComments as $item): ?>
        <div
            class="comment-wrapper portlet-comments-comment <?php echo !$item->approved ? 'comment-pending' : ''; ?> <?php echo $item->reported ? 'comment-reported' : ''; ?>">
            <div class="comment-photo">
                <img src="<?php echo JHtml::_('BlogFactoryComments.getAvatarSource', $item); ?>"/>
            </div>

            <div class="comment-info">
                <div>
                    <?php echo JHtml::_('BlogFactoryBlog.commentAuthorLink', $item); ?>

                    <?php #echo $item->author_name; ?>
                    <span class="muted"><?php echo BlogFactoryText::_('dashboard_portlet_comments_on'); ?></span>
                    <a href="<?php echo BlogFactoryRoute::view('postedit&id=' . $item->post_id); ?>"
                       class="post-title"><?php echo JHtml::_('string.abridge', $item->title, 50, 30); ?></a>
                    <div class="muted small"><?php echo JHtml::_('date', $item->created_at, 'DATE_FORMAT_LC2'); ?></div>
                </div>

                <div class="smalls"><?php echo JHtml::_('string.truncate', strip_tags($item->text), 150); ?></div>

                <?php echo JHtml::_('BlogFactoryDashboard.portletCommentsItemActions', $item); ?>
            </div>
        </div>
    <?php endforeach; ?>

    <div class="portlet-footer">
        <a href="<?php echo BlogFactoryRoute::view('managecomments&status=pending"'); ?>"
           class="btn btn-mini"><?php echo BlogFactoryText::_('dashboard_portlet_comments_button_pending'); ?></a>
        <a href="<?php echo BlogFactoryRoute::view('managecomments&status=reported'); ?>"
           class="btn btn-mini"><?php echo BlogFactoryText::_('dashboard_portlet_comments_button_reported'); ?></a>
        <a href="<?php echo BlogFactoryRoute::view('managecomments'); ?>"
           class="btn btn-mini"><?php echo BlogFactoryText::_('dashboard_portlet_comments_button_all'); ?></a>
    </div>
<?php endif; ?>
