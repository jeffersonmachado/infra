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

foreach ($this->items as $this->i => $this->item): ?>
    <tr>
        <td class="center hidden-phone">
            <?php echo JHtml::_('grid.id', $this->i, $this->item->id); ?>
        </td>

        <td class="center">
            <div class="btn-group">
                <?php echo JHtml::_('jgrid.published', $this->item->published, $this->i, $this->getName() . '.', true); ?>
            </div>
        </td>

        <td class="nowrap has-context">
            <a href="<?php echo BlogFactoryRoute::task('post.edit&id=' . $this->item->id); ?>"
               title="<?php echo JText::_('JACTION_EDIT'); ?>">
                <?php echo $this->escape($this->item->title); ?></a>

            <div class="small muted">
                <?php echo $this->escape(JHtml::_('string.truncate', strip_tags($this->item->content), 100)); ?>
            </div>
        </td>

        <td>
            <a href="<?php echo BlogFactoryRoute::task('blog.edit&id=' . $this->item->blog_id); ?>">
                <?php echo $this->escape($this->item->blog_title); ?></a>
        </td>

        <td>
            <a href="<?php echo JRoute::_('index.php?option=com_categories&task=category.edit&id=' . $this->item->category_id . '&extension=com_blogfactory'); ?>">
                <?php echo $this->escape($this->item->category_title); ?>
            </a>
        </td>

        <td>
            <a href="<?php echo JRoute::_('index.php?option=com_users&task=user.edit&id=' . $this->item->user_id); ?>">
                <?php echo $this->escape($this->item->username); ?></a>
        </td>

        <td class="small">
            <?php echo JHtml::_('date', $this->item->created_at, 'Y-m-d'); ?>
        </td>

        <td class="center hidden-phone">
            <?php echo (int)$this->item->id; ?>
        </td>
    </tr>
<?php endforeach; ?>
