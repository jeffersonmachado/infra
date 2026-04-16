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

class BlogFactoryViewHelp
{
    protected $component;
    protected $override = 'http://wiki.thephpfactory.com/doku.php?id=joomla{major}0:{component}:{keyref}';
    protected $xpath = '//div[@class="dokuwiki"]/div[@class="page"]/div/ul/li/div/a';
    protected $cache = 24;

    public function __construct(array $config = array())
    {
        if (isset($config['component'])) {
            $this->component = $config['component'];
        } else {
            $input = new JInput();
            $this->component = str_replace('com_', '', $input->getString('option'));
        }

        if (isset($config['override'])) {
            $this->override = $config['override'];
        }

        if (isset($config['xpath'])) {
            $this->xpath = $config['xpath'];
        }

        if (isset($config['cache'])) {
            $this->cache = $config['cache'];
        }
    }

    public function render($ref)
    {
        $pages = $this->getAvailablePages();

        if (!$pages || !in_array($ref, $pages)) {
            $ref = $this->component;
        }

        JToolbarHelper::help($ref, false, $this->override, $this->component);
    }

    protected function readUrl($url)
    {
        jimport('joomla.filesystem.folder');
        jimport('joomla.filesystem.file');

        $hash = md5($url);
        $path = JPATH_ADMINISTRATOR . '/cache/com_' . $this->component;

        if (!JFolder::exists($path)) {
            JFolder::create($path);
        }

        if (!JFile::exists($path . '/' . $hash) || time() - 60 * 60 * $this->cache > filemtime($path . '/' . $hash)) {
            $data = $this->getUrl($url);

            file_put_contents($path . '/' . $hash, $data);
        } else {
            $data = file_get_contents($path . '/' . $hash);
        }

        return $data;
    }

    protected function parseHtml($html)
    {
        $pages = array();

        if ($html == strip_tags($html)) {
            return $pages;
        }

        $doc = new DOMDocument();
        libxml_use_internal_errors(true);
        $doc->loadHTML($html);
        libxml_use_internal_errors(false);

        $xpath = new DOMXpath($doc);
        $items = $xpath->query($this->xpath);

        foreach ($items as $item) {
            /** @var DOMElement $item */
            $href = $item->getAttribute('href');
            $explode = explode(':', $href);
            $page = end($explode);

            if (false !== strpos($page, '#')) {
                list($page, $anchor) = explode('#', $page);
            }

            $pages[] = $page;
        }

        return $pages;
    }

    protected function getAvailablePages()
    {
        $url = JHelp::createURL($this->component, false, $this->override, $this->component);
        $html = $this->readUrl($url);

        return $this->parseHtml($html);
    }

    protected function getUrl($url)
    {
        $data = $this->getUrlCurl($url);

        if (false !== $data) {
            return $data;
        }

        $data = $this->getUrlFileOpen($url);

        if (false !== $data) {
            return $data;
        }

        $data = $this->getUrlFSockOpen($url);

        return $data;
    }

    protected function getUrlCurl($url)
    {
        if (!function_exists('curl_init')) {
            return false;
        }

        $ch = curl_init();

        curl_setopt_array($ch, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_CONNECTTIMEOUT => 5,
        ));

        $data = curl_exec($ch);

        curl_close($ch);

        return $data;
    }

    protected function getUrlFileOpen($url)
    {
        if (!ini_get('allow_url_fopen')) {
            return false;
        }

        return file_get_contents($url);
    }

    protected function getUrlFSockOpen($url)
    {
        $uri = JUri::getInstance($url);
        $fp = fsockopen($uri->getHost(), 80, $errno, $errstr, 30);

        if (!$fp) {
            return false;
        }

        $data = array();
        $out = array(
            'GET ' . $uri->getPath() . $uri->getQuery() . ' HTTP/1.1' . "\r\n",
            'Host: ' . $uri->getHost() . "\r\n",
            'Connection: Close' . "\r\n\r\n",
        );

        fwrite($fp, implode($out));

        while (!feof($fp)) {
            $data[] = fgets($fp, 128);
        }

        fclose($fp);

        return implode($data);
    }
}
