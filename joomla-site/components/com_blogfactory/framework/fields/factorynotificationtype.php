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

class JFormFieldFactoryNotificationType extends JFormFieldList
{
    protected $type = 'FactoryNotificationType';

    protected function getInput()
    {
        $this->element['onchange'] = 'Joomla.submitbutton(\'notification.update\')';

        return parent::getInput();
    }

    protected function getOptions()
    {
        $options = BlogFactoryHelper::getNotificationTypes();

        array_unshift($options, array('value' => '', 'text' => ''));

        return $options;
    }
}
