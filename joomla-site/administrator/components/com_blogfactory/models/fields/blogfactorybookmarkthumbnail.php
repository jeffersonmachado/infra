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

JFormHelper::loadFieldType('File');

class JFormFieldBlogFactoryBookmarkThumbnail extends JFormFieldFile
{
    public function getInput()
    {
        $html = array();

        $html[] = $this->renderThumbnail($this->value);
        $html[] = parent::getInput();

        return implode("\n", $html);
    }

    protected function renderThumbnail($value)
    {
        jimport('joomla.filesystem.file');

        $html = array();

        $path = JPATH_SITE . '/media/com_blogfactory/share/' . $value;
        $src = JUri::root() . 'media/com_blogfactory/share/' . $value;

        if (JFile::exists($path)) {
            $html[] = '<div>';
            $html[] = '<img src="' . $src . '" />';
            $html[] = '</div>';
        }

        return implode("\n", $html);
    }
}
