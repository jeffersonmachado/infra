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

class BlogFactoryBackendViewReports extends BlogFactoryBackendView
{
    protected
        $variables = array('items', 'pagination', 'state', 'listDirn', 'listOrder', 'sortFields'),
        $buttons = array('edit', 'resolve', 'unresolve', 'delete'),
        $jhtmls = array('behavior.tooltip', 'formbehavior.chosen/select'),
        $js = array('admin/list'),
        $filters = array('resolved', 'type');

    protected function getFilterResolved()
    {
        return array(
            0 => BlogFactoryText::_('report_filter_resolved_option_unresolved'),
            1 => BlogFactoryText::_('report_filter_resolved_option_resolved'),
        );
    }

    protected function getFilterType()
    {
        return array(
            'comment' => BlogFactoryText::_('report_type_comment'),
        );
    }

    protected function getSortFields()
    {
        return array(
            'r.type' => BlogFactoryText::_('reports_heading_type'),
            'r.status' => JText::_('JSTATUS'),
            'u.username' => BlogFactoryText::_('reports_heading_username'),
            'r.created_at' => JText::_('JDATE'),
            'r.id' => JText::_('JGRID_HEADING_ID'),
        );
    }
}
