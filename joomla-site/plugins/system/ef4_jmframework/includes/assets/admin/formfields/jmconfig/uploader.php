<?php
/**
 * @version $Id: uploader.php 38 2014-10-29 07:42:48Z michal $
 * @package JMFramework
 * @copyright Copyright (C) 2012 DJ-Extensions.com LTD, All rights reserved.
 * @license http://www.gnu.org/licenses GNU/GPL
 * @author url: http://dj-extensions.com
 * @author email contact@dj-extensions.com
 * @developer Michal Olczyk - michal.olczyk@design-joomla.eu
 *
 * JMFramework is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * JMFramework is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with JMFramework. If not, see <http://www.gnu.org/licenses/>.
 *
 */


/**
 * @package     Joomla.Administrator
 * @copyright   Copyright (C) 2005 - 2012 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


die('DEPRECATED!');

// Set flag that this is a parent file
define('_JEXEC', 1);
defined('_JEXEC') or die('Restricted access');
define('DS', DIRECTORY_SEPARATOR);

if (file_exists(dirname(__FILE__) .DS.'..'.DS.'..'.DS.'..'.DS.'..'.DS.'..'.DS.'..'.DS.'..'.DS.'..' . DS . 'defines.php')) {
    include_once dirname(__FILE__) .DS.'..'.DS.'..'.DS.'..'.DS.'..'.DS.'..'.DS.'..'.DS.'..'.DS.'..' . DS . 'defines.php';
}

if (!defined('_JDEFINES')) {
    define('JPATH_BASE', realpath(dirname( __FILE__ ).DS.'..'.DS.'..'.DS.'..'.DS.'..'.DS.'..'.DS.'..'.DS.'..'.DS.'..'));
    require_once JPATH_BASE.'/includes/defines.php';
}

require_once JPATH_BASE.'/includes/framework.php';

$app = JFactory::getApplication('administrator');

// Initialise the application.
$app->initialise(array(
    'language' => $app->getUserState('application.lang')
));


$user   = JFactory::getUser();
$result = new JObject;
$actions = JAccess::getActions('com_templates');

foreach ($actions as $action) {
    $result->set($action->name, $user->authorise($action->name, 'com_templates'));
}

if ($result->get('core.edit')) {
    jimport('joomla.filesystem.file');
    jimport('joomla.filesystem.folder');
    
    $files = JRequest::get('files');
    $template = JRequest::getVar('jmconfig_template', null);
    if (!JFolder::exists(JPATH_ROOT.DS.'templates'.DS.$template.DS.'assets'.DS.'config')) {
    	JFolder::create(JPATH_ROOT.DS.'templates'.DS.$template.DS.'assets'.DS.'config');
    }
    if (array_key_exists('jmconfig_file', $files) && $template) {
        if (JFile::upload($files['jmconfig_file']['tmp_name'], JPATH_ROOT.DS.'templates'.DS.$template.DS.'assets'.DS.'config'.DS.$files['jmconfig_file']['name'])) {
            echo $files['jmconfig_file']['name'];
            echo JPATH_ROOT.DS.'templates'.DS.$template.DS.'assets'.DS.'config'.DS.$files['jmconfig_file']['name'];die();
        }
    }
    
}