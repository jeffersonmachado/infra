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

class ModBlogFactoryPostHelper
{
    public static function getList($params)
    {
        $dbo = JFactory::getDbo();
        $date = JFactory::getDate();
        $type = $params->get('type');
        $categories = $params->get('categories', array());

        $query = $dbo->getQuery(true)
            ->select('p.id, p.title, p.alias, p.created_at, p.publish_up')
            ->from('#__com_blogfactory_posts p')
            ->where('p.published = ' . $dbo->quote(1))
            ->where('(p.publish_up = ' . $dbo->quote($dbo->getNullDate()) . ' OR p.publish_up < ' . $dbo->quote($date->toSql()) . ')');

        // Filter by categories.
        if ($categories) {
            $query->where('p.category_id IN (' . implode(',', $categories) . ')');
        }

        // Order posts.
        if ('latest_posts' == $type) {
            $query->order('p.created_at DESC');
        } elseif ('random_posts' == $type) {
            $query->order('RAND()');
        } else {
            $query->order('p.votes_up - p.votes_down DESC');
        }

        $results = $dbo->setQuery($query, 0, $params->get('count', 10))
            ->loadObjectList();

        return $results;
    }

    public static function renderItem($item, $params)
    {
        $html = array();
        $dbo = JFactory::getDbo();

        $ItemId = '' == $params->get('item_id', '') ? '' : '&Itemid=' . $params->get('item_id', '');
        $href = JRoute::_('index.php?option=com_blogfactory&view=post&id=' . $item->id . '&alias=' . $item->alias . $ItemId);
        $date = $dbo->getNullDate() == $item->publish_up ? $item->created_at : $item->publish_up;

        $html[] = '<a href="' . $href . '">' . $item->title . '</a>';
        $html[] = '<span class="small muted">' . JHtml::_('date', $date) . '</span>';

        return implode("\n", $html);
    }
}
