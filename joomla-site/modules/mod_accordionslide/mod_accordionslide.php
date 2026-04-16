<?php
/**
* @package      Module Accordion slide content for Joomla!
* @version      $Id: mod_accordionslide.php  kiennh $ 
* @author       Omegatheme
* @copyright    Copyright (C) 2009 - 2015 Omegatheme. All rights reserved.
* @license      GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
*/

defined('_JEXEC') or die;

// Include the syndicate functions only once
require_once __DIR__ . '/helper.php';

$list            = ModAccordionSlideHelper::getList($params);
$moduleclass_sfx = htmlspecialchars($params->get('moduleclass_sfx'));
$doc = JFactory::getDocument(); 
$doc->addStyleSheet('modules/mod_accordionslide/assets/css/accordion-slider.min.css');  
$doc->addStyleSheet('modules/mod_accordionslide/assets/css/jquery.fancybox.css');  
$doc->addStyleSheet('modules/mod_accordionslide/assets/css/accordionslide.css'); 

$doc->addScript('modules/mod_accordionslide/assets/js/jquery.min.js');      
$doc->addScript('modules/mod_accordionslide/assets/js/jquery.accordionSlider.min.js');
$doc->addScript('modules/mod_accordionslide/assets/js/jquery.fancybox.pack.js');
//$doc->addScript('modules/mod_accordionslide/assets/js/examples.js');
 
require JModuleHelper::getLayoutPath('mod_accordionslide', $params->get('layout', 'default'));
