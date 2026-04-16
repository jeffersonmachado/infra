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
    public function vote()
    {
        $data = $this->input->get('data', array(), 'array');
        $model = $this->getModel('Comment');
        $userId = JFactory::getUser()->id;
        $response = array();

        if ($model->vote($userId, $data)) {
            $response['status'] = 1;
            $response['message'] = BlogFactoryText::_('comment_task_vote_success');
            $response['rating'] = $model->getRating($data['id']);
        } else {
            $response['status'] = 0;
            $response['message'] = BlogFactoryText::_('comment_task_vote_error');
            $response['error'] = $model->getState('error');
        }

        echo json_encode($response);

        jexit();
    }

    public function delete()
    {
        $commentId = $this->input->getInt('id', 0);
        $model = $this->getModel('Comment');
        $response = array();

        if ($model->delete($commentId)) {
            $response['status'] = 1;
            $response['message'] = BlogFactoryText::_('comment_task_delete_success');
        } else {
            $response['status'] = 0;
            $response['message'] = BlogFactoryText::_('comment_task_delete_error');
            $response['error'] = $model->getState('error');
        }

        echo json_encode($response);

        jexit();
    }

    public function approve()
    {
        $id = $this->input->getInt('id');
        $approved = $this->input->getInt('approved');
        $model = $this->getModel('Comment');
        $response = array();

        if ($model->approve($id, $approved)) {
            $response['status'] = 1;
            $response['message'] = BlogFactoryText::plural('comment_task_approve_success', $approved);
            $response['text'] = BlogFactoryText::plural('dashboard_portlet_comments_comment_action_approve', !$approved);
        } else {
            $response['status'] = 0;
            $response['message'] = BlogFactoryText::plural('comment_task_approve_error', $approved);
            $response['error'] = $model->getState('error');
        }

        echo json_encode($response);

        jexit();
    }

    public function resolve()
    {
        $id = $this->input->getInt('id');
        $model = $this->getModel('Comment');
        $response = array();

        if ($model->resolve($id)) {
            $response['status'] = 1;
            $response['message'] = BlogFactoryText::_('comment_task_resolve_success');
        } else {
            $response['status'] = 0;
            $response['message'] = BlogFactoryText::_('comment_task_resolve_error');
            $response['error'] = $model->getState('error');
        }

        echo json_encode($response);

        jexit();
    }
}
