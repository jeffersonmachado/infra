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

class BlogFactoryFrontendControllerManageComment extends JControllerLegacy
{
    protected $option = 'com_blogfactory';

    public function delete()
    {
        $batch = $this->input->getInt('id');
        $model = $this->getModel('Comment');

        if ($model->delete($batch)) {
            $msg = BlogFactoryText::_('managecomment_task_delete_success');
        } else {
            $msg = BlogFactoryText::_('managecomment_task_delete_error');
            JFactory::getApplication()->enqueueMessage($model->getState('error'), 'error');
        }

        $this->setRedirect(BlogFactoryRoute::view('managecomments'), $msg);

        return true;
    }

    public function save()
    {
        $data = $this->input->get('comment', array(), 'array');
        $id = $this->input->getInt('id');
        $model = $this->getModel('ManageComment');

        $data['id'] = $id;

        if ($model->save($data)) {
            $msg = BlogFactoryText::_('managecomment_task_save_success');
            $route = BlogFactoryRoute::view('managecomments');
        } else {
            $msg = BlogFactoryText::_('managecomment_task_save_error');
            $route = BlogFactoryRoute::view('managecomment&id=' . $id);

            JFactory::getApplication()->enqueueMessage($model->getState('error'), 'error');
        }

        $this->setRedirect($route, $msg);

        return true;
    }
}
