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

class JHtmlBlogFactoryManagePosts
{
    public static function date($item)
    {
        $html = array();

        if ($item->published) {
            if ($item->publish_up == JFactory::getDbo()->getNullDate()) {
                $item->publish_up = $item->created_at;
            }

            if ($item->publish_up > JFactory::getDate()->toSql()) {
                $absolute = $relative = JHtml::_('date', $item->publish_up, 'DATE_FORMAT_LC2');
                $label = BlogFactoryText::_('manageposts_list_date_label_scheduled');
            } else {
                $absolute = JHtml::_('date', $item->publish_up, 'DATE_FORMAT_LC2');
                $relative = JHtml::_('date.relative', $item->publish_up);
                $label = BlogFactoryText::_('manageposts_list_date_label_published');
            }
        } else {
            $absolute = JHtml::_('date', $item->updated_at, 'DATE_FORMAT_LC2');
            $relative = JHtml::_('date.relative', $item->updated_at);
            $label = BlogFactoryText::_('manageposts_list_date_label_modified');
        }

        $html[] = '<abbr title="' . $absolute . '">' . $relative . '</abbr>';
        $html[] = '<div>' . $label . '</div>';

        return implode("\n", $html);
    }
}
