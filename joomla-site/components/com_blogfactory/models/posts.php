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

class BlogFactoryFrontendModelPosts extends JModelLegacy
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

    public function getTags($id)
    {
        $dbo = $this->getDbo();

        $query = $dbo->getQuery(true)
            ->select('t.name, t.alias')
            ->from('#__com_blogfactory_post_tag_map m')
            ->leftJoin('#__com_blogfactory_tags t ON t.id = m.tag_id')
            ->where('m.post_id = ' . $dbo->quote($id));
        $results = $dbo->setQuery($query)
            ->loadObjectList();

        return $results;
    }

    public function getFilterTag()
    {
        static $tag = null;

        if (is_null($tag)) {
            $alias = JFactory::getApplication()->input->getString('tag', '');
            $tag = $this->getTable('Tag', 'BlogFactoryTable');
            $tag->load(array('alias' => $alias));
        }

        return $tag;
    }

    public function getFilterDate()
    {
        // Filter by date.
        $date = JFactory::getApplication()->input->getString('date');

        if ($date) {
            $date = explode('-', $date);

            $year = $date[0];
            $month = isset($date[1]) ? $date[1] : null;
            $day = isset($date[2]) ? $date[2] : null;

            if (is_null($month)) {
                return $year;
            }

            if (is_null($day)) {
                return $year . '-' . $month;
            }

            return $year . '-' . $month . '-' . $day;
        }

        return false;
    }

    public function getFilterCategory()
    {
        static $category = null;

        if (is_null($category)) {
            $id = JFactory::getApplication()->input->getInt('category_id', 0);
            $category = JTable::getInstance('Category', 'JTable');

            $category->load($id);
        }

        return $category;
    }

    public function getQuery($total = false)
    {
        $dbo = $this->getDbo();
        $date = JFactory::getDate();
        $settings = JComponentHelper::getParams('com_blogfactory');

        $query = $dbo->getQuery(true)
            ->from('#__com_blogfactory_posts p')
            ->where('p.published = ' . $dbo->quote(1))
            ->where('(p.publish_down = ' . $dbo->quote($dbo->getNullDate()) . ' OR p.publish_down > ' . $dbo->quote($date->toSql()) . ')')
            ->where('(p.publish_up = ' . $dbo->quote($dbo->getNullDate()) . ' OR p.publish_up < ' . $dbo->quote($date->toSql()) . ')')
            ->where('p.archived = ' . $dbo->quote(0));

        // Filter by category.
        $category = JFactory::getApplication()->input->getInt('category_id', 0);
        if ($category) {
            $query->where('p.category_id = ' . $dbo->quote((int)$category));
        }

        // Filter by tag.
        $tag = JFactory::getApplication()->input->getString('tag', '');
        if ('' != $tag) {
            $table = $this->getTable('Tag', 'BlogFactoryTable');
            if ($table->load(array('alias' => $tag))) {
                $query->leftJoin('#__com_blogfactory_post_tag_map m ON m.tag_id = ' . $dbo->quote($table->id))
                    ->where('m.post_id = p.id');
            }
        }

        // Filter by date.
        $date = JFactory::getApplication()->input->getString('date');

        if ($date) {
            $date = explode('-', $date);

            $year = $date[0];
            $month = isset($date[1]) ? $date[1] : null;
            $day = isset($date[2]) ? $date[2] : null;

            $start = $year . '-' . (is_null($month) ? '01' : $month) . '-' . (is_null($day) ? '01' : $day) . ' 00:00:00';
            $end = $year . '-' . (is_null($month) ? '12' : $month) . '-' . (is_null($day) ? '31' : $day) . ' 23:59:59';

            $query->where('p.created_at >= ' . $dbo->quote($start))
                ->where('p.created_at <= ' . $dbo->quote($end));
        }

        if ($total) {
            $query->select('COUNT(p.id) AS total');
        } else {
            $query->select('p.*, p.comments AS has_comments_enabled');

            // Select username.
            $query->select('u.username')
                ->leftJoin('#__users u ON u.id = p.user_id');

            // Select category title.
            $query->select('c.title AS category_title')
                ->leftJoin('#__categories c ON c.id = p.category_id');

            // Select comments count.
            $query->select('COUNT(cm.id) AS comments');
            if ($settings->get('comments.approval', 0)) {
                $query->leftJoin('#__com_blogfactory_comments cm ON cm.post_id = p.id AND cm.approved = ' . $dbo->q(1));
            } else {
                $query->leftJoin('#__com_blogfactory_comments cm ON cm.post_id = p.id');
            }

            // Select blog title.
            $query->select('b.title AS blog_title, b.alias AS blog_alias')
                ->leftJoin('#__com_blogfactory_blogs b ON b.id = p.blog_id');

            $query->group('p.id');

            // Order results.
            $query->order('p.created_at DESC');
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
