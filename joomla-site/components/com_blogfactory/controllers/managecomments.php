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

class BlogFactoryFrontendControllerManageComments extends JControllerLegacy
{
    public function __construct($config = array())
    {
        parent::__construct($config);

        $this->registerTask('unapprove', 'approve');
    }

    public function approve()
    {
        $batch = $this->input->get('batch', array(), 'array');
        $model = $this->getModel('Comment');
        $task = $this->getTask();
        $approved = 'approve' == $task ? 0 : 1;

        if ($model->approve($batch, $approved)) {
            $msg = BlogFactoryText::_('managecomments_task_' . $task . '_success');
        } else {
            $msg = BlogFactoryText::_('managecomments_task_' . $task . '_error');
            JFactory::getApplication()->enqueueMessage($model->getState('error'), 'error');
        }

        $this->setRedirect($this->getRedirect(), $msg);

        return true;
    }

    public function delete()
    {
        $batch = $this->input->get('batch', array(), 'array');
        $model = $this->getModel('Comment');

        if ($model->delete($batch)) {
            $msg = BlogFactoryText::_('managecomments_task_delete_success');
        } else {
            $msg = BlogFactoryText::_('managecomments_task_delete_error');
            JFactory::getApplication()->enqueueMessage($model->getState('error'), 'error');
        }

        $this->setRedirect($this->getRedirect(), $msg);

        return true;
    }

    public function resolve()
    {
        $batch = $this->input->get('batch', array(), 'array');
        $model = $this->getModel('Comment');

        if ($model->resolve($batch)) {
            $msg = BlogFactoryText::_('managecomments_task_resolve_success');
        } else {
            $msg = BlogFactoryText::_('managecomments_task_resolve_error');
            JFactory::getApplication()->enqueueMessage($model->getState('error'), 'error');
        }

        $this->setRedirect($this->getRedirect(), $msg);

        return true;
    }

    protected function getRedirect()
    {
        return $this->input->server->getString('HTTP_REFERER', BlogFactoryRoute::view('managecomments'));
    }
}
