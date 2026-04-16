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

class JHtmlBlogFactoryIntegration
{
    public static function item($type, $item, $options = array())
    {
        $html = array();
        $data = isset($options['data']) ? $options['data'] : self::getData($type, $item);

        if (!$data) {
            return false;
        }

        $data = htmlentities($data, ENT_COMPAT, 'UTF-8');
        $label = isset($options['label']) ? $options['label'] : BlogFactoryText::_('integrations_item_text_' . $type);

        $html[] = '<a href="#" class="btn btn-mini data-item" data-item="' . $data . '">';
        $html[] = $label;
        $html[] = '</a>';

        return implode("\n", $html);
    }

    protected function getData($type, $item)
    {
        $data = false;

        if (!isset($item->$type)) {
            return $data;
        }

        switch ($type) {
            case 'thumbnails':
                $array = array();

                foreach ($item->$type as $thumbnail => $image) {
                    $array[] = '<a href="' . $image . '"><img src="' . $thumbnail . '" /></a>';
                }

                $data = implode("\n", $array);
                break;

            case 'link':
                $data = '<a href="' . $item->link . '">' . $item->title . '</a>';
                break;

            case 'photos':
                if (!$item->photos) {
                    return $data;
                }

                $data = '<img src="' . implode('" /><img src="', $item->photos) . '" />';
                break;

            default:
                $data = $item->$type;
                break;

        }

        return $data;
    }
}
