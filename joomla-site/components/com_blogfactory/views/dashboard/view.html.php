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

class BlogFactoryFrontendViewDashboard extends BlogFactoryFrontendView
{
    protected
        $variables = array(
        'setup',
        'portletPosts',
        'portletComments',
        'portletOverview',
        'portletBookmarks',
        'portletFollowers',
    ),
        $css = array('icons', 'modal', 'tooltip', 'notification', 'media'),
        $js = array('modal', 'jquery.cookie', 'tooltip', 'notification', 'media'),
        $jhtmls = array('jquery.ui/[core,sortable]', 'bootstrap.tooltip');

    protected function renderPortlet($portlet, $minimized)
    {
        $this->portlet = $portlet;
        $this->minimized = $minimized;

        return $this->loadTemplate('portlet');
    }
}
