<?php
/**
* @version $Id: mod_jdownloads_related.php
* @package mod_jdownloads_related
* @copyright (C) 2015 Arno Betz
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
* @author Arno Betz http://www.jDownloads.com
*
* This modul shows you some related downloads from the jDownloads component. 
* It is only for jDownloads 3.x and later (Support: www.jDownloads.com)
*/

defined( '_JEXEC' ) or die( 'Restricted access' );

    require_once __DIR__ . '/helper.php';
    
    $user = JFactory::getUser();
    if (!$user->id){
        // User is guest
        return;
    }
    
    $types = $params->get('limit_types');
    if (!isset($types) && !$params->get('display_no_limits_found_msg')){
        // No limit type selected
        return;
    } 
    
    $access_groups = implode(',', $user->getAuthorisedGroups()); 
    $access_levels = implode(',', $user->getAuthorisedViewLevels());    
    
    $document   = JFactory::getDocument();
    $active_language = $document->language;
    
    $db = JFactory::getDBO();
    
    $view_link = (int)$params->get('display_link_to_history'); 
    if ($view_link){
        $sql = 'SELECT id FROM #__menu WHERE link = ' . $db->Quote('index.php?option=com_jdownloads&view=myhistory'). ' AND published = 1 AND language = '.$db->Quote($active_language).' AND access IN ('.$access_levels.')' ;
        $db->setQuery($sql);
        $history_link_id = $db->loadResult();
        if (!$history_link_id){
            $sql = 'SELECT id FROM #__menu WHERE link = ' . $db->Quote('index.php?option=com_jdownloads&view=myhistory'). ' AND published = 1 AND language = '.$db->Quote('*').' AND access IN ('.$access_levels.')' ;
            $db->setQuery($sql);
            $history_link_id = $db->loadResult();
        }
    } else {
        $history_link_id = 0;
    }
    
    $alignment             = $params->get( 'alignment' );
    $moduleclass_sfx       = htmlspecialchars($params->get('moduleclass_sfx'), ENT_COMPAT, 'UTF-8');
    
    $total_consumed = modJdownloadsViewLimitsHelper::getLimits();

    if ($total_consumed['no_limits_defined']){
        // 'No Limits' message not activated
        if (!$params->get('display_no_limits_found_msg')){
            return;
        }
    }
    
    require JModuleHelper::getLayoutPath('mod_jdownloads_view_limits',$params->get('layout', 'default'));
?>