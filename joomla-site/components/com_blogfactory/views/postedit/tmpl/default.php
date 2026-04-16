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
    <?php if ($this->item->id): ?>
        <?php echo BlogFactoryText::sprintf('postedit_page_heading_edit', ('' == $this->item->title ? BlogFactoryText::_('post_untitled') : $this->item->title)); ?>
    <?php else: ?>
        <?php echo BlogFactoryText::_('postedit_page_heading_new'); ?>
    <?php endif; ?>
</h1>

<form action="<?php echo BlogFactoryRoute::task('post.save&id=' . (int)$this->item->id); ?>" method="POST"
      name="post-edit" id="post-edit" data-id="<?php echo (int)$this->item->id; ?>"
      data-revision="<?php echo $this->item->isRevision ? 'true' : 'false'; ?>" novalidate="novalidate">

    <?php if ($this->item->isRevision): ?>
        <div class="alert alert-danger">
            <?php echo BlogFactoryText::sprintf('postedit_review_revision', JHtml::_('date', $this->item->revisionDate, 'DATE_FORMAT_LC2')); ?>
            <a href="<?php echo BlogFactoryRoute::view('postedit&id=' . $this->item->id); ?>"><?php echo BlogFactoryText::_('postedit_view_current_version'); ?></a>
        </div>
    <?php endif; ?>

    <?php if ($this->item->isNewerAutosave): ?>
        <div class="alert alert-danger newer-autosave-warning">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <?php echo BlogFactoryText::_('postedit_newer_autosave_exists'); ?>
            <a href="<?php echo BlogFactoryRoute::view('postedit&id=' . $this->item->id . '&revision=' . $this->item->isNewerAutosave->id); ?>"><?php echo BlogFactoryText::_('postedit_view_autosave'); ?></a>
        </div>
    <?php endif; ?>

    <?php echo $this->form->getField('title')->input; ?>

    <div style="overflow: hidden; margin: 10px 0;">
        <?php echo $this->form->getField('alias')->input; ?>
    </div>

    <div style="margin-bottom: 10px; overflow: hidden;">
        <?php if ($this->mediaManagerEnabled): ?>
            <a href="#" class="btn button-add-media"><i
                    class="factory-icon-image-plus"></i><?php echo BlogFactoryText::_('postedit_button_add_media'); ?>
            </a>
        <?php endif; ?>

        <?php if ($this->externalSources): ?>
            <a href="<?php echo BlogFactoryRoute::view('integrations&tmpl=component'); ?>"
               class="modal btn button-add-integration" rel="{ handler: 'iframe', size: { x: 800, y: 600 } }"><i
                    class="factory-icon-document-horizontal-text"></i><?php echo BlogFactoryText::_('postedit_button_add_integration'); ?>
            </a>
        <?php endif; ?>

        <div style="float: right;" class="blogfactory-post-submit">
            <?php if ($this->item->id): ?>
                <a href="#"
                   class="btn button-preview"><?php echo BlogFactoryText::_('postedit_button_preview_changes'); ?></a>
            <?php endif; ?>

            <?php if (!$this->item->published): ?>
                <a href="#" class="btn button-draft"
                   rel="draft"><?php echo BlogFactoryText::_('postedit_button_save_draft'); ?></a>
            <?php endif; ?>

            <a href="#" class="btn btn-primary button-save"
               rel="<?php echo $this->item->published ? 'update' : 'publish'; ?>">
                <?php if ($this->item->published): ?>
                    <?php echo BlogFactoryText::_('postedit_button_update'); ?>
                <?php else: ?>
                    <?php echo BlogFactoryText::_('postedit_button_publish'); ?>
                <?php endif; ?>
            </a>
        </div>
    </div>

    <?php echo $this->form->getField('content')->input; ?>

    <?php if ($this->item->id): ?>
        <div style="margin: 10px 0 20px;" class="muted">
            <?php echo BlogFactoryText::sprintf('postedit_last_edited', JHtml::_('date', $this->item->updated_at, 'DATE_FORMAT_LC2')); ?>

            <div class="autosave" style="float: right;">
                <?php if ($this->item->isRevision): ?>
                    <?php echo BlogFactoryText::_('postedit_revison_autosave_disabled'); ?>
                <?php endif; ?>
                <span class="status"></span>
                <span class="loader"
                      style="display: none;"><?php echo BlogFactoryText::_('postedit_autosaving'); ?></span>
            </div>
        </div>
    <?php endif; ?>

    <div class="row-fluid">
        <div class="span6">
            <?php echo $this->renderFieldset('status'); ?>

            <?php if ($this->categoriesEnabled): ?>
                <?php echo $this->renderFieldset('category'); ?>
            <?php endif; ?>

            <?php echo $this->renderFieldset('discussion'); ?>
            <?php echo $this->renderFieldset('tags'); ?>
        </div>

        <div class="span6">
            <?php if ($this->revisions): ?>
                <?php echo $this->loadTemplate('revisions'); ?>
            <?php endif; ?>

            <?php echo $this->renderFieldset('metadata'); ?>
        </div>
    </div>

    <input type="hidden" name="mode" id="mode" value=""/>
    <?php echo JHtml::_('form.token'); ?>
</form>
