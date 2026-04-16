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

class JHtmlBlogFactoryComments
{
    protected static $limit = 5;
    protected static $start;

    public static function display($postId, $config = array())
    {
        // Initialise variables.
        $settings = JComponentHelper::getParams('com_blogfactory');
        self::$start = JFactory::getApplication()->input->getInt('limitstart', 0);

        if (isset($config['limit'])) {
            self::$limit = $config['limit'];
        } else {
            self::$limit = $settings->get('comments.pagination.limit', 10);
        }

        $html = array();
        $list = self::getList($postId);
        $pagination = self::getPagination($postId);

        // Load assets.
        JHtml::_('jquery.framework');
        JHtml::_('bootstrap.framework');
        JHtml::_('behavior.tooltip');

        BlogFactoryHtml::stylesheets(array('icons', 'comments'));
        BlogFactoryHtml::script('modal');
        BlogFactoryHtml::script('tooltip');
        BlogFactoryHtml::script('notification');
        BlogFactoryHtml::script('comments');
        BlogFactoryHtml::stylesheet('modal');

        $html[] = '<div class="blogfactory-comments-post" id="blogfactory-comments-post-' . $postId . '">';
        $html[] = '<a name="comments"></a>';

        if ($list) {
            // Render total.
            $html[] = self::displayTotal($pagination->total);

            // Render pagination.
            $html[] = $pagination->getPagesLinks();

            // Render comments list.
            $html[] = self::displayList($list);

            // Render pagination.
            $html[] = $pagination->getPagesLinks();
        }

        // Add comment.
        $html[] = self::displayAddReply($postId, $config);

        $html[] = '</div>';

        return implode("\n", $html);
    }

    public static function getAvatarSource($item)
    {
        $default = JUri::root() . 'components/com_blogfactory/assets/images/user.png';
        $settings = JComponentHelper::getParams('com_blogfactory');

        if (!$settings->get('avatars.enable.avatars', 1)) {
            return $default;
        }

        switch ($item->avatar_source) {
            case 'none':
            default:
                return $default;

            case 'gravatar':
                if (!$settings->get('avatars.enable.gravatars', 1)) {
                    return $default;
                }

                return 'http://www.gravatar.com/avatar/' . md5(strtolower(trim($item->user_email))) . '?d=' . urlencode($default) . '&s=48';

            case 'cb':
                if (!$settings->get('avatars.enable.cb', 0)) {
                    return $default;
                }

                try {
                    if (!file_exists(JPATH_ADMINISTRATOR . '/components/com_comprofiler/plugin.foundation.php')) {
                        throw new Exception('CB not installed!');
                    }

                    include_once(JPATH_ADMINISTRATOR . '/components/com_comprofiler/plugin.foundation.php');

                    $cbUser = CBuser::getInstance($item->user_id);
                    $avatar = $cbUser->getField('avatar', null, 'csv', 'none', 'list');
                } catch (Exception $e) {
                    $avatar = $default;
                }

                return $avatar;

            case 'upload':
                if (!$item->avatar) {
                    return $default;
                }

                jimport('joomla.filesystem.file');

                $path = JPATH_SITE . '/media/com_blogfactory/avatars/' . $item->avatar;
                if (!JFile::exists($path)) {
                    return $default;
                }

                return JUri::root() . 'media/com_blogfactory/avatars/' . $item->avatar;
        }
    }

    protected static function displayTotal($total)
    {
        $html = array();

        $html[] = '<div class="comments-total comments-title">';
        $html[] = BlogFactoryText::plural('comments_total', $total);
        $html[] = '</div>';

        return implode("\n", $html);
    }

    protected static function displayList($list)
    {
        $html = array();
        $settings = JComponentHelper::getParams('com_blogfactory');
        $user = JFactory::getUser();

        foreach ($list as $item) {
            $html[] = '<div class="blogfactory-post-comment" id="blogfactory-post-comment-' . $item->id . '">';

            if ('pingback' == $item->email) {
                $html[] = BlogFactoryText::_('comments_comment_pingback');
                $html[] = '<a href="' . $item->url . '">' . $item->text . '</a>';
                $html[] = '</div>';

                continue;
            }

            $avatar = self::getAvatarSource($item);

            $html[] = '<div class="comment-user-photo">';
            $html[] = '<img src="' . $avatar . '" />';
            $html[] = '</div>';

            $html[] = '<div class="comment-bubble">';

            $html[] = '<div class="comment-user">';

            //$name = $item->url ? '<a href="' . $item->url . '" rek="nofollow">' . $item->name . '</a>' : $item->name;
            $name = JHtml::_('BlogFactoryBlog.commentAuthorLink', $item);

            $date = JHtml::date($item->created_at, 'DATE_FORMAT_LC2');

            $html[] = BlogFactoryText::sprintf('comment_user', $name, $date);
            $html[] = '</div>';

            if ($settings->get('comments.approval', 0) && !$item->approved) {
                $html[] = '<div class="comment-pending">';
                $html[] = BlogFactoryText::_('comments_comment_pending_approval');
                $html[] = '</div>';
            }

            $html[] = '<div class="comment-text">';
            $html[] = nl2br($item->text);
            $html[] = '</div>';

            if ((!$settings->get('comments.approval', 0) || $item->approved) && 'pingback' != $item->email) {
                $html[] = '<div class="comment-actions">';

                $html[] = self::displayRatings($item);

                $html[] = '<div class="comment-user-actions">';

                if (($item->user_id && $user->id == $item->user_id) || $user->id == $item->post_user_id) {
                    $html[] = '<a href="' . BlogFactoryRoute::task('comment.delete&format=raw&id=' . $item->id) . '" class="btn btn-small btn-danger comment-delete"><span style="display: none;"><i class="factory-icon-loader"></i>&nbsp;</span>' . BlogFactoryText::_('comment_delete') . '</a>';
                }

                if ($user->id != $item->user_id) {
                    $html[] = '<a href="' . BlogFactoryRoute::view('report&format=raw&type=comment&id=' . $item->id) . '" class="btn btn-small comment-report">' . BlogFactoryText::_('comment_report') . '</a>';
                }

                $html[] = '</div>';

                $html[] = '</div>';
            }

            $html[] = '</div>';

            $html[] = '</div>';
        }

        return implode("\n", $html);
    }

