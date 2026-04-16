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

        <?php if ($this->approveComments): ?>
            <td class="center">
                <div class="btn-group">
                    <?php echo JHtml::_('BlogFactoryComments.approved', $this->item->approved, $this->i, $this->getName() . '.', true); ?>
                </div>
            </td>
        <?php endif; ?>

        <td>
            <?php if ($this->item->user_id): ?>
                <a href="<?php echo JRoute::_('index.php?option=com_users&task=user.edit&id=' . $this->item->user_id); ?>">
                    <?php echo $this->escape($this->item->username); ?></a>
            <?php else: ?>
                <?php if ($this->item->url): ?>
                    <a href="<?php echo $this->item->url; ?>" target="_blank"><?php echo $this->item->name; ?></a>
                <?php else: ?>
                    <?php echo $this->item->name; ?>
                <?php endif; ?>

                <div class="small">
                    <a href="mailto:<?php echo $this->item->email; ?>"><?php echo $this->item->email; ?></a>
                </div>
            <?php endif; ?>
        </td>

        <td class="has-context">
            <?php echo JHtml::_('string.truncate', $this->item->text, 0); ?>

            <div class="small muted">
                <a href="<?php echo BlogFactoryRoute::task('post.edit&id=' . $this->item->post_id); ?>"
                   class="muted"><?php echo $this->item->post_title; ?></a>
            </div>
        </td>

        <td class="small">
            <?php echo JHtml::_('date', $this->item->created_at, 'DATE_FORMAT_LC2'); ?>
        </td>

        <td class="center">
            <div class="btn-group">
                <?php echo JHtml::_('jgrid.published', $this->item->published, $this->i, $this->getName() . '.', true); ?>
            </div>
        </td>

        <td class="center hidden-phone">
            <?php echo (int)$this->item->id; ?>
        </td>
    </tr>
<?php endforeach; ?>
