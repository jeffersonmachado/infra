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

JFormHelper::loadFieldType('Checkbox');

class JFormFieldBlogFactoryCheckbox extends JFormFieldCheckbox
{
    public $type = 'BlogFactoryCheckbox';

    protected function getInput()
    {
        $html = array();

        $html[] = parent::getInput();
        $html[] = '<label for="' . $this->id . '">';
        $html[] = (string)$this->element['label'];
        $html[] = '</label>';

        return implode('', $html);
    }
}
