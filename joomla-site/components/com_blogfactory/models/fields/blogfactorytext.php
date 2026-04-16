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

JFormHelper::loadFieldType('Text');

class JFormFieldBlogFactoryText extends JFormFieldText
{
    public $type = 'BlogFactoryText';

    protected function getInput()
    {
        // Initialize some field attributes.
        $size = $this->element['size'] ? ' size="' . (int)$this->element['size'] . '"' : '';
        $maxLength = $this->element['maxlength'] ? ' maxlength="' . (int)$this->element['maxlength'] . '"' : '';
        $class = $this->element['class'] ? ' class="' . (string)$this->element['class'] . '"' : '';
        $readonly = ((string)$this->element['readonly'] == 'true') ? ' readonly="readonly"' : '';
        $disabled = ((string)$this->element['disabled'] == 'true') ? ' disabled="disabled"' : '';
        $required = $this->required ? ' required="required" aria-required="true"' : '';
        $placeholder = (string)$this->element['placeholder'] ? BlogFactoryText::_((string)$this->element['placeholder']) : '';

        // Initialize JavaScript field attributes.
        $onchange = $this->element['onchange'] ? ' onchange="' . (string)$this->element['onchange'] . '"' : '';

        return '<input type="text" placeholder="' . $placeholder . '" name="' . $this->name . '" id="' . $this->id . '" value="'
        . htmlspecialchars($this->value, ENT_COMPAT, 'UTF-8') . '"' . $class . $size . $disabled . $readonly . $onchange . $maxLength . $required . '/>';
    }
}
