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

class BlogFactoryMenu
{
    public static function create($xml, $option)
    {
        foreach ($xml->menu as $menu) {
            self::createMenu($menu, $option);
        }

        return true;
    }

    protected static function createMenu($menu, $option)
    {
        // Initialise variables.
        $type = (string)$menu->attributes()->type;
        $title = (string)$menu->attributes()->title;
        $description = (string)$menu->attributes()->description;
        $position = (string)$menu->attributes()->position;

        $extension = JTable::getInstance('Extension');
        $componentId = $extension->find(array('type' => 'component', 'element' => 'com_blogfactory'));

        // 1. Create Joomla menu.
        $result = self::createMenuType($type, $title, $description);

        if (!$result) {
            return false;
        }

        // 2. Create module for menu.
        $moduleId = self::createModule($type, $title, $position);

        // 3. Add module to all pages.
        self::addModuleToAllPAges($moduleId);

        // 4. Add items to menu.
        foreach ($menu->item as $item) {
            self::addMenuItem($item, $componentId, $type, $option);
        }

        return true;
    }

    protected static function createMenuType($type, $title, $description = null)
    {
        $table = JTable::getInstance('MenuType');

        $data = array(
            'menutype' => $type,
            'title' => $title,
            'description' => $description,
        );

        if ($table->load($data)) {
            return true;
        }

        if (!$table->save($data)) {
            return false;
        }

        return true;
    }

    protected static function createModule($type, $title, $position)
    {
        $table = JTable::getInstance('Module');

        $params = new JRegistry(array(
            'menutype' => $type,
        ));

        $data = array(
            'title' => $title,
            'position' => $position,
            'published' => 1,
            'module' => 'mod_menu',
            'access' => 1,
            'showtitle' => 1,
            'client_id' => 0,
            'language' => '*',
            'params' => $params->toString(),
        );

        if ($table->load($data)) {
            return $table->id;
        }

        if (!$table->save($data)) {
            return false;
        }

        return $table->id;
    }

    protected static function addModuleToAllPages($moduleId)
    {
        $dbo = JFactory::getDbo();

        $query = $dbo->getQuery(true)
            ->select('m.moduleid')
            ->from('#__modules_menu m')
            ->where('m.moduleid = ' . $dbo->quote($moduleId))
            ->where('m.menuid = ' . $dbo->quote(0));
        $result = $dbo->setQuery($query)
            ->loadResult();

        if ($result) {
            return true;
        }

        $query = $dbo->getQuery(true)
            ->insert($dbo->quoteName('#__modules_menu'))
            ->values($dbo->quote($moduleId) . ', ' . $dbo->quote(0));

        $result = $dbo->setQuery($query)
            ->execute();

        if (!$result) {
            return false;
        }

        return true;
    }

    protected static function addMenuItem($item, $componentId, $type, $option)
    {
        $title = (string)$item->attributes()->title;
        $view = (string)$item->attributes()->view;

        $table = JTable::getInstance('Menu');

        $data = array(
            'menutype' => $type,
            'type' => 'component',
            'published' => 1,
            'client_id' => 0,
            'level' => 1,
            'parent_id' => 1,
            'component_id' => $componentId,
            'title' => $title,
            'alias' => JFilterOutput::stringURLSafe($title),
            'link' => 'index.php?option=' . $option . '&view=' . $view,
            'access' => 1,
            'language' => '*',
        );

        if ($table->load($data)) {
            return true;
        }

        $table->setLocation($table->parent_id, 'last-child');

        if (!$table->save($data)) {
            return false;
        }

        return true;
    }
}
