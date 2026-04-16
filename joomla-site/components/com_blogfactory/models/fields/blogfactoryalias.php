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

class JFormFieldBlogFactoryAlias extends JFormField
{
    public $type = 'BlogFactoryAlias';

    protected function getInput()
    {
        BlogFactoryHtml::script('fields/alias');
        BlogFactoryHtml::stylesheet('fields/alias');

        $observe = $this->form->getField((string)$this->element['observe'])->id;
        $url = BlogFactoryRoute::task('helper.alias&format=raw');

        $html = array();

        $html[] = '<div class="blogfactory-field-alias" data-observe="' . $observe . '" data-url="' . $url . '">';

        $html[] = '<span class="muted">' . $this->element['label'] . ':</span>';

        $html[] = '<span class="view">';
        $html[] = '<span class="current">' . $this->value . '</span>';
        $html[] = '<a href="#" class="btn btn-mini button-edit">' . BlogFactoryText::_('field_alias_button_edit') . '</a>';
        $html[] = '</span>';

        $html[] = '<span class="edit">';
        $html[] = '<input type="text" style="margin: 0;" />';
        $html[] = '<a href="#" class="btn btn-mini button-save">' . BlogFactoryText::_('field_alias_button_save') . '</a>';
        $html[] = '<a href="#" class="btn btn-mini btn-link button-cancel">' . BlogFactoryText::_('field_alias_button_cancel') . '</a>';
        $html[] = '</span>';

        $html[] = '<input type="hidden" value="' . $this->value . '" name="' . $this->name . '" id="' . $this->id . '" />';

        $html[] = '</div>';

        return implode("\n", $html);
    }
}
