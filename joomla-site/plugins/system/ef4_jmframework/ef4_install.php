<?php
/**
 * @version $Id: djcatalog2.script.php 703 2017-05-05 08:00:37Z michal $
 * @package DJ-Catalog2
 * @copyright Copyright (C) 2012 DJ-Extensions.com LTD, All rights reserved.
 * @license http://www.gnu.org/licenses GNU/GPL
 * @author url: http://dj-extensions.com
 * @author email contact@dj-extensions.com
 * @developer Michal Olczyk - michal.olczyk@design-joomla.eu
 *
 * DJ-Catalog2 is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * DJ-Catalog2 is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with DJ-Catalog2. If not, see <http://www.gnu.org/licenses/>.
 *
 */
defined('_JEXEC') or die('Restricted access');

class PlgSystemEF4_jmframeworkInstallerScript {
	function update($parent) 
	{
	}
	
	function preflight($type, $parent)
	{
	}

	function postflight($type, $parent)
	{
		// removing invalid entries from #__menu table which somehow have been created	
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		
		$query->delete('#__menu');
		$query->where('( '.$db->quoteName('menutype').'='.$db->quote('main').' OR '.$db->quoteName('client_id').'=1 )');
		$query->where($db->quoteName('title').'='.$db->quote('ef4_jmframework'));
		
		$db->setQuery($query);
		$db->execute();
	}
}