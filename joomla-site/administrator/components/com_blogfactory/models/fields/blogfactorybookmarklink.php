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

class JFormFieldBlogFactoryBookmarkLink extends JFormFieldText
{
    public function getInput()
    {
        $html = array();

        $html[] = parent::getInput();

        $html[] = '<ul class="muted">';
        $html[] = '<li>' . BlogFactoryText::sprintf('bookmark_link_token_title', '%%title%%') . '</li>';
        $html[] = '<li>' . BlogFactoryText::sprintf('bookmark_link_token_url', '%%url%%') . '</li>';
        $html[] = '</ul>';

        return implode("\n", $html);
    }
}
