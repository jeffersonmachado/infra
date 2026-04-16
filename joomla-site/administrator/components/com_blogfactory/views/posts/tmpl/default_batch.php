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

<div class="modal hide fade" id="modalBatch">
    <div class="modal-header">
        <button type="button" role="presentation" class="close" data-dismiss="modal">x</button>
        <h3><?php echo BlogFactoryText::_('blogs_batch_title'); ?></h3>
    </div>

    <div class="modal-body" style="min-height: 400px;">
        <div class="control-group">
            <label for="batchblog"><?php echo BlogFactoryText::_('blogs_batch_select_blog'); ?></label>
            <div class="controls">
                <?php echo JHtml::_('select.genericlist', $this->batchBlogs, 'batch[blog]'); ?>
            </div>
        </div>
    </div>

    <div class="modal-footer">
        <button class="btn" type="button" onclick="document.id('batchblog_id').value='';" data-dismiss="modal">
            <?php echo JText::_('JCANCEL'); ?>
        </button>

        <button class="btn btn-primary" type="submit"
                onclick="Joomla.submitbutton('<?php echo $this->getName(); ?>.batch');">
            <?php echo JText::_('JGLOBAL_BATCH_PROCESS'); ?>
        </button>
    </div>
</div>
