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

class BlogFactoryBackendModelBlogs extends JModelList
{
    protected $filters = array('search', 'published');

    public function __construct($config = array())
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = array(
                'b.published', 'b.title', 'b.id', 'u.username', 'b.created_at', 'posts'
            );
        }

        parent::__construct($config);
    }

    public function getTotal()
    {
        $dbo = $this->getDbo();
        $query = $dbo->getQuery(true);

        $query->select('COUNT(1)')
            ->from('#__com_blogfactory_blogs b');

        // Filter by search.
        if ('' != $search = $this->getState('filter.search')) {
            $array = array();

            $array[] = 'b.title LIKE ' . $query->quote('%' . $search . '%');
            $array[] = 'b.description LIKE ' . $query->quote('%' . $search . '%');

            $query->where('(' . implode(' OR ', $array) . ')');
        }

        // Filter by published.
        if ('' !== $published = $this->getState('filter.published')) {
            $query->where('b.published = ' . $query->quote((int)$published));
        }

        $result = $dbo->setQuery($query)
            ->loadResult();

        return $result;
    }

    protected function getListQuery()
    {
        $query = parent::getListQuery();

        $query->select('b.*')
            ->from('#__com_blogfactory_blogs b');

        // Select the username.
        $query->select('u.username')
            ->leftJoin('#__users u ON u.id = b.user_id');

        // Select the number of posts for each blog.
        $query->select('COUNT(p.id) AS posts')
            ->leftJoin('#__com_blogfactory_posts p ON p.blog_id = b.id AND p.published = ' . $query->q(1))
            ->group('b.id');

        // Filter by search.
        if ('' != $search = $this->getState('filter.search')) {
            $array = array();

            $array[] = 'b.title LIKE ' . $query->quote('%' . $search . '%');
            $array[] = 'b.description LIKE ' . $query->quote('%' . $search . '%');

            $query->where('(' . implode(' OR ', $array) . ')');
        }

        // Filter by published.
        if ('' !== $published = $this->getState('filter.published')) {
            $query->where('b.published = ' . $query->quote((int)$published));
        }

        $this->addOrderQuery($query);

        return $query;
    }

    protected function populateState($ordering = null, $direction = null)
    {
        foreach ($this->filters as $filter) {
            $value = $this->getUserStateFromRequest($this->context . '.filter.' . $filter, 'filter_' . $filter, '');
            $this->setState('filter.' . $filter, $value);
        }

        // List state information.
        parent::populateState('b.title', 'asc');
    }

    protected function addOrderQuery($query)
    {
        // Add the list ordering clause.
        $orderCol = $this->state->get('list.ordering', 'b.title');
        $orderDirn = $this->state->get('list.direction', 'asc');

        $query->order($query->escape($orderCol . ' ' . $orderDirn));
    }
}
