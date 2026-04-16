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

class BlogFactoryFrontendModelBlog extends JModelLegacy
{
    protected $start;
    protected $limit;

    public function __construct($config = array())
    {
        parent::__construct($config);

        $this->start = JFactory::getApplication()->input->getInt('limitstart', 0);
        $this->limit = 10;
    }

    public function getBlog()
    {
        static $blogs = array();

        // Initialise variables.
        $alias = JFactory::getApplication()->input->getString('alias');
        $id = JFactory::getApplication()->input->getInt('id');
        $table = $this->getTable('Blog', 'BlogFactoryTable');
        $blog = array('id' => $id, 'published' => 1);
        $hash = md5(implode('.', $blog));

        // Find blog.
        if (!isset($blogs[$hash])) {
            $blogs[$hash] = $table->load($blog) ? $table : false;
        }

        // Find blog.
        if (!isset($blogs[$hash]) || !$blogs[$hash]) {
            throw new Exception(BlogFactoryText::_('blog_error_not_found'), 404);
        }

        return $blogs[$hash];
    }

    public function getPosts()
    {
        $dbo = $this->getDbo();
        $query = $this->getQuery();

        $results = $dbo->setQuery($query, $this->start, $this->limit)
            ->loadObjectList();

        return $results;
    }

    public function getPagination()
    {
        $total = $this->getTotal();
        $pagination = new BlogFactoryPagination($total, $this->start, $this->limit);

        return $pagination;
    }

    public function bookmark($blogId, $userId, $bookmarked)
    {
        // Initialise variables.
        $bookmark = $this->getTable('Follower', 'BlogFactoryTable');
        $data = array('user_id' => $userId, 'blog_id' => $blogId);

        // Check if user is logged in.
        if (!$userId) {
            $this->setState('error', BlogFactoryText::_('blog_bookmark_error_login'));
            return false;
        }

        // Check if blog exists.
        $blog = $this->getTable('Blog', 'BlogFactoryTable');
        if (!$blogId || !$blog->load($blogId)) {
            $this->setState('error', BlogFactoryText::_('blog_bookmark_error_not_found'));
            return false;
        }

        // Blog is bookmarked.
        if ($bookmarked) {
            // Check if bookmark exists.
            if (!$bookmark->load($data)) {
                $this->setState('error', BlogFactoryText::_('blog_bookmark_error_bookmark_not_found'));
                return false;
            }

            // Remove bookmark.
            if (!$bookmark->delete()) {
                return false;
            }

            $this->setState('bookmarks', $this->getBlogBookmarks($blogId));

            return true;
        }

        // Blog is not bookmarked, check if bookmark exists.
        if ($bookmark->load($data)) {
            $this->setState('error', BlogFactoryText::_('blog_bookmark_error_bookmark_already_exists'));
            return false;
        }

        if (!$bookmark->save($data)) {
            return false;
        }

        $this->setState('bookmarks', $this->getBlogBookmarks($blogId));

        return true;
    }

    public function subscribe($blogId, $userId, $subscribed)
    {
        // Initialise variables.
        $subscription = $this->getTable('Subscription', 'BlogFactoryTable');
        $data = array('user_id' => $userId, 'blog_id' => $blogId);

        // Check if user is logged in.
        if (!$userId) {
            $this->setState('error', BlogFactoryText::_('blog_subscribe_error_login'));
            return false;
        }

        // Check if blog exists.
        $blog = $this->getTable('Blog', 'BlogFactoryTable');
        if (!$blogId || !$blog->load($blogId)) {
            $this->setState('error', BlogFactoryText::_('blog_subscribe_error_not_found'));
            return false;
        }

        // Blog is subscribed.
        if ($subscribed) {
            // Check if subscription exists.
            if (!$subscription->load($data)) {
                $this->setState('error', BlogFactoryText::_('blog_subscribe_error_subscription_not_found'));
                return false;
            }

            // Remove subscription.
            if (!$subscription->delete()) {
                return false;
            }

            return true;
        }

        // Blog is not subscribed, check if subscription exists.
        if ($subscription->load($data)) {
            $this->setState('error', BlogFactoryText::_('blog_subscribe_error_subscription_already_exists'));
            return false;
        }

        if (!$subscription->save($data)) {
            return false;
        }

        return true;
    }

    public function getRss()
    {
        $blog = $this->getBlog();
        $posts = $this->getPosts();

        $dom = new DOMDocument();
        $dom->formatOutput = true;

        $rss = $dom->createElement('rss');
        $channel = $dom->createElement('channel');

        $rss->setAttribute('version', '2.0');
        $rss->setAttribute('xmlns:atom', 'http://www.w3.org/2005/Atom');

        $channel->appendChild($dom->createElement('title', $this->strip($blog->title)));
        $channel->appendChild($dom->createElement('link', BlogFactoryRoute::view('blog&id=' . $blog->id, true, -1)));
        $channel->appendChild($dom->createElement('description', $this->strip($blog->description)));

        $atomLink = $dom->createElement('atom:link');
        $atomLink->setAttribute('href', JUri::getInstance()->toString());
        $atomLink->setAttribute('rel', 'self');
        $atomLink->setAttribute('type', 'application/rss+xml');
        $channel->appendChild($atomLink);

        foreach ($posts as $post) {
            $item = $dom->createElement('item');
            $link = BlogFactoryRoute::view('post&id=' . $post->id . '&alias=' . $post->alias, true, -1);

            $item->appendChild($dom->createElement('title', $this->strip($post->title)));
            $item->appendChild($dom->createElement('link', $link));
            $item->appendChild($dom->createElement('description', $this->strip($post->content)));
            $item->appendChild($dom->createElement('guid', $link));

            $channel->appendChild($item);
        }

        $rss->appendChild($channel);
        $dom->appendChild($rss);

        return $dom;
    }

    protected function getQuery($total = false)
    {
        $model = JModelLegacy::getInstance('Posts', 'BlogFactoryFrontendModel');

        $query = $model->getQuery($total);

        $blogId = JFactory::getApplication()->input->getInt('id');
        $query->where('p.blog_id =' . $this->getDbo()->quote($blogId));

        return $query;
    }

    protected function getTotal()
    {
        $dbo = $this->getDbo();
        $query = $this->getQuery(true);

        $result = $dbo->setQuery($query)
            ->loadResult();

        return $result;
    }

    protected function getBlogBookmarks($id)
    {
        $table = $this->getTable('Follower', 'BlogFactoryTable');
        $dbo = $this->getDbo();

        $query = $dbo->getQuery(true)
            ->select('COUNT(b.id) AS total')
            ->from($dbo->quoteName($table->getTableName()) . ' b')
            ->where('b.blog_id = ' . $dbo->quote($id));
        $result = $dbo->setQuery($query)
            ->loadResult();

        return $result;
    }

    protected function strip($text)
    {
        return str_replace("&nbsp;", " ", $text);
    }
}
