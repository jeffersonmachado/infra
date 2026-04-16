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
            <div class="row-fluid dashboard-panels">
                <div class="span6">
                    <?php foreach ($this->setup[0] as $this->panel => $this->state): ?>
                        <?php echo $this->loadPanel($this->panel, $this->state); ?>
                    <?php endforeach; ?>
                </div>

                <div class="span6">
                    <?php foreach ($this->setup[1] as $this->panel => $this->state): ?>
                        <?php echo $this->loadPanel($this->panel, $this->state); ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <input type="hidden" name="boxchecked" value="0"/>
        <input type="hidden" name="task" value=""/>
    </form>
</div>
