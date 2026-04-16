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

class BlogFactoryBackendModelBlog extends JModelAdmin
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

        return $form;
    }

    public function getTable($name = 'Blog', $prefix = 'BlogFactoryTable', $options = array())
    {
        return parent::getTable($name, $prefix, $options);
    }

    public function export($cid, $export)
    {
        $posts = $this->getPostsForBlogs($cid);
        $model = JModelLegacy::getInstance('Post', 'BlogFactoryBackendModel');

        return $model->export($posts, $export);
    }

    public function save($data)
    {
        if (isset($data['export']) && is_array($data['export'])) {
            $registry = new JRegistry($data['export']);
            $data['export'] = $registry->toString();
        }

        return parent::save($data);
    }

    public function getItem($pk = null)
    {
        if ($item = parent::getItem($pk)) {
            $registry = new JRegistry($item->export);
            $item->export = $registry->toArray();
        }

        return $item;
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

    protected function getPostsForBlogs($blogs)
    {
        if (!$blogs) {
            return array();
        }

        $dbo = $this->getDbo();
        $query = $dbo->getQuery(true)
            ->select('p.id')
            ->from('#__com_blogfactory_posts p')
            ->where('p.blog_id IN (' . implode(',', $blogs) . ')');
        $results = $dbo->setQuery($query)
            ->loadColumn();

        return $results;
    }
}
