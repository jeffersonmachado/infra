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

class BlogFactoryFrontendModelBlogEdit extends BlogFactoryModel
{
    public function getForm()
    {
        BlogFactoryForm::addFormPath(JPATH_SITE . '/components/com_blogfactory/models/forms');
        BlogFactoryForm::addFieldPath(JPATH_SITE . '/components/com_blogfactory/models/fields');

        $form = BlogFactoryForm::getInstance('com_blogfactory.blog', 'blog', array('control' => 'jform'));

        $session = JFactory::getSession();
        $context = 'com_blogfactory.blog.save';
        $data = $session->get($context, array());

        if (!$data) {
            $table = $this->getTable('Blog', 'BlogFactoryTable');
            $table->load(array('user_id' => JFactory::getUser()->id));

            $registry = new JRegistry($table->params);
            $table->params = $registry->toArray();

            $data = $table->getProperties();
        }

        $form->bind($data);
        $session->set($context, null);

        $notifications_report = BlogFactoryNotification::getNotifications('report.add.owner');
        if (!$notifications_report) {
            $form->removeField('notification_report');
        }

        $notifications_comment = BlogFactoryNotification::getNotifications('comment.add.owner');
        if (!$notifications_comment) {
            $form->removeField('notification_comment');
        }

        return $form;
    }

    public function save($data)
    {
        jimport('joomla.filesystem.file');
        jimport('joomla.filesystem.folder');

        // Get form.
        $form = $this->getForm();
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

        $table = $this->getTable('Blog', 'BlogFactoryTable');
        $user = JFactory::getUser();

        // If editing blog, check if blog exists and user is allowed to edit.
        if (isset($data['id']) && $data['id']) {
            // Check if the blog exists.
            if (!$table->load($data['id'])) {
                $this->setError(BlogFactoryText::_('blog_save_error_not_found'));
                return false;
            }

            // Check if the user is allowed to edit the blog.
            if ($table->user_id != $user->id) {
                $this->setError(BlogFactoryText::_('blog_save_error_not_allowed'));
                return false;
            }
        } else {
            $data['published'] = 1;
        }

        // Check if uploading a new photo.
        if (is_array($data['photo']) && isset($data['photo']['error']) && 0 == $data['photo']['error']) {
            // Remove old photo.
            if (isset($data['id']) && $data['id']) {
                $temp = $this->getTable('Blog', 'BlogFactoryTable');
                $temp->load($data['id']);

                if ('' != $temp->photo) {
                    jimport('joomla.filesystem.file');
                    jimport('joomla.filesystem.folder');

                    $base = JPATH_SITE . '/media/com_blogfactory/blogs/';
                    $name = JFile::stripExt($temp->photo);
                    $thumbnails = JFolder::files($base, $name . '_');

                    // Remove main photo.
                    $src = $base . $temp->photo;
                    if (JFile::exists($src)) {
                        JFile::delete($src);
                    }

                    // Remove thumbnails.
                    foreach ($thumbnails as $thumbnail) {
                        JFile::delete($base . $thumbnail);
                    }
                }
            }

            $ext = JFile::getExt($data['photo']['name']);
            $name = md5(time() . mt_rand(0, 9999));
            $base = JPATH_SITE . '/media/com_blogfactory/blogs/';

            JFile::upload($data['photo']['tmp_name'], $base . $name . '.' . $ext);

            $image = BlogFactoryImages::getInstance();
            //$image->resizeScale($base . $name . '.' . $ext, 64, $base . $name . '_64.' . $ext);
            $image->resizeCrop($base . $name . '.' . $ext, 64, $base . $name . '_64.' . $ext);

            $data['photo'] = $name . '.' . $ext;
        }

        $registry = new JRegistry($data['params']);
        $data['params'] = $registry->toString();

        if (!$table->save($data)) {
            return false;
        }

        // Create user folder.
        $folder = JPATH_SITE . '/media/com_blogfactory/users/' . $table->user_id;
        if (!JFolder::exists($folder)) {
            JFolder::create($folder);
        }

        return true;
    }
}
