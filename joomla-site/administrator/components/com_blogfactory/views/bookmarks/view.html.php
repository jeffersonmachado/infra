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

class BlogFactoryBackendViewBookmarks extends BlogFactoryBackendView
{
    protected
        $variables = array('items', 'pagination', 'state', 'listDirn', 'listOrder', 'sortFields', 'saveOrder'),
        $buttons = array('add', 'edit', 'publish', 'unpublish', 'delete'),
        $jhtmls = array('behavior.tooltip', 'formbehavior.chosen/select'),
        $js = array('admin/list'),
        $filters = array('published');

    protected function getSaveOrder()
    {
        $saveOrder = $this->listOrder == 'b.ordering';

        if ($saveOrder) {
            $saveOrderingUrl = BlogFactoryRoute::task($this->getName() . '.saveOrderAjax&tmpl=component');

            JHtml::_(
                'sortablelist.sortable',
                $this->getName() . 'List',
                'adminForm',
                strtolower($this->listDirn),
                $saveOrderingUrl
            );
        }

        return $saveOrder;
    }

    protected function getSortFields()
    {
        return array(
            'b.title' => JText::_('JGLOBAL_TITLE'),
            'b.published' => JText::_('JSTATUS'),
            'b.ordering' => JText::_('JGRID_HEADING_ORDERING'),
            'b.id' => JText::_('JGRID_HEADING_ID'),
        );
    }
}
