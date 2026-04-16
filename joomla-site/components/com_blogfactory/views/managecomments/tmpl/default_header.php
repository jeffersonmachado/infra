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

    <th class="heading-author hidden-phone">
        <?php echo JHtml::_('BlogFactoryList.heading', 'managecomments_heading_author', 'author', $this->sort, $this->order); ?>
    </th>

    <th class="heading-comment">
        <?php echo BlogFactoryText::_('managecomments_heading_comment'); ?>
    </th>

    <th class="heading-date">
        <?php echo JHtml::_('BlogFactoryList.heading', 'managecomment_heading_date', 'date', $this->sort, $this->order); ?>
    </th>
</tr>
