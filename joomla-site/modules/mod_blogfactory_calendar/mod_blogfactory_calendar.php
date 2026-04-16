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

require_once __DIR__ . '/helper.php';

$id = ModBlogFactoryCalendarHelper::getId($module);

$date = JFactory::getApplication()->input->getCmd($id);
$moduleclass_sfx = htmlspecialchars($params->get('moduleclass_sfx'));

$date = ModBlogFactoryCalendarHelper::getDate($date);
$posts = ModBlogFactoryCalendarHelper::getPostsForDate($date);
$prev = ModBlogFactoryCalendarHelper::hasPrev($date);
$next = ModBlogFactoryCalendarHelper::hasNext($date);

JHtml::stylesheet('modules/mod_blogfactory_calendar/assets/style.css');

require JModuleHelper::getLayoutPath('mod_blogfactory_calendar', $params->get('layout', 'default'));
