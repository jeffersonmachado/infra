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

<tr class="<?php echo $this->item->published ? '' : 'post-pending'; ?>">

    <td class="center">
        <input type="checkbox" name="batch[]" value="<?php echo $this->item->id; ?>"/>
    </td>

    <td class="post-title">
        <a href="<?php echo BlogFactoryRoute::view('postedit&id=' . $this->item->id); ?>">
            <?php echo('' == $this->item->title ? BlogFactoryText::_('post_untitled') : $this->item->title); ?></a><b>
            &nbsp;-&nbsp;<?php echo BlogFactoryText::_('manageposts_post_draft'); ?></b>
        <span class="visible-phone visible-tablet comments"><?php echo $this->loadTemplate('comments'); ?></span>

        <div class="smalls post-excerpt">
            <?php echo JHtml::_('string.truncate', $this->item->content, 280, true, false); ?>
        </div>

        <div class="smalls muted post-actions">
            <?php echo $this->renderPostActions($this->item); ?>
        </div>
    </td>

    <td class="hidden-phone">
        <?php echo $this->item->category_id ? $this->item->category_title : '<span class="muted">&mdash;</span>'; ?>
    </td>

    <td class="center comments hidden-phone hidden-tablet">
        <?php echo $this->loadTemplate('comments'); ?>
    </td>

    <td class="small post-date hidden-phone">
        <?php echo JHtml::_('BlogFactoryManagePosts.date', $this->item); ?>
    </td>
</tr>
