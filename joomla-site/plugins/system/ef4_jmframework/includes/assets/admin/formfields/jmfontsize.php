<?php
/**
 * @version $Id: jmfontsize.php 38 2014-10-29 07:42:48Z michal $
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

defined('JPATH_PLATFORM') or die;

jimport('joomla.filesystem.file');
jimport('joomla.filesystem.folder');
JFormHelper::loadFieldClass('list');

/**
 * Font-size selector
 */

class JFormFieldJmfontsize extends JFormFieldList {
	protected $type = 'Jmfontsize';
	
	protected function getOptions()
	{
		$options = array();
		
		for ($i = 5; $i <= 50; $i++)
		{
			$option = array('value' => '', 'text'=> '', 'disabled' => false, 'class' => '', 'onclick' => '');
			
			if ($i > 0) {
				$option['value'] 	= $i.'px';
				$option['text'] 	= $i.' px';
			}
			
			$value = $option['value'];
	
			$disabled = (string) $option['disabled'];
			$disabled = ($disabled == 'true' || $disabled == 'disabled' || $disabled == '1');
	
			$disabled = $disabled || ($this->readonly && $value != $this->value);
	
			// Create a new option object based on the <option /> element.
			$tmp = JHtml::_(
					'select.option', $value, JText::alt($option['text'], preg_replace('/[^a-zA-Z0-9_\-]/', '_', $this->fieldname)), 'value', 'text',
					$disabled
			);
	
			// Set some option attributes.
			$tmp->class = (string) $option['class'];
	
			// Set some JavaScript option attributes.
			$tmp->onclick = (string) $option['onclick'];
	
			// Add the option object to the result set.
			$options[] = $tmp;
			
			if ($i == 0) $i = 5;
		}
	
		reset($options);
	
		return $options;
	}
}