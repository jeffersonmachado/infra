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

class BlogFactoryBackendModelComments extends JModelList
{
    protected $filters = array('search', 'published', 'approved');

    public function __construct($config = array())
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = array(
                'c.published', 'c.id', 'u.username', 'c.created_at', 'c.text', 'c.approved'
            );
        }

        parent::__construct($config);
    }

    public function getApproveComments()
    {
        return JComponentHelper::getParams('com_blogfactory')->get('comments.approval', 0);
    }

    protected function getListQuery()
    {
        $query = parent::getListQuery();

        $query->select('c.*')
            ->from('#__com_blogfactory_comments c');

        // Select the username.
        $query->select('u.username')
            ->leftJoin('#__users u ON u.id = c.user_id');

        // Select the post.
        $query->select('p.title AS post_title')
            ->leftJoin('#__com_blogfactory_posts p ON p.id = c.post_id');

        // Filter by search.
        if ('' != $search = $this->getState('filter.search')) {
            $array = array();

            $array[] = 'c.text LIKE ' . $query->quote('%' . $search . '%');

            $query->where('(' . implode(' OR ', $array) . ')');
        }

        // Filter by published.
        if ('' !== $published = $this->getState('filter.published')) {
            $query->where('c.published = ' . $query->quote((int)$published));
        }

        // Filter by approved.
        if ('' !== $approved = $this->getState('filter.approved')) {
            $query->where('c.approved = ' . $query->quote((int)$approved));
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
        parent::populateState('c.created_at', 'desc');
    }

    protected function addOrderQuery($query)
    {
        // Add the list ordering clause.
        $orderCol = $this->state->get('list.ordering', 'c.created_at');
        $orderDirn = $this->state->get('list.direction', 'desc');

        $query->order($query->escape($orderCol . ' ' . $orderDirn));
    }
}
