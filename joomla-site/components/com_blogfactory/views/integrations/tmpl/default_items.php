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

<form class="form-search pull-right" action="<?php echo JUri::root(); ?>index.php">
    <input type="hidden" name="option" value="com_blogfactory"/>
    <input type="hidden" name="view" value="integrations"/>
    <input type="hidden" name="tmpl" value="component"/>
    <input type="hidden" name="extension" value="<?php echo $this->extension; ?>"/>

    <input type="text" class="input-medium search-query" name="query"
           value="<?php echo $this->escape($this->query); ?>"/>
    <button type="submit" class="btn"><?php echo BlogFactoryText::_('integrations_search_text'); ?></button>
</form>

<div style="clear: both;"></div>

<?php if ($this->items): ?>
    <table class="table table-striped table-bordered">
        <?php foreach ($this->items as $item): ?>
            <tr>
                <td>
                    <div>
                        <a href="<?php echo $item->link; ?>" target="_blank"><b><?php echo $item->title; ?></b></a>
                    </div>

                    <p class="muted small">
                        <?php if ($item->description): ?>
                            <?php echo JHtml::_('string.truncate', $item->description, 250); ?>
                        <?php else: ?>
                            <?php echo BlogFactoryText::_('integrations_no_description'); ?>
                        <?php endif; ?>
                    </p>

                    <div class="btn-group">
                        <?php echo JHtml::_('BlogFactoryIntegration.item', 'title', $item); ?>
                        <?php echo JHtml::_('BlogFactoryIntegration.item', 'description', $item); ?>
                        <?php echo JHtml::_('BlogFactoryIntegration.item', 'link', $item); ?>
                        <?php echo JHtml::_('BlogFactoryIntegration.item', 'photos', $item); ?>
                        <?php echo JHtml::_('BlogFactoryIntegration.item', 'thumbnails', $item); ?>

                        <?php if (isset($item->extra)): ?>
                            <?php foreach ($item->extra as $label => $value): ?>
                                <?php echo JHtml::_('BlogFactoryIntegration.item', 'extra', $item, array('data' => $value, 'label' => $label)); ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php elseif ($this->extension): ?>
    <div class="alert alert-info">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        <?php echo BlogFactoryText::_('integrations_no_items_found'); ?>
    </div>
<?php else: ?>
    <div class="alert">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        <?php echo BlogFactoryText::_('integrations_select_component'); ?>
    </div>
<?php endif; ?>
