<?php
/**
 * @copyright    Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license      GNU General Public License version 2 or later; see LICENSE.txt
 */

/**
 * @package jDownloads
 * @version 3.2  
 * @copyright (C) 2007 - 2017 - Arno Betz - www.jdownloads.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.txt
 * 
 * jDownloads is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */

defined('JPATH_BASE') or die;

jimport('joomla.html.html');
jimport('joomla.form.formfield');
jimport('joomla.form.helper');
JFormHelper::loadFieldClass('list');

/**
 * Form Field class
 *
 */
class JFormFieldjdCategorySelectEditFE extends JFormFieldList
{
	/**
	 * A flexible category list that respects access controls.
	 *
	 * @var		string
	 */
	protected $type = 'jdCategorySelectEditFE';

	/**
     * Method to get a list of categories that respects access controls and can be used for category assignment in edit screens.
	 *
	 * @return	array	The field option objects.
	 */
	protected function getOptions()
	{
        // Include helpers
        require_once JPATH_SITE.'/administrator/components/com_jdownloads/helpers/jdownloadshelper.php';
        require_once JPATH_SITE.'/components/com_jdownloads/helpers/query.php';
        
        $app   = JFactory::getApplication();

        $jd_config = JDownloadsHelper::buildjlistConfig();         
        
        $menus = $app->getMenu();
        $menu = $menus->getActive();

        if ($menu){
            $orderby_pri = $menu->params->get('orderby_pri');
        } else {
            $orderby_pri = '';
        }
        
        // use default sort order or menu order settings
        if (empty($orderby_pri) || !isset($orderby_pri)){
            // use config settings
            switch ($jd_config['cats.order']){
                case '1':
                     // cat title asc 
                     $orderCol = 'a.title ';
                     $categoryOrderby = 'alpha';
                     break;
                case '2':
                     // cat title desc 
                     $orderCol = 'a.title DESC ';
                     $categoryOrderby = 'ralpha';
                     break;
                default:
                     // cat ordering
                     $orderCol = 'a.lft ';
                     $categoryOrderby = '';
                     break;                
            }
        }  else {
            // use order from menu settings 
            $categoryOrderby    = $orderby_pri;
            $orderCol           = str_replace(', ', '', JDContentHelperQuery::orderbyPrimary($categoryOrderby));
        } 
        
        // Initialise variables.
		$cats = array();
        $user = JFactory::getUser();
                
		$db		= JFactory::getDbo();
		$query	= $db->getQuery(true);

		$query->select('a.id AS value, a.lft, a.rgt, a.parent_id, a.title AS text, a.level, a.access');
		$query->from('#__jdownloads_categories AS a');
        $query->join('LEFT', '`#__jdownloads_categories` AS b ON a.lft > b.lft AND a.rgt < b.rgt');

        $query->where('a.access IN (' . implode(',', $user->getAuthorisedViewLevels()) . ')');
        $query->where('a.parent_id > 0');
        $query->where('a.published IN (0,1)');
        $query->group('a.id, a.title, a.cat_dir_parent');
		
        if ($categoryOrderby == 'alpha'){
            $query->order('a.level ASC, a.parent_id ASC, a.title ASC');
        } elseif ($categoryOrderby == 'ralpha'){
            $query->order('a.level ASC, a.parent_id ASC, a.title DESC');
        } else {
		$query->order('a.lft ASC');
        }

		// Get the data
		$db->setQuery($query);

		$cats = $db->loadObjectList();
        
        // Check for a database error.
        if ($db->getErrorNum()) {
            JError::raiseWarning(500, $db->getErrorMsg());
        }

        // Order subcategories
        if (count($cats)) {
            if ($categoryOrderby == 'alpha' || $categoryOrderby == 'ralpha') {
                $i = 0;
                $depth = 0;
                $parent_id = 0;
                $parents = array();
                
                foreach($cats as $cat) {
                    if($depth < $cat->level || $parent_id < $cat->parent_id) {
                        $i = @$parents["{$cat->parent_id}"] + 1;
                    }
                    $tree[$i] = $cat;
                    $parents["{$cat->value}"] = $i;
                    $depth = $cat->level;
                    $parent_id = $cat->parent_id;
                    $i += (($cat->rgt - $cat->lft - 1) / 2) + 1;
                }    
                ksort($tree);
                $cats = $tree;
            }
            
        }

        foreach ($cats as &$cat){
            $repeat = ($cat->level - 1 >= 0) ? $cat->level - 1 : 0;
            $cat->text = str_repeat('- ', $repeat) . $cat->text;
        }

        if (empty($id)) {
            // New item, only have to check core.create.
            foreach ($cats as $i => $option)
            {
                if ($option->value > 0){
                    // Special handling for the uncategorisied option (value (id) = 1)
                    // Use here the components settings
                    if ($option->value == 1){
                        // Unset the option if the user isn't authorised for it.
                        if (!$user->authorise('core.create', 'com_jdownloads')) {
                            unset($cats[$i]);
                        }
                    } else {        
                        // Unset the option if the user isn't authorised for it.
                        if (!$user->authorise('core.create', 'com_jdownloads.category.'.$option->value)) {
                            unset($cats[$i]);
                        }
                    }    
                }    
            }
        } else {
            // Existing item is a bit more complex. Need to account for core.edit and core.edit.own.
            foreach ($cats as $i => $option)
            {
                // Special handling for the uncategorisied option (value (id) = 1)
                // Use here the components settings
                if ($option->value == 1){
                    if (!$user->authorise('core.edit', 'com_jdownloads')) {
                        // As a backup, check core.edit.own
                        if (!$user->authorise('core.edit.own', 'com_jdownloads')) {
                            // No core.edit nor core.edit.own - bounce this one
                            unset($cats[$i]);
                        }                
                    }
                } else {        
                
                    // Unset the option if the user isn't authorised for it.
                    if (!$user->authorise('core.edit', 'com_jdownloads.category.'.$option->value)) {
                        // As a backup, check core.edit.own
                        if (!$user->authorise('core.edit.own', 'com_jdownloads.category.'.$option->value)) {
                            // No core.edit nor core.edit.own - bounce this one
                            unset($cats[$i]);
                        }
                    }
                }
            }    
        }
        
        // add an empty array item in the first position 
        $empty_cat_object = new stdClass();
        $empty_cat_object->value = null;
        $empty_cat_object->text = JText::_('COM_JDOWNLOADS_BACKEND_FILESEDIT_SELECT_CATEGORY');
        $empty_cat_object->level = 0;
        $empty_array[0] = $empty_cat_object; 
        $cats = array_merge($empty_array, $cats);
       
		// Merge any additional options in the XML definition.
		$cats = array_merge(parent::getOptions(), $cats);

		return $cats;
	}
}