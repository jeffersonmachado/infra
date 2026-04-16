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

<div class="blogfactory-view view-<?php echo $this->getName(); ?>">
    <form action="<?php echo BlogFactoryRoute::view('dashboard'); ?>" method="POST" name="adminForm" id="adminForm">
        <?php if (!empty($this->sidebar)): ?>
            <div id="j-sidebar-container" class="span2"><?php echo $this->sidebar; ?></div>
        <?php endif; ?>

        <div id="j-main-container" class="<?php echo !empty($this->sidebar) ? 'span10' : ''; ?>">
            <div class="about-factory">
                <h1>Latest Release Notes</h1>
                <div class="text">
                    <div style="width: 200px; display: inline-block;">
                        <div style="margin: 0 0 10px 0;"><b>
                                <?php if ($this->about->newVersion): ?>
                                    <span style="color: red; text-decoration: blink;">New version available!</span>
                                <?php else: ?>
                                    No new version available!
                                <?php endif; ?>
                            </b></div>

                        <div>
                            <div class="version-label">Your installed version:</div>
                            <b><?php echo $this->about->currentVersion; ?></b></div>
                        <div>
                            <div class="version-label">Latest version available:</div>
                            <b><?php echo $this->about->latestVersion; ?></b></div>
                    </div>

                    <div class="fb-like" data-href="https://www.facebook.com/theFactoryJoomla" data-send="false"
                         data-layout="box_count" data-width="450" data-show-faces="false"></div>

                    <div style="margin-top: 10px;"><?php echo $this->about->versionHistory; ?></div>
                </div>

                <h1>Support and Updates</h1>
                <div class="text"><?php echo $this->about->downloadLink; ?></div>

                <h1>Other Products</h1>
                <div class="text"><?php echo $this->about->otherProducts; ?></div>

                <h1>About thePHPfactory</h1>
                <div class="text"><?php echo $this->about->aboutFactory; ?></div>
            </div>
        </div>

        <input type="hidden" name="boxchecked" value="0"/>
        <input type="hidden" name="task" value=""/>
    </form>
</div>
