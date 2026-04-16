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

class BlogFactoryBackendViewComments extends BlogFactoryBackendView
{
    protected
        $variables = array('items', 'pagination', 'state', 'listDirn', 'listOrder', 'sortFields', 'approveComments'),
        $buttons = array('add', 'edit', 'approve', 'unapprove', 'publish', 'unpublish', 'delete'),
        $jhtmls = array('behavior.tooltip', 'formbehavior.chosen/select', 'dropdown.init'),
        $js = array('admin/list'),
        $filters = array('published', 'approved');

    protected function getFilterApproved()
    {
        return array(
            0 => BlogFactoryText::_('comments_filter_approved_option_unapproved'),
            1 => BlogFactoryText::_('comments_filter_approved_option_approved'),
        );
    }

    protected function getSortFields()
    {
        return array(
            'c.text' => BlogFactoryText::_('comments_heading_comment'),
            'c.approved' => BlogFactoryText::_('comments_heading_approved'),
            'c.published' => JText::_('JSTATUS'),
            'u.username' => BlogFactoryText::_('comments_heading_username'),
            'c.created_at' => JText::_('JDATE'),
            'c.id' => JText::_('JGRID_HEADING_ID'),
        );
    }

    protected function beforeDisplay()
    {
        if (!$this->approveComments) {
            unset($this->buttons[array_search('approve', $this->buttons)]);
            unset($this->buttons[array_search('unapprove', $this->buttons)]);
        }

        parent::beforeDisplay();
    }
}
