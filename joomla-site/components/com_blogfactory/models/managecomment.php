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

class BlogFactoryFrontendModelManageComment extends BlogFactoryModel
{
    protected $option = 'com_blogfactory';

    public function getForm()
    {
        BlogFactoryForm::addFormPath(JPATH_SITE . '/components/' . $this->option . '/models/forms');
        BlogFactoryForm::addFieldPath(JPATH_SITE . '/components/' . $this->option . '/models/fields');

        $form = BlogFactoryForm::getInstance(
            $this->option . '.' . $this->getName(),
            $this->getName(),
            array('control' => 'comment')
        );

        $settings = JComponentHelper::getParams('com_blogfactory');
        if (1 != $settings->get('comments.approval', 0)) {
            $form->removeField('approved');
        }

        return $form;
    }

    public function getItem()
    {
        static $items = array();

        $id = JFactory::getApplication()->input->getInt('id');

        if (!isset($items[$id])) {
            $items[$id] = $this->getTable();
            $items[$id]->load($id);
        }

        return $items[$id];
    }

    public function save($data)
    {
        // Initialise variables.
        $form = $this->getForm();
        $table = $this->getTable();
        $user = JFactory::getUser();

        // Filter data.
        $data = $form->filter($data);

        // Store submitted data.
        $context = $this->option . '.' . $this->getName() . '.edit';
        $session = JFactory::getSession();
        $session->set($context, $data);

        // Check if data is valid.
        if (!$form->validate($data)) {
            $this->setState('error', implode('<br />', $form->getError()));
            return false;
        }

        // Load post.
        $post = $this->getTable('post');
        $post->load($data['post_id']);

        // Check if user is allowed to save comment.
        if ($post->user_id != $user->id) {
            $this->setState('error', BlogFactoryText::_('managecomment_save_error_not_allowed'));
            return false;
        }

        // Save item.
        if (!$table->save($data)) {
            return false;
        }

        // Reset submitted data.
        $session->set($context, null);

        return true;
    }

    public function getData()
    {
        $context = $this->option . '.' . $this->getName() . '.edit';
        $session = JFactory::getSession();

        $data = $session->get($context, null);

        if (is_null($data)) {
            $data = $this->getItem();
        }

        $session->set($context, null);

        return $data;
    }

    public function getTable($name = 'Comment', $prefix = 'BlogFactoryTable', $options = array())
    {
        return parent::getTable($name, $prefix, $options);
    }
}
