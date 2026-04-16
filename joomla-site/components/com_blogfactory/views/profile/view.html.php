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

class BlogFactoryFrontendViewProfile extends BlogFactoryFrontendView
{
    protected
        $variables = array('form', 'item', 'enabledAvatars'),
        $jhtmls = array('behavior.tooltip', 'formbehavior.chosen/select');

    protected function getEnabledAvatars()
    {
        $settings = JComponentHelper::getParams('com_blogfactory');

        return $settings->get('avatars.enable.avatars', 1);
    }
}
