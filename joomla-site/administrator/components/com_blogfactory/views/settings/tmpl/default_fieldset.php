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

foreach ($this->fieldsets as $fieldset): ?>
    <h4><?php echo BlogFactoryText::_($this->getName() . '_fieldset_label_' . $fieldset); ?></h4>

    <?php if (JFactory::getLanguage()->hasKey($this->option . '_' . $this->getName() . '_fieldset_desc_' . $fieldset)): ?>
        <p class="muted"><?php echo JText::_($this->option . '_' . $this->getName() . '_fieldset_desc_' . $fieldset); ?></p>
    <?php endif; ?>

    <?php echo $this->form->renderFieldset($fieldset); ?>
<?php endforeach; ?>
