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

    <th class="heading-title">
        <?php echo JHtml::_('BlogFactoryList.heading', 'managesubscriptions_heading_title', 'title', $this->sort, $this->order); ?>
    </th>

    <th class="heading-author">
        <?php echo JHtml::_('BlogFactoryList.heading', 'managesubscriptions_heading_author', 'author', $this->sort, $this->order); ?>
    </th>

    <th class="heading-date">
        <?php echo JHtml::_('BlogFactoryList.heading', 'managesubscriptions_heading_date', 'date', $this->sort, $this->order); ?>
    </th>
</tr>
