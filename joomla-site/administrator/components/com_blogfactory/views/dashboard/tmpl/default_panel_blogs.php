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
        <th>Title</th>
        <th style="width: 80px;">Owner</th>
    </tr>
    </thead>

    <tbody>
    <?php foreach ($this->blogs as $this->blog): ?>
        <tr>
            <td>
                <a href="<?php echo BlogFactoryRoute::task('blog.edit&id=' . $this->blog->id); ?>">
                    <?php echo $this->blog->title; ?>
                </a>
                <div
                    class="muted small"><?php echo JHtml::_('date', $this->blog->created_at, 'DATE_FORMAT_LC2'); ?></div>
            </td>

            <td>
                <a href="<?php echo BlogFactoryRoute::task('user.edit&id=' . $this->blog->user_id); ?>">
                    <?php echo $this->blog->username; ?>
                </a>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<a href="<?php echo BlogFactoryRoute::view('blogs'); ?>" class="btn btn-small">View all blogs</a>
