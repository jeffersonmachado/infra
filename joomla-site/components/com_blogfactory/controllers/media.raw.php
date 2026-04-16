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

class BlogFactoryFrontendControllerMedia extends JControllerLegacy
{
    public function files()
    {
        jimport('joomla.filesystem.folder');

        $folder = base64_decode($this->input->getBase64('folder'));
        $files = JFolder::files($folder);
        $response = array();

        $temp = str_replace(JPATH_SITE, JUri::root(), $folder);

        foreach ($files as $file) {
            $response[] = array(
                'title' => $file,
                'src' => str_replace('\\', '/', $temp . '/' . $file),
            );
        }

        echo json_encode($response);

        jexit();
    }

    public function createFolder()
    {
        $title = $this->input->getString('title');
        $folder = base64_decode($this->input->getBase64('folder'));
        $response = array();

        jimport('joomla.filesystem.folder');

        if (!JFolder::exists($this->getBaseFolder() . $folder . '/' . $title) && JFolder::create($this->getBaseFolder() . $folder . '/' . $title)) {
            $response['status'] = 1;
            $response['path'] = base64_encode($folder . '/' . $title);
            $response['title'] = $title;
        } else {
            $response['status'] = 0;
        }

        echo json_encode($response);

        jexit();
    }

    public function removeFolder()
    {
        $folder = base64_decode($this->input->getBase64('folder'));
        $response = array();

        jimport('joomla.filesystem.folder');

        if (JFolder::exists($this->getBaseFolder() . $folder) && JFolder::delete($this->getBaseFolder() . $folder)) {
            $response['status'] = 1;

            $model = $this->getModel('Media');
            $response['free_space'] = $model->getFreeSpace();
        } else {
            $response['status'] = 0;
        }

        echo json_encode($response);

        jexit();
    }

    public function removeFile()
    {
        $folder = base64_decode($this->input->getBase64('folder'));
        $file = $this->input->getString('file');
        $response = array();
        $path = $this->getBaseFolder() . $folder . '/' . $file;

        jimport('joomla.filesystem.file');

        if (JFile::exists($path) && JFile::delete($path)) {
            $response['status'] = 1;

            // Remove thumbnails.
            $thumbnails = array('thumbnail', 'medium', 'large');
            $name = JFile::stripExt($file);
            $ext = JFile::getExt($file);
            foreach ($thumbnails as $thumbnail) {
                $path = $this->getBaseFolder() . $folder . '/' . $name . '_' . $thumbnail . '.' . $ext;

                if (JFile::exists($path)) {
                    JFile::delete($path);
                }
            }

            $model = $this->getModel('Media');
            $response['free_space'] = $model->getFreeSpace();
        } else {
            $response['status'] = 0;
        }

        echo json_encode($response);

        jexit();
    }

    public function upload()
    {
        jimport('joomla.filesystem.file');

        $model = $this->getModel('Media');
        $folder = base64_decode($this->input->getBase64('folder'));
        $file = ($this->input->files->get('file', array(), 'array'));
        $response = array();

        if ($model->upload($file, $folder)) {
            $response['status'] = 1;
            $response['message'] = BlogFactoryText::_('media_task_upload_success');
            $response['free_space'] = $model->getFreeSpace();
        } else {
            $response['status'] = 0;
            $response['message'] = BlogFactoryText::_('media_task_upload_error');
            $response['error'] = $model->getState('error');
        }

        echo json_encode($response);

        jexit();
    }

    protected function getBaseFolder()
    {
        $user = JFactory::getUser();

        return JPATH_SITE . '/media/com_blogfactory/users/' . $user->id;
    }
}
