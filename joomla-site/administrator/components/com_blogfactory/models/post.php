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

class BlogFactoryBackendModelPost extends JModelAdmin
{
    protected $option = 'com_blogfactory';

    public function getForm($data = array(), $loadData = true)
    {
        /* @var $form JForm */
        JFormHelper::addFieldPath(JPATH_COMPONENT_SITE . '/framework/fields');
        JFormHelper::addFieldPath(JPATH_COMPONENT_SITE . '/models/fields');

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

    public function getTable($name = 'Post', $prefix = 'BlogFactoryTable', $options = array())
    {
        return parent::getTable($name, $prefix, $options);
    }

    public function getItem($pk = null)
    {
        $item = parent::getItem($pk);

        $metadata = new JRegistry($item->metadata);
        $item->metadata = $metadata->toArray();

        return $item;
    }

    public function save($data)
    {
        $metadata = new JRegistry($data['metadata']);
        $data['metadata'] = $metadata->toString();
        $dbo = $this->getDbo();
        $date = JFactory::getDate();

        if (!parent::save($data)) {
            return false;
        }

        $post = $this->getTable();
        $post->load($this->getState($this->getName() . '.id'));

        /** @var BlogFactoryFrontendModelPostEdit $model */
        JModelLegacy::addIncludePath(JPATH_SITE . '/components/com_blogfactory/models');
        $model = JModelLegacy::getInstance('PostEdit', 'BlogFactoryFrontendModel');
        $model->saveTags($this->getState($this->getName() . '.id'), $data['tags']);

        // TODO (alex): Move this to a plugin.
        if ($post->published && $post->visibility &&
            ($post->publish_up == $dbo->getNullDate() || $post->publish_up < $dbo->quote($date->toSql()))
        ) {
            $blog = $this->getTable('Blog', 'BlogFactoryTable');
            $blog->load(array('user_id' => $data['user_id']));

            $model->exportContent($post, $blog);
        }

        return true;
    }

    public function export($cid, $export)
    {
        foreach ($cid as $id) {
            BlogFactoryHelper::exportPostToContent($id, $export);
        }

        return true;
    }

    public function import($import)
    {
        // Check if blog id is set.
        if (!isset($import['target']['blog_id']) || !$blogId = $import['target']['blog_id']) {
            throw new InvalidArgumentException(BlogFactoryText::_('post_import_error_blog_not_set'));
        }

        // Check if blog exists.
        $blog = $this->getTable('Blog');
        if (!$blog->load($blogId)) {
            throw new Exception(BlogFactoryText::sprintf('post_import_error_blog_not_found', $blogId), 404);
        }

        $import['target']['user_id'] = $blog->user_id;

        try {
            $imported = BlogFactoryHelper::importContent($import);
        } catch (Exception $e) {
            throw $e;
        }

        $this->setState('items.imported', $imported);

        return true;
    }

    public function batch($cid, $batch, $contexts)
    {
        if (isset($batch['blog']) && $batch['blog']) {
            $this->batchMove($cid, $batch['blog']);
        }

        return true;
    }

    protected function batchMove($cid, $blogId, $contexts)
    {
        $blog = JTable::getInstance('Blog', 'BlogFactoryTable');
        $blog->load($blogId);

        foreach ($cid as $id) {
            $post = JTable::getInstance('Post', 'BlogFactoryTable');
            $post->load($id);

            $post->blog_id = $blog->id;
            $post->user_id = $blog->user_id;

            $post->store();
        }

        return true;
    }

    protected function prepareTable($table)
    {
        parent::prepareTable($table);

        if ($table->blog_id) {
            $blog = $this->getTable('Blog');
            $blog->load($table->blog_id);

            $table->user_id = $blog->user_id;
        } else {
            $table->user_id = 0;
        }
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
