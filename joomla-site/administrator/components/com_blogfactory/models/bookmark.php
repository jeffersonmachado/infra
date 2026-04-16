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

class BlogFactoryBackendModelBookmark extends JModelAdmin
{
    protected $option = 'com_blogfactory';
    protected $event_after_save = 'onBlogFactoryBookmarkAfterSave';

    public function __construct($config = array())
    {
        parent::__construct($config);

        $dispatcher = JEventDispatcher::getInstance();
        $dispatcher->register($this->event_after_save, $this->event_after_save);
    }

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

        return $form;
    }

    public function getTable($name = 'Bookmark', $prefix = 'BlogFactoryTable', $options = array())
    {
        return parent::getTable($name, $prefix, $options);
    }

    protected function prepareTable($table)
    {
        jimport('joomla.filesystem.file');

        if (empty($table->id)) {
            $table->reorder();
        }

        // Upload new file.
        if (is_array($table->thumbnail) && 0 == $table->thumbnail['error']) {
            $dest = JPATH_SITE . '/media/com_blogfactory/temp/' . $table->thumbnail['name'];

            if (false === getimagesize($table->thumbnail['tmp_name']) || !JFile::upload($table->thumbnail['tmp_name'], $dest)) {
                $table->thumbnail = null;
                return false;
            }

            BlogFactoryImages::getInstance()->resize($dest, 32, $dest);

            $table->thumbnail = $table->thumbnail['name'];

            // Remove old file.
            if ($table->id) {
                $bookmark = $this->getTable();
                $bookmark->load($table->id);

                $path = JPATH_SITE . '/media/com_blogfactory/share/' . $bookmark->thumbnail;

                if (JFile::exists($path)) {
                    JFile::delete($path);
                }
            }
        }

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

function onBlogFactoryBookmarkAfterSave($context, $table, $isNew)
{
    if ('com_blogfactory.bookmark' != $context) {
        return true;
    }

    jimport('joomla.filesystem.file');

    $path = JPATH_SITE . '/media/com_blogfactory/temp/' . $table->thumbnail;

    if (JFile::exists($path)) {
        $extension = JFile::getExt($table->thumbnail);
        $array = array($table->id, $table->title);
        $name = JApplicationHelper::stringURLSafe(implode(' ', $array)) . '.' . $extension;

        JFile::move($path, JPATH_SITE . '/media/com_blogfactory/share/' . $name);

        $table->thumbnail = $name;
        $table->store();
    }

    return true;
}
