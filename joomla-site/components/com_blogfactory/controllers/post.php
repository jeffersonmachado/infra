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

class BlogFactoryFrontendControllerPost extends JControllerLegacy
{
    public function save()
    {
        JSession::checkToken() or die();

        $app = JFactory::getApplication();
        $user = JFactory::getUser();
        $session = JFactory::getSession();
        $data = $this->input->post->get('jform', array(), 'array');
        $id = $this->input->getInt('id');
        $mode = $this->input->getCmd('mode');
        $model = $this->getModel('PostEdit');
        $context = 'com_blogfactory.post.save';

        $data['user_id'] = $user->id;
        $data['id'] = $id;

        if ($model->save($data, $mode)) {
            $id = $model->getState('item.id');
            $msg = BlogFactoryText::_('post_task_save_success');
            $session->set($context, null);
        } else {
            $app->enqueueMessage($model->getState('error'), 'error');
            $msg = BlogFactoryText::_('post_task_save_error');
            $session->set($context, $data);
        }

        $this->setRedirect(BlogFactoryRoute::view('postedit&id=' . $id), $msg);

        return true;
    }
}
