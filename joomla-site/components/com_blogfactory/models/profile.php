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

class BlogFactoryFrontendModelProfile extends JModelLegacy
{
    protected $option = 'com_blogfactory';

    public function getForm($loadData = true)
    {
        BlogFactoryForm::addFormPath(JPATH_SITE . '/components/com_blogfactory/models/forms');
        BlogFactoryForm::addFieldPath(JPATH_SITE . '/components/com_blogfactory/models/fields');

        /* @var $form JForm */
        $form = BlogFactoryForm::getInstance(
            $this->option . '.' . $this->getName(),
            $this->getName(),
            array('control' => 'jform')
        );

        if ($loadData) {
            $item = $this->getItem();
            $form->bind($item);
        }

        return $form;
    }

    public function getItem()
    {
        $table = $this->getTable('Profile', 'BlogFactoryTable');
        $table->load(JFactory::getUser()->id);

        return $table;
    }

    public function save($data)
    {
        // Get form.
        $form = $this->getForm(false);
        $data = $form->filter($data);

        // Validate input data.
        if (!$form->validate($data)) {
            $errors = array();

            foreach ($form->getErrors() as $error) {
                $errors[] = $error->getMessage();
            }

            $this->setState('error', implode('<br />', $errors));
            return false;
        }

        $table = $this->getTable('Profile', 'BlogFactoryTable');

        if ($data['id'] && !$table->load($data['id'])) {
            $dbo = $this->getDbo();
            $table->id = $data['id'];

            $dbo->insertObject($table->getTableName(), $table);
        }

        // Save user avatar.
        if (is_array($file = $data['avatar'])) {
            $data['avatar'] = BlogFactoryHelper::saveUserAvatar($file, $table->avatar);
        }

        if (!$table->save($data)) {
            return false;
        }

        return true;
    }
}
