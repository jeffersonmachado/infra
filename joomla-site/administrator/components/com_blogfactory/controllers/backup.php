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

class BlogFactoryBackendControllerBackup extends JControllerForm
{
    protected $option = 'com_blogfactory';

    public function backup()
    {
        $model = $this->getModel('Backup');
        $archive = $model->backup();

        if (false === $archive) {
            $this->setRedirect(BlogFactoryRoute::view('settings'), $model->getState('error'), 'error');
        }
    }

    public function restore()
    {
        $model = $this->getModel('Backup');
        $data = $this->input->post->get('jform', array(), 'array');
        $files = $this->input->files->get('jform', array(), 'array');

        $data = array_merge($data, $files);

        if ($model->restore($data)) {
            $message = BlogFactoryText::_('backup_task_restore_success');
            $type = 'message';
        } else {
            $message = $model->getState('error');
            $type = 'error';
        }

        $this->setRedirect(BlogFactoryRoute::view('settings'), $message, $type);
    }
}
