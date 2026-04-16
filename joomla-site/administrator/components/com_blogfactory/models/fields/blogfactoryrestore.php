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

JFormHelper::loadFieldClass('File');

class JFormFieldBlogFactoryRestore extends JFormFieldFile
{
    public $type = 'BlogFactoryRestore';

    protected function getInput()
    {
        $html = array();

        $html[] = '<div class="alert alert-danger">';
        $html[] = BlogFactoryText::sprintf('backup_restore_info', JPATH_SITE . '/media/com_blogfactory/');
        $html[] = '</div>';

        $html[] = parent::getInput();
        $html[] = '<a href="#" class="btn btn-primary" onclick="Joomla.submitbutton(\'backup.restore\'); return false;">' . BlogFactoryText::_('field_restore_button') . '</a>';

        return implode("\n", $html);
    }
}
