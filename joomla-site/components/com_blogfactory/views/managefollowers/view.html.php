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

class BlogFactoryFrontendViewManageFollowers extends BlogFactoryFrontendView
{
    protected
        $variables = array(
        'items',
        'pagination',
        'status',
        'sort',
        'order',
        'filterTotals',
        'viewName',
    ),
        $css = array('list', 'icons', 'tooltip', 'notification'),
        $js = array('list', 'tooltip', 'notification'),
        $jhtmls = array('formbehavior.chosen/select', 'jquery.framework', 'bootstrap.tooltip');

    protected function getViewName()
    {
        return $this->getName();
    }
}
