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
    <th class="heading-username">
        <?php echo JHtml::_('BlogFactoryList.heading', 'managefollowers_heading_username', 'username', $this->sort, $this->order); ?>
    </th>

    <th class="heading-date">
        <?php echo JHtml::_('BlogFactoryList.heading', 'managefollowers_heading_date', 'date', $this->sort, $this->order); ?>
    </th>
</tr>
