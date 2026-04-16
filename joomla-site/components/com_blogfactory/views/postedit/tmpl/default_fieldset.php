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

<div class="blogfactory-edit-box">
    <div class="blogfactory-edit-box-header">
        <span><?php echo BlogFactoryText::_('post_edit_box_header_' . $this->fieldset); ?></span>
    </div>

    <div class="blogfactory-edit-box-content padded">
        <?php foreach ($this->form->getFieldset($this->fieldset) as $field): ?>
            <div class="control-group">
                <div class="control-label"><?php echo $field->label; ?></div>
                <div class="controls"><?php echo $field->input; ?></div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
