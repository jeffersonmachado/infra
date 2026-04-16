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

class BlogFactoryFrontendControllerProfile extends JControllerLegacy
{
    public function save()
    {
        $app = JFactory::getApplication();
        $user = JFactory::getUser();
        $session = JFactory::getSession();
        $data = $this->input->post->get('jform', array(), 'array');
        $files = $this->input->files->get('jform', array(), 'array');
        $model = $this->getModel('Profile');
        $context = 'com_blogfactory.profile.save';

        $data = array_merge($data, $files);
        $data['id'] = $user->id;

        if ($model->save($data)) {
            $msg = BlogFactoryText::_('profile_task_save_success');

            $session->set($context, null);
        } else {
            $app->enqueueMessage($model->getState('error'), 'error');
            $msg = BlogFactoryText::_('profile_task_save_error');

            $session->set($context, $data);
        }

        $this->setRedirect(BlogFactoryRoute::view('profile'), $msg);

        return true;
    }
}
