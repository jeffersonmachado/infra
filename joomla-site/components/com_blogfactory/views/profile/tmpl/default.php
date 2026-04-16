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

<h1><?php echo BlogFactoryText::_('profile_page_heading'); ?></h1>

<form action="<?php echo BlogFactoryRoute::task('profile.save'); ?>" method="POST" enctype="multipart/form-data">
    <div class="row-fluid">
        <div class="blogfactory-edit-box span6">
            <div class="blogfactory-edit-box-header">
                <span><?php echo BlogFactoryText::_('profile_edit_box_header_details'); ?></span>
            </div>

            <div class="blogfactory-edit-box-content padded">
                <?php foreach ($this->form->getFieldset('details') as $field): ?>
                    <div class="control-group">
                        <div class="control-label"><?php echo $field->label; ?></div>
                        <div class="controls"><?php echo $field->input; ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if ($this->enabledAvatars): ?>
            <div class="blogfactory-edit-box span6">
                <div class="blogfactory-edit-box-header">
                    <span><?php echo BlogFactoryText::_('profile_edit_box_header_avatar'); ?></span>
                </div>

                <div class="blogfactory-edit-box-content padded">
                    <?php foreach ($this->form->getFieldset('avatar') as $field): ?>
                        <div class="control-group">
                            <div class="control-label"><?php echo $field->label; ?></div>
                            <div class="controls"><?php echo $field->input; ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div>
        <input type="submit" value="<?php echo BlogFactoryText::_('profile_button_save'); ?>" class="btn btn-primary"/>
        <a href="<?php echo BlogFactoryRoute::view('posts'); ?>"
           class="btn"><?php echo BlogFactoryText::_('profile_button_cancel'); ?></a>
    </div>
</form>
