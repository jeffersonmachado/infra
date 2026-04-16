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

class BlogFactoryFrontendModelBlogs extends JModelLegacy
{
    protected $start;
    protected $limit;

    public function __construct($config = array())
    {
        parent::__construct($config);

        $menu = JFactory::getApplication()->getMenu()->getActive();

        $this->start = JFactory::getApplication()->input->getInt('limitstart', 0);
        $this->limit = $menu ? $menu->params->get('limit', 10) : 10;
    }

    public function getBlogs()
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

    protected function getQuery($total = false)
    {
        $dbo = $this->getDbo();
        $user = JFactory::getUser();

        $query = $dbo->getQuery(true)
            ->from('#__com_blogfactory_blogs b')
            ->where('b.published = ' . $dbo->quote(1));

        // Filter blog by search.
        $search = JFactory::getApplication()->input->getString('search');
        if ('' != $search) {
            $query->where('b.title LIKE ' . $dbo->quote('%' . $search . '%'));
        }

        // Filter by bookmarked.
        $filters = JFactory::getApplication()->input->get('filter', array(), 'array');
        if (isset($filters['bookmarked']) && '' != $filters['bookmarked']) {
            if ($filters['bookmarked']) {
                $query->where('bkm.id IS NOT NULL');
            } else {
                $query->where('bkm.id IS NULL');
            }

            $query->leftJoin('#__com_blogfactory_followers bkm ON bkm.blog_id = b.id AND bkm.user_id = ' . $dbo->quote($user->id));
        }

        if ($total) {
            $query->select('COUNT(b.id) AS total');
        } else {
            $query->select('b.*')
                ->group('b.id');

            // Select username.
            $query->select('u.username')
                ->leftJoin('#__users u ON u.id = b.user_id');

            // Select last activity.
            $query->select('MAX(p.created_at) AS activity')
                ->leftJoin('#__com_blogfactory_posts p ON p.blog_id = b.id');

            // Select number of posts.
            $query->select('COUNT(p.id) AS posts');

            // Select the number of followers.
            $query->select('f.followers')
                ->leftJoin('(SELECT COUNT(id) AS followers, blog_id FROM #__com_blogfactory_followers GROUP BY blog_id) f ON f.blog_id = b.id');

            // Select if blog is bookmarked.
            $query->select('IF (bkm.id IS NULL, 0, 1) AS bookmarked');

            if (!isset($filters['bookmarked']) || '' == $filters['bookmarked']) {
                $query->leftJoin('#__com_blogfactory_followers bkm ON bkm.blog_id = b.id AND bkm.user_id = ' . $dbo->quote($user->id));
            }

            // Select if blog is subscribed to.
            $query->select('IF (s.id IS NULL, 0, 1) AS subscribed')
                ->leftJoin('#__com_blogfactory_subscriptions s ON s.blog_id = b.id AND s.user_id = ' . $dbo->quote($user->id));

            // Order blogs.
            $input = JFactory::getApplication()->input;
            $order = $input->getCmd('order', '');
            $sort = $input->getCmd('sort', '');

            if (!in_array($sort, array('title', 'posts', 'followers', 'activity'))) {
                $sort = 'title';
            }
            if (!in_array($order, array('asc', 'desc'))) {
                $order = 'asc';
            }

            $query->order($sort . ' ' . $order);
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
}
