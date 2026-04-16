<?php
/**
* @package      Module Accordion slide content for Joomla!
* @version      $Id: default.php  kiennh $ 
* @author       Omegatheme
* @copyright    Copyright (C) 2009 - 2015 Omegatheme. All rights reserved.
* @license      GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
*/

defined('_JEXEC') or die;
?>
<div id="accordion<?php echo $module->id;?>" class="accordion-default accordion-slider">
	<?php /* REMOVING Copyright warning 
        The Joomla module: OT Accordion slide  is free for all websites. We're welcome any developer want to contributes the module. But you must keep our credits that is the very tiny image under the module. If you want to remove it, you may visit http://www.omegatheme.com/member/signup/additional to purchase the Removing copyrights, then you can free your self to remove it. Thank you very much. Omegatheme.com
    */ ?>
	<div class="credit">
		<a href="//www.omegatheme.com" class="omega-powered" >
			<img src="<?php echo '//www.omegatheme.com/credits.php?utm_source='.$_SERVER['SERVER_NAME']; ?>" title="Joomla Module OT Accordion slide powered by Joomla templates - OmegaTheme.com" alt="Joomla Module OT Accordion slide powered by Joomla templates - OmegaTheme.com">
		</a>		
	</div>
	<div class="as-panels">
		<?php for ($i = 0, $n = count($list); $i < $n; $i ++) : ?>
		<?php
			$item = $list[$i]; 
			$images = json_decode($item->images);
			$img = ModAccordionSlideHelper::getfirstimage($item->introtext);     
		?>
		<div class="as-panel">
			<a href="<?php echo $item->link; ?>">
			<?php if (isset($images->image_intro) && !empty($images->image_intro)) {?>
					<img class="as-background" src="<?php echo JURI::base(); ?>modules/mod_accordionslide/assets/images/blank.gif" data-src="<?php echo JURI::base().$images->image_intro; ?>" alt="<?php echo $item->title;?>" />
			<?php } else { 
				?>
				<img class="as-background" src="<?php echo JURI::base(); ?>modules/mod_accordionslide/assets/images/blank.gif" data-src="<?php echo JURI::base().$img; ?>" alt="<?php echo $item->title;?>" /> 
			<?php	
			} ?> 			
			</a>
            
            <?php if($params->get('item_title_show')) : ?>
			<div class="as-layer as-closed as-white panel-counter" data-position="bottomLeft" data-horizontal="8" data-vertical="8">
				<?php echo $item->title; ?>
			</div>
            <?php endif; ?>
            <?php if($params->get('introtext_show')) : ?>
			<div class="as-layer as-opened as-black as-padding hide-medium-screen" data-position="bottomLeft" data-show-transition="left" data-show-delay="200" data-hide-transition="left" data-hide-delay="200">
				<?php $text = ModAccordionSlideHelper::_cleanIntrotext($item->introtext); echo ModAccordionSlideHelper::substr_word($text,$params->get('count_text')); ?>
			</div>
            <?php endif; ?>         
		</div>
		<?php endfor; ?>
	</div>
</div>

<!-- End of Accordion Slider -->

<script>
$('#accordion<?php echo $module->id;?>').accordionSlider({
    width: '<?php echo $params->get('width_slider') ?>',
    height: '<?php echo $params->get('height_slider') ?>',
	responsiveMode: 'auto',
	openedPanelSize: 'max',
	maxOpenedPanelSize: '80%',
	visiblePanels: <?php echo $params->get('count_panel'); ?>,
	closePanelsOnMouseOut: false,
	autoplay: <?php echo $params->get('autoplay'); ?>
});

</script>