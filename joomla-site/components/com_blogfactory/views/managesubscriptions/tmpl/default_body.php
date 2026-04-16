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

<tr class="bookmark-wrapper">

    <td class="center">
        <input type="checkbox" name="batch[]" value="<?php echo $this->item->id; ?>"/>
    </td>

    <td>
        <a href="<?php echo BlogFactoryRoute::view('blog&alias=' . $this->item->alias . '&id=' . $this->item->id); ?>">
            <?php echo JHtml::_('string.abridge', $this->item->title); ?>
        </a>

        <div class="muted item-actions">
            <?php echo $this->renderItemActions($this->item); ?>
        </div>
    </td>

    <td>
        <?php echo $this->item->username; ?>
    </td>

    <td>
        <?php echo JHtml::_('date', $this->item->created_at, 'DATE_FORMAT_LC2'); ?>
    </td>
</tr>
