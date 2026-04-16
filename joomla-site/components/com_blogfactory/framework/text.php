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

class BlogFactoryText
{
    public static function _($string)
    {
        $string = strtoupper(self::getOption() . '_' . str_replace(' ', '_', $string));

        return JText::_($string);
    }

    public static function sprintf()
    {
        $args = func_get_args();
        $args[0] = strtoupper(self::getOption() . '_' . $args[0]);

        return call_user_func_array(array('JText', 'sprintf'), $args);
    }

    public static function script($string = null)
    {
        $string = strtoupper(self::getOption() . '_' . $string);

        return JText::script($string);
    }

    public static function plural($string, $n)
    {
        $args = func_get_args();
        $args[0] = strtoupper(self::getOption() . '_' . $args[0]);

        return call_user_func_array(array('JText', 'plural'), $args);
    }

    protected static function getOption()
    {
        return 'com_blogfactory';
    }
}
