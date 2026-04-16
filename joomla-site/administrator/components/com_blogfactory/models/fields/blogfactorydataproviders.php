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

JFormHelper::loadFieldType('List');

class JFormFieldBlogFactoryDataProviders extends JFormFieldList
{
    protected $type = 'BlogFactoryDataProviders';

    protected function getOptions()
    {
        jimport('joomla.filesystem.folder');

        $folders = JFolder::folders(JPATH_SITE . '/components', 'models', true, true);
        $options = array();

        foreach ($folders as $folder) {
            $file = $folder . '/blogfactorydataprovider.php';

            if (!JFile::exists($file)) {
                continue;
            }

            preg_match('/com_(.+)\\\|\//', $file, $matches);
            $component = $matches[1];

            $class = ucfirst($component) . 'BlogFactoryDataProvider';

            if (!class_exists($class)) {
                require_once $file;
            }

            if (!class_exists($class)) {
                continue;
            }

            $reflection = new ReflectionClass($class);
            if (!$reflection->implementsInterface('BlogFactoryDataProviderInterface')) {
                continue;
            }

            $component = 'com_' . $component;

            $language = JFactory::getLanguage();
            $language->load($component, JPATH_ADMINISTRATOR);
            $language->load($component . '.sys', JPATH_ADMINISTRATOR);

            $options[] = array('value' => $component, 'text' => JText::_(strtoupper($component)));
        }

        return $options;
    }
}
