<?php
/**
* @version $Id: mod_jdownloads_view_limits.php
* @package mod_jdownloads_view_limits
* @copyright (C) 2017 Arno Betz
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
* @author Arno Betz http://www.jDownloads.com
*
* It is only for jDownloads 3.x and later (Support: www.jDownloads.com)
*/

defined('_JEXEC') or die;

    $here = JText::_('MOD_JDOWNLOADS_VIEW_LIMITS_HERE_TERM');
    $link = '<a href="'.JRoute::_('index.php?option=com_jdownloads&view=myhistory&Itemid='.$history_link_id).'">'.$here.'</a>';
    
    $html  = '<div class="moduletable'.$moduleclass_sfx.'" style="">';
    
    if ($params->get('display_user_group')){
        $html .= '<p class="">'.JText::sprintf('MOD_JDOWNLOADS_VIEW_LIMITS_VIEW_USER_GROUP_TEXT', $total_consumed['group_name']).'</p>';
    }
    
    if ($total_consumed['no_limits_defined']){
        // This is only displayed when a)the visitor is not a guest and b)we could not find download restrictions for his user group. 
        $html .= '<p class="">'.JText::_('MOD_JDOWNLOADS_VIEW_LIMITS_VIEW_NO_LIMITS_MSG').'</p>';
        
        if ($view_link){
            if ($history_link_id){
                $html .= '<div class="center">'.JText::sprintf('MOD_JDOWNLOADS_VIEW_LIMITS_LINK_TO_HISTORY_TEXT', $link).'</div>';
            }
        }
                
        echo $html.'</div>';
        
    } else {
    
        $html .= '<ul class="">';
        
        if (in_array('daily', $types) && ($total_consumed['today_remaining'] != -1)){
            if ($total_consumed['today_remaining'] > 0){
                $html .= '<li class="">'.JText::sprintf('MOD_JDOWNLOADS_VIEW_LIMITS_DAY_HINT', $total_consumed['today_remaining']).'</li>'; 
            } else {
                // Limit reached
                $html .= '<li class="">'.JText::_('MOD_JDOWNLOADS_VIEW_LIMITS_DAY_LIMITS_REACHED').'</li>'; 
            }
        }
        
        if (in_array('weekly', $types) && ($total_consumed['week_remaining'] != -1)){
            if ($total_consumed['week_remaining'] > 0){
                $html .= '<li class="">'.JText::sprintf('MOD_JDOWNLOADS_VIEW_LIMITS_WEEK_HINT', $total_consumed['week_remaining']).'</li>'; 
            } else {
                // Limit reached
                $html .= '<li class="">'.JText::_('MOD_JDOWNLOADS_VIEW_LIMITS_WEEK_LIMITS_REACHED').'</li>'; 
            }
        }

        if (in_array('monthly', $types) && ($total_consumed['month_remaining'] != -1)){
            if ($total_consumed['month_remaining'] > 0){
                $html .= '<li class="">'.JText::sprintf('MOD_JDOWNLOADS_VIEW_LIMITS_MONTH_HINT', $total_consumed['month_remaining']).'</li>'; 
            } else {
                // Limit reached
                $html .= '<li class="">'.JText::_('MOD_JDOWNLOADS_VIEW_LIMITS_MONTH_LIMITS_REACHED').'</li>'; 
            }
        }

        // Volume limits:
        
        if (in_array('daily_vol', $types) && ($total_consumed['today_volume_remaining'] != -1)){
            if ($total_consumed['today_volume_remaining'] > 0){
                $remain = (int)$total_consumed['today_volume_remaining'] / 1024;
                $html .= '<li class="">'.JText::sprintf('MOD_JDOWNLOADS_VIEW_VOL_LIMITS_DAY_HINT', $remain, $link).'</li>'; 
            } else {
                // Limit reached
                $html .= '<li class="">'.JText::_('MOD_JDOWNLOADS_VIEW_LIMITS_DAY_LIMITS_REACHED').'</li>'; 
            }
        }
        
        if (in_array('weekly_vol', $types) && ($total_consumed['week_volume_remaining'] != -1)){
            if ($total_consumed['week_volume_remaining'] > 0){
                $remain = (int)$total_consumed['week_volume_remaining'] / 1024;
                $html .= '<li class="">'.JText::sprintf('MOD_JDOWNLOADS_VIEW_VOL_LIMITS_WEEK_HINT', $remain).'</li>'; 
            } else {
                // Limit reached
                $html .= '<li class="">'.JText::_('MOD_JDOWNLOADS_VIEW_LIMITS_WEEK_LIMITS_REACHED').'</li>'; 
            }
        }
        
        if (in_array('monthly_vol', $types) && ($total_consumed['month_volume_remaining'] != -1)){
            if ($total_consumed['month_volume_remaining'] > 0){
                $remain = (int)$total_consumed['month_volume_remaining'] / 1024;
                $html .= '<li class="">'.JText::sprintf('MOD_JDOWNLOADS_VIEW_VOL_LIMITS_MONTH_HINT', $remain).'</li>'; 
            } else {
                // Limit reached
                $html .= '<li class="">'.JText::_('MOD_JDOWNLOADS_VIEW_LIMITS_MONTH_LIMITS_REACHED').'</li>'; 
            }
        }    
        
        $html .= '</ul>';
        
        if ($view_link){
            if ($history_link_id){
                $html .= '<div class="center">'.JText::sprintf('MOD_JDOWNLOADS_VIEW_LIMITS_LINK_TO_HISTORY_TEXT', $link).'</div>';
            }
        }
        
        echo $html.'</div>'; 
    }
    
?>