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

JFormHelper::loadFieldType('Editor');

class JFormFieldFactoryNotificationEditor extends JFormFieldEditor
{
    public $type = 'FactoryNotificationEditor';

    protected function getInput()
    {
        $this->element['buttons'] = 'article,pagebreak,readmore';

        $type = $this->form->getValue('type');
        $tokens = $this->getTokensForNotification($type);
        $html = array();

        $html[] = parent::getInput();

        if ($tokens) {
            $html[] = '<ul id="' . $this->id . '_tokens" style="list-style-type: none; margin: 0; padding: 0;">';

            foreach ($tokens as $token => $label) {
                $html[] = '<li style="margin-bottom: 5px;"><a href="#' . $token . '" class="btn">' . $label . '</a></li>';
            }

            $html[] = '</ul>';

            $this->addJavascript();
        }

        return implode("\n", $html);
    }

    protected function getTokensForNotification($type)
    {
        $xml = simplexml_load_file(JPATH_COMPONENT_ADMINISTRATOR . '/blogfactory.xml');
        $tokens = $xml->xpath('//parameters/notifications/notification[@type="' . $type . '"]/token');
        $options = array();

        if ($tokens) {
            foreach ($tokens as $token) {
                $token = (string)$token;
                $options[$token] = BlogFactoryText::_('notification_type_' . $type . '_token_' . $token);
            }
        }

        return $options;
    }

    protected function addJavascript()
    {
        $document = JFactory::getDocument();
        $document->addScriptDeclaration('
      jQuery(document).ready(function ($) {
        $("a", "ul#' . $this->id . '_tokens").click(function (event) {
          event.preventDefault();

          var token = "%%" + $(this).attr("href").replace("#", "") + "%%";
          jInsertEditorText(token, "' . $this->id . '");
        });
      });
    ');
    }
}
