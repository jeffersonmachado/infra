<?php
/**
* @version $Id: mod_jdownloads_view_limits.php
* @package mod_jdownloads_view_limits
* @copyright (C) 2017 Arno Betz
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
* @author Arno Betz http://www.jDownloads.com
*
*/

defined( '_JEXEC' ) or die( 'Restricted access' );

require_once JPATH_SITE . '/components/com_jdownloads/helpers/jdownloadshelper.php';

class modJdownloadsViewLimitsHelper
{
	static function getLimits()
	{
        $user_rules = JDHelper::getUserRules();
        $total_consumed = JDHelper::getUserLimits($user_rules, '');
        
        if (!$user_rules->download_limit_daily && !$user_rules->download_limit_weekly && !$user_rules->download_limit_monthly && !$user_rules->download_volume_limit_daily && !$user_rules->download_volume_limit_weekly && !$user_rules->download_volume_limit_monthly){
            $total_consumed['no_limits_defined'] = true;
        } else {
            $total_consumed['no_limits_defined'] = false;
        }
        
        $db = JFactory::getDBO();
        $sql = 'SELECT title FROM #__usergroups WHERE id = '.$db->Quote($user_rules->group_id);
        $db->setQuery($sql);
        $usergroup = $db->loadResult();
        
        if ($usergroup){
            $total_consumed['group_name'] = $usergroup;
        } else {
            $total_consumed['group_name'] = '';
        }
        
        return $total_consumed;
	}
    
}	
?>