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

class BlogFactoryFrontendViewManageComments extends BlogFactoryFrontendView
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
        'approval'
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

    protected function renderPostActions($item)
    {
        $html = array();

        $html[] = '<a class="comment-approve" rel="' . $item->published . '"';
        $html[] = 'href="' . BlogFactoryRoute::task('post.publish&format=raw&&id=' . $item->id) . '">';
        $html[] = '<i class="factory-icon-loader" style="display: none;"></i>';
        $html[] = '<span>' . BlogFactoryText::plural('managecomments_comment_approve', $item->approved) . '</span>';
        $html[] = '</a>';

        $html[] = '<span class="spacer">|</span>';

        $html[] = '<a href="#">' . BlogFactoryText::_('manageposts_post_edit') . '</a>';

        $html[] = '<span class="spacer">|</span>';

        $html[] = '<a class="comment-delete"';
        $html[] = 'href="' . BlogFactoryRoute::task('post.delete&format=raw&id=' . $item->id) . '">';
        $html[] = '<i class="factory-icon-loader" style="display: none;"></i>';
        $html[] = '<span>' . BlogFactoryText::_('manangeposts_post_delete') . '</span>';
        $html[] = '</a>';

        if ($item->reported) {
            $html[] = '<span class="spacer">|</span>';

            $html[] = '<a href="#">' . BlogFactoryText::_('managecomments_comment_resolve_report') . '</a>';
        }

        return implode('', $html);
    }
}
