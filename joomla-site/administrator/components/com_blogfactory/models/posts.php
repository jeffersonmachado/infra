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

class BlogFactoryBackendModelPosts extends JModelList
{
    protected $filters = array('search', 'published', 'category');

    public function __construct($config = array())
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = array(
                'p.published', 'p.title', 'p.id', 'u.username', 'p.created_at', 'c.title', 'b.title'
            );
        }

        parent::__construct($config);
    }

    public function getBatchBlogs()
    {
        $dbo = $this->getDbo();

        $query = $dbo->getQuery(true)
            ->select('b.id AS value, b.title AS text')
            ->from('#__com_blogfactory_blogs b')
            ->order('b.title ASC');

        $results = $dbo->setQuery($query)
            ->loadObjectList();

        array_unshift($results, array('value' => '', 'text' => ''));

        return $results;
    }

    public function getImportForm()
    {
        JFormHelper::addFieldPath(JPATH_COMPONENT_SITE . '/framework/fields');
        $path = JPATH_ADMINISTRATOR . '/components/com_blogfactory/models/forms/import.xml';

        $form = JForm::getInstance('com_blogfactory.import', $path, array(
            'control' => 'import',
        ));

        if (!$form) {
            return false;
        }

        BlogFactoryHelper::addLabelsToForm($form);

        return $form;
    }

    protected function getListQuery()
    {
        $query = parent::getListQuery();

        $query->select('p.*')
            ->from('#__com_blogfactory_posts p');

        // Select the blog title.
        $query->select('b.title AS blog_title')
            ->leftJoin('#__com_blogfactory_blogs AS b ON b.id = p.blog_id');

        // Select the username.
        $query->select('u.username')
            ->leftJoin('#__users u ON u.id = p.user_id');

        // Select the category.
        $query->select('c.title AS category_title')
            ->leftJoin('#__categories c ON c.id = p.category_id');

        // Filter by search.
        if ('' != $search = $this->getState('filter.search')) {
            $array = array();

            $array[] = 'p.title LIKE ' . $query->quote('%' . $search . '%');
            $array[] = 'p.content LIKE ' . $query->quote('%' . $search . '%');

            $query->where('(' . implode(' OR ', $array) . ')');
        }

        // Filter by published.
        if ('' !== $published = $this->getState('filter.published')) {
            $query->where('p.published = ' . $query->quote((int)$published));
        }

        // Filter by category.
        if ('' !== $category = $this->getState('filter.category')) {
            $query->where('p.category_id = ' . $query->quote((int)$category));
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
        parent::populateState('p.title', 'asc');
    }

    protected function addOrderQuery($query)
    {
        // Add the list ordering clause.
        $orderCol = $this->state->get('list.ordering', 'p.title');
        $orderDirn = $this->state->get('list.direction', 'asc');

        $query->order($query->escape($orderCol . ' ' . $orderDirn));
    }
}
