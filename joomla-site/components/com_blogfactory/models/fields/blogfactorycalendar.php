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

class JFormFieldBlogFactoryCalendar extends JFormField
{
    public $type = 'BlogFactoryCalendar';

    protected function getInput()
    {
        BlogFactoryHtml::script('fields/calendar');
        BlogFactoryHtml::stylesheet('fields/calendar');

        $dbo = JFactory::getDbo();
        $date = JFactory::getDate();

        if ('' == $this->value) {
            $this->value = $dbo->getNullDate();
        }

        $value = $this->value;

        $html = array();

        $html[] = '<div class="blogfactory-calendar">';

        $html[] = '<div class="text">';
        $html[] = '<span>';

        if (in_array($this->element['extra'], array('immediately', 'never')) && $dbo->getNullDate() == $this->value) {
            $html[] = BlogFactoryText::_('field_calendar_label_' . $this->element['extra']);
            $this->value = $date;
        } else {
            $html[] = JHtml::_('date', $this->value, 'l, d F Y @ H:i');
        }

        $html[] = '</span>';
        $html[] = '<a href="#" class="btn btn-mini blogfactory-calendar-edit">' . BlogFactoryText::_('field_calendar_edit') . '</a>';
        $html[] = '</div>';

        $html[] = '<div class="edit" style="display: none;">';

        if (in_array($this->element['extra'], array('immediately', 'never'))) {
            $html[] = '<a href="#" class="btn btn-small blogfactory-calendar-extra">' . BlogFactoryText::_('field_calendar_label_' . $this->element['extra']) . '</a>';
            $html[] = '&nbsp;' . BlogFactoryText::_('field_calendar_or') . '&nbsp;';
        }

        $html[] = $this->getMonth();
        $html[] = $this->getDay();
        $html[] = ', ';
        $html[] = $this->getYear();
        $html[] = ' <span style="vertical-align: middle;">@</span> ';
        $html[] = $this->getHour();
        $html[] = ' : ';
        $html[] = $this->getMinutes();

        $html[] = '<a href="#" class="btn btn-mini blogfactory-calendar-save">' . BlogFactoryText::_('field_calendar_save') . '</a>';
        $html[] = '<a href="#" class="btn btn-mini btn-link blogfactory-calendar-cancel">' . BlogFactoryText::_('field_calendar_cancel') . '</a>';

        $html[] = '</div>';

        $html[] = '<input type="hidden" name="' . $this->name . '" id="' . $this->id . '" value="' . JHtml::_('date', $value, 'Y-m-d H:i:s') . '" />';

        $html[] = '</div>';

        return implode("\n", $html);
    }

    protected function getMonth()
    {
        $options = array();
        $selected = JHtml::date($this->value, 'n');
        $date = JFactory::getDate();

        for ($i = 1; $i < 13; $i++) {
            $options[$i] = $date->monthToString($i, true);
        }

        return JHtml::_('select.genericlist', $options, 'date[month]', 'class="blogfactory-calendar-month"', '', '', $selected, $this->id . '_month');
    }

    protected function getDay()
    {
        $value = JHtml::date($this->value, 'd');

        return '<input type="text" name="date[day]" class="blogfactory-calendar-day" value="' . $value . '" />';
    }

    protected function getYear()
    {
        $value = JHtml::date($this->value, 'Y');

        return '<input type="text" name="date[year]" class="blogfactory-calendar-year" value="' . $value . '" />';
    }

    protected function getHour()
    {
        $value = JHtml::date($this->value, 'H');

        return '<input type="text" name="date[hour]" class="blogfactory-calendar-hour" value="' . $value . '" />';
    }

    protected function getMinutes()
    {
        $value = JHtml::date($this->value, 'i');

        return '<input type="text" name="date[minutes]" class="blogfactory-calendar-minutes" value="' . $value . '" />';
    }
}
