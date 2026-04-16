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

class JHtmlBlogFactoryBlog
{
    public static function getPhotoSource($photo, $size = 64)
    {
        jimport('joomla.filesystem.file');

        $name = JFile::stripExt($photo);
        $ext = JFile::getExt($photo);
        $filename = $name . '_' . $size . '.' . $ext;

        $src = JPATH_SITE . '/media/com_blogfactory/blogs/' . $filename;

        if (!JFile::exists($src)) {
            return JUri::root() . 'components/com_blogfactory/assets/images/camera.png';
        }

        return JUri::root() . 'media/com_blogfactory/blogs/' . $filename;
    }

    public static function commentAuthor($item)
    {
        if ('' != $item->name) {
            return $item->name;
        }

        if ('' != $item->profile_name) {
            return $item->profile_name;
        }

        return $item->username;
    }

    public static function commentUrl($item, $link = false)
    {
        if ('' != $item->url) {
            $url = $item->url;
        } elseif ('' != $item->profile_url) {
            $url = $item->profile_url;
        } else {
            $url = null;
        }

        if (is_null($url)) {
            return '';
        }

        if (!$link) {
            return $url;
        }

        $html = array();

        $html[] = '<a href="' . $url . '" target="_blank">';
        $html[] = JHtml::_('string.abridge', str_replace(array('http://', 'https://', 'www.'), '', $url), 20, 0);
        $html[] = '</a>';

        return implode("\n", $html);
    }

    public static function commentEmail($item)
    {
        if ('' != $item->email) {
            $email = $item->email;
        } else {
            $email = $item->user_email;
        }

        $html = array();

        $html[] = '<a href="mailto:' . $email . '">';
        $html[] = JHtml::_('string.abridge', $email, 20, 10);
        $html[] = '</a>';

        return implode("\n", $html);
    }

    public static function commentAuthorLink($item)
    {
        $name = self::commentAuthor($item);
        $url = self::commentUrl($item);

        if (is_null($url)) {
            return $name;
        }

        return '<a href="' . $url . '">' . $name . '</a>';
    }
}
