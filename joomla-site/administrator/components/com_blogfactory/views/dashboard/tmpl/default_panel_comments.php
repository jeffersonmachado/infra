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

<table class="table table-striped table-hover">
    <thead>
    <tr>
        <th>Comment</th>
        <th style="width: 150px;">Post</th>
    </tr>
    </thead>

    <tbody>
    <?php foreach ($this->comments as $this->comment): ?>
        <tr>
            <td>
                <a href="<?php echo BlogFactoryRoute::task('comment.edit&id=' . $this->comment->id); ?>">
                    <?php echo JHtml::_('date', $this->comment->created_at, 'DATE_FORMAT_LC2'); ?>
                </a>
                <div class="muted"><?php echo JHtml::_('string.truncate', $this->comment->text, 150); ?></div>
            </td>

            <td>
                <a href="<?php echo BlogFactoryRoute::task('user.edit&id=' . $this->comment->post_id); ?>">
                    <?php echo $this->comment->post_title; ?>
                </a>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<a href="<?php echo BlogFactoryRoute::view('comments'); ?>" class="btn btn-small">View all comments</a>
