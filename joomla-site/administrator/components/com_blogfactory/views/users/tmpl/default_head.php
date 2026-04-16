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
    <th width="1%" class="hidden-phone">
        <input type="checkbox" name="checkall-toggle" value="" title="<?php echo JText::_('JGLOBAL_CHECK_ALL'); ?>"
               onclick="Joomla.checkAll(this)"/>
    </th>

    <th class="nowrap hidden-phone">
        <?php echo JHtml::_('grid.sort', BlogFactoryText::_('users_heading_user'), 'u.username', $this->listDirn, $this->listOrder); ?>
    </th>

    <th width="30%" class="nowrap hidden-phone">
        <?php echo JHtml::_('grid.sort', BlogFactoryText::_('users_heading_blog'), 'b.title', $this->listDirn, $this->listOrder); ?>
    </th>

    <th width="1%" class="nowrap hidden-phone">
        <?php echo JHtml::_('grid.sort', 'JGRID_HEADING_ID', 'p.id', $this->listDirn, $this->listOrder); ?>
    </th>
</tr>
