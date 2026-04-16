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
jimport('joomla.filesystem.file');

class BlogFactoryBackendModelBackup extends JModelLegacy
{
    public function backup()
    {
        $folder = $this->createFolder();

        // Create backup manifest.
        $this->createBackupManifest($folder);

        // Create settings file.
        $this->createSettingsFile($folder);

        // Backup tables.
        foreach ($this->getTables() as $table) {
            $this->backupTable($table, $folder);
        }

        // Create bookmarks thumbnails archive.
        $this->createBookmarksArchive($folder);

        // Create archive.
        $archive = $this->createArchive($folder);

        // Send archive to user.
        $this->outputArchive($archive, $folder);

        // Remove temp folder.
        $this->removeTempFolder($folder);

        jexit();
    }

    public function restore($data)
    {
        $folder = $this->createFolder();

        $this->extractArchive($data, $folder);

        // Check if we're restoring on the same component version.
        $manifest = json_decode(file_get_contents($folder . '/backup.json'));
        $component = $this->getComponentManifest();
        if ($manifest->version != $component['version']) {
            $this->setState('error', BlogFactoryText::sprintf('backup_restore_error_different_version', $manifest->version));
            return false;
        }

        // Restore settings.
        $this->restoreSettingsFile($folder);

        // Restore tables.
        $this->restoreTables($folder);

        // Restore bookmarks thumbnails.
        $this->restoreBookmarksArchive($folder);

        // Remove temp folder.
        $this->removeTempFolder($folder);

        return true;
    }

    protected function createFolder()
    {
        $folder = JFactory::getApplication()->get('tmp_path') . '/com_blogfactory_' . uniqid();

        if (JFolder::exists($folder)) {
            JFolder::delete($folder);
        }

        JFolder::create($folder);

        return $folder;
    }

    protected function createBackupManifest($folder)
    {
        $manifest = $this->getComponentManifest();

        $array = array(
            'version' => $manifest['version'],
            'date' => JFactory::getDate()->toSql(),
        );

        return file_put_contents($folder . '/backup.json', json_encode($array));
    }

    protected function createSettingsFile($folder)
    {
        $settings = JComponentHelper::getParams('com_blogfactory');

        file_put_contents($folder . '/settings.json', $settings->toString());
    }

    protected function restoreSettingsFile($folder)
    {
        $settings = new JRegistry(file_get_contents($folder . '/settings.json'));
        $model = JModelLegacy::getInstance('Settings', 'BlogFactoryBackendModel');
        $model->save($settings->toArray());
    }

    protected function getComponentManifest()
    {
        return JInstaller::parseXMLInstallFile(JPATH_COMPONENT_ADMINISTRATOR . '/blogfactory.xml');
    }

    protected function getTables()
    {
        static $tables = null;

        if (is_null($tables)) {
            $path = JPATH_COMPONENT_ADMINISTRATOR . '/sqls/uninstall.mysql.sql';
            $contents = file_get_contents($path);

            preg_match_all('/\`(#__com_blogfactory_[a-z_]{1,})\`/', $contents, $matches);

            $tables = $matches[1];
        }

        array_unshift($tables, '#__categories');

        return $tables;
    }

    protected function backupTable($table, $folder)
    {
        $dbo = $this->getDbo();
        $tableName = $dbo->replacePrefix($table);
        $fileName = $folder . '/' . $table . '.sql';
        $output = array();

        $query = $dbo->getQuery(true)
            ->select('t.*')
            ->from($dbo->quoteName($tableName) . ' t');
        $results = $dbo->setQuery($query)
            ->loadAssocList();

        foreach ($results as $i => $result) {
            $results[$i] = array_values($result);

            $output[] = json_encode($result);
        }

        file_put_contents($fileName, implode("\r\n", $output));

        return true;
    }

    protected function restoreTables($folder)
    {
        foreach ($this->getTables() as $table) {
            switch ($table) {
                case '#__categories':
                    $this->restoreTableCategories($table, $folder);
                    break;

                case '#__com_blogfactory_posts':
                    $this->restoreTablePosts($table, $folder);
                    break;

                default:
                    $this->restoreTable($table, $folder);
                    break;
            }
        }
    }

    protected function restoreTable($table, $folder)
    {
        $dbo = $this->getDbo();
        $tableName = $dbo->replacePrefix($table);
        $fileName = $folder . '/' . $table . '.sql';

        // Clear the table.
        $this->clearTable($table);

        $contents = explode("\r\n", file_get_contents($fileName));

        foreach ($contents as $content) {
            $content = (array)json_decode($content);
            $values = array();

            foreach ($content as $key => $value) {
                if (is_null($value)) {
                    $values[] = $dbo->quote(0);
                } else {
                    $values[] = $dbo->quote($value);
                }
            }

            if (!$values) {
                continue;
            }

            $query = $dbo->getQuery(true)
                ->insert($dbo->quoteName($tableName))
                ->values(implode(',', $values));
            $dbo->setQuery($query)
                ->execute();
        }

        return true;
    }

