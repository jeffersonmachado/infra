<?php
/**
 * @package jDownloads
 * @version 2.5  
 * @copyright (C) 2007 - 2013 - Arno Betz - www.jdownloads.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.txt
 * 
 * jDownloads is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */

defined('_JEXEC') or die('Restricted access');

use Joomla\Utilities\ArrayHelper;

jimport( 'joomla.application.component.view' );

/**
 * View downloads list
  * @package    jDownloads
 */
class jdownloadsViewList extends JViewLegacy
{
	
    protected $state;
    protected $items;
    protected $pagination;
    protected static $rows = array();
    protected $canDo;
    
    
    /**
	 * Downloads list view method
	 * @return void
	 **/
	function display($tpl = null)
	{

        global $jlistConfig;
        
        $app = JFactory::getApplication();
        if ($this->getLayout() == 'modal' || $this->getLayout() == 'modallist'){
            // set a switch so we can build later a valid: db query
            $app->setUserState( 'jd_modal', true );
            // Load the backend helper
            require_once JPATH_ADMINISTRATOR.'/components/com_jdownloads/helpers/jdownloadshelper.php';
            // we must load the admin language here explicit
            $lang = JFactory::getLanguage();
            $locale = JDownloadsHelper::getLangKey();
            $lang->load( 'com_jdownloads', JPATH_ADMINISTRATOR, $locale, true);
        }  else {
            $app->setUserState( 'jd_modal', false );
        }
         
        $this->state        = $this->get('State');
        $this->items        = $this->get('Items');
        $this->pagination   = $this->get('Pagination');

        // Check for errors.
        if (count($errors = $this->get('Errors'))) {
            JError::raiseError(500, implode("\n", $errors));
            return false;
        }
   
		// create 'delete also file' option
		$filters = array();
        $filters[] = JHtml::_('select.option', '0', JText::_( 'NO' ) );
		$filters[] = JHtml::_('select.option', '1', JText::_( 'YES' ) );
        $this->delete_file_option = $filters;
        
        // build categories list box 
        $lists = array();
        $config = array('filter.published' => array(0, 1));
        $select[] = JHtml::_('select.option', 0, JText::_('COM_JDOWNLOADS_SELECT_CATEGORY'));
        $select[] = JHtml::_('select.option', 1, JText::_('COM_JDOWNLOADS_SELECT_UNCATEGORISED'));
        
        // get the categories data for filter listbox
        $categories = $this->getCategoriesList($config);
        $this->categories = @array_merge($select, $categories);
        
        parent::display($tpl);
	}
    
    
    /**
     * Returns an array of the categories 
     *
     * @param   array   $config     An array of configuration options. By default published and unpublished categories are returned.
     *
     * @return  array
     *
     */
    public static function getCategoriesList($config = array('filter.published' => array(0, 1)), $orderby_pri = '')
    {
        global $jlistConfig;
        
        $hash = md5('com_jdownloads' . '.categories.' . serialize($config));
        
        if (!isset(self::$rows[$hash])){
        
            // use default sort order or menu order settings
            if (empty($orderby_pri)){
                // use config settings
                switch ($jlistConfig['cats.order']){
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
            }  
        
        	$user = JFactory::getUser();
            $db = JFactory::getDbo();
            $query = $db->getQuery(true);

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

            // Get the options.

            $db->setQuery($query);
            $rows = $db->loadObjectList();

            // Assemble the list options.
            self::$rows[$hash] = array();

            // Check for a database error.
            if ($db->getErrorNum()) {
                JError::raiseWarning(500, $db->getErrorMsg());
            }

            // Order subcategories
            if (count($rows)) {
                if ($categoryOrderby == 'alpha' || $categoryOrderby == 'ralpha') {
                    $i = 0;
                    $depth = 0;
                    $parent_id = 0;
                    $parents = array();
                    
                    foreach($rows as $cat) {
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
                    $rows = $tree;
                }
                
            }

            if (empty($id)) {
                // New item, only have to check core.create.
                foreach ($rows as $i => $option)
                {
                    if ($option->value > 0){
                        // Special handling for the uncategorisied option (value (id) = 1)
                        // Use here the components settings
                        if ($option->value == 1){
                            // Unset the option if the user isn't authorised for it.
                            if (!$user->authorise('core.create', 'com_jdownloads')) {
                                unset($rows[$i]);
                            }
                        } else {        
                            // Unset the option if the user isn't authorised for it.
                            if (!$user->authorise('core.create', 'com_jdownloads.category.'.$option->value)) {
                                unset($rows[$i]);
                            }
                        }    
                    }    
                }
            } else {
                // Existing item is a bit more complex. Need to account for core.edit and core.edit.own.
                foreach ($rows as $i => $option)
            {
                    // Special handling for the uncategorisied option (value (id) = 1)
                    // Use here the components settings
                    if ($option->value == 1){
                        if (!$user->authorise('core.edit', 'com_jdownloads')) {
                            // As a backup, check core.edit.own
                            if (!$user->authorise('core.edit.own', 'com_jdownloads')) {
                                // No core.edit nor core.edit.own - bounce this one
                                unset($rows[$i]);
                            }                
                        }
                    } else {        
                    
                        // Unset the option if the user isn't authorised for it.
                        if (!$user->authorise('core.edit', 'com_jdownloads.category.'.$option->value)) {
                            // As a backup, check core.edit.own
                            if (!$user->authorise('core.edit.own', 'com_jdownloads.category.'.$option->value)) {
                                // No core.edit nor core.edit.own - bounce this one
                                unset($rows[$i]);
                            }
                        }
                    }
                }    
            }

            foreach ($rows as &$row){
                $repeat = ($row->level - 1 >= 0) ? $row->level - 1 : 0;
                $row->text = str_repeat('- ', $repeat) . $row->text;
                self::$rows[$hash][] = JHtml::_('select.option', $row->value, $row->text);
            }
            
            return self::$rows[$hash];
        }

    }
}
