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

class BlogFactoryBackendViewBlogs extends BlogFactoryBackendView
{
    protected
        $variables = array('items', 'pagination', 'state', 'listDirn', 'listOrder', 'sortFields'),
        $buttons = array('add', 'edit', 'export', 'publish', 'unpublish', 'delete'),
        $jhtmls = array('behavior.tooltip', 'formbehavior.chosen/select'),
        $js = array('admin/list'),
        $filters = array('published');

    protected function getSortFields()
    {
        return array(
            'b.title' => JText::_('JGLOBAL_TITLE'),
            'b.published' => JText::_('JSTATUS'),
            'u.username' => BlogFactoryText::_('blogs_heading_owner'),
            'b.created_at' => JText::_('JDATE'),
            'b.id' => JText::_('JGRID_HEADING_ID'),
        );
    }
}
