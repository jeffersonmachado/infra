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

jimport('joomla.filesystem.folder');

class BlogFactoryFrontendModelMedia extends JModelLegacy
{
    public function getFolders()
    {
        if (false === $base = $this->getBaseFolder()) {
            return array();
        }

        if (!JFolder::exists($base)) {
            JFolder::create($base);
        }

        if (!JFolder::exists($base)) {
            throw new Exception(sprintf('User folder could not be created!', $base));
        }

        $folders = $this->DirectoryIteratorToArray(new DirectoryIterator($base));

        $folders = array(
            BlogFactoryText::_('media_root_folder') => array(
                'path' => base64_encode(''),
                'base' => true,
                'folders' => $folders,
            ),
        );

        return $folders;
    }

    public function getFiles()
    {
        jimport('joomla.filesystem.folder');
        jimport('joomla.filesystem.file');

        if (false === $base = $this->getBaseFolder()) {
            return array();
        }

        $folder = base64_decode(JFactory::getApplication()->input->getBase64('folder'));
        $files = JFolder::files($base . $folder);
        $results = array();

        foreach ($files as $file) {
            if (false !== strpos($file, '_thumbnail') ||
                false !== strpos($file, '_medium') ||
                false !== strpos($file, '_large')
            ) {
                continue;
            }

            $array = array(
                'filename' => $file,
                'url' => str_replace('\\', '/', JUri::root() . 'media/com_blogfactory/users/' . JFactory::getUser()->id . $folder . '/' . $file),
            );

            $name = JFile::stripExt($file);
            $ext = JFile::getExt($file);

            $size = getimagesize($this->getBaseFolder() . $folder . '/' . $file);
            if ($size) {
                $array['type'] = 'image';
                $array['thumbnail'] = str_replace('\\', '/', JUri::root() . 'media/com_blogfactory/users/' . JFactory::getUser()->id . $folder . '/' . $name . '_thumbnail.' . $ext);
            } else {
                switch (strtolower($ext)) {
                    case 'zip':
                    case '7z':
                    case 'rar':
                    case 'tar':
                        $array['type'] = 'archive';
                        break;

                    case 'mov':
                    case 'flv':
                    case 'avi':
                    case 'wmv':
                        $array['type'] = 'video';
                        break;

                    case 'mp3':
                    case 'wav':
                        $array['type'] = 'audio';
                        break;

                    default:
                        $array['type'] = 'document';
                        break;
                }

                $array['thumbnail'] = JUri::root() . 'media/com_blogfactory/default/thumbnails/' . $array['type'] . '.png';
            }

            $results[] = $array;
        }

        return $results;
    }

    public function getMaxUploadSize()
    {
        $size = min(ini_get('post_max_size'), ini_get('upload_max_filesize'));

        return str_replace('M', 'MB', $size);
    }

    public function upload($file, $folder)
    {
        // Check if file was uploaded.
        if (!$file) {
            $this->setState('error', BlogFactoryText::_('media_upload_error_no_file_uploaded'));
            return false;
        }

        // Check for errors uploading the file.
        if (!isset($file['error']) || 0 != $file['error']) {
            if (1 == $file['error']) {
                $this->setState('error', BlogFactoryText::_('media_upload_error_no_file_uploaded'));
            } else {
                $this->setState('error', BlogFactoryText::sprintf('media_upload_error_error_uploading_file', $file['error']));
            }
            return false;
        }

        // Check for user folder quota.
        $settings = JComponentHelper::getParams('com_blogfactory');
        if (!$settings->get('user_folder.general.enabled', 1)) {
            $this->setState('error', BlogFactoryText::_('media_upload_error_user_folder_not_enabled'));
            return false;
        }

        // Check if user has reached User Folder Quota.
        if (!$this->checkUserFolderQuota($settings, $file['size'])) {
            return false;
        }

        $file['name'] = $this->getFileName($file['name'], $folder);
        $name = JFile::stripExt($file['name']);
        $ext = JFile::getExt($file['name']);
        $dest = $this->getBaseFolder() . $folder . '/' . $file['name'];

        // Move uploaded file.
        if (!JFile::upload($file['tmp_name'], $dest)) {
            $this->setState('error', BlogFactoryText::_('media_upload_error_uploading_file'));
            return false;
        }

        // Create thumbnails.
        if (getimagesize($dest)) {
            $image = BlogFactoryImages::getInstance();

            $table = $this->getTable('Blog', 'BlogFactoryTable');
            $table->load(array('user_id' => JFactory::getUser()->id));

            $params = new JRegistry($table->params);

            $image->resizeScale($dest, $params->get('thumbnail_small_size', 100), $this->getBaseFolder() . $folder . '/' . $name . '_thumbnail.' . $ext);
            $image->resizeScale($dest, $params->get('thumbnail_medium_size', 400), $this->getBaseFolder() . $folder . '/' . $name . '_medium.' . $ext);
            $image->resizeScale($dest, $params->get('thumbnail_large_size', 800), $this->getBaseFolder() . $folder . '/' . $name . '_large.' . $ext);
        }

        return true;
    }

