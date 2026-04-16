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

<div class="modal hide fade" id="batch-import">
    <div class="modal-header">
        <button type="button" role="presentation" class="close" data-dismiss="modal">x</button>
        <h3><?php echo BlogFactoryText::_('posts_import_title'); ?></h3>
    </div>

    <div class="modal-body" style="min-height: 400px;">
        <p><?php echo BlogFactoryText::_('posts_import_text'); ?></p>

        <div class="row-fluid">
            <div class="span6"><h5><?php echo BlogFactoryText::_('posts_import_source_text'); ?></h5>
                <?php foreach ($this->importForm->getFieldset('source') as $field): ?>
                    <?php echo $field->renderField(); ?>
                <?php endforeach; ?>

            </div>

            <div class="span6">
                <h5><?php echo BlogFactoryText::_('posts_import_target_text'); ?></h5>
                <?php foreach ($this->importForm->getFieldset('target') as $field): ?>
                    <?php echo $field->renderField(); ?>
                <?php endforeach; ?>
            </div>
        </div>

    </div>

    <div class="modal-footer">
        <button class="btn" type="button" onclick="document.id('importcategory_id').value='';" data-dismiss="modal">
            <?php echo JText::_('JCANCEL'); ?>
        </button>

        <button class="btn btn-primary" type="submit"
                onclick="Joomla.submitbutton('<?php echo $this->getName(); ?>.import');">
            <?php echo JText::_('JGLOBAL_BATCH_PROCESS'); ?>
        </button>
    </div>
</div>
