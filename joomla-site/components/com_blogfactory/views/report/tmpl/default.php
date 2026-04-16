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

<div class="factory-modal-title"><?php echo BlogFactoryText::_('report_page_heading'); ?></div>

<form method="post" action="<?php echo BlogFactoryRoute::task('report.send&format=raw'); ?>">
    <?php foreach ($this->form->getFieldset('details') as $field): ?>
        <div class="control-group">
            <div class="control-label"><?php echo $field->label; ?></div>
            <div class="controls"><?php echo $field->input; ?></div>
        </div>
    <?php endforeach; ?>
</form>

<div class="factory-modal-buttons">
    <a href="#" class="btn btn-primary btn-small factory-modal-submit">Send report</a>
    <a href="#" class="btn btn-link btn-small factory-modal-close">Cancel</a>
</div>
