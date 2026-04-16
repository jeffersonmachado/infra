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

<h1><?php echo BlogFactoryText::_('blogedit_page_heading'); ?></h1>

<form action="<?php echo BlogFactoryRoute::task('blog.save'); ?>" method="POST" enctype="multipart/form-data">
    <div class="row-fluid">
        <div class="span6">
            <?php echo $this->renderBox('details'); ?>
        </div>

        <div class="span6">
            <?php if ($this->form->getfieldset('notifications')): ?>
                <?php echo $this->renderBox('notifications'); ?>
            <?php endif; ?>

            <?php echo $this->renderBox('thumbnails'); ?>
        </div>
    </div>

    <button type="submit" class="btn btn-primary"><?php echo BlogFactoryText::_('blogedit_button_submit'); ?></button>
    <a href="<?php echo BlogFactoryRoute::view('posts'); ?>"
       class="btn"><?php echo BlogFactoryText::_('blogedit_button_cancel'); ?></a>
</form>
