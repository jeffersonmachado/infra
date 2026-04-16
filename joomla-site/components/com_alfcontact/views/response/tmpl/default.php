<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');
	
	$app = JFactory::getApplication();
	$params = JComponentHelper::getParams('com_alfcontact');
	$this->custom_header = $params->get('custom_header', '');
	$this->custom_text = $params->get('custom_text', '');
?>

<div class="item-page">
	<div class="page-header">
		<h2><?php echo $this->custom_header; ?></h2>
	</div>	
	<div class="clr"></div>
	<p style="text-align: justify;"><span class="content_table">
		<?php echo $this->custom_text; ?>
	</span></p>
</div>
