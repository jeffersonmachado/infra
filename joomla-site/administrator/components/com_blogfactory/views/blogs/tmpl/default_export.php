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

<div class="modal hide fade" id="collapseModal">
    <div class="modal-header">
        <button type="button" role="presentation" class="close" data-dismiss="modal">x</button>
        <h3><?php echo BlogFactoryText::_('blogs_export_title'); ?></h3>
    </div>

    <div class="modal-body" style="min-height: 400px;">
        <p><?php echo BlogFactoryText::_('blogs_export_text'); ?></p>

        <div class="control-group">
            <label for="exportcategory"><?php echo BlogFactoryText::_('blogs_export_select_category'); ?></label>
            <div class="controls">
                <?php echo JHtml::_('select.genericlist', JHtml::_('category.options', 'com_content'), 'export[category]'); ?>
            </div>
        </div>

        <div class="control-group">
            <label for="export_status"><?php echo BlogFactoryText::_('blogs_export_select_status'); ?></label>
            <div class="controls">
                <?php echo JHtml::_(
                    'select.genericlist',
                    JHtml::_('jgrid.publishedOptions', array('all' => false)),
                    'export[status]',
                    '',
                    'value',
                    'text',
                    'export_status',
                    null,
                    true
                ); ?>
            </div>
        </div>

        <div class="control-group">
            <label for="export_access"><?php echo BlogFactoryText::_('blogs_export_select_access'); ?></label>
            <div class="controls">
                <?php echo JHtml::_(
                    'access.assetgrouplist',
                    'export[access]', '',
                    'class="inputbox"',
                    array(
                        'id' => 'export_access')
                ); ?>
            </div>
        </div>

        <div class="control-group">
            <label for="export_language"><?php echo BlogFactoryText::_('blogs_export_select_language'); ?></label>
            <div class="controls">
                <?php echo JHtml::_(
                    'select.genericlist',
                    JHtml::_('contentlanguage.existing', true, true),
                    'export[language]',
                    '',
                    'value',
                    'text',
                    'export_language',
                    null,
                    true
                ); ?>
            </div>
        </div>

        <div class="control-group">
            <label for="export_featured"><?php echo BlogFactoryText::_('blogs_export_select_featured'); ?></label>
            <div class="controls">
                <?php echo JHtml::_(
                    'select.genericlist',
                    array(0 => 'JNO', 1 => 'JYES'),
                    'export[featured]',
                    '',
                    'value',
                    'text',
                    'export_featured',
                    null,
                    true
                ); ?>
            </div>
        </div>
    </div>

    <div class="modal-footer">
        <button class="btn" type="button" onclick="document.id('exportcategory_id').value='';" data-dismiss="modal">
            <?php echo JText::_('JCANCEL'); ?>
        </button>

        <button class="btn btn-primary" type="submit"
                onclick="Joomla.submitbutton('<?php echo $this->getName(); ?>.export');">
            <?php echo JText::_('JGLOBAL_BATCH_PROCESS'); ?>
        </button>
    </div>
</div>
