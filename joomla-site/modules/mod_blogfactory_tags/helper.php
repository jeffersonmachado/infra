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

class ModBlogFactoryTagsHelper
{
    public static function getTags($params)
    {
        $dbo = JFactory::getDbo();

        $limit = $params->get('limit', 5);
        $min_count = $params->get('min_count', 0);
        $min_size = $params->get('min_size', 10);
        $max_size = $params->get('max_size', 20);

        $query = $dbo->getQuery(true)
            ->select('COUNT(m.tag_id) AS count')
            ->from('#__com_blogfactory_post_tag_map m')
            ->group('m.tag_id')
            ->having('COUNT(m.tag_id) >= ' . $dbo->quote($min_count))
            ->order('count DESC, RAND()');

        $query->leftJoin('#__com_blogfactory_posts p ON p.id = m.post_id')
            ->where('p.id IS NOT NULL');

        $query->select('t.name, t.alias')
            ->leftJoin('#__com_blogfactory_tags t ON t.id = m.tag_id');

        $results = $dbo->setQuery($query, 0, $limit)
            ->loadObjectList();

        $max = null;
        $min = null;

        foreach ($results as $result) {
            if (is_null($max) || $result->count > $max) {
                $max = $result->count;
            }
            if (is_null($min) || $result->count < $min) {
                $min = $result->count;
            }
        }

        foreach ($results as $i => $result) {
            $size = round($result->count * $max_size / $max);
            $size = max($size, $min_size);

            $results[$i]->size = $size;
        }

        shuffle($results);

        return $results;
    }
}
