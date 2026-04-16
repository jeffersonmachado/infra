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

<h4><?php echo BlogFactoryText::_($this->getName() . '_fieldset_label_' . $this->fieldset); ?></h4>

<?php foreach ($this->form->getFieldset($this->fieldset) as $field): ?>
    <div class="control-group">
        <?php echo $field->label; ?>
        <div class="controls"><?php echo $field->input; ?></div>
    </div>
<?php endforeach; ?>
