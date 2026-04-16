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
        <td class="order nowrap center hidden-phone">
      <span
          class="sortable-handler hasTooltip <?php echo !$this->saveOrder ? 'inactive tip-top' : ''; ?>"
          title="<?php echo !$this->saveOrder ? JText::_('JORDERINGDISABLED') : ''; ?>">
			  <i class="icon-menu"></i>
			</span>
            <input type="text" style="display:none" name="order[]" size="5" value="<?php echo $this->item->ordering; ?>"
                   class="width-20 text-area-order "/>
        </td>

        <td class="center hidden-phone">
            <?php echo JHtml::_('grid.id', $this->i, $this->item->id); ?>
        </td>

        <td class="center">
            <div class="btn-group">
                <?php echo JHtml::_('jgrid.published', $this->item->published, $this->i, $this->getName() . '.', true); ?>
            </div>
        </td>

        <td>
            <img src="<?php echo Juri::root(); ?>media/com_blogfactory/share/<?php echo $this->item->thumbnail; ?>"/>
        </td>

        <td class="nowrap has-context">
            <a href="<?php echo BlogFactoryRoute::task('bookmark.edit&id=' . $this->item->id); ?>"
               title="<?php echo JText::_('JACTION_EDIT'); ?>">
                <?php echo $this->escape($this->item->title); ?></a>
        </td>

        <td class="center hidden-phone">
            <?php echo (int)$this->item->id; ?>
        </td>
    </tr>
<?php endforeach; ?>
