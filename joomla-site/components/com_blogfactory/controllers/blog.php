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

class BlogFactoryFrontendControllerBlog extends JControllerLegacy
{
    public function save()
    {
        $app = JFactory::getApplication();
        $user = JFactory::getUser();
        $session = JFactory::getSession();
        $data = $this->input->post->get('jform', array(), 'array');
        $files = $this->input->files->get('jform', array(), 'array');
        $model = $this->getModel('BlogEdit');
        $context = 'com_blogfactory.blog.save';

        $data = array_merge($data, $files);
        $data['user_id'] = $user->id;

        if ($model->save($data)) {
            $msg = BlogFactoryText::_('blog_task_save_success');

            $session->set($context, null);
        } else {
            $app->enqueueMessage($model->getState('error'), 'error');
            $msg = BlogFactoryText::_('blog_task_save_error');

            $session->set($context, $data);
        }

        $this->setRedirect(BlogFactoryRoute::view('blogedit'), $msg);

        return true;
    }

    public function bookmark()
    {
        $id = $this->input->getInt('id');
        $userId = JFactory::getUser()->id;
        $bookmarked = $this->input->getInt('bookmarked');
        $model = $this->getModel('Blog');
        $response = array();

        if ($model->bookmark($id, $userId, $bookmarked)) {
            $response['status'] = 1;
            $response['message'] = BlogFactoryText::plural('blog_task_bookmark_success', intval(!$bookmarked));
            $response['text'] = BlogFactoryText::plural('blogs_blog_bookmark', intval(!$bookmarked));
            $response['bookmarks'] = BlogFactoryText::plural('blogs_blog_followers', $model->getState('bookmarks'));
        } else {
            $response['status'] = 0;
            $response['message'] = BlogFactoryText::plural('blog_task_bookmark_error', intval(!$bookmarked));
            $response['error'] = $model->getState('error');
        }

        echo json_encode($response);

        jexit();
    }

    public function subscribe()
    {
        $id = $this->input->getInt('id');
        $userId = JFactory::getUser()->id;
        $subscribed = $this->input->getInt('subscribed');
        $model = $this->getModel('Blog');
        $response = array();

        if ($model->subscribe($id, $userId, $subscribed)) {
            $response['status'] = 1;
            $response['message'] = BlogFactoryText::plural('blog_task_subscribe_success', intval(!$subscribed));
            $response['text'] = BlogFactoryText::plural('blogs_blog_subscribe', intval(!$subscribed));
        } else {
            $response['status'] = 0;
            $response['message'] = BlogFactoryText::plural('blog_task_subscribe_error', intval(!$subscribed));
            $response['error'] = $model->getState('error');
        }

        echo json_encode($response);

        jexit();
    }

    public function auto()
    {
        $app = JFactory::getApplication();
        $user = JFactory::getUser();
        $session = JFactory::getSession();
        $data = $this->input->post->get('jform', array(), 'array');
        $files = $this->input->files->get('jform', array(), 'array');
        $model = $this->getModel('BlogEdit');
        $context = 'com_blogfactory.blog.save';
        $return = base64_decode($this->input->get('return'));

        $data = array_merge($data, $files);
        $data['user_id'] = $user->id;

        $data['title'] = BlogFactoryText::sprintf('blog_creation_auto_title', $user->name);
        $data['params'] = array(
            'thumbnail_small_size' => 100,
            'thumbnail_medium_size' => 400,
            'thumbnail_large_size' => 800,
        );

        if ($model->save($data)) {
            $msg = BlogFactoryText::_('blog_task_save_success');

            $session->set($context, null);

            if ($return) {
                $this->setRedirect($return, $msg);
            } else {
                $this->setRedirect(BlogFactoryRoute::view('dashboard'), $msg);
            }
        } else {
            $app->enqueueMessage($model->getState('error'), 'error');
            $msg = BlogFactoryText::_('blog_task_save_error');

            $session->set($context, $data);

            $this->setRedirect(BlogFactoryRoute::view('blogedit'), $msg);
        }

        return true;
    }
}
