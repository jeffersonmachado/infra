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

<div class="blogfactory-edit-box box-revisions">
    <div class="blogfactory-edit-box-header">
        <span><?php echo BlogFactoryText::_('post_edit_box_header_revisions'); ?></span>
    </div>

    <div class="blogfactory-edit-box-content padded">
        <ul>
            <?php foreach ($this->revisions as $revision): ?>
                <li class="<?php echo $this->item->isRevision && $this->item->revisionId == $revision->id ? 'reviewing' : ''; ?>">
                    <a href="<?php echo BlogFactoryRoute::view('postedit&id=' . $revision->post_id . '&revision=' . $revision->id); ?>"><?php echo JHtml::_('date', $revision->created_at, 'DATE_FORMAT_LC2'); ?></a>

                    <?php if ('autosave' == $revision->type): ?>
                        <span class="label"><?php echo BlogFactoryText::_('post_edit_revision_autosave'); ?></span>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>
