<?php
/**
* @version $Id: mod_jdownloads_admin_stats.php v3.2
* @package mod_jdownloads_admin_stats
* @copyright (C) 2016 Arno Betz
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
* @author Arno Betz http://www.jDownloads.com
* 
* jDownloads admin stats module for use in the Joomla Control Panel.
* 
*/

defined( '_JEXEC' ) or die( 'Restricted access' );

    require_once __DIR__ . '/helper.php';

    $db     = JFactory::getDBO();
    $user   = JFactory::getUser();

	if (!$user->authorise('core.manage', 'com_jdownloads')){
		return;
	}

	$language = JFactory::getLanguage();
	$language->load('mod_jdownloads_admin_stats.ini', JPATH_ADMINISTRATOR);

    $latesttab     = $params->get('view_latest', 1);
    $populartab    = $params->get('view_popular', 1);
    $featuredtab   = $params->get('view_featured', 1);
    $mostratedtab  = $params->get('view_most_rated', 1);
    $topratedtab   = $params->get('view_top_rated', 1);
    $statisticstab = $params->get('view_statistics', 1);
    $count         = $params->get('amount_items', 5);
    
    if ($latesttab)
    {
	    $latest_items = modJDownloadsAdminStatsHelper::getLatestItems($count);
    }
    if ($populartab)
    {
	    $popular_items = modJDownloadsAdminStatsHelper::getPopularItems($count);
    }
    if ($featuredtab)
    {
        $featured_items = modJDownloadsAdminStatsHelper::getFeaturedItems($count);
    }

    if ($mostratedtab)
    {
	    $most_rated_items = modJDownloadsAdminStatsHelper::getMostRatedItems($count);
    }
    if ($topratedtab)
    {
	    $top_rated_items = modJDownloadsAdminStatsHelper::getTopRatedItems($count);
    }
    if ($statisticstab)
    {
	    $statistics = modJDownloadsAdminStatsHelper::getStatistics();
    }
    
    if (!$latesttab && !$populartab && !$featuredtab && !$mostratedtab && !$topratedtab && !$statisticstab){
        return;
    }
    
require JModuleHelper::getLayoutPath('mod_jdownloads_admin_stats', $params->get('layout', 'default'));
