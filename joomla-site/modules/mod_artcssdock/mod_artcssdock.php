<?php
/**
* @module		Art CSS Dock
* @copyright	Copyright (C) 2010 artetics.com
* @license		GPL
*/

defined('_JEXEC') or die('Restricted access');
error_reporting(E_ERROR);
$document = &JFactory::getDocument();
$moduleId = $module->id;

$icons = $params->get('icons', true);
$titles = $params->get('titles', true);
$links = $params->get('links', true);
$width = $params->get('width', 32);
$hoverWidth = $params->get('hoverWidth', 64);

$iconsArray = explode ("\n", $icons);
$titlesArray = explode ("\n", $titles);
$linksArray = explode ("\n", $links);
$count = count($iconsArray);

$document->addStylesheet(JURI::root() . 'modules/mod_artcssdock/css/style.css');
?>
<style type="text/css">
#dock<?php echo $moduleId; ?> img {
  width: <?php echo $width; ?>px;
}

#dock<?php echo $moduleId; ?> li:hover img {
	width: <?php echo $hoverWidth; ?>px;
}
</style>
<div class="dock" id="dock<?php echo $moduleId; ?>">
  <ul>
    <?php for ($i = 0; $i < $count; $i++) { ?>
    <li>
      <a href="<?php echo $linksArray[$i]; ?>">
        <em><span><?php echo $titlesArray[$i]; ?></span></em>
        <img src="<?php echo $iconsArray[$i]; ?>" alt="<?php echo $titlesArray[$i]; ?>" />
      </a>
    </li>
    <?php } ?>
  </ul>
</div>