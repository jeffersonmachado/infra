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

?>

<div class="portlet-overview">
    <div class="portlet-overview-row">
        <div class="portlet-overview-column">
            <a href="<?php echo BlogFactoryRoute::view('manageposts'); ?>">
                <?php echo BlogFactoryText::plural('dashboard_portlet_overview_posts', $this->portletOverview['posts_total']); ?>
            </a>
        </div>

        <div class="portlet-overview-column">
            <a href="<?php echo BlogFactoryRoute::view('manageposts&status=published'); ?>">
                <?php echo BlogFactoryText::plural('dashboard_portlet_overview_posts_published', $this->portletOverview['posts_published']); ?>
            </a>
        </div>
    </div>

    <div class="portlet-overview-row">
        <div class="portlet-overview-column">
            <a href="<?php echo BlogFactoryRoute::view('managecomments'); ?>">
                <?php echo BlogFactoryText::plural('dashboard_portlet_overview_comments', $this->portletOverview['comments_total']); ?>
            </a>
        </div>

        <div class="portlet-overview-column">
            <a href="<?php echo BlogFactoryRoute::view('managecomments&status=pending'); ?>">
                <?php echo BlogFactoryText::plural('dashboard_portlet_overview_comments_pending', $this->portletOverview['comments_pending']); ?>
            </a>
        </div>
    </div>

    <div class="portlet-overview-row">
        <div class="portlet-overview-column">
            <a href="<?php echo BlogFactoryRoute::view('managebookmarks'); ?>">
                <?php echo BlogFactoryText::plural('dashboard_portlet_overview_bookmarks', $this->portletOverview['bookmarks']); ?>
            </a>
        </div>

        <div class="portlet-overview-column">
            <a href="<?php echo BlogFactoryRoute::view('managefollowers'); ?>">
                <?php echo BlogFactoryText::plural('dashboard_portlet_overview_followers', $this->portletOverview['followers']); ?>
            </a>
        </div>
    </div>

    <div class="portlet-overview-row">
        <div class="portlet-overview-column">
            <a href="<?php echo BlogFactoryRoute::view('managesubscriptions'); ?>">
                <?php echo BlogFactoryText::plural('dashboard_portlet_overview_bookmarks_subscribed', $this->portletOverview['bookmarks_subscribed']); ?>
            </a>
        </div>

        <div class="portlet-overview-column">
            <a href="javascript:void(0);">
                <?php echo BlogFactoryText::plural('dashboard_portlet_overview_followers_subscribed', $this->portletOverview['followers_subscribed']); ?>
            </a>
        </div>
    </div>
</div>
