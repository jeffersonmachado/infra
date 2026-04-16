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

class ModBlogFactoryCalendarHelper
{
    public static function renderCalendar($date, $posts, $prev, $next, $params, $id)
    {
        $monthDays = $date->format('t');

        $firstDayDate = JFactory::getDate($date->format('Y-m-01'));
        $firstDay = $firstDayDate->format('w');

        $nav_buttons_format = $params->get('nav_buttons_format', 'M Y');
        $week_start = $params->get('week_start', 0);

        $html = array();

        $html[] = '<table class="table table-condensed table-striped">';

        $html[] = '<caption>';
        $html[] = $date->format('F Y');
        $html[] = '</caption>';

        $html[] = '<thead>';
        $html[] = '<tr>';
        for ($i = $week_start; $i < 7 + $week_start; $i++) {
            $value = 7 == $i ? 0 : $i;
            $html[] = '<th>' . substr($date->dayToString($value, true), 0, 1) . '</th>';
        }
        $html[] = '</tr>';
        $html[] = '</thead>';

        if ($prev || $next) {
            $html[] = '<tfoot>';
            $html[] = '<tr>';

            $html[] = '<td colspan="3" style="text-align: left;">';
            if ($prev) {
                $uri = JUri::getInstance();
                $uri->setVar($id, $prev->format('Y-m'));

                $html[] = '<a href="' . $uri . '">&laquo;&nbsp;' . $prev->format($nav_buttons_format) . '</a>';
            }
            $html[] = '</td>';

            $html[] = '<td></td>';

            $html[] = '<td colspan="3">';
            if ($next) {
                $uri = JUri::getInstance();
                $uri->setVar($id, $next->format('Y-m'));

                $html[] = '<a href="' . $uri . '">' . $next->format($nav_buttons_format) . '&nbsp;&raquo;</a>';
            }

            $html[] = '</td>';

            $html[] = '</tr>';
            $html[] = '</tfoot>';
        }

        $html[] = '<tbody>';
        $html[] = '<tr>';

        $firstDay -= $week_start;

        for ($i = 0; $i < $firstDay; $i++) {
            $html[] = '<td></td>';
        }

        $daysGone = $firstDay;

        for ($i = 1; $i <= $monthDays; $i++) {
            if (7 == $daysGone) {
                $html[] = '</tr><tr>';
                $daysGone = 0;
            }

            if (in_array($i, $posts)) {
                $temp = JFactory::getDate($date->format('Y-m') . '-' . $i)->format('Y-m-d');
                $html[] = '<td><a href="' . JRoute::_('index.php?option=com_blogfactory&view=posts&date=' . $temp) . '">' . $i . '</a></td>';
            } else {
                $html[] = '<td class="muted">' . $i . '</td>';
            }

            $daysGone++;
        }

        for ($i = $daysGone; $i <= 6; $i++) {
            $html[] = '<td></td>';
        }

        $html[] = '</tr>';
        $html[] = '</tbody>';

        $html[] = '</table>';

        return implode("\n", $html);
    }

    public static function getPostsForDate($date)
    {
        $prev = JFactory::getDate($date->format('Y-m-1'));
        $next = JFactory::getDate($date->format('Y-m-t 23:59:59'));
        $dbo = JFactory::getDbo();
        $array = array();

        $query = $dbo->getQuery(true)
            ->select('p.created_at')
            ->from('#__com_blogfactory_posts p')
            ->where('p.published = ' . $dbo->quote(1))
            ->where('p.created_at < ' . $dbo->quote($next->toSql()))
            ->where('p.created_at > ' . $dbo->quote($prev->toSql()));
        $results = $dbo->setQuery($query)
            ->loadColumn();

        foreach ($results as $result) {
            $date = JFactory::getDate($result)->format('d');

            if (!in_array($date, $array)) {
                $array[] = $date;
            }
        }

        return $array;
    }

    public static function hasPrev($date)
    {
        $prev = JFactory::getDate($date->format('Y-m-1'));
        $dbo = JFactory::getDbo();

        $query = $dbo->getQuery(true)
            ->select('p.created_at')
            ->from('#__com_blogfactory_posts p')
            ->where('p.published = ' . $dbo->quote(1))
            ->where('p.created_at < ' . $dbo->quote($prev->toSql()))
            ->order('p.created_at DESC');
        $result = $dbo->setQuery($query)
            ->loadResult();

        if ($result) {
            return JFactory::getDate($result);
        }

        return false;
    }

    public static function hasNext($date)
    {
        $next = JFactory::getDate($date->format('Y-m-t 23:59:59'));
        $dbo = JFactory::getDbo();

        $query = $dbo->getQuery(true)
            ->select('p.created_at')
            ->from('#__com_blogfactory_posts p')
            ->where('p.published = ' . $dbo->quote(1))
            ->where('p.created_at > ' . $dbo->quote($next->toSql()))
            ->order('p.created_at ASC');
        $result = $dbo->setQuery($query)
            ->loadResult();

        if ($result) {
            return JFactory::getDate($result);
        }

        return false;
    }

    public static function getDate($date)
    {
        try {
            $date = JFactory::getDate($date);
        } catch (Exception $e) {
            $date = JFactory::getDate();
        }

        return $date;
    }

    public static function getId($module)
    {
        return $module->module . '_' . $module->id;
    }
}
