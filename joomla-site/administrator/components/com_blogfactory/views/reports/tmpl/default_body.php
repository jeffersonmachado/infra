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
                <?php echo JHtml::_('BlogFactoryReports.resolved', $this->item->status, $this->i, $this->getName() . '.', true); ?>
            </div>
        </td>

        <td class="nowrap has-context">
            <a href="<?php echo BlogFactoryRoute::task('report.edit&id=' . $this->item->id); ?>"
               title="<?php echo JText::_('JACTION_EDIT'); ?>">
                <?php echo $this->escape(BlogFactoryText::_('report_type_' . $this->item->type)); ?></a>

            <div class="small muted">
                <?php echo JHtml::_('string.truncate', $this->item->text, 100); ?>
            </div>
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
