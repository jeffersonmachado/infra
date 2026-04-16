<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');
 
// Get an instance of the controller prefixed by AlfContact
$controller = JControllerLegacy::getInstance('Alfcontact');
 
// Perform the Request task
$controller->execute(JFactory::getApplication()->input->get('task', 'display'));
// Redirect if set by the controller
$controller->redirect();
