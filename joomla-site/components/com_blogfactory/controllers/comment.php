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

class BlogFactoryFrontendControllerComment extends JControllerLegacy
{
    public function save()
    {
        $app = JFactory::getApplication();
        $user = JFactory::getUser();
        $session = JFactory::getSession();
        $data = $this->input->get('jform', array(), 'array');
        $model = $this->getModel('Comment');
        $context = 'com_blogfactory.comment.save';

        // Check if user is authorised to comment.
        if (!$user->authorise('frontend.comment.create', 'com_blogfactory')) {
            $this->setMessage(BlogFactoryText::_('comment_leave_reply_not_allowed'), 'error');
            $this->setRedirect($this->input->server->getString('HTTP_REFERER'));
            return true;
        }

        $data['user_id'] = $user->id;

        if ($model->save($data)) {
            $settings = JComponentHelper::getParams('com_blogfactory');

            if ($settings->get('comments.approval', 0)) {
                $msg = BlogFactoryText::_('comment_task_save_success_pending_approval');
            } else {
                $msg = BlogFactoryText::_('comment_task_save_success');
            }

            $session->set($context, null);
        } else {
            $app->enqueueMessage($model->getState('error'), 'error');
            $msg = BlogFactoryText::_('comment_task_save_error');

            $session->set($context, $data);
        }

        $this->setRedirect($this->input->server->getString('HTTP_REFERER'), $msg);

        return true;
    }
}
