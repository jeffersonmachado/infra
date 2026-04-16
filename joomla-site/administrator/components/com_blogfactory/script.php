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

class com_BlogFactoryInstallerScript
{
    protected $option = 'com_blogfactory';

    public function install($parent)
    {
        return true;
    }

    public function uninstall($parent)
    {
        return true;
    }

    public function update($parent)
    {
        return true;
    }

    public function preflight($type, $parent)
    {
        if ('update' == $type) {
            $file = JPATH_ADMINISTRATOR . '/components/' . $this->option . '/blogfactory.xml';
            $data = JInstaller::parseXMLInstallFile($file);
            $this->updateSchemasTable($data);
        }

        return true;
    }

    public function postflight($type, $parent)
    {
        JLoader::discover('BlogFactory', JPATH_SITE . '/components/com_blogfactory/framework');

        $this->createMenu();

        return true;
    }

    protected function createMenu()
    {
        jimport('joomla.filesystem.file');

        $file = JPATH_ADMINISTRATOR . '/components/com_blogfactory/helpers/menu.xml';

        if (!JFile::exists($file)) {
            return false;
        }

        $xml = simplexml_load_file($file);
        $result = BlogFactoryMenu::create($xml, 'com_blogfactory');

        if (!$result) {
            return false;
        }

        return true;
    }

    protected function updateSchemasTable($data)
    {
        $extension = JTable::getInstance('Extension', 'JTable');
        $componentId = $extension->find(array('type' => 'component', 'element' => $this->option));

        $dbo = JFactory::getDbo();
        $query = $dbo->getQuery(true)
            ->select('s.version_id')
            ->from('#__schemas s')
            ->where('s.extension_id = ' . $dbo->quote($componentId));
        $result = $dbo->setQuery($query)
            ->loadResult();

        if (!$result) {
            $query = $dbo->getQuery(true)
                ->insert('#__schemas')
                ->set('extension_id = ' . $dbo->quote($componentId))
                ->set('version_id = ' . $dbo->quote($data['version']));
        } else {
            $query = $dbo->getQuery(true)
                ->update('#__schemas')
                ->set('version_id = ' . $dbo->quote($data['version']))
                ->where('extension_id = ' . $dbo->quote($componentId));
        }

        $dbo->setQuery($query)
            ->execute();
    }
}
