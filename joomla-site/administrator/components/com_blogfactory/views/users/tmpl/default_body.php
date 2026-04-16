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

        <td class="nowrap has-context">
            <a href="<?php echo BlogFactoryRoute::task('user.edit&id=' . $this->item->id); ?>"
               title="<?php echo JText::_('JACTION_EDIT'); ?>">
                <?php echo $this->escape($this->item->username); ?></a>
        </td>

        <td class="nowrap has-context">
            <?php if ($this->item->blog_id): ?>
                <a href="<?php echo BlogFactoryRoute::task('blog.edit&id=' . $this->item->blog_id); ?>"
                   title="<?php echo JText::_('JACTION_EDIT'); ?>">
                    <?php echo $this->escape($this->item->blog_title); ?></a>
            <?php endif; ?>
        </td>

        <td class="center hidden-phone">
            <?php echo (int)$this->item->id; ?>
        </td>
    </tr>
<?php endforeach; ?>
