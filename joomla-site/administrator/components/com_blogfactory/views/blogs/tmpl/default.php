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
    <form action="<?php echo BlogFactoryRoute::_('view=' . $this->getName()); ?>" method="POST" name="adminForm"
          id="adminForm">
        <?php if (!empty($this->sidebar)): ?>
            <div id="j-sidebar-container" class="span2"><?php echo $this->sidebar; ?></div>
        <?php endif; ?>

        <div id="j-main-container" class="<?php echo !empty($this->sidebar) ? 'span10' : ''; ?>">
            <?php echo $this->loadTemplate('filters'); ?>

            <table class="table table-striped">
                <thead>
                <?php echo $this->loadTemplate('head'); ?>
                </thead>

                <tbody>
                <?php echo $this->loadTemplate('body'); ?>
                </tbody>
            </table>

            <?php echo $this->pagination->getListFooter(); ?>

        </div>

        <?php echo $this->loadTemplate('hidden'); ?>
        <?php echo $this->loadTemplate('export'); ?>
    </form>
</div>
