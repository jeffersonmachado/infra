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

class BlogFactoryBackendModelSettings extends BlogFactoryModelAdmin
{
    public function __construct($config = array())
    {
        $this->option = JFactory::getApplication()->input->getCmd('option');

        parent::__construct($config);
    }

    public function getForm($data = array(), $loadData = true)
    {
        /* @var $form JForm */
        JFormHelper::addFormPath(JPATH_COMPONENT_ADMINISTRATOR . '/models/forms');
        JFormHelper::addFieldPath(JPATH_COMPONENT_SITE . '/framework/fields');

        $form = $this->loadForm($this->option . '.' . $this->getName(), $this->getName(), array(
                'control' => 'jform',
                'load_data' => $loadData)
        );

        if (!$form) {
            return false;
        }

        BlogFactoryHelper::addLabelsToForm($form, false);

        return $form;
    }

    public function save($data)
    {
        // Save the rules.
        if (isset($data['rules'])) {
            jimport('joomla.access.rules');
            $rules = new JAccessRules($data['rules']);
            $asset = JTable::getInstance('asset');

            if (!$asset->loadByName($this->option)) {
                $root = JTable::getInstance('asset');
                $root->loadByName('root.1');
                $asset->name = $this->option;
                $asset->title = $this->option;
                $asset->setLocation($root->id, 'last-child');
            }

            $asset->rules = (string)$rules;

            if (!$asset->check() || !$asset->store()) {
                $this->setError($asset->getError());
                return false;
            }

            unset($data['rules']);
        }

        // Save component settings.
        $extension = JTable::getInstance('Extension');
        $id = $extension->find(array('element' => $this->option, 'type' => 'component'));

        $extension->load($id);
        $extension->bind(array('params' => $data));

        if (!$extension->store()) {
            $this->setError($extension->getError());
            return false;
        }

        parent::cleanCache('_system', 1);
        parent::cleanCache('_system', 0);

        return true;
    }

    public function getGuestGroup()
    {
        $settings = JComponentHelper::getParams('com_users');

        return $settings->get('guest_usergroup', null);
    }

    protected function loadFormData()
    {
        $params = JComponentHelper::getParams($this->option);

        return $params;
    }
}
