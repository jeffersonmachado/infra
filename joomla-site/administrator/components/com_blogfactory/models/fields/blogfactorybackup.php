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

class JFormFieldBlogFactoryBackup extends JFormField
{
    protected $type = 'BlogFactoryBackup';

    protected function getInput()
    {
        $html = array();

        $html[] = '<div class="alert alert-info">';
        $html[] = BlogFactoryText::sprintf('backup_backup_info', JPATH_SITE . '/media/com_blogfactory/');
        $html[] = '</div>';

        $html[] = '<a href="#" class="btn btn-primary" onclick="Joomla.submitbutton(\'backup.backup\'); return false;">' . BlogFactoryText::_('field_backup_button') . '</a>';

        return implode("\n", $html);
    }
}
