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

require_once JPATH_SITE . '/components/com_blogfactory/vendor/autoload.php';

JLoader::discover('BlogFactory', JPATH_SITE . '/components/com_blogfactory/framework');
JLoader::discover('JHtmlBlogFactory', JPATH_ADMINISTRATOR . '/components/com_blogfactory/helpers/html');
JLoader::register('BlogFactoryHelper', __DIR__ . '/helpers/blogfactory.php');

$controller = JControllerLegacy::getInstance('BlogFactoryBackend');
$controller->execute(JFactory::getApplication()->input->get('task'));
$controller->redirect();
