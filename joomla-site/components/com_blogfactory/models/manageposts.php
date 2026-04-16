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

class BlogFactoryFrontendModelManagePosts extends JModelLegacy
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
            'published' => $this->getFilterTotal('published'),
            'scheduled' => $this->getFilterTotal('scheduled'),
            'draft' => $this->getFilterTotal('draft'),
        );
    }

    protected function getQuery($total = false)
    {
        // Initialise variables.
        $dbo = $this->getDbo();
        $user = JFactory::getUser();

        // Get main query.
        $query = $dbo->getQuery(true)
            ->from('#__com_blogfactory_posts p')
            ->where('p.user_id = ' . $dbo->quote($user->id));

        // Filter by status.
        $this->addQueryFilterStatus($query, $this->getStatus());

        // Filter by search.
        $search = JFactory::getApplication()->input->getString('search');
        if ('' != $search) {
            $query->where('(p.title LIKE ' . $dbo->quote('%' . $search . '%') . ' OR p.content LIKE ' . $dbo->quote('%' . $search . '%') . ')');
        }

        if ($total) {
            $query->select('COUNT(p.id) AS total');
        } else {
            $query->select('p.*')
                ->group('p.id');

            // Select category.
            $query->select('c.title AS category_title')
                ->leftJoin('#__categories c ON c.id = p.category_id AND c.extension = ' . $dbo->quote('com_blogfactory'));

            // Select number of comments.
            $query->select('COUNT(comm.id) AS comments')
                ->leftJoin('#__com_blogfactory_comments comm ON comm.post_id = p.id');

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

        if (!in_array($sort, array('title', 'comments', 'date'))) {
            $sort = 'date';
        }
        if (!in_array($order, array('asc', 'desc'))) {
            $order = 'asc';
        }

        switch ($sort) {
            case 'title':
                $query->order('p.title ' . $order);
                break;

            case 'comments':
                $query->order('comments ' . $order);
                break;

            case 'date':
                $query->order('IF (p.published, p.publish_up, p.updated_at) ' . $order);
                break;
        }
    }

    protected function addQueryFilterStatus($query, $status)
    {
        if ('' == $status) {
            return true;
        }

        $date = JFactory::getDate();

        switch ($status) {
            case 'scheduled':
                $query->where('(p.published = ' . $query->quote(1) . ' AND p.publish_up > ' . $query->quote($date->toSql()) . ')');
                break;

            case 'published':
                $query->where('(p.published = ' . $query->quote(1) . ' AND p.publish_up < ' . $query->quote($date->toSql()) . ')');
                break;

            case 'draft':
                $query->where('p.published = ' . $query->quote(0));
                break;
        }

        return true;
    }

    protected function getFilterTotal($type)
    {
        $dbo = $this->getDbo();
        $user = JFactory::getUser();
        $date = JFactory::getDate();

        $query = $dbo->getQuery(true)
            ->select('COUNT(p.id) AS total')
            ->from('#__com_blogfactory_posts p')
            ->where('p.user_id = ' . $dbo->quote($user->id));

        switch ($type) {
            case 'published':
                $query->where('(p.published = ' . $dbo->quote(1) . ' AND p.publish_up < ' . $dbo->quote($date->toSql()) . ')');
                break;

            case 'scheduled':
                $query->where('(p.published = ' . $dbo->quote(1) . ' AND p.publish_up > ' . $dbo->quote($date->toSql()) . ')');
                break;

            case 'draft':
                $query->where('p.published = ' . $dbo->quote(0));
                break;
        }

        $result = $dbo->setQuery($query)
            ->loadResult();

        return $result;
    }
}
