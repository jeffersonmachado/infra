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

<tr class="comment-wrapper <?php echo $this->item->approved ? '' : 'comment-pending'; ?> <?php echo $this->item->reported ? 'comment-reported' : ''; ?>">

    <td class="center">
        <input type="checkbox" name="batch[]" value="<?php echo $this->item->id; ?>"/>
    </td>

    <td class="hidden-phone">
        <img class="blogfactory-avatar"
             src="<?php echo JHtml::_('BlogFactoryComments.getAvatarSource', $this->item); ?>"/>

        <div class="">
            <div><?php echo JHtml::_('BlogFactoryBlog.commentAuthor', $this->item); ?></div>
            <div>
                <?php echo JHtml::_('BlogFactoryBlog.commentUrl', $this->item, true); ?>
            </div>
            <div><?php echo JHtml::_('BlogFactoryBlog.commentEmail', $this->item); ?></div>
        </div>
    </td>

    <td colspan="2">
        <div class="smalls">
            <div style="margin-bottom: 5px;">
                <?php if (!$this->post): ?>
                    <a href="<?php echo BlogFactoryRoute::view('postedit&id=' . $this->item->post_id); ?>"><b><?php echo $this->item->post_title; ?></b></a>
                <?php endif; ?>

                <span
                    class="small muted"><?php echo JHtml::_('date', $this->item->created_at, 'DATE_FORMAT_LC2'); ?></span>

                <?php if (!$this->post): ?>
                    <a href="<?php echo JHtml::_('BlogFactoryDashboard.manageCommentsFilterPost', $this->item->post_id); ?>"
                       class="hasTooltip"
                       title="<?php echo BlogFactoryText::plural('managecomments_post_comments_tooltip', $this->item->comments_total); ?>"><i
                            class="factory-icon-balloon"></i><?php echo $this->item->comments_total; ?></a>
                <?php endif; ?>
            </div>
            <?php echo nl2br($this->item->text); ?>
        </div>

        <?php echo JHtml::_('BlogFactoryDashboard.portletCommentsItemActions', $this->item); ?>
    </td>
</tr>