    protected static function displayRatings($item)
    {
        $settings = JComponentHelper::getParams('com_blogfactory');

        if (!$settings->get('comments.enable.votes', 1)) {
            return false;
        }

        $html = array();
        $user = JFactory::getUser();

        $html[] = '<div class="comment-ratings">';

        if (!isset($item->vote) && $user->id) {
            $html[] = '<a href="#" class="comment-vote-up"><i class="factory-icon-thumb-up"></i><span>' . $item->votes_up . '</span></a>';
            $html[] = '<a href="#" class="comment-vote-down"><i class="factory-icon-thumb-down"></i><span>' . $item->votes_down . '</span></a>';
        } else {
            $html[] = '<span href="#" class="comment-vote-up"><i class="factory-icon-thumb-up"></i><span class="' . (1 == $item->vote ? 'voted' : '') . '">' . $item->votes_up . '</span></span>';
            $html[] = '<span href="#" class="comment-vote-down"><i class="factory-icon-thumb-down"></i><span class="' . (-1 == $item->vote ? 'voted' : '') . '">' . $item->votes_down . '</span></span>';
        }

        $html[] = '</div>';

        return implode("\n", $html);
    }

    protected static function displayAddReply($postId, $config)
    {
        if (isset($config['comments']) && !$config['comments']) {
            return '';
        }

        JHtml::_('behavior.formvalidation');

        $settings = JComponentHelper::getParams('com_blogfactory');
        $user = JFactory::getUser();
        $model = JModelLegacy::getInstance('Comment', 'BlogFactoryFrontendModel');
        $form = $model->getForm();
        $authorised = $user->authorise('frontend.comment.create', 'com_blogfactory');

        $html = array();

        $html[] = '<div class="comments-reply comments-title">' . BlogFactoryText::_('comment_leave_reply') . '</div>';

        if ($authorised) {
            $session = JFactory::getSession();
            $context = 'com_blogfactory.comment.save';

            $data = $session->get($context, array());
            $data['post_id'] = $postId;
            $form->bind($data);

            $session->set($context, null);

            $html[] = '<form novalidate action="' . BlogFactoryRoute::task('comment.save') . '" method="POST" class="form-validate" id="comment-reply">';

            foreach ($form->getFieldset('details') as $field) {
                $html[] = '<div class="control-group">';
                $html[] = '<div class="control-label">' . $field->label . '</div>';
                $html[] = '<div class="controls">' . $field->input . '</div>';
                $html[] = '</div>';
            }

            $html[] = '<button class="btn btn-primary">' . BlogFactoryText::_('comment_post_comment') . '</button>';

            $html[] = '</form>';
        } else {
            $html[] = BlogFactoryText::_('comment_leave_reply_not_allowed');
        }

        return implode("\n", $html);
    }

    protected static function getList($postId)
    {
        $dbo = JFactory::getDbo();
        $query = self::getListQuery($postId);

        $results = $dbo->setQuery($query, self::$start, self::$limit)
            ->loadObjectList();

        return $results;
    }

    protected static function getPagination($postId)
    {
        $total = self::getTotal($postId);
        $pagination = new BlogFactoryPagination($total, self::$start, self::$limit);

        $pagination->setAnchor('comments');

        return $pagination;
    }

    protected static function getTotal($postId)
    {
        $dbo = JFactory::getDbo();
        $query = self::getListQuery($postId, true);

        $result = $dbo->setQuery($query)
            ->loadResult();

        return $result;
    }

    protected static function getListQuery($postId, $total = false)
    {
        $dbo = JFactory::getDbo();
        $user = JFactory::getUser();
        $settings = JComponentHelper::getParams('com_blogfactory');

        $query = $dbo->getQuery(true)
            ->from('#__com_blogfactory_comments c')
            ->where('c.post_id = ' . $dbo->quote($postId));

        // Filter by published.
        $query->where('c.published = ' . $dbo->quote(1));

        // Filter by approved.
        if ($settings->get('comments.approval', 0)) {
            $query->where('(c.approved = ' . $dbo->quote(1) . ' OR (c.user_id = ' . $dbo->quote($user->id) . ' AND c.user_id <> ' . $dbo->q(0) . '))');
        }

        if ($total) {
            $query->select('COUNT(c.id) AS total');
        } else {
            $query->select('c.*');

            // Select the username.
            $query->select('u.username, u.email AS user_email')
                ->leftJoin('#__users u ON u.id = c.user_id');

            // Select the profile.
            $query->select('p.avatar_source, p.avatar, p.name AS profile_name, p.url AS profile_url')
                ->leftJoin('#__com_blogfactory_profiles p ON p.id = c.user_id');

            // Select the vote.
            $query->select('v.vote')
                ->leftJoin('#__com_blogfactory_votes v ON v.item_id = c.id AND v.type = ' . $dbo->quote('comment') . ' AND v.user_id = ' . $user->id);

            // Select the post owner.
            $query->select('post.user_id AS post_user_id')
                ->leftJoin('#__com_blogfactory_posts post ON post.id = c.post_id');

            // Order results.
            $order = 1 == $settings->get('comments.pagination.order', 1) ? 'DESC' : 'ASC';
            $query->order('c.created_at ' . $order);
        }

        return $query;
    }
}
