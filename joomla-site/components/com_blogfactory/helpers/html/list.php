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

class JHtmlBlogFactoryList
{
    public static function heading($title, $sort, $currentSort, $currentOrder)
    {
        $html = array();
        $uri = JUri::getInstance();
        $order = $sort == $currentSort ? ('asc' == $currentOrder ? 'desc' : 'asc') : $currentOrder;

        $uri->setVar('sort', $sort);
        $uri->setVar('order', $order);

        $url = $uri->toString();

        $html[] = '<a class="list-heading" href="' . $url . '">' . BlogFactoryText::_($title);

        if ($sort == $currentSort) {
            $html[] = '<span class="factory-sort-' . $currentOrder . ' current"></span>';
        }

        $html[] = '<span class="factory-sort-' . $order . ' next" style="display: none;"></span>';

        $html[] = '</a>';

        return implode('', $html);
    }

    public static function filter($name, $currentValue, $label, $value = null, $totals = array())
    {
        $uri = JUri::getInstance();
        $html = array();

        $isSet = $uri->getVar($name);

        if (is_null($value)) {
            $uri->delVar($name);
        } else {
            $uri->setVar($name, $value);
        }

        $uri->delVar('start');
        $uri->delVar('limitstart');

        $url = $uri->toString();
        $class = $currentValue == $value ? 'current' : '';

        $html[] = '<a href="' . $url . '" class="' . $class . '">';
        $html[] = BlogFactoryText::_($label);
        $html[] = '</a>';

        if (is_null($value)) {
            $value = 'all';
        }

        if (isset($totals[$value])) {
            $html[] = '<span class="muted" style="margin: 0 0 0 5px; font-weight: normal;">(' . intval($totals[$value]) . ')</span>';
        }

        $uri->setVar($name, $isSet);

        return implode('', $html);
    }
}
