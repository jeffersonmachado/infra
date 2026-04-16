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

class BlogFactoryFrontendModelHelper extends JModelLegacy
{
    public function parseDate($date)
    {
        // Check if all values are set.
        $keys = array('year', 'month', 'day', 'hour', 'minutes');
        foreach ($keys as $key) {
            if (!isset($date[$key]) || '' == $date[$key]) {
                return false;
            }
        }

        // Check if date is valid.
        if (!checkdate($date['month'], $date['day'], $date['year'])) {
            return false;
        }

        // Check if hour is valid.
        if ($date['hour'] < 0 || $date['hour'] > 24) {
            return false;
        }

        // Check if minutes is valid.
        if ($date['minutes'] < 0 || $date['minutes'] > 59) {
            return false;
        }

        $value = $date['year'] . '-' . $date['month'] . '-' . $date['day'] . ' ' . $date['hour'] . ':' . $date['minutes'];
        //$offset = JFactory::getUser()->getParam('timezone', JFactory::getConfig()->get('offset'));

        $this->setState('date.value', $value);
        $this->setState('date.label', JFactory::getDate($value)->format('l, d F Y @ H:i'));

        return true;
    }

    public function getAlias($query)
    {
        JLoader::register('BlogFactoryHelper', JPATH_COMPONENT_ADMINISTRATOR . '/helpers/blogfactory.php');

        $alias = JApplicationHelper::stringURLSafe($query);

        $settings = JComponentHelper::getParams('com_blogfactory');
        if ($settings->get('general.enable.router_plugin', 0)) {
            $alias = BlogFactoryHelper::checkUniqueAlias($alias);
        }

        return $alias;
    }
}
