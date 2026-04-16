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

class BlogFactoryBackendControllerSettings extends JControllerLegacy
{
    public function __construct($config = array())
    {
        parent::__construct($config);

        $this->registerTask('apply', 'save');
    }

    public function save()
    {
        $model = $this->getModel('Settings');
        $form = $model->getForm();
        $data = $this->input->get('jform', array(), 'array');
        $app = JFactory::getApplication();
        $route = BlogFactoryRoute::view('settings');

        $return = $model->validate($form, $data);

        if ($return === false) {
            $this->setMessage($model->getError(), 'error');

            // Redirect back to the edit screen.
            $this->setRedirect($route);
            return false;
        }

        if ($model->save($return)) {
            $msg = BlogFactoryText::_('settings_task_save_success');
        } else {
            $app->enqueueMessage($model->getError(), 'error');
            $msg = BlogFactoryText::_('settings_task_save_error');
        }

        if ('save' == $this->getTask()) {
            $route = BlogFactoryRoute::_('');
        }

        $this->setRedirect($route, $msg);

        return true;
    }

    public function cancel()
    {
        $this->setRedirect(BlogFactoryRoute::_());
    }
}
