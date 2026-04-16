<?php
// No direct access
defined('_JEXEC') or die('Restricted access');
 
JHtml::_('behavior.tooltip');
JHtml::_('formbehavior.chosen', 'select');
JHtml::_('behavior.formvalidation');
?>

<script type="text/javascript">
	Joomla.submitbutton = function(task)
	{
		if (task == 'alfcontact.cancel' || document.formvalidator.isValid(document.id('alfcontact-form'))) {
			Joomla.submitform(task, document.getElementById('alfcontact-form'));
		}
	}
</script>

<form action="<?php echo JRoute::_('index.php?option=com_alfcontact&layout=edit&id='.(int) $this->item->id); ?>" method="post" name="adminForm" id="alfcontact-form" class="form-validate form-horizontal">
	<fieldset>
		<ul class="nav nav-tabs">
			<li class="active"><a href="#details" data-toggle="tab"><?php echo JText::_( 'COM_ALFCONTACT_ALFCONTACT_DETAILS' ); ?></a></li>
		</ul>
		<div class="tab-content">
			<div class="tab-pane active" id="details">
				<?php foreach($this->form->getFieldset() as $field): ?>
					<div class="control-group">
						<div class="control-label">
							<?php echo $field->label; ?>
						</div>
						<div class="controls">
							<?php echo $field->input; ?>
						</div>					
					</div>
				<?php endforeach; ?>
			</div>
		</div>
    </fieldset>

    <input type="hidden" name="task" value="" />
    <?php echo JHtml::_('form.token'); ?>
 </form>
