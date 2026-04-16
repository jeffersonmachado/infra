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

class ModBlogFactoryTreeCategoryHelper
{
    public static function getCategories()
    {
        $dbo  = JFactory::getDbo();
        $date = JFactory::getDate();

        $query = $dbo->getQuery(true)
            ->select('a.id, a.title, a.level, a.parent_id')
            ->from('#__categories AS a')
            ->where('a.parent_id > 0')
            ->where('extension = ' . $dbo->quote('com_blogfactory'))
            ->where('a.published = ' . $dbo->q(1))
            ->order('a.lft');

        $query->select('COUNT(p.id) AS posts')
            ->leftJoin('#__com_blogfactory_posts p ON p.category_id = a.id AND p.published = ' . $dbo->quote(1) . ' AND (p.publish_up = ' . $dbo->quote($dbo->getNullDate()) . ' OR p.publish_up <= ' . $dbo->quote($date->toSql()) . ')')
            ->group('a.id');

        $results = $dbo->setQuery($query)
            ->loadObjectList();

        $array = array();
        $temp = array();

        foreach ($results as $result) {
            $category = array(
                'title'    => $result->title,
                'posts'    => $result->posts,
                'children' => array(),
                'id'       => $result->id
            );

            if (1 == $result->parent_id) {
                $array[$result->id] = $category;
                $temp[$result->id] =& $array[$result->id];
            }
            else {
                $temp[$result->parent_id]['children'][$result->id] = $category;
                $temp[$result->id] =& $temp[$result->parent_id]['children'][$result->id];
            }
        }

        return $array;
    }

    public static function renderCategory($category, $params)
    {
        $html = array();
        $ItemId = $params->get('item_id', '');
        $ItemId = '' == $ItemId ? '' : '&Itemid=' . $ItemId;
        $text = ($params->get('no_posts', 1) ? '<span class="badge badge-info">' . $category['posts'] . '</span>' : '') . $category['title'];

        $html[] = '<li>';

        $html[] = '<a href="' . JRoute::_('index.php?option=com_blogfactory&view=posts&category_id=' . $category['id'] . $ItemId) . '">' . $text . '</a>';

        if ($category['children']) {
            $html[] = '<ul>';

            foreach ($category['children'] as $children) {
                $html[] = self::renderCategory($children, $params);
            }

            $html[] = '</ul>';
        }

        $html[] = '</li>';

        return implode("\n", $html);
    }

    public static function assets($id, $params)
    {
        static $loaded = false;

        if ($loaded) {
            return true;
        }

        JHtml::_('jquery.framework');
        JHtml::stylesheet('components/com_blogfactory/assets/css/jquery.treeview.css');
        JHtml::stylesheet('modules/mod_blogfactory_tree_category/assets/style.css');
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

    public static function getId($module)
    {
        return $module->module . '_' . $module->id;
    }
}
