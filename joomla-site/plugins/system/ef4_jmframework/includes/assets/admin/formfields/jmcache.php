<?php
/**
 * @version $Id: jmcache.php 131 2016-03-08 11:58:04Z szymon $
 * @package JMFramework
 * @copyright Copyright (C) 2012 DJ-Extensions.com LTD, All rights reserved.
 * @license http://www.gnu.org/licenses GNU/GPL
 * @author url: http://dj-extensions.com
 * @author email contact@dj-extensions.com
 * @developer Szymon Woronowski - szymon.woronowski@design-joomla.eu
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

defined('JPATH_BASE') or die;

jimport('joomla.form.formfield');

/**
 * Color picker for the Joomla Framework.
 */
class JFormFieldJmcache extends JFormField
{
	/**
	 * The form field type.
	 *
	 * @var		string
	 * @since	1.6
	 */
	protected $type = 'jmcache';

	/**
	 * Method to get the field input markup.
	 *
	 * @return	string	The field input markup.
	 * @since	1.6
	 */
	protected function getInput()
	{
		$doc = JFactory::getDocument();
		
		$files = JFolder::files(JMF_TPL_PATH.'/cache', '.', false, false, array('index.html', '.svn', 'CVS', '.DS_Store', '__MACOSX'));
		
		$class = $this->element['class'] ? (string) $this->element['class'] : 'btn btn-danger';
		$text = count($files) ? JText::sprintf('PLG_SYSTEM_JMFRAMEWORK_CONFIG_DELETE_CACHE_BUTTON', count($files)) : JText::_('PLG_SYSTEM_JMFRAMEWORK_CONFIG_DELETE_CACHE_EMPTY');
		$disabled = count($files) ? '' : ' disabled="disabled"';
		
		// Initialize JavaScript
		$doc->addScriptDeclaration("
		jQuery(document).ready(function(){
			
			var button = jQuery('#" . $this->id . "');
		
			button.click(function(e){
				
				e.preventDefault();
				button.prop('disabled', true);
				
				jQuery.ajax({
					async: true,
					url : '" . JFactory::getURI()->toString() . "',
					data : {
						jmajax : 'config',
						jmtask: 'purge_cache'
					}
				}).done(function(response) {
					button.text(response);
				}).fail(function(xhr, status, error) {
					alert(error);
				}).always(function() {
					//$('.jmajax-loader').remove();
				});
				
			});
				
		});");
		
		return '<button id="'.$this->id.'" class="'.$class.'"'.$disabled.'>'.$text.'</button>';
	}
}