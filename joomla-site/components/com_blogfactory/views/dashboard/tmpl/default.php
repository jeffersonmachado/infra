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

<h1>
    <?php echo BlogFactoryText::_('dashboard_page_heading'); ?>
    <a href="<?php echo BlogFactoryRoute::view('postedit'); ?>" class="btn btn-small btn-primary">
        <i class="factory-icon-document-text-plus"></i>
        <?php echo BlogFactoryText::_('dashboard_page_heading_new_post'); ?>
    </a>
</h1>

<div style="overflow: hidden;" class="blogfactory-dashboard-columns columns-2">

    <div style="overflow: hidden; margin-bottom: 10px;">
        <a href="#" class="btn btn-small btn-links button-media-manager">
            <i class="factory-icon-images"></i><?php echo BlogFactoryText::_('dashboard_link_media_manager'); ?>
        </a>

        <a href="<?php echo BlogFactoryRoute::view('blogedit'); ?>" class="btn btn-small btn-links">
            <i class="factory-icon-gear"></i><?php echo BlogFactoryText::_('dashboard_link_blog_options'); ?>
        </a>
    </div>

    <div class="row-fluid">
        <?php for ($i = 1; $i < 3; $i++): ?>
            <div class="blogfactory-dashboard-column span6">
                <?php if (isset($this->setup[$i])): ?>
                    <?php foreach ($this->setup[$i] as $portlet => $minimized): ?>
                        <?php echo $this->renderPortlet($portlet, $minimized); ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        <?php endfor; ?>
    </div>
</div>
