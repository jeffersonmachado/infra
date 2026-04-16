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

<div class="blogfactory-edit-box box-tags">
    <div class="blogfactory-edit-box-header">
        <span><?php echo BlogFactoryText::_('post_edit_box_header_tags'); ?></span>
    </div>

    <div class="blogfactory-edit-box-content padded">
        <input id="tag" type="text"/>
        <a href="#" class="btn button-add-tag"><?php echo BlogFactoryText::_('post_edit_tag_tag'); ?></a>

        <div class="muted info-tag"><?php echo BlogFactoryText::_('post_edit_tags_info'); ?></div>

        <div class="tags">
            <?php foreach ($this->tags as $this->tag): ?>
                <a href="#">
                    <span class="label label-info"><?php echo $this->tag; ?></span>
                    <input type="hidden" name="jform[tags][]" value="<?php echo $this->tag; ?>"/>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>
