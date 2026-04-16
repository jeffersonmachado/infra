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

class BlogFactoryBackendModelReports extends JModelList
{
    protected $filters = array('search', 'resolved', 'type');

    public function __construct($config = array())
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = array(
                'r.status', 'r.type', 'r.id', 'u.username', 'r.created_at'
            );
        }

        parent::__construct($config);
    }

    protected function getListQuery()
    {
        $query = parent::getListQuery();

        $query->select('r.*')
            ->from('#__com_blogfactory_reports r');

        // Select the username.
        $query->select('u.username')
            ->leftJoin('#__users u ON u.id = r.user_id');

        // Filter by search.
        if ('' != $search = $this->getState('filter.search')) {
            $array = array();

            $array[] = 'r.text LIKE ' . $query->quote('%' . $search . '%');

            $query->where('(' . implode(' OR ', $array) . ')');
        }

        // Filter by resolved.
        if ('' !== $resolved = $this->getState('filter.resolved')) {
            $query->where('r.status = ' . $query->quote((int)$resolved));
        }

        // Filter by type.
        if ('' !== $type = $this->getState('filter.type')) {
            $query->where('r.type = ' . $query->quote($type));
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
        parent::populateState('r.created_at', 'asc');
    }

    protected function addOrderQuery($query)
    {
        // Add the list ordering clause.
        $orderCol = $this->state->get('list.ordering', 'r.created_at');
        $orderDirn = $this->state->get('list.direction', 'desc');

        $query->order($query->escape($orderCol . ' ' . $orderDirn));
    }
}
