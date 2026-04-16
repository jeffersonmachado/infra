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

class BlogFactoryFrontendViewManagePosts extends BlogFactoryFrontendView
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
    ),
        $css = array('list', 'icons', 'tooltip', 'notification'),
        $js = array('list', 'tooltip', 'notification'),
        $jhtmls = array('formbehavior.chosen/select', 'jquery.framework', 'bootstrap.tooltip');

    protected function getSearch()
    {
        $search = JFactory::getApplication()->input->getString('search', '');

        return htmlentities($search, ENT_COMPAT, 'UTF-8');
    }

    protected function renderPostActions($item)
    {
        $html = array();

        $html[] = '<a class="post-publish" rel="' . $item->published . '"';
        $html[] = 'href="' . BlogFactoryRoute::task('post.publish&format=raw&&id=' . $item->id) . '">';
        $html[] = '<i class="factory-icon-loader" style="display: none;"></i>';
        $html[] = '<span>' . BlogFactoryText::plural('manageposts_post_publish', $item->published) . '</span>';
        $html[] = '</a>';

        $html[] = '<span class="spacer">|</span>';

        $html[] = '<a href="' . BlogFactoryRoute::view('postedit&id=' . $item->id) . '">' . BlogFactoryText::_('manageposts_post_edit') . '</a>';

        $html[] = '<span class="spacer">|</span>';

        $html[] = '<a class="post-delete"';
        $html[] = 'href="' . BlogFactoryRoute::task('post.delete&format=raw&id=' . $item->id) . '">';
        $html[] = '<i class="factory-icon-loader" style="display: none;"></i>';
        $html[] = '<span>' . BlogFactoryText::_('manangeposts_post_delete') . '</span>';
        $html[] = '</a>';

        $html[] = '<span class="spacer">|</span>';

        $html[] = '<a href="' . BlogFactoryRoute::view('post&id=' . $item->id . '&alias=' . $item->alias) . '">' . BlogFactoryText::_('manageposts_post_preview') . '</a>';

        return implode('', $html);
    }
}
