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
            <a href="<?php echo BlogFactoryRoute::task('notification.edit&id=' . $this->item->id); ?>"
               title="<?php echo JText::_('JACTION_EDIT'); ?>">
                <?php echo $this->escape($this->item->subject); ?></a>
        </td>

        <td>
            <?php echo BlogFactoryText::_('notification_type_' . $this->item->type); ?>
        </td>

        <td class="small hidden-phone">
            <?php if ($this->item->lang_code == '*'): ?>
                <?php echo JText::alt('JALL', 'language'); ?>
            <?php else: ?>
                <?php echo $this->item->language_title ? $this->escape($this->item->language_title) : JText::_('JUNDEFINED'); ?>
            <?php endif; ?>
        </td>

        <td class="center hidden-phone">
            <?php echo (int)$this->item->id; ?>
        </td>
    </tr>
<?php endforeach; ?>