    public function getFreeSpace()
    {
        $settings = JComponentHelper::getParams('com_blogfactory');

        if (!$settings->get('user_folder.general.limit')) {
            return false;
        }

        $quota = $this->getUserFoderQuota();
        $size = $this->getUserFolderSize();

        if ($size > $quota) {
            return 0;
        }

        return number_format($this->getUserFoderQuota() - $this->getUserFolderSize(), 2);
    }

    protected function DirectoryIteratorToArray(DirectoryIterator $it)
    {
        $result = array();

        foreach ($it as $key => $child) {
            if ($child->isDot()) {
                continue;
            }

            $name = $child->getBasename();
            $path = base64_encode(str_replace($this->getBaseFolder(), '', $child->getPathname()));

            if ($child->isDir()) {
                $subit = new DirectoryIterator($child->getPathname());
                $result[$name] = array(
                    'path' => $path,
                    'base' => false,
                    'folders' => $this->DirectoryIteratorToArray($subit),
                );
            }
        }
        return $result;
    }

    protected function getBaseFolder()
    {
        $user = JFactory::getUser();

        if (!$user->id) {
            return false;
        }

        return JPATH_SITE . '/media/com_blogfactory/users/' . $user->id;
    }

    protected function getFileName($fullname, $folder)
    {
        $dest = $this->getBaseFolder() . $folder . '/' . $fullname;
        $name = JFile::stripExt($fullname);
        $ext = JFile::getExt($fullname);
        $i = 1;

        while (JFile::exists($dest)) {
            $fullname = $name . $i . '.' . $ext;
            $dest = $this->getBaseFolder() . $folder . '/' . $fullname;

            $i++;
        }

        return $fullname;
    }

    protected function checkUserFolderQuota($settings, $fileSize = 0)
    {
        if (!$settings->get('user_folder.general.limit')) {
            return true;
        }

        $maxQuota = $this->getUserFoderQuota();

        if (!$maxQuota) {
            $this->setState('error', BlogFactoryText::_('media_upload_error_user_folder_quota_reached'));
            return false;
        }

        $size = $this->getUserFolderSize();
        $fileSize = $fileSize / 1024 / 1024;

        if ($size + $fileSize > $maxQuota) {
            $this->setState('error', BlogFactoryText::_('media_upload_error_user_folder_quota_reached'));
            return false;
        }

        return true;
    }

    protected function getUserFolderSize()
    {
        jimport('joomla.filesystem.folder');
        $files = JFolder::files($this->getBaseFolder(), '.', true, true);
        $size = 0;

        foreach ($files as $file_temp) {
            $size += filesize($file_temp);
        }

        $size = $size / 1024 / 1024;

        return $size;
    }

    protected function getUserFoderQuota()
    {
        $settings = JComponentHelper::getParams('com_blogfactory');

        $groups = JAccess::getGroupsByUser(JFactory::getUser()->id);
        $array = (array)$settings->get('user_folder.quota');
        $quota = array();
        $maxQuota = 0;

        foreach ($array as $key => $value) {
            $quota[(int)$key] = $value;
        }

        foreach ($groups as $group) {
            if (!isset($quota[$group])) {
                continue;
            }

            $maxQuota = max($maxQuota, (int)$quota[$group]);
        }

        return $maxQuota;
    }
}
