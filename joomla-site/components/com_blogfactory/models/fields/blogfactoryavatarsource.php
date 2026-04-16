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

JFormHelper::loadFieldType('List');

class JFormFieldBlogFactoryAvatarSource extends JFormFieldList
{
    public $type = 'BlogFactoryAvatarSource';

    protected function getOptions()
    {
        $options = parent::getOptions();
        $settings = JComponentHelper::getParams('com_blogfactory');

        $gravatar = $settings->get('avatars.enable.gravatars', 1);
        $cb = $settings->get('avatars.enable.cb', 0);

        foreach ($options as $i => $option) {
            if ('gravatar' == $option->value && !$gravatar) {
                unset($options[$i]);
            }

            if ('cb' == $option->value && !$cb) {
                unset($options[$i]);
            }
        }

        return $options;
    }
}
