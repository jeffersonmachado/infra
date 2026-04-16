<?php
/**
* @version $Id: mod_jdownloads_admin_stats.php v3.2
* @package mod_jdownloads_admin_stats
* @copyright (C) 2016 Arno Betz
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
* @author Arno Betz http://www.jDownloads.com
*/

defined( '_JEXEC' ) or die( 'Restricted access' );

class modJDownloadsAdminStatsHelper
{
    public static function getLatestItems($count)
    {
        $db = JFactory::getDBO();
        $query = "SELECT i.*, c.title AS cat_title, v.name AS author FROM #__jdownloads_files as i 
        LEFT JOIN #__jdownloads_categories AS c ON c.id = i.cat_id 
        LEFT JOIN #__users AS v ON v.id = i.created_id 
        ORDER BY i.date_added DESC";
        $db->setQuery($query, 0, $count);
        $rows = $db->loadObjectList();
        return $rows;
    }

    public static function getPopularItems($count)
    {
        $db = JFactory::getDBO();
        $query = "SELECT i.*, c.title AS cat_title, v.name AS author FROM #__jdownloads_files as i 
        LEFT JOIN #__jdownloads_categories AS c ON c.id = i.cat_id 
        LEFT JOIN #__users AS v ON v.id = i.created_id 
        ORDER BY i.downloads DESC";
        $db->setQuery($query, 0, $count);
        $rows = $db->loadObjectList();
        return $rows;
    }

    public static function getFeaturedItems($count)
    {
        $db = JFactory::getDBO();
        $query = "SELECT i.*, c.title AS cat_title, v.name AS author FROM #__jdownloads_files as i 
        LEFT JOIN #__jdownloads_categories AS c ON c.id = i.cat_id 
        LEFT JOIN #__users AS v ON v.id = i.created_id 
        WHERE i.featured = 1
        ORDER BY i.date_added DESC";
        $db->setQuery($query, 0, $count);
        $rows = $db->loadObjectList();
        return $rows;
    }    
    
    public static function getMostRatedItems($count)
    {
        $db = JFactory::getDBO();
        $query = "SELECT i.*, c.title AS cat_title, v.name AS author, r.file_id, r.rating_count, round( r.rating_sum / r.rating_count ) * 20 AS ratenum FROM #__jdownloads_files as i
        LEFT JOIN #__jdownloads_categories AS c ON c.id = i.cat_id
        INNER JOIN #__jdownloads_ratings AS r ON i.file_id = r.file_id 
        LEFT JOIN #__users AS v ON v.id = i.created_by 
        ORDER BY rating_count DESC, ratenum DESC";
        $db->setQuery($query, 0, $count);
        $rows = $db->loadObjectList();
        return $rows;
    }

    public static function getTopRatedItems($count)
    {
        $db = JFactory::getDBO();
        $query = "SELECT i.*, c.title AS cat_title, v.name AS author, r.file_id, r.rating_count, round( r.rating_sum / r.rating_count ) * 20 AS ratenum FROM #__jdownloads_files as i
        LEFT JOIN #__jdownloads_categories AS c ON c.id = i.cat_id
        INNER JOIN #__jdownloads_ratings AS r ON i.file_id = r.file_id 
        LEFT JOIN #__users AS v ON v.id = i.created_by 
        ORDER BY ratenum DESC , rating_count DESC";
        $db->setQuery($query, 0, $count);
        $rows = $db->loadObjectList();
        return $rows;
    }

    public static function getStatistics()
    {
        $statistics = new stdClass;
        $statistics->num_downloads              = self::countItems();
        $statistics->num_unpublished_downloads  = self::countItemsUnpublished();
        $statistics->num_featured               = self::countFeaturedItems();
        $statistics->num_categories             = self::countCategories();
        $statistics->num_unpublished_categories = self::countCategoriesUnpublished();
        $statistics->num_tags                   = self::countTags();
        return $statistics;
    }

    public static function countItems()
    {
        $db = JFactory::getDBO();
        $query = "SELECT COUNT(*) FROM #__jdownloads_files";
        $db->setQuery($query);
        $result = $db->loadResult();
        return $result;
    }

    public static function countItemsUnpublished()
    {
        $db = JFactory::getDBO();
        $query = "SELECT COUNT(*) FROM #__jdownloads_files WHERE published = '0'";
        $db->setQuery($query);
        $result = $db->loadResult();
        return $result;
    }    
    
    public static function countFeaturedItems()
    {
        $db = JFactory::getDBO();
        $query = "SELECT COUNT(*) FROM #__jdownloads_files WHERE featured=1";
        $db->setQuery($query);
        $result = $db->loadResult();
        return $result;
    }

    public static function countCategories()
    {
        $db = JFactory::getDBO();
        $query = "SELECT COUNT(*) FROM #__jdownloads_categories";
        $db->setQuery($query);
        $result = $db->loadResult();
        return $result;
    }

    public static function countCategoriesUnpublished()
    {
        $db = JFactory::getDBO();
        $query = "SELECT COUNT(*) FROM #__jdownloads_categories WHERE published = '0'";
        $db->setQuery($query);
        $result = $db->loadResult();
        return $result;
    }    
    
    public static function countTags()
    {
        $db = JFactory::getDBO();
        $query = "SELECT COUNT(*) FROM #__ucm_content WHERE core_type_alias = 'com_jdownloads.download'";
        $db->setQuery($query);
        $result = $db->loadResult();
        return $result;
    }

}
