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

class JFormFieldBlogFactoryAvatar extends JFormFieldFile
{
    public $type = 'BlogFactoryAvatar';

    protected function getInput()
    {
        $html = array();

        $html[] = '<div>';
        $html[] = '<img src = "' . $this->getAvatarSrc() . '" />';
        $html[] = '</div>';

        $html[] = parent::getInput();

        return implode("\n", $html);
    }

    protected function getAvatarSrc()
    {
        $default = JUri::root() . 'components/com_blogfactory/assets/images/user.png';

        if (is_null($this->value) || '' == $this->value) {
            return $default;
        }

        jimport('joomla.filesystem.file');

        $path = JPATH_SITE . '/media/com_blogfactory/avatars/' . $this->value;
        if (!JFile::exists($path)) {
            return $default;
        }

        return JUri::root() . 'media/com_blogfactory/avatars/' . $this->value;
    }
}
