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

class ModBlogFactoryBlogHelper
{
    public static function getList($params)
    {
        $dbo = JFactory::getDbo();
        $type = $params->get('type');

        switch ($type) {
            case 'latest_blogs':
            case 'random_blogs':
                $query = $dbo->getQuery(true)
                    ->select('b.id, b.title, b.alias, b.created_at')
                    ->from('#__com_blogfactory_blogs b')
                    ->where('b.published = ' . $dbo->quote(1));

                if ('latest_blogs' == $type) {
                    $query->order('b.created_at DESC');
                } else {
                    $query->order('RAND()');
                }

                break;

            case 'active_blogs':
                $query = $dbo->getQuery(true)
                    ->select('b.id, b.title, b.alias, COUNT(p.id) AS posts')
                    ->from('#__com_blogfactory_blogs b')
                    ->leftJoin('#__com_blogfactory_posts p ON p.blog_id = b.id')
                    ->group('b.id')
                    ->order('posts DESC');
                break;

            case 'followed_blogs':
                $query = $dbo->getQuery(true)
                    ->select('b.id, b.title, b.alias, COUNT(f.id) AS followers')
                    ->from('#__com_blogfactory_blogs b')
                    ->leftJoin('#__com_blogfactory_followers f ON f.blog_id = b.id')
                    ->group('b.id')
                    ->order('followers DESC');
                break;
        }

        $results = $dbo->setQuery($query, 0, $params->get('count', 10))
            ->loadObjectList();

        return $results;
    }

    public static function renderItem($item, $type, $params)
    {
        $html = array();
        $ItemId = '' == $params->get('item_id', '') ? '' : '&Itemid=' . $params->get('item_id', '');
        $href = JRoute::_('index.php?option=com_blogfactory&view=blog&id=' . $item->id . '&alias=' . $item->alias . $ItemId);

        $html[] = '<a href="' . $href . '">' . $item->title . '</a>';

        switch ($type) {
            case 'latest_blogs':
            case 'random_blogs':
                $html[] = '<span class="small muted">' . JHtml::_('date', $item->created_at) . '</span>';
                break;

            case 'active_blogs':
                $html[] = '<span class="small muted">' . JText::plural('MOD_BLOGFACTORY_BLOG_COUNT_POSTS', $item->posts) . '</span>';
                break;

            case 'followed_blogs':
                $html[] = '<span class="small muted">' . JText::plural('MOD_BLOGFACTORY_BLOG_COUNT_FOLLOWERS', $item->followers) . '</span>';
                break;
        }

        return implode("\n", $html);
    }
}
