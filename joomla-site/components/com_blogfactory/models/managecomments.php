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

class BlogFactoryFrontendModelManageComments extends JModelLegacy
{
    protected $start;
    protected $limit = 10;

    public function __construct($config = array())
    {
        parent::__construct($config);

        $this->start = JFactory::getApplication()->input->getInt('limitstart', 0);
    }

    public function getItems()
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

    public function getStatus()
    {
        return JFactory::getApplication()->input->getCmd('status', null);
    }

    public function getSort()
    {
        return JFactory::getApplication()->input->getCmd('sort', 'date');
    }

    public function getOrder()
    {
        return JFactory::getApplication()->input->getCmd('order', 'desc');
    }

    public function getFilterTotals()
    {
        return array(
            'all' => $this->getFilterTotal('all'),
            'approved' => $this->getFilterTotal('approved'),
            'pending' => $this->getFilterTotal('pending'),
            'reported' => $this->getFilterTotal('reported'),
        );
    }

    public function getPost()
    {
        static $posts = array();

        $id = JFactory::getApplication()->input->getInt('post');

        if (!isset($posts[$id])) {
            $posts[$id] = false;
            $table = $this->getTable('Post', 'BlogFactoryTable');

            if ($id && $table->load($id)) {
                $posts[$id] = $table;
            }
        }

        return $posts[$id];
    }

    public function getApproval()
    {
        return JComponentHelper::getParams('com_blogfactory')->get('comments.approval', 0);
    }

    protected function getQuery($total = false)
    {
        // Initialise variables.
        $dbo = $this->getDbo();
        $user = JFactory::getUser();

        // Get main query.
        $query = $dbo->getQuery(true)
            ->from('#__com_blogfactory_comments c')
            ->leftJoin('#__com_blogfactory_posts p ON p.id = c.post_id')
            ->where('p.user_id = ' . $dbo->quote($user->id));

        // Filter by status.
        $this->addQueryFilterStatus($query, $this->getStatus());

        if ($post = $this->getPost()) {
            $query->where('c.post_id = ' . $dbo->quote($post->id));
        }

        if ($total) {
            $query->select('COUNT(c.id) AS total');
        } else {
            $query->select('c.*')
                ->select('p.title AS post_title')
                ->group('c.id');

            // Select the total number of comment for the post.
            $query->select('COUNT(t.id) AS comments_total')
                ->leftJoin('#__com_blogfactory_comments t ON t.post_id = p.id');

            // Select the profile.
            $query->select('prf.avatar_source, prf.avatar, prf.name AS profile_name, prf.url AS profile_url')
                ->leftJoin('#__com_blogfactory_profiles prf ON prf.id = c.user_id');

            // Select user email.
            $query->select('u.email AS user_email, u.username')
                ->leftJoin('#__users u ON u.id = c.user_id');

            // Order items.
            $this->addQueryOrderItems($query);
        }

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

    protected function addQueryOrderItems($query)
    {
        $input = JFactory::getApplication()->input;
        $order = $input->getCmd('order', 'desc');
        $sort = $input->getCmd('sort', 'date');

        if (!in_array($sort, array('author', 'date'))) {
            $sort = 'date';
        }
        if (!in_array($order, array('asc', 'desc'))) {
            $order = 'asc';
        }

        switch ($sort) {
            case 'author':
                break;

            case 'date':
                $query->order('c.created_at ' . $order);
                break;
        }
    }

    protected function addQueryFilterStatus($query, $status)
    {
        switch ($status) {
            case 'approved':
                $query->where('c.approved = ' . $query->quote(1));
                break;

            case 'pending':
                $query->where('c.approved = ' . $query->quote(0));
                break;

            case 'reported':
                $query->where('c.reported = ' . $query->quote(1));
                break;
        }

        return true;
    }

    protected function getFilterTotal($type)
    {
        $dbo = $this->getDbo();
        $user = JFactory::getUser();

        $query = $dbo->getQuery(true)
            ->select('COUNT(c.id) AS total')
            ->from('#__com_blogfactory_comments c')
            ->leftJoin('#__com_blogfactory_posts p ON p.id = c.post_id')
            ->where('p.user_id = ' . $dbo->quote($user->id));

        if ($post = $this->getPost()) {
            $query->where('c.post_id = ' . $dbo->quote($post->id));
        }

        switch ($type) {
            case 'approved':
                $query->where('c.approved = ' . $dbo->quote(1));
                break;

            case 'pending':
                $query->where('c.approved = ' . $dbo->quote(0));
                break;

            case 'reported':
                $query->where('c.reported = ' . $dbo->quote(1));
                break;
        }

        $result = $dbo->setQuery($query)
            ->loadResult();

        return $result;
    }
}
