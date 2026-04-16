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

class BlogFactoryBackendModelNotifications extends JModelList
{
    protected $filters = array('search', 'published', 'language', 'type');

    public function __construct($config = array())
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = array(
                'n.published', 'n.subject', 'l.title', 'n.id', 'n.type',
            );
        }

        parent::__construct($config);
    }

    protected function getListQuery()
    {
        $query = parent::getListQuery();

        $query->select('n.*')
            ->from('#__com_blogfactory_notifications n');

        // Select language.
        $query->select('l.title AS language_title')
            ->leftJoin('#__languages l ON l.lang_code = n.lang_code');

        // Filter by search.
        if ('' != $search = $this->getState('filter.search')) {
            $query->where('n.subject LIKE ' . $query->quote('%' . $search . '%'));
        }

        // Filter by published.
        if ('' !== $published = $this->getState('filter.published')) {
            $query->where('n.published = ' . $query->quote((int)$published));
        }

        // Filter by type.
        if ('' !== $type = $this->getState('filter.type')) {
            $query->where('n.type = ' . $query->quote($type));
        }

        // Filter by language.
        if ('' !== $language = $this->getState('filter.language')) {
            $query->where('n.lang_code = ' . $query->quote($language));
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
        parent::populateState('n.subject', 'asc');
    }

    protected function addOrderQuery($query)
    {
        // Add the list ordering clause.
        $orderCol = $this->state->get('list.ordering', 'n.subject');
        $orderDirn = $this->state->get('list.direction', 'asc');

        $query->order($query->escape($orderCol . ' ' . $orderDirn));
    }
}
