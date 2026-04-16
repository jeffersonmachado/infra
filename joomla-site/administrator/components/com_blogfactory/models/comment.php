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

class BlogFactoryBackendModelComment extends BlogFactoryModelAdmin
{
    protected $option = 'com_blogfactory';

    public function getForm($data = array(), $loadData = true)
    {
        /* @var $form JForm */
        JFormHelper::addFieldPath(JPATH_COMPONENT_SITE . '/framework/fields');

        $form = $this->loadForm($this->option . '.' . $this->getName(), $this->getName(), array(
                'control' => 'jform',
                'load_data' => $loadData)
        );

        if (!$form) {
            return false;
        }

        if (!JComponentHelper::getParams('com_blogfactory')->get('comments.approval', 0)) {
            $form->removeField('approved');
        }

        BlogFactoryHelper::addLabelsToForm($form);

        return $form;
    }

    public function getTable($name = '', $prefix = 'BlogFactoryTable', $options = array())
    {
        if ('' == $name) {
            $name = $this->getName();
        }

        return parent::getTable($name, $prefix, $options);
    }

    public function approve(&$pks, $value = 1)
    {
        $table = $this->getTable();
        $pks = (array)$pks;

        // Attempt to change the state of the records.
        if (!$table->approve($pks, $value)) {
            return false;
        }

        // Clear the component's cache
        $this->cleanCache();

        return true;
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
