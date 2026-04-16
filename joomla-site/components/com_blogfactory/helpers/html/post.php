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

class JHtmlBlogFactoryPost
{
    public static function tags($tags)
    {
        $array = array();

        foreach ($tags as $tag) {
            $array[] = '<a href="' . BlogFactoryRoute::view('posts&tag=' . $tag->alias) . '"><span>' . $tag->name . '</span></a>';
        }

        return implode(', ', $array);
    }

    public static function bookmark($bookmark, $options = array())
    {
        if (!isset($options['url'])) {
            $options['url'] = JUri::current();
        }

        if (!isset($options['title'])) {
            $options['title'] = JFactory::getDocument()->getTitle();
        }

        foreach ($options as $option => $value) {
            $options[$option] = urlencode($options[$option]);
        }

        $search = array();
        $replace = array();

        foreach ($options as $option => $value) {
            $search[] = '%%' . $option . '%%';
            $replace[] = $value;
        }

        $link = str_replace($search, $replace, $bookmark->link);
        $src = JUri::root() . 'media/com_blogfactory/share/' . $bookmark->thumbnail;

        $html = '<a href="' . $link . '" target="_blank"><img src="' . $src . '" /></a>';

        return $html;
    }
}
