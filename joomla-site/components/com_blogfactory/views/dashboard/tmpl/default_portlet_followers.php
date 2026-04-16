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

if (!$this->portletFollowers): ?>
    <div class="portlet-no-results">
        <?php echo BlogFactoryText::_('dashboard_portlet_followers_no_items'); ?>
    </div>
<?php else: ?>
    <div style="padding-top: 20px; overflow: hidden;">
        <?php foreach ($this->portletFollowers as $item): ?>
            <div class="portlet-followers-follower">
                <img src="<?php echo JHtml::_('BlogFactoryComments.getAvatarSource', $item); ?>"/>
                <div class="small muted follower-username"><?php echo $item->username; ?></div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="portlet-footer" style="clear: both;">
        <a href="<?php echo BlogFactoryRoute::view('managefollowers'); ?>" class="btn btn-mini">
            <?php echo BlogFactoryText::_('dashboard_portlet_followers_all_followers'); ?>
        </a>
    </div>
<?php endif; ?>
