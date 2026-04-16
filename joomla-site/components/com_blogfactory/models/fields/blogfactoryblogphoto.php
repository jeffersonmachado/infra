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

class JFormFieldBlogFactoryBlogPhoto extends JFormFieldFile
{
    public $type = 'BlogFactoryBlogPhoto';

    protected function getInput()
    {
        jimport('joomla.filesystem.file');
        $src = JPATH_SITE . '/media/com_blogfactory/blogs/' . $this->value;
        $html = array();

        if (JFile::exists($src)) {
            $ext = JFile::getExt($this->value);
            $name = JFile::stripExt($this->value);

            $html[] = '<div>';
            $html[] = '<img src="' . JUri::root() . 'media/com_blogfactory/blogs/' . $name . '_64.' . $ext . '" />';
            $html[] = '</div>';
        }

        $html[] = parent::getInput();

        return implode("\n", $html);
    }
}
