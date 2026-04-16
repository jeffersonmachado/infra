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

class BlogFactoryFrontendViewManageSubscriptions extends BlogFactoryFrontendView
{
    protected
        $variables = array(
        'items',
        'pagination',
        'sort',
        'order',
        'viewName',
        'post',
    ),
        $css = array('list', 'icons', 'tooltip', 'notification'),
        $js = array('list', 'tooltip', 'notification'),
        $jhtmls = array('formbehavior.chosen/select', 'jquery.framework', 'bootstrap.tooltip');

    protected function getViewName()
    {
        return $this->getName();
    }

    protected function renderItemActions($item)
    {
        $html = array();

        $html[] = '<a class="subscription-delete item-delete"';
        $html[] = 'href="' . BlogFactoryRoute::task('managesubscription.delete&id=' . $item->id) . '">';
        $html[] = '<i class="factory-icon-loader" style="display: none;"></i>';
        $html[] = '<span>' . BlogFactoryText::_('managesubscriptions_subscription_delete') . '</span>';
        $html[] = '</a>';

        return implode('', $html);
    }
}
