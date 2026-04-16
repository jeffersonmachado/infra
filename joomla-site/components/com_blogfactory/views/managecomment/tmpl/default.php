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

<div class="blogfactory-view view-managecomment">
    <h1>
        <?php echo BlogFactoryText::_('managecomment_page_heading'); ?>
        <a href="<?php echo BlogFactoryRoute::view('managecomments'); ?>" class="btn btn-small">
            <i class="factory-icon-balloons"></i>
            <?php echo BlogFactoryText::_('managecomment_page_heading_comments'); ?>
        </a>
    </h1>

    <form action="<?php echo BlogFactoryRoute::task('managecomment.save&id=' . $this->item->id); ?>" method="POST"
          novalidate>
        <div class="row-fluid">
            <div class="span8">
                <div class="blogfactory-edit-box box-author">
                    <div class="blogfactory-edit-box-header">
                        <span><?php echo BlogFactoryText::_('managecomment_box_author_heading'); ?></span>
                    </div>

                    <div class="blogfactory-edit-box-content padded">
                        <?php foreach ($this->form->getFieldset('author') as $field): ?>
                            <?php echo $field->label; ?>
                            <?php echo $field->input; ?>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="blogfactory-edit-box">
                    <div class="blogfactory-edit-box-header">
                        <span><?php echo BlogFactoryText::_('managecomment_box_content_heading'); ?></span>
                    </div>

                    <div class="blogfactory-edit-box-content">
                        <?php echo $this->form->getField('text')->input; ?>
                    </div>
                </div>
            </div>

            <div class="span4">
                <div class="blogfactory-edit-box">
                    <div class="blogfactory-edit-box-header">
                        <span><?php echo BlogFactoryText::_('managecomment_box_status_heading'); ?></span>
                    </div>

                    <div class="blogfactory-edit-box-content">
                        <div class="padded">
                            <?php foreach ($this->form->getFieldset('status') as $field): ?>
                                <?php echo $field->label; ?>
                                <?php echo $field->input; ?>
                            <?php endforeach; ?>
                        </div>

                        <div class="padded status-box">
                            <?php echo $this->form->getField('created_at')->label; ?>
                            <?php echo $this->form->getField('created_at')->input; ?>
                        </div>

                        <div class="padded status-box">
                            <a href="<?php echo BlogFactoryRoute::task('managecomment.delete&id=' . $this->item->id); ?>"
                               class="muted btn btn-link btn-small button-delete">
                                <?php echo BlogFactoryText::_('managecomment_status_delete'); ?>
                            </a>

                            <button type="submit" class="btn btn-primary btn-small button-update">
                                <?php echo BlogFactoryText::_('managecomment_status_update'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php echo $this->form->getField('post_id')->input; ?>
    </form>
</div>
