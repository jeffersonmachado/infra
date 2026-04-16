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

JFormHelper::loadFieldType('FactoryRules');

class JFormFieldBlogFactoryRules extends JFormFieldFactoryRules
{
    public $type = 'BlogFactoryRules';

    protected function getActions($component)
    {
        $actions = parent::getActions($component);

        if ($this->isLoveFactoryIntegrated() || $this->isSocialFactoryIntegrated()) {
            foreach ($actions as $i => $action) {
                if ('frontend.blog.create' == $action->name) {
                    unset($actions[$i]);
                    break;
                }
            }
        }

        return $actions;
    }

    protected function isLoveFactoryIntegrated()
    {
        $extension = JTable::getInstance('Extension');
        $result = $extension->find(array('type' => 'component', 'element' => 'com_lovefactory'));

        if (!$result) {
            return false;
        }

        $file = JPATH_ADMINISTRATOR . '/components/com_lovefactory/settings.php';

        if (!file_exists($file)) {
            return false;
        }

        require_once $file;

        if (!class_exists('LoveFactorySettings')) {
            return false;
        }

        $settings = new LovefactorySettings();

        return isset($settings->enable_blogfactory_integration) ? $settings->enable_blogfactory_integration : 0;
    }

    protected function isSocialFactoryIntegrated()
    {
        $extension = JTable::getInstance('Extension');
        $result = $extension->find(array('type' => 'component', 'element' => 'com_socialfactory'));

        if (!$result) {
            return false;
        }

        $settings = JComponentHelper::getParams('com_socialfactory');

        return $settings->get('integration_blogfactory.enable', 0);
    }
}
