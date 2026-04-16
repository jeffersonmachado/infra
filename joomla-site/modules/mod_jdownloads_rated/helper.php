<?php
/**
* @version $Id: mod_jdownloads_rated.php v3.2
* @package mod_jdownloads_rated
* @copyright (C) 2016 Arno Betz
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
* @author Arno Betz http://www.jDownloads.com
*/

defined( '_JEXEC' ) or die( 'Restricted access' );

class ModJDownloadsRatedHelper
{
	static function getList($params)
	{
        $db = JFactory::getDbo();
        $app = JFactory::getApplication();
        $appParams = $app->getParams('com_jdownloads');
        
        $type = $params->get('top_view');
        if (!$type){
            // most rated 
            $order = 'rating_count DESC, ratenum DESC';
        } else {
            // top rated
            $order = 'ratenum DESC , rating_count DESC';
        }

        // Access filter
        $access = !JComponentHelper::getParams('com_jdownloads')->get('show_noauth');
        $authorised = JAccess::getAuthorisedViewLevels(JFactory::getUser()->get('id'));
        $cat_id = $params->get('cat_id', array());
        $cat_id = implode(',', $cat_id);

        if (!$cat_id) {
            $were_categ = 'WHERE a.access IN (' . implode(",", $authorised) . 
            ') AND c.access IN (' . implode(",", $authorised) .
            ') AND a.published = 1 AND a.language in (' . $db->quote(JFactory::getLanguage()->getTag()) . ',' . $db->quote('*') . ')';
        } else {
            $were_categ = 'WHERE a.access IN (' . implode(",", $authorised) . 
            ') AND c.access IN (' . implode(",", $authorised) . 
            ') AND a.published = 1 and a.language in (' . $db->quote(JFactory::getLanguage()->getTag()) . ',' . $db->quote('*') .
            ') AND a.cat_id IN (' . $cat_id . ')';
        }

        // create the query
        $query = 'SELECT a.file_id, 
                         a.file_title,
                         a.file_alias, 
                         a.description,
                         a.file_pic,
                         a.url_download,
                         a.other_file_id,
                         a.extern_file,
                         a.cat_id,
                         a.release,
                         a.access,
                         c.title,
                         c.access as category_access,
                         c.alias as category_alias,
                         c.cat_dir as category_cat_dir,
                         c.cat_dir_parent as category_cat_dir_parent,
                         mf.id as menu_itemid,
                         mf.link as menu_link,
                         mf.access as menu_access,
                         mf.published as menu_published,
                         mc.id as menu_cat_itemid,
                         mc.link as menu_cat_link,
                         mc.access as menu_cat_access,
                         mc.published as menu_cat_published,                         
                         r.file_id,
                         r.rating_count ,
                       round( r.rating_sum / r.rating_count ) * 20 as ratenum
                       
                    FROM #__jdownloads_files AS a
                    LEFT JOIN #__jdownloads_categories AS c
                          ON c.id = a.cat_id
                    LEFT JOIN #__menu AS mf
                          ON mf.link LIKE CONCAT(\'index.php?option=com_jdownloads&view=download&id=\',a.file_id)
                    LEFT JOIN #__menu AS mc
                          ON mc.link LIKE CONCAT(\'index.php?option=com_jdownloads&view=category&catid=\',a.cat_id)                                                    
                    INNER JOIN #__jdownloads_ratings AS r
                          ON a.file_id = r.file_id ' .
                    $were_categ .
                    ' ORDER BY '.$order;

        $db->setQuery($query, 0, (int) $params->get('sum_view'));
        $items = $db->loadObjectList();
        
        if ($db->getErrorNum()) {
            jError::raiseWarning(S00, $db->stderr(true));
        }
        
        foreach ($items as &$item){
            $item->slug = $item->file_id . ':' . $item->file_alias;
            $item->catslug = $item->cat_id . ':' . $item->category_alias;

            if ($access || in_array($item->access, $authorised)){
                // We know that user has the privilege to view the download
                $item->link = '-';
            } else {
                $item->link = JRoute::_('index.php?option=com_users&view=login');
            }
        }
        return $items;        
	}
    
    /**
    * remove the language tag from a given text and return only the text
    *    
    * @param string     $msg
    */
    public static function getOnlyLanguageSubstring($msg)
    {
        // Get the current locale language tag
        $lang       = JFactory::getLanguage();
        $lang_key   = $lang->getTag();        
        
        // remove the language tag from the text
        $startpos = strpos($msg, '{'.$lang_key.'}') +  strlen( $lang_key) + 2 ;
        $endpos   = strpos($msg, '{/'.$lang_key.'}') ;
        
        if ($startpos !== false && $endpos !== false){
            return substr($msg, $startpos, ($endpos - $startpos ));
        } else {    
            return $msg;
        }    
    }      
}	
?>