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

    <?php if ($this->approveComments): ?>
        <th width="1%" style="min-width:55px" class="nowrap center">
            <?php echo JHtml::_('grid.sort', BlogFactoryText::_('comments_heading_approved'), 'c.approved', $this->listDirn, $this->listOrder); ?>
        </th>
    <?php endif; ?>

    <th width="10%" class="nowrap hidden-phone">
        <?php echo JHtml::_('grid.sort', BlogFactoryText::_('comments_heading_username'), 'u.username', $this->listDirn, $this->listOrder); ?>
    </th>

    <th>
        <?php echo JHtml::_('grid.sort', BlogFactoryText::_('comments_heading_comment'), 'c.text', $this->listDirn, $this->listOrder); ?>
    </th>

    <th width="15%" class="nowrap hidden-phone">
        <?php echo JHtml::_('grid.sort', 'JDATE', 'c.created_at', $this->listDirn, $this->listOrder); ?>
    </th>

    <th width="1%" style="min-width:55px" class="nowrap center">
        <?php echo JHtml::_('grid.sort', 'JSTATUS', 'c.published', $this->listDirn, $this->listOrder); ?>
    </th>

    <th width="1%" class="nowrap hidden-phone">
        <?php echo JHtml::_('grid.sort', 'JGRID_HEADING_ID', 'c.id', $this->listDirn, $this->listOrder); ?>
    </th>
</tr>
