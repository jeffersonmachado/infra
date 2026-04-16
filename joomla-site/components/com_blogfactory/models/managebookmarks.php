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

class BlogFactoryFrontendModelManageBookmarks extends JModelLegacy
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

    public function getSort()
    {
        return JFactory::getApplication()->input->getCmd('sort', 'date');
    }

    public function getOrder()
    {
        return JFactory::getApplication()->input->getCmd('order', 'desc');
    }

    protected function getQuery($total = false)
    {
        // Initialise variables.
        $dbo = $this->getDbo();
        $user = JFactory::getUser();

        // Get main query.
        $query = $dbo->getQuery(true)
            ->from('#__com_blogfactory_followers bm')
            ->leftJoin('#__com_blogfactory_blogs b ON b.id = bm.blog_id')
            ->where('bm.user_id = ' . $dbo->quote($user->id));

        if ($total) {
            $query->select('COUNT(bm.id) AS total');
        } else {
            $query->select('b.id, b.title, b.alias, bm.created_at');

            $query->select('u.username')
                ->leftJoin('#__users u ON u.id = b.user_id');

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
        $order = $input->getCmd('order', 'asc');
        $sort = $input->getCmd('sort', 'title');

        if (!in_array($sort, array('title', 'date', 'author'))) {
            $sort = 'title';
        }
        if (!in_array($order, array('asc', 'desc'))) {
            $order = 'asc';
        }

        switch ($sort) {
            case 'title':
                $query->order('b.title ' . $order);
                break;

            case 'date':
                $query->order('bm.created_at ' . $order);
                break;

            case 'author':
                $query->order('u.username ' . $order);
                break;
        }
    }
}
