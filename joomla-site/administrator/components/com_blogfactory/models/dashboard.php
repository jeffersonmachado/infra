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

class BlogFactoryBackendModelDashboard extends JModelLegacy
{
    protected $limit = 5;

    public function getTable($name = '', $prefix = 'BlogFactoryTable', $options = array())
    {
        return parent::getTable($name, $prefix, $options);
    }

    public function getPosts()
    {
        $dbo = $this->getDbo();
        $post = $this->getTable('Post');

        $query = $dbo->getQuery(true)
            ->select('p.id, p.title, p.blog_id, p.created_at, p.user_id')
            ->from($dbo->qn((string)$post) . ' p')
            ->order('p.created_at DESC');

        // Select author username.
        $query->select('u.username')
            ->leftJoin('#__users u ON u.id = p.user_id');

        $results = $dbo->setQuery($query, 0, $this->limit)
            ->loadObjectList();

        return $results;
    }

    public function getBlogs()
    {
        $dbo = $this->getDbo();
        $blog = $this->getTable('Blog');

        $query = $dbo->getQuery(true)
            ->select('b.id, b.title, b.created_at, b.user_id')
            ->from($dbo->qn((string)$blog) . ' b')
            ->order('b.created_at DESC');

        // Select blog owner username.
        $query->select('u.username')
            ->leftJoin('#__users u ON u.id = b.user_id');

        $results = $dbo->setQuery($query, 0, $this->limit)
            ->loadObjectList();

        return $results;
    }

    public function getComments()
    {
        $dbo = $this->getDbo();
        $comment = $this->getTable('Comment');
        $post = $this->getTable('Post');

        $query = $dbo->getQuery(true)
            ->select('c.id, c.text, c.created_at, c.post_id')
            ->from($dbo->qn((string)$comment) . ' c')
            ->order('c.created_at DESC');

        // Select post title.
        $query->select('p.title AS post_title')
            ->leftJoin($dbo->qn((string)$post) . ' p ON p.id = c.post_id');

        $results = $dbo->setQuery($query, 0, $this->limit)
            ->loadObjectList();

        return $results;
    }

    public function getTopRatedPosts()
    {
        $dbo = $this->getDbo();
        $post = $this->getTable('Post');

        $query = $dbo->getQuery(true)
            ->select('p.id, p.title, p.blog_id, p.created_at, p.user_id, p.votes_up, p.votes_down')
            ->from($dbo->qn((string)$post) . ' p')
            ->order('p.votes_up - p.votes_down DESC');

        $results = $dbo->setQuery($query, 0, $this->limit)
            ->loadObjectList();

        return $results;
    }

    public function getStatistics()
    {
        $statistics = array();

        $dbo = $this->getDbo();
        $blog = $this->getTable('Blog');
        $post = $this->getTable('Post');
        $comment = $this->getTable('Comment');

        // Get blogs created this week.
        $date = JFactory::getDate('this week midnight');

        $query = $dbo->getQuery(true)
            ->select('COUNT(1) AS total')
            ->from($dbo->qn((string)$blog) . ' b')
            ->where('b.created_at > ' . $dbo->q($date->toSql()));

        $statistics['blogs_this_week'] = $dbo->setQuery($query)
            ->loadResult();

        // Get total blogs.
        $query = $dbo->getQuery(true)
            ->select('COUNT(1) AS total')
            ->from($dbo->qn((string)$blog) . ' b');

        $statistics['blogs_total'] = $dbo->setQuery($query)
            ->loadResult();

        // Get posts created this week.
        $date = JFactory::getDate('this week midnight');

        $query = $dbo->getQuery(true)
            ->select('COUNT(1) AS total')
            ->from($dbo->qn((string)$post) . ' p')
            ->where('p.created_at > ' . $dbo->q($date->toSql()));

        $statistics['posts_this_week'] = $dbo->setQuery($query)
            ->loadResult();

        // Get total posts.
        $query = $dbo->getQuery(true)
            ->select('COUNT(1) AS total')
            ->from($dbo->qn((string)$post) . ' p');

        $statistics['posts_total'] = $dbo->setQuery($query)
            ->loadResult();

        // Get comments created this week.
        $date = JFactory::getDate('this week midnight');

        $query = $dbo->getQuery(true)
            ->select('COUNT(1) AS total')
            ->from($dbo->qn((string)$comment) . ' c')
            ->where('c.created_at > ' . $dbo->q($date->toSql()));

        $statistics['comments_this_week'] = $dbo->setQuery($query)
            ->loadResult();

        // Get total comments.
        $query = $dbo->getQuery(true)
            ->select('COUNT(1) AS total')
            ->from($dbo->qn((string)$comment) . ' c');

        $statistics['comments_total'] = $dbo->setQuery($query)
            ->loadResult();

        return $statistics;
    }

    public function getSetup($default = 'statistics/1/0;blogs/1/0;comments/1/0;posts/1/1;top_rated_posts/1/1')
    {
        $state = explode(';', JFactory::getApplication()->input->cookie->getString('com_blogfactory_backend_dashboard', $default));
        $array = array();

        foreach ($state as $item) {
            list($portlet, $minimized, $column) = explode('/', $item);

            $array[$column][$portlet] = $minimized;
        }

        return $array;
    }
}