    protected function restoreTableCategories($table, $folder)
    {
        // Initialise variables.
        $dbo = $this->getDbo();
        $fileName = $folder . '/' . $table . '.sql';
        $contents = explode("\r\n", file_get_contents($fileName));
        $array = array();
        $ids = array();

        // Get current Blog Factory categories.
        $query = $dbo->getQuery(true)
            ->select('c.*')
            ->from('#__categories c')
            ->where('c.extension = ' . $dbo->quote('com_blogfactory'));
        $results = $dbo->setQuery($query)
            ->loadAssocList();

        // Parse and remove current Blog Factory categories one by one.
        foreach ($results as $result) {
            $category = $this->getTable('Category', 'JTable');
            $category->bind($result);
            $category->delete($result['id']);
        }

        // Parse data from backup.
        foreach ($contents as $content) {
            $registry = new JRegistry($content);

            if ('com_blogfactory' != $registry->get('extension')) {
                continue;
            }

            $category = $this->getTable('Category', 'JTable');
            $category->bind($registry->toArray());

            $array[] = $category;
        }

        // Function to sort backup categories.
        function compare($a, $b)
        {
            if ($b->parent_id < $a->parent_id) {
                return 1;
            } elseif ($b->parent_id > $a->parent_id) {
                return -1;
            } elseif ($b->id < $a->id) {
                return 1;
            }

            return -1;
        }

        usort($array, 'compare');

        // Parse backup categories and restore them one by one.
        foreach ($array as $category) {
            $id = $category->id;

            if (1 != $category->parent_id) {
                $category->parent_id = $ids[$category->parent_id];
            }

            $category->id = null;
            $category->setLocation($category->parent_id, 'last-child');

            $category->store();

            $ids[$id] = $category->id;
        }

        $this->setState('restore.categories.map', $ids);

        return true;
    }

    protected function restoreTablePosts($table, $folder)
    {
        $map = $this->getState('restore.categories.map', array());

        $dbo = $this->getDbo();
        $tableName = $dbo->replacePrefix($table);
        $fileName = $folder . '/' . $table . '.sql';

        // Clear the table.
        $this->clearTable($table);

        $contents = explode("\r\n", file_get_contents($fileName));

        foreach ($contents as $content) {
            $content = (array)json_decode($content);
            $values = array();

            foreach ($content as $key => $value) {
                if ('category_id' == $key) {
                    $values[] = $dbo->quote($map[$value]);
                } elseif (is_null($value)) {
                    $values[] = $dbo->quote(0);
                } else {
                    $values[] = $dbo->quote($value);
                }
            }

            if (!$values) {
                continue;
            }

            $query = $dbo->getQuery(true)
                ->insert($dbo->quoteName($tableName))
                ->values(implode(',', $values));
            $dbo->setQuery($query)
                ->execute();
        }

        return true;
    }

    protected function clearTable($table)
    {
        return $this->getDbo()
            ->setQuery(' TRUNCATE TABLE ' . $table)
            ->execute();
    }

    protected function createArchive($folder)
    {
        $name = 'Blog_Factory_Backup_' . date('Y-m-d_H-i-s') . '.zip';
        $dest = $folder . '/' . $name;
        $filesToAdd = array();

        // Get the files for the archive
        $files = JFolder::files($folder);

        // Check if there are any files to archive
        if (!count($files)) {
            return false;
        }

        // Parse the files
        foreach ($files as $file) {
            $data = file_get_contents($folder . '/' . $file);
            $filesToAdd[] = array('name' => $file, 'data' => $data);
        }

        // Create the archive
        $zip = JArchive::getAdapter('zip');
        $zip->create($dest, $filesToAdd);

        return $name;
    }

    protected function outputArchive($archive, $folder)
    {
        $src = $folder . '/' . $archive;
        $filesize = filesize($src);

        // Send the archive
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control: must-revalidate,post-check=0,pre-check=0");
        header("Content-Type: application/zip");
        header('Content-Disposition: attachment; filename="' . $archive . '"');
        header("Content-Length: " . $filesize);
        header("Content-size: " . $filesize);
        header('Content-Transfer-Encoding: binary');

        @ob_end_clean();
        readfile($src);
        @ob_start(false);

        return true;
    }

    protected function removeTempFolder($folder)
    {
        if (JFolder::exists($folder)) {
            JFolder::delete($folder);
        }

        return true;
    }

    protected function createBookmarksArchive($folder)
    {
        $zip = JArchive::getAdapter('zip');
        $array = array();

        foreach (JFolder::files(JPATH_SITE . '/media/com_blogfactory/share') as $file) {
            $array[] = array('name' => $file, 'data' => file_get_contents(JPATH_SITE . '/media/com_blogfactory/share/' . $file));
        }

        $zip->create($folder . '/share.zip', $array);
    }

    protected function restoreBookmarksArchive($folder)
    {
        $share = JPATH_SITE . '/media/com_blogfactory/share';
        JFolder::delete($share);
        JFolder::create($share);

        $zip = JArchive::getAdapter('zip');
        $zip->extract($folder . '/share.zip', $share);
    }

    protected function extractArchive($data, $folder)
    {
        // Extract archive contents
        $zip = JArchive::getAdapter('zip');
        $zip->extract($data['restore']['tmp_name'], $folder);
    }
}
