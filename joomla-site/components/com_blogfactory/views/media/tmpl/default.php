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

<div class="pane-buttons">
    <a href="#" class="btn btn-small button-new-folder">
        <i class="factory-icon-folder-plus"></i><?php echo BlogFactoryText::_('media_button_create_folder'); ?>
    </a>

    <a href="#" class="btn btn-small button-delete">
        <i class="factory-icon-cross"></i><?php echo BlogFactoryText::_('media_button_delete'); ?>
    </a>

    <a href="#" class="btn btn-small button-upload">
        <i class="factory-icon-drive-upload"></i><?php echo BlogFactoryText::_('media_button_upload'); ?>
    </a>

    <?php if (false !== $this->freeSpace): ?>
        <span class="space-remaining">
      <span><?php echo BlogFactoryText::sprintf('media_space_remaining', $this->freeSpace); ?></span>
    </span>
    <?php endif; ?>

    <div style="float: right;">
        <a href="#" class="btn btn-mini btn-danger media-close"><i class="icon-delete"></i></a>
    </div>
</div>

<div class="pane-folders">
    <ul>
        <?php foreach ($this->folders as $name => $folder): ?>
            <?php echo $this->tree($name, $folder); ?>
        <?php endforeach; ?>
    </ul>
</div>

<div class="pane-upload" style="display: none;">
    <input type="file" multiple="multiple" style="display: none;" id="upload_files"/>
    <a href="#" class="btn btn-primary button-select-files"><?php echo BlogFactoryText::_('media_upload_button'); ?></a>

    <div style="float: right;">
        <a href="#"
           class="btn btn-small button-clear-list"><?php echo BlogFactoryText::_('media_upload_clear_list_button'); ?></a>
        <a href="#" class="btn btn-small button-close"><?php echo BlogFactoryText::_('media_upload_close'); ?></a>
    </div>

    <div class="small muted"
         style="margin-top: 5px;"><?php echo BlogFactoryText::sprintf('media_upload_warning', $this->maxUploadSize); ?></div>

    <ul class="upload-status"></ul>
</div>

<div class="pane-files"></div>

<div class="pane-file-details">
    <div class="details" style="display: none;">
        <div id="details_title_wrapper">
            <label for="details_title"><?php echo BlogFactoryText::_('media_filed_details_title_label'); ?></label>
            <input type="text" id="details_title" name="details[title]"/>
        </div>

        <div id="details_alt_text_wrapper">
            <label
                for="details_alt_text"><?php echo BlogFactoryText::_('media_filed_details_alt_text_label'); ?></label>
            <input type="text" id="details_alt_text" name="details[alt_text]"/>
        </div>

        <div id="details_size_wrapper">
            <label for="details_size"><?php echo BlogFactoryText::_('media_filed_details_size_label'); ?></label>
            <select id="details_size" name="details[size]">
                <option
                    value="thumbnail"><?php echo BlogFactoryText::_('media_filed_details_size_option_thumbnail'); ?></option>
                <option
                    value="medium"><?php echo BlogFactoryText::_('media_filed_details_size_option_medium'); ?></option>
                <option
                    value="large"><?php echo BlogFactoryText::_('media_filed_details_size_option_large'); ?></option>
                <option
                    value="original"><?php echo BlogFactoryText::_('media_filed_details_size_option_original'); ?></option>
            </select>

            <label for="details_align"><?php echo BlogFactoryText::_('media_filed_details_align_label'); ?></label>
            <select id="details_align" name="details[align]">
                <option value=""><?php echo BlogFactoryText::_('media_filed_details_align_option_none'); ?></option>
                <option value="left"><?php echo BlogFactoryText::_('media_filed_details_align_option_left'); ?></option>
                <option
                    value="right"><?php echo BlogFactoryText::_('media_filed_details_align_option_right'); ?></option>
            </select>
        </div>

        <label for="details_link_type"><?php echo BlogFactoryText::_('media_filed_details_link_type_label'); ?></label>
        <select id="details_link_type" name="details[link_type]">
            <option value="none"><?php echo BlogFactoryText::_('media_filed_details_link_type_option_none'); ?></option>
            <option value="media"
                    selected="selected"><?php echo BlogFactoryText::_('media_filed_details_link_type_option_media'); ?></option>
            <option
                value="custom"><?php echo BlogFactoryText::_('media_filed_details_link_type_option_custom'); ?></option>
        </select>

        <input type="text" id="details_link" name="details[link]" readonly="readonly"/>

        <div style="margin-top: 10px;">
            <a href="#"
               class="btn btn-primary button-insert-file"><?php echo BlogFactoryText::_('media_filed_details_button_insert'); ?></a>
        </div>
    </div>
</div>
<div class="pane-status"></div>

<div class="pane-new-folder" style="display: none;">
    <input id="folder_title" type="text"
           placeholder="<?php echo BlogFactoryText::_('media_create_folder_placeholder'); ?>"/>
    <a href="#" class="btn btn-small btn-success button-submit"><i class="factory-icon-loader"
                                                                   style="display: none;"></i><?php echo BlogFactoryText::_('media_create_folder_button'); ?>
    </a>
    <a href="#"
       class="btn btn-small btn-danger button-cancel"><?php echo BlogFactoryText::_('media_create_folder_cancel'); ?></a>
</div>
