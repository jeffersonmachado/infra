<?php
/**
 * @package 	mod_bt_contentslider - BT ContentSlider Module
 * @version		1.4
 * @created		Oct 2011

 * @author		BowThemes
 * @email		support@bowthems.com
 * @website		http://bowthemes.com
 * @support		Forum - http://bowthemes.com/forum/
 * @copyright	Copyright (C) 2012 Bowthemes. All rights reserved.
 * @license		http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 *
 */
jimport('joomla.filesystem.folder');
// no direct access
defined('_JEXEC') or die('Restricted access');

class modBtContentSliderHelper {

	/**
	 * Get list articles
	 * Ver 1 : only form content
	 */
	public static function getList( &$params, $module ){
		$thumbPath = JPATH_BASE . '/cache/' .$module->module.'/';
		$thumbUrl  = str_replace(JPATH_BASE.'/',JURI::base(),$thumbPath);
		$defaultThumb = JURI::base().'modules/'.$module->module.'/images/no-image.jpg';	
		
		if( !is_dir($thumbPath) ) {
			JFolder::create( $thumbPath, 0755 );
		};
		//Get source form params
		$source 	= $params->get('source','category');

		if($source == 'category' || $source == 'article_ids' || $source == 'joomla_tags')
		{
			$source = 'content';
		}
		else if($source == 'k2_category' || $source == 'k2_article_ids' || $source == 'k2_tags')
		{
			$source = 'k2';
		}
		else if($source == 'btportfolio_category' || $source == 'btportfolio_article_ids')
		{
			$source = 'btportfolio';
		}
		else if($source == 'easyblog_category' || $source == 'easyblog_article_ids')
		{
			$source = 'easyblog';
		}
		else{
			$source = 'content';
		}

		$path = JPATH_SITE.'/modules/mod_bt_contentslider/classes/'.$source.".php";

		require_once $path;
		$objectName = "Bt".ucfirst($source)."DataSource";
	 	$object = new $objectName($params );
		//3 step
		//1.set images path
		//2.Render thumb
		//3.Get List
	 	$items = $object->setThumbPathInfo($thumbPath,$thumbUrl,$defaultThumb)
			->setImagesRendered( array( 'thumbnail' =>
										array( (int)$params->get( 'thumbnail_width', 60 ), (int)$params->get( 'thumbnail_height', 60 ))
									) )
			->getList();
  		return $items;
	}

	public static function fetchHead($params){
		$document	= JFactory::getDocument();
		$header = $document->getHeadData();
		$mainframe = JFactory::getApplication();
		$template = $mainframe->getTemplate();

		if(file_exists(JPATH_BASE.'/templates/'.$template.'/html/mod_bt_contentslider/css/btcontentslider.css'))
		{
			$document->addStyleSheet(  JURI::root().'templates/'.$template.'/html/mod_bt_contentslider/css/btcontentslider.css');
		}
		else{
			$document->addStyleSheet(JURI::root().'modules/mod_bt_contentslider/tmpl/css/btcontentslider.css');
		}

		$loadJquery = true;
		switch($params->get('loadJquery',"auto")){
			case "0":
				$loadJquery = false;
				break;
			case "1":
				$loadJquery = true;
				break;
			case "auto":

				foreach($header['scripts'] as $scriptName => $scriptData)
				{
					if(substr_count($scriptName,'/jquery'))
					{
						$loadJquery = false;
						break;
					}
				}
			break;
		}
		
		if($loadJquery)
		{
			$document->addScript(JURI::root().'modules/mod_bt_contentslider/tmpl/js/jquery.min.js');
		}
		$document->addScript(JURI::root().'modules/mod_bt_contentslider/tmpl/js/slides.js');
		$document->addScript(JURI::root().'modules/mod_bt_contentslider/tmpl/js/default.js');
		$document->addScript(JURI::root().'modules/mod_bt_contentslider/tmpl/js/jquery.easing.1.3.js');
	}
}
?>