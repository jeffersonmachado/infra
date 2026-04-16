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

class BlogFactoryFrontendViewBlogs extends BlogFactoryFrontendView
{
    protected
        $variables = array('blogs', 'pagination', 'filterBookmark', 'order', 'sort', 'search'),
        $css = array('icons', 'notification'),
        $js = array('tooltip', 'notification'),
        $jhtmls = array('formbehavior.chosen/select', 'bootstrap.framework');

    protected function getFilterBookmark()
    {
        $filter = JFactory::getApplication()->input->get('filter', array(), 'array');
        $value = isset($filter['bookmarked']) ? $filter['bookmarked'] : '';
        $options = array(
            '' => BlogFactoryText::_('blogs_filter_bookmark_heading'),
            1 => BlogFactoryText::_('blogs_filter_bookmark_bookmarked'),
            0 => BlogFactoryText::_('blogs_filter_bookmark_unbookmarked'),
        );

        $select = JHtml::_(
            'select.genericlist',
            $options,
            'filter[bookmarked]',
            '',
            'value',
            'text',
            $value,
            'filter_bookmarked'
        );

        return $select;
    }

    protected function getSort()
    {
        $value = JFactory::getApplication()->input->getCmd('sort', '');
        $options = array(
            '' => BlogFactoryText::_('blogs_sort_heading'),
            'title' => BlogFactoryText::_('blogs_sort_title'),
            'posts' => BlogFactoryText::_('blogs_sort_posts'),
            'followers' => BlogFactoryText::_('blogs_sort_followers'),
            'activity' => BlogFactoryText::_('blogs_sort_activity'),
        );

        $select = JHtml::_(
            'select.genericlist',
            $options,
            'sort',
            '',
            'value',
            'text',
            $value,
            'sort'
        );

        return $select;
    }

    protected function getOrder()
    {
        $value = JFactory::getApplication()->input->getCmd('order', '');
        $options = array(
            '' => BlogFactoryText::_('blogs_order_heading'),
            'asc' => BlogFactoryText::_('blogs_order_asc'),
            'desc' => BlogFactoryText::_('blogs_order_desc'),
        );

        $select = JHtml::_(
            'select.genericlist',
            $options,
            'order',
            '',
            'value',
            'text',
            $value,
            'order'
        );

        return $select;
    }

    protected function getSearch()
    {
        $search = JFactory::getApplication()->input->getString('search', '');

        return htmlentities($search, ENT_COMPAT, 'UTF-8');
    }
}
