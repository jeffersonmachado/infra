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

class BlogFactoryBackendModelUser extends JModelAdmin
{
    protected $option = 'com_blogfactory';

    public function getForm($data = array(), $loadData = true)
    {
        /* @var $form JForm */
        JFormHelper::addFieldPath(JPATH_COMPONENT_SITE . '/framework/fields');
        JFormHelper::addFieldPath(JPATH_COMPONENT_SITE . '/models/fields');

        JFactory::getLanguage()->load('com_blogfactory', JPATH_SITE);

        $form = $this->loadForm($this->option . '.' . $this->getName(), $this->getName(), array(
                'control' => 'jform',
                'load_data' => $loadData)
        );

        if (!$form) {
            return false;
        }

        BlogFactoryHelper::addLabelsToForm($form);

        return $form;
    }

    public function getTable($name = 'Profile', $prefix = 'BlogFactoryTable', $options = array())
    {
        return parent::getTable($name, $prefix, $options);
    }

    public function save($data)
    {
        jimport('joomla.filesystem.file');

        // Load current user.
        $table = $this->getTable();
        $table->load($data['id']);

        // Remove old avatar.
        if ($data['delete_avatar']) {
            BlogFactoryHelper::deleteUserAvatar($table->avatar);
        }

        // Upload new avatar.
        if (is_array($file = $data['avatar'])) {
            $data['avatar'] = BlogFactoryHelper::saveUserAvatar($file, $table->avatar);
        }

        return parent::save($data);
    }

    protected function loadFormData()
    {
        // Check the session for previously entered form data.
        $app = JFactory::getApplication();
        $data = $app->getUserState($this->option . '.edit.' . $this->getName() . '.data', array());

        if (empty($data)) {
            $data = $this->getItem();
        }

        return $data;
    }
}
