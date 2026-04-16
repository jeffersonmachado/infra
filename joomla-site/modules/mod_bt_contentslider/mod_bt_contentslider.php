<?php
/**
 * @package 	mod_bt_contentslider - BT ContentSlider Module
 * @version		2.0.0
 * @created		Oct 2011

 * @author		BowThemes
 * @email		support@bowthems.com
 * @website		http://bowthemes.com
 * @support		Forum - http://bowthemes.com/forum/
 * @copyright	Copyright (C) 2012 Bowthemes. All rights reserved.
 * @license		http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 *
 */

// no direct access
defined('_JEXEC') or die('Restricted access');

require_once JPATH_SITE.'/modules/mod_bt_contentslider/helpers/helper.php';
//Get list content
$list = modBtContentSliderHelper::getList( $params, $module );
//var_dump($list);

// ROW * COL
$itemsPerRow = (int)$params->get( 'items_per_rows', 3 );
$itemsPerCol = (int)$params->get( 'items_per_cols', 1 );
$moduleclass_sfx = $params->get('moduleclass_sfx');
$imgClass = $params->get('hovereffect',1)? 'class="hovereffect"':'';
$modal = $params->get('modalbox');

//Num of item display
$maxPages = $itemsPerRow*$itemsPerCol;

//Get pages list array
$pages = array_chunk( $list, $maxPages  );

//Get total pages
$totalPages = count($pages);

// calculate width of each row. (percent)
$itemWidth = 100/$itemsPerRow;

$tmp = $params->get( 'module_height', 'auto' );
$moduleHeight   =  ( $tmp=='auto' ) ? 'auto' :(int)$tmp.'px';
$tmp = $params->get( 'module_width', 'auto' );
$moduleWidth    =  ( $tmp=='auto') ? 'auto': ((int)$tmp).'px';
$moduleWidthWrapper = ( $tmp=='auto') ? 'auto': (int)$tmp.'px';

//Get Open target
$openTarget 	= $params->get( 'open_target', '_parent' );

//auto_start
$auto_start 	= $params->get('auto_start',1);

//butlet and next back
$next_back 		= $params->get( 'next_back', 0 );
$butlet 		= $params->get( 'butlet', 1 );



//Option for content
$showReadmore = $params->get( 'show_readmore', '1' );
$showTitle = $params->get( 'show_title', '1' );

$show_category_name = $params->get( 'show_category_name', 0 );
$show_category_name_as_link = $params->get( 'show_category_name_as_link', 0 );

$showDate = $params->get( 'show_date', '0' );
$showAuthor = $params->get( 'show_author', '0' );
$show_intro = $params->get( 'show_intro', '0' );

//Option for image
$thumbWidth    = (int)$params->get( 'thumbnail_width', 200 );
$thumbHeight   = (int)$params->get( 'thumbnail_height', 150 );

$image_crop = $params->get( 'image_crop', '0' );
$show_image = $params->get( 'show_image', '0' );
$effect = $params->get('next_back_effect', 'slider') .','.$params->get('bullet_effect', 'slider');
$slideEasing = $params->get('effect', 'easeInQuad');
$preloadImg = JURI::root().'/modules/mod_bt_contentslider/tmpl/images/loading.gif';
$paging = $params->get( 'butlet', 0 )>0 ?  'true':'false';
$play = $auto_start?(int)$params->get('interval', '5000'):0;
$hoverPause = $params->get( 'pause_hover', 1 )>0? 'true':'false';
$duration = (int)$params->get('duration', '1000');
$autoHeight = $params->get( 'auto_height', 0 )>0?'true':'false';
$fadeSpeed = (int)$params->get('duration', '1000');
$modid = '#btcontentslider'.$module->id;

$touchScreen = $params->get('touch_screen', 0);

modBtContentSliderHelper::fetchHead( $params );

//Get tmpl
$align_image = strtolower($params->get( 'image_align', "center" ));
$equalHeight = $params->get( 'equalHeight', 0 ) > 0 && $align_image =='center' ?'true':'false';

require( JModuleHelper::getLayoutPath($module->module, $params->get('layout', 'default')));	
?>

