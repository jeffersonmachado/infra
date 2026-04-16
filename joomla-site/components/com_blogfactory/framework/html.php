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

class BlogFactoryHtml
{
    protected static $stylesheets = array();
    protected static $scripts = array();
    protected static $compress = null;

    public static function script($file)
    {
        if (!self::getCompress() ||
            in_array($file, array('tinymce/tinymce.min', 'tinymce/jquery.tinymce.min', 'plugins/blogfactoryimage/plugin.min', 'plugins/readmore/plugin.min'))
        ) {
            $file = self::parsePath($file, 'js');
            return JHtml::script($file);
        }

        if (in_array($file, self::$scripts)) {
            return true;
        }

        self::$scripts[] = $file;

        return true;
    }

    public static function stylesheet($file)
    {
        if (!self::getCompress()) {
            $file = self::parsePath($file, 'css');
            return JHtml::stylesheet($file);
        }

        if (in_array($file, self::$stylesheets)) {
            return true;
        }

        self::$stylesheets[] = $file;

        return true;
    }

    public static function scripts($scripts)
    {
        foreach ($scripts as $script) {
            self::stylesheet($script);
        }

        return true;
    }

    public static function stylesheets($stylesheets)
    {
        foreach ($stylesheets as $stylesheet) {
            self::stylesheet($stylesheet);
        }

        return true;
    }

    public static function renderAssets()
    {
        self::renderStylesheets();
        self::renderScripts();

        return true;
    }

    protected static function renderStylesheets()
    {
        return self::compress(self::$stylesheets, 'css');
    }

    protected static function renderScripts()
    {
        return self::compress(self::$scripts, 'js');
    }

    protected static function parsePath($file, $type = 'js')
    {
        $path = array();
        $parts = explode('/', $file);

        $path[] = 'components';
        $path[] = self::getOption();

        if (isset($parts[0]) && 'admin' == $parts[0]) {
            array_unshift($path, 'administrator');
            unset($parts[0]);
            $parts = array_values($parts);
        }

        $path[] = 'assets';
        $path[] = $type;

        $count = count($parts);
        foreach ($parts as $i => $part) {
            if ($i + 1 == $count) {
                $path[] = $part . '.' . $type;
            } else {
                $path[] = $part;
            }
        }

        return implode('/', $path);
    }

    protected static function compress($files, $type = 'js')
    {
        if (!$files) {
            return true;
        }

        jimport('joomla.filesystem.file');

        // Initialise variables.
        $view = JFactory::getApplication()->input->getCmd('view', 'default');
        $filename = $view . '_' . md5(implode('.', $files));
        $output = JPATH_SITE . '/' . self::parsePath('cached/' . $filename, $type);
        $rebuild = false;

        // Check if output file exists.
        if (!JFile::exists($output)) {
            $content = '';
            JFile::write($output, $content);
            $rebuild = true;
        } else {
            $modified = filemtime($output);
        }

        // Check if a rebuild is required depending on files last modified dates.
        if (!$rebuild) {
            foreach ($files as $file) {
                $path = JPATH_SITE . '/' . self::parsePath($file, $type);

                if (!JFile::exists($path)) {
                    continue;
                }

                if (filemtime($path) > $modified) {
                    $rebuild = true;
                    break;
                }
            }
        }

        if (JDEBUG) {
            $rebuild = true;
        }

        // Rebuild output file.
        if ($rebuild) {
            $contents = array();

            foreach ($files as $file) {
                $path = JPATH_SITE . '/' . self::parsePath($file, $type);

                if (!JFile::exists($path)) {
                    continue;
                }

                $filepath = self::parsePath($file, $type);
                $header = 'js' == $type ? '// ' . $filepath : '/* ' . $filepath . ' */';

                $contents[] = $header . "\n" . file_get_contents($path);
            }

            if ('js' == $type) {
                $contents = implode(';' . "\n\n", $contents);
            } else {
                $contents = str_replace('../', '../../', $contents);
                $contents = implode("\n", $contents);
            }

            JFile::write($output, $contents);
        }

        // Output asset.
        $file = self::parsePath('cached/' . $filename, $type);

        if ('js' == $type) {
            JHtml::script($file);
        } else {
            JHtml::stylesheet($file);
        }

        return true;
    }

    protected static function getOption()
    {
        return 'com_blogfactory';
    }

    protected static function getCompress()
    {
        // Do not compress on the backend.
        if (JFactory::getApplication()->isAdmin()) {
            self::$compress = false;
        }

        if (is_null(self::$compress)) {
            // Check if system plugin is enabled.
            $extension = JTable::getInstance('Extension');
            $result = $extension->load(array(
                'type' => 'plugin',
                'element' => 'blogfactory',
                'folder' => 'system',
                'enabled' => 1,
            ));

            if ($result && $extension->extension_id) {
                $params = new \Joomla\Registry\Registry($extension->params);
                self::$compress = (boolean)$params->get('cache_assets', 1);
            } else {
                self::$compress = false;
            }
        }

        return self::$compress;
    }
}
