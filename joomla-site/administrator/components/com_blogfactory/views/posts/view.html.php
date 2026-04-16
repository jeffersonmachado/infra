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

class BlogFactoryBackendViewPosts extends BlogFactoryBackendView
{
    protected
        $variables = array('items', 'pagination', 'state', 'listDirn', 'listOrder', 'sortFields', 'batchBlogs', 'importForm'),
        $buttons = array('add', 'edit', 'export', 'import', 'batch', 'publish', 'unpublish', 'delete'),
        $jhtmls = array('behavior.tooltip', 'formbehavior.chosen/select'),
        $js = array('admin/list'),
        $filters = array('published', 'category');

    protected function getFilterCategory()
    {
        return JHtml::_('category.options', 'com_blogfactory');
    }

    protected function getSortFields()
    {
        return array(
            'p.title' => JText::_('JGLOBAL_TITLE'),
            'p.published' => JText::_('JSTATUS'),
            'u.username' => BlogFactoryText::_('blogs_heading_owner'),
            'p.created_at' => JText::_('JDATE'),
            'c.title' => JText::_('JCATEGORY'),
            'p.id' => JText::_('JGRID_HEADING_ID'),
            'b.title' => BlogFactoryText::_('blogs_heading_blog_title'),
        );
    }
}
