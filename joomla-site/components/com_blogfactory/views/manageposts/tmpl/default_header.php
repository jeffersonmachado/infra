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

<tr>
    <th class="heading-check-all center"><input type="checkbox" class="check-all"/></th>

    <th>
        <?php echo JHtml::_('BlogFactoryList.heading', 'manageposts_heading_title', 'title', $this->sort, $this->order); ?>
    </th>

    <th class="heading-category hidden-phone"><?php echo BlogFactoryText::_('manageposts_heading_category'); ?></th>

    <th class="heading-comments hidden-phone hidden-tablet">
        <?php echo JHtml::_('BlogFactoryList.heading', 'manageposts_heading_comments', 'comments', $this->sort, $this->order); ?>
    </th>

    <th class="heading-date hidden-phone">
        <?php echo JHtml::_('BlogFactoryList.heading', 'manageposts_heading_date', 'date', $this->sort, $this->order); ?>
    </th>
</tr>
