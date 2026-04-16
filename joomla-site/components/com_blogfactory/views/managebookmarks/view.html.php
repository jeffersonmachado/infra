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

class BlogFactoryFrontendViewManageBookmarks extends BlogFactoryFrontendView
{
    protected
        $variables = array(
        'items',
        'pagination',
        'status',
        'sort',
        'order',
        'search',
        'filterTotals',
        'viewName',
        'post',
    ),
        $css = array('list', 'icons', 'tooltip', 'notification'),
        $js = array('list', 'tooltip', 'notification'),
        $jhtmls = array('formbehavior.chosen/select', 'jquery.framework', 'bootstrap.tooltip');

    protected function getViewName()
    {
        return $this->getName();
    }

    protected function getSearch()
    {
        $search = JFactory::getApplication()->input->getString('search', '');

        return htmlentities($search, ENT_COMPAT, 'UTF-8');
    }

    protected function renderItemActions($item)
    {
        $html = array();

        $html[] = '<a class="bookmark-delete item-delete"';
        $html[] = 'href="' . BlogFactoryRoute::task('managebookmark.delete&id=' . $item->id) . '">';
        $html[] = '<i class="factory-icon-loader" style="display: none;"></i>';
        $html[] = '<span>' . BlogFactoryText::_('manangebookmarks_bookmark_delete') . '</span>';
        $html[] = '</a>';

        return implode('', $html);
    }
}
