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

class BlogFactoryBackendViewUsers extends BlogFactoryBackendView
{
    protected
        $variables = array('items', 'pagination', 'state', 'listDirn', 'listOrder', 'sortFields'),
        $buttons = array('edit', 'delete'),
        $jhtmls = array('behavior.tooltip', 'formbehavior.chosen/select'),
        $js = array('admin/list');

    protected function getSortFields()
    {
        return array(
            'u.username' => BlogFactoryText::_('users_heading_user'),
            'p.id' => JText::_('JGRID_HEADING_ID'),
        );
    }
}
