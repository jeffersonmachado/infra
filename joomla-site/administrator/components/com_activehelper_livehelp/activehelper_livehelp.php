<?php

 /**
 * @ ActiveHelper LiveHelp component for Joomla
 * @author    : ActiveHelper Inc.
 * @copyright : (C) 2010- 2017 ActiveHelper Inc.
 * @license   : GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @version     5.0
 * @Joomla      3.7
**/


defined( '_JEXEC' ) or die( 'Restricted access' );

// import joomla controller library
jimport('joomla.application.component.controller');

// Set some global property
$document = JFactory::getDocument();

$controller = JControllerLegacy::getInstance('activehelper_livehelp');
$controller->execute(JFactory::getApplication()->input->get('task'));
$controller->redirect();

