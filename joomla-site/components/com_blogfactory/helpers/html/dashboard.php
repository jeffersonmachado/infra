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

class JHtmlBlogFactoryDashboard
{
    public static function getPortletPostsItem($item)
    {
        $html = array();

        $html[] = '<div class="portlet-posts-post ' . (!$item->published ? 'post-pending' : '') . '">';
        $html[] = '<a href="' . BlogFactoryRoute::view('postedit&id=' . $item->id) . '">' . ('' == $item->title ? BlogFactoryText::_('post_untitled') : $item->title) . '</a>';
        $html[] = '<span class="muted small">' . JHtml::_('date', $item->created_at, 'DATE_FORMAT_LC2') . '</span>';
        $html[] = '<div>' . JHtml::_('string.truncate', strip_tags($item->content), 200) . '</div>';
        $html[] = '</div>';

        return implode("\n", $html);
    }

    public static function portletCommentsItemActions($item)
    {
        $settings = JComponentHelper::getParams('com_blogfactory');

        BlogFactoryHtml::script('html/comment_actions');
        BlogFactoryHtml::stylesheet('html/comment_actions');

        $html = array();

        $html[] = '<div class="muted comment-actions">';

        // Approve / Unapprove button.
        if (1 == $settings->get('comments.approval', 0)) {
            $href = BlogFactoryRoute::task('comment.approve&format=raw&id=' . $item->id);
            $label = BlogFactoryText::plural('dashboard_portlet_comments_comment_action_approve', $item->approved);

            $html[] = '<a class="comment-approve" rel="' . $item->approved . '" href="' . $href . '">';
            $html[] = '<i class="factory-icon-loader" style="display: none;"></i>';
            $html[] = '<span>' . $label . '</span>';
            $html[] = '</a>';

            $html[] = '<span class="spacer">|</span>';
        }

        // Edit button.
        $href = BlogFactoryRoute::view('managecomment&id=' . $item->id);
        $label = BlogFactoryText::_('dashboard_portlet_comments_comment_action_edit');
        $html[] = '<a href="' . $href . '">' . $label . '</a>';

        $html[] = '<span class="spacer">|</span>';

        // Delete button.
        $href = BlogFactoryRoute::task('comment.delete&format=raw&id=' . $item->id);
        $label = BlogFactoryText::_('dashboard_portlet_comments_comment_action_delete');

        $html[] = '<a class="comment-delete" href="' . $href . '">';
        $html[] = '<i class="factory-icon-loader" style="display: none;"></i>';
        $html[] = '<span>' . $label . '</span>';
        $html[] = '</a>';

        // Report button.
        if ($item->reported) {
            $html[] = '<span class="spacer">|</span>';

            $href = BlogFactoryRoute::task('comment.resolve&format=raw&id=' . $item->id);
            $html[] = '<a href="' . $href . '" class="comment-resolve">';
            $html[] = '<i class="factory-icon-loader" style="display: none;"></i>';
            $html[] = BlogFactoryText::_('managecomments_comment_resolve_report');
            $html[] = '</a>';
        }

        $html[] = '</div>';

        return implode('', $html);
    }

    public static function manageCommentsFilterPost($id = null)
    {
        $uri = JUri::getInstance();

        if (is_null($id)) {
            $uri->delVar('post');
        } else {
            $uri->setVar('post', $id);
        }

        return $uri->toString();
    }
}
