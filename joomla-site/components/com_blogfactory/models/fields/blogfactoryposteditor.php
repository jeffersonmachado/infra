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

JFormHelper::loadFieldType('Textarea');

class JFormFieldBlogFactoryPostEditor extends JFormFieldTextarea
{
    public $type = 'BlogFactoryPostEditor';

    protected function getInput()
    {
        BlogFactoryHtml::script('tinymce/tinymce.min');
        BlogFactoryHtml::script('tinymce/jquery.tinymce.min');
        BlogFactoryHtml::script('plugins/blogfactoryimage/plugin.min');
        BlogFactoryHtml::script('plugins/readmore/plugin.min');

        BlogFactoryHtml::script('fields/posteditor');
        BlogFactoryHtml::stylesheet('fields/posteditor');

        $document = JFactory::getDocument();
        $document->addScriptDeclaration('var blogfactory_editor_css = "' . JUri::root() . 'components/com_blogfactory/assets/css/tinymce.css";');

        $html = array();

        $html[] = parent::getInput();

        return implode("\n", $html);
    }
}
