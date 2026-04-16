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

class ModBlogFactoryTreePostHelper
{
    public static function getItems($params)
    {
        $dbo = JFactory::getDbo();
        $date = JFactory::getDate();
        $items = array();
        $categories = $params->get('categories', array());

        $query = $dbo->getQuery(true)
            ->select('p.id, p.title, p.alias, p.created_at, p.publish_up')
            ->from('#__com_blogfactory_posts p')
            ->where('p.published = ' . $dbo->quote(1))
            ->where('(p.publish_up = ' . $dbo->quote($dbo->getNullDate()) . ' OR p.publish_up <= ' . $dbo->quote($date->toSql()) . ')')
            ->order('p.created_at DESC, p.publish_up DESC');

        // Filter by categories.
        if ($categories) {
            $query->where('p.category_id IN (' . implode(',', $categories) . ')');
        }

        $results = $dbo->setQuery($query)
            ->loadObjectList();

        $ItemId = '' == $params->get('item_id', '') ? '' : '&Itemid=' . $params->get('item_id', '');
        $posts_limit = $params->get('posts_limit', 0);

        foreach ($results as $result) {
            $result->link = JRoute::_('index.php?option=com_blogfactory&view=post&id=' . $result->id . '&alias=' . $result->alias . $ItemId);

            $date = $dbo->getNullDate() == $result->publish_up ? $result->created_at : $result->publish_up;
            $date = JFactory::getDate($date);

            $year = $date->format('Y');
            $month = $date->format('m');

            if (!isset($items[$year])) {
                $items[$year] = array('months' => array(), 'count' => 0);
            }

            if (!isset($items[$year]['months'][$month])) {
                $items[$year]['months'][$month] = array('posts' => array(), 'count' => 0);
            }

            if ($posts_limit && $items[$year]['months'][$month]['count'] >= $posts_limit) {
                continue;
            }

            $items[$year]['months'][$month]['posts'][] = $result;

            // Update count.
            $items[$year]['months'][$month]['count']++;
            $items[$year]['count']++;
        }

        // Sort items.
        $order_year = $params->get('order_year', 'desc');
        $order_month = $params->get('order_month', 'asc');

        foreach ($items as $year => $months) {
            if ('asc' == $order_year) {
                ksort($items);
            } else {
                krsort($items);
            }
        }

        foreach ($items as $year => &$months) {
            if ('asc' == $order_month) {
                ksort($months['months']);
            } else {
                krsort($months['months']);
            }
        }

        return $items;
    }

    public static function getId($module)
    {
        return $module->module . '_' . $module->id;
    }

    public static function assets($id, $params)
    {
        static $loaded = false;

        if ($loaded) {
            return true;
        }

        JHtml::_('jquery.framework');
        JHtml::stylesheet('components/com_blogfactory/assets/css/jquery.treeview.css');
        JHtml::stylesheet('modules/mod_blogfactory_tree_post/assets/style.css');
        JHtml::script('components/com_blogfactory/assets/js/jquery.cookie.js');
        JHtml::script('components/com_blogfactory/assets/js/jquery.treeview.js');

        $collapsed = $params->get('collapsed', 1) ? 'true' : 'false';
        $persist = $params->get('persistent', 1) ? 'cookie' : '';

        $document = JFactory::getDocument();
        $document->addScriptDeclaration('
      jQuery(document).ready(function ($) {
        $("#' . $id . '").treeview({
          animated: "fast",
          collapsed: ' . $collapsed . ',
          persist: "' . $persist . '",
          cookieOptions: { expires: 7, path: "/" },
          cookieId: "' . $id . '"
        });
      });
    ');

        $loaded = true;

        return true;
    }
}
