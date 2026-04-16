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

    <th width="1%" style="min-width:55px" class="nowrap center">
        <?php echo JHtml::_('grid.sort', 'JSTATUS', 'p.published', $this->listDirn, $this->listOrder); ?>
    </th>

    <th>
        <?php echo JHtml::_('grid.sort', 'JGLOBAL_TITLE', 'p.title', $this->listDirn, $this->listOrder); ?>
    </th>

    <th width="20%">
        <?php echo JHtml::_('grid.sort', BlogFactoryText::_('blogs_heading_blog_title'), 'b.title', $this->listDirn, $this->listOrder); ?>
    </th>

    <th width="20%" class="nowrap hidden-phone">
        <?php echo JHtml::_('grid.sort', JText::_('JCATEGORY'), 'c.title', $this->listDirn, $this->listOrder); ?>
    </th>

    <th width="10%" class="nowrap hidden-phone">
        <?php echo JHtml::_('grid.sort', BlogFactoryText::_('blogs_heading_owner'), 'u.username', $this->listDirn, $this->listOrder); ?>
    </th>

    <th width="10%" class="nowrap hidden-phone">
        <?php echo JHtml::_('grid.sort', 'JDATE', 'p.created_at', $this->listDirn, $this->listOrder); ?>
    </th>

    <th width="1%" class="nowrap hidden-phone">
        <?php echo JHtml::_('grid.sort', 'JGRID_HEADING_ID', 'p.id', $this->listDirn, $this->listOrder); ?>
    </th>
</tr>
