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

class BlogFactoryBackendModelNotification extends JModelAdmin
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

        BlogFactoryHelper::addLabelsToForm($form);

        $type = !is_null($form->getValue('type')) ? $form->getValue('type') : (isset($data['type']) ? $data['type'] : null);
        if (!$this->notificationHasGroups($type)) {
            $form->removeField('groups');
        } else {
            $form->removeField('lang_code');
        }

        return $form;
    }

    public function getTable($name = 'Notification', $prefix = 'BlogFactoryTable', $options = array())
    {
        return parent::getTable($name, $prefix, $options);
    }

    public function getItem($pk = null)
    {
        $item = parent::getItem($pk);

        $registry = new JRegistry($item->groups);
        $item->groups = $registry->toArray();

        return $item;
    }

    public function save($data)
    {
        $app = JFactory::getApplication();

        // Alter the subject for save as copy.
        if ($app->input->get('task') == 'save2copy') {
            $data['subject'] = $this->generateNewSubject($data['subject']);
        }

        return parent::save($data);
    }

    protected function prepareTable($table)
    {
        JArrayHelper::toInteger($table->groups);
        $registry = new JRegistry($table->groups);

        $table->groups = $registry->toString();
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

    protected function generateNewSubject($subject)
    {
        // Alter the subject.
        $table = $this->getTable();

        while ($table->load(array('subject' => $subject))) {
            if ($subject == $table->subject) {
                $subject = JString::increment($subject);
            }
        }

        return $subject;
    }

    protected function notificationHasGroups($type)
    {
        $xml = simplexml_load_file(JPATH_COMPONENT_ADMINISTRATOR . '/blogfactory.xml');
        $notification = $xml->xpath('//parameters/notifications/notification[@type="' . $type . '"]');

        if (!isset($notification[0])) {
            return false;
        }

        if ('true' == $notification[0]->attributes()->hasGroups) {
            return true;
        }

        return false;
    }
}
