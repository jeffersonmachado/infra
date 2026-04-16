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

class FactoryHelper
{
    public static function beginForm($url, $method = 'GET', $config = array())
    {
        $app = JFactory::getApplication();
        $html = array();
        $class = isset($config['class']) ? $config['class'] : '';

        // Add the start form tag.
        $html[] = '<form action="' . $url . '" method="' . $method . '" class="' . $class . '">';

        // Check if SEF it's not enabled.
        if (!$app->get('sef', 0)) {
            $url = parse_url($url);
            parse_str($url['query'], $output);

            foreach ($output as $key => $value) {
                $html[] = '<input type="hidden" name="' . $key . '" value="' . $value . '">';
            }
        }

        FactoryHtml::script('form');

        return implode("\n", $html);
    }
}
