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

class BlogFactoryBackendModelUsers extends JModelList
{
    protected $filters = array('search');

    public function __construct($config = array())
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = array(
                'p.id', 'u.username', 'b.title'
            );
        }

        parent::__construct($config);
    }

    protected function getListQuery()
    {
        $query = parent::getListQuery();

        $query->select('p.*')
            ->from('#__com_blogfactory_profiles p');

        // Select the username.
        $query->select('u.username')
            ->leftJoin('#__users u ON u.id = p.id');

        // Select the blog.
        $query->select('b.title AS blog_title, b.id AS blog_id')
            ->leftJoin('#__com_blogfactory_blogs b ON b.user_id = p.id');

        // Filter by search.
        if ('' != $search = $this->getState('filter.search')) {
            $array = array();

            $array[] = 'u.username LIKE ' . $query->quote('%' . $search . '%');

            $query->where('(' . implode(' OR ', $array) . ')');
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
        parent::populateState('u.username', 'asc');
    }

    protected function addOrderQuery($query)
    {
        // Add the list ordering clause.
        $orderCol = $this->state->get('list.ordering', 'u.username');
        $orderDirn = $this->state->get('list.direction', 'asc');

        $query->order($query->escape($orderCol . ' ' . $orderDirn));
    }
}
