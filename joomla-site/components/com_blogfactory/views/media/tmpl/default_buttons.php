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

<div class="media-buttons">
    <a href="#" class="btn btn-small media-action-create-folder">
        <i class="factory-icon-folder-plus"></i>
        <?php echo BlogFactoryText::_('media_buttons_create_folder'); ?>
    </a>

    <a href="#" class="btn btn-small">
        <i class="factory-icon-minus-circle"></i>
        <?php echo BlogFactoryText::_('media_buttons_delete'); ?>
    </a>

    <a href="#" class="btn btn-small">
        <i class="factory-icon-drive-upload"></i>
        <?php echo BlogFactoryText::_('media_buttons_upload'); ?>
    </a>

    <a href="#" class="btn btn-small">
        <i class="factory-icon-insert"></i>
        <?php echo BlogFactoryText::_('media_buttons_insert'); ?>
    </a>

    <div class="media-buttons-right">
        <a href="#" class="btn btn-small factory-modal-close">
            <i class="factory-icon-cross"></i>
            <?php echo BlogFactoryText::_('media_buttons_close'); ?>
        </a>
    </div>
</div>
