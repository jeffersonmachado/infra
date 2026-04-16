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

class BlogFactoryBackendModelAbout extends JModelLegacy
{
    protected $option = null;
    protected $base = 'http://thephpfactory.com/versions/';
    protected $manifest = null;

    public function __construct($config = array())
    {
        if (is_null($this->option)) {
            $this->option = JFactory::getApplication()->input->getCmd('option');
        }
        $this->manifest = $this->getManifest();

        parent::__construct($config);
    }

    public function getAbout()
    {
        $information = new stdClass();

        $information->currentVersion = $this->getCurrentVersion();
        $information->latestVersion = $this->getLatestVersion();
        $information->newVersion = $this->getNewVersion($information->currentVersion, $information->latestVersion);
        $information->versionHistory = (string)$this->manifest->versionhistory;
        $information->downloadLink = (string)$this->manifest->downloadlink;
        $information->otherProducts = (string)$this->manifest->otherproducts;
        $information->aboutFactory = (string)$this->manifest->aboutfactory;

        return $information;
    }

    protected function getCurrentVersion()
    {
        jimport('joomla.filesystem.file');

        $file = JPATH_COMPONENT_ADMINISTRATOR . '/manifest.xml';

        if (!JFile::exists($file)) {
            $file = JPATH_COMPONENT_ADMINISTRATOR . '/' . str_replace('com_', '', $this->option) . '.xml';
        }

        $data = JInstaller::parseXMLInstallFile($file);

        return $data['version'];
    }

    protected function getLatestVersion()
    {
        return (string)$this->manifest->latestversion;
    }

    protected function getNewVersion($current, $latest)
    {
        return !in_array(version_compare($current, $latest), array(1, 0));
    }

    protected function getManifest()
    {
        if (is_null($this->manifest)) {
            $contents = $this->fileGetContents();

            if (false === $contents) {
                throw new Exception('There was an error retrieving the information from the server. Please try again later!', 500);
            }

            $this->manifest = simplexml_load_string($contents);
        }

        return $this->manifest;
    }

    protected function fileGetContents()
    {
        static $contents = null;

        if (is_null($contents)) {
            $filename = $this->base . $this->option . '.xml';

            if (function_exists('curl_init')) {
                $contents = $this->getContentsCurl($filename);
            } elseif (ini_get('allow_url_fopen')) {
                $contents = $this->getContentsRead($filename);
            } else {
                $contents = false;
            }
        }

        return $contents;
    }

    protected function getContentsCurl($filename)
    {
        $handle = curl_init();

        curl_setopt($handle, CURLOPT_URL, $filename);
        curl_setopt($handle, CURLOPT_MAXREDIRS, 5);
        curl_setopt($handle, CURLOPT_AUTOREFERER, 1);
        curl_setopt($handle, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($handle, CURLOPT_TIMEOUT, 10);

        $buffer = curl_exec($handle);
        curl_close($handle);

        return $buffer;
    }

    protected function getContentsRead($filename)
    {
        $fp = fopen($filename, 'r');

        if (!$fp) {
            return false;
        }

        stream_set_timeout($fp, 20);
        $linea = '';
        while ($remote_read = fread($fp, 4096)) {
            $linea .= $remote_read;
        }

        $info = stream_get_meta_data($fp);
        fclose($fp);

        if ($info['timed_out']) {
            return false;
        }

        return $linea;
    }
}
