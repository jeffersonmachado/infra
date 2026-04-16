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

class BlogFactoryBackendViewNotifications extends BlogFactoryBackendView
{
    protected
        $variables = array('items', 'pagination', 'state', 'listDirn', 'listOrder', 'sortFields'),
        $buttons = array('add', 'edit', 'publish', 'unpublish', 'delete'),
        $jhtmls = array('behavior.tooltip', 'formbehavior.chosen/select'),
        $js = array('admin/list'),
        $filters = array('published', 'type', 'language');

    protected function getListDirn()
    {
        return $this->state->get('list.direction');
    }

    protected function getListOrder()
    {
        return $this->state->get('list.ordering');
    }

    protected function getFilterType()
    {
        return BlogFactoryHelper::getNotificationTypes();
    }

    protected function getSortFields()
    {
        return array(
            'n.published' => BlogFactoryText::_($this->getName() . '_heading_published'),
            'n.subject' => BlogFactoryText::_($this->getName() . '_heading_subject'),
            'n.type' => BlogFactoryText::_($this->getName() . '_heading_type'),
            'l.title' => JText::_('JGRID_HEADING_LANGUAGE'),
            'n.id' => JText::_('JGRID_HEADING_ID'),
        );
    }
}
