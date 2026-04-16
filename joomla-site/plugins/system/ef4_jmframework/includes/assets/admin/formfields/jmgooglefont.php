<?php
/**
 * @version $Id: jmgooglefont.php 38 2014-10-29 07:42:48Z michal $
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

defined('JPATH_PLATFORM') or die;

/**
 * Google web-font selector. DEPRECATED
 */

class JFormFieldJmgooglefont extends JFormField
{
	protected $type = 'Jmgooglefont';

	/**
	 * Method to get the field input markup for a generic list.
	 * Use the multiple attribute to enable multiselect.
	 *
	 */
	protected function getInput()
	{
		$html = array();
		$attr = '';

		// Initialize some field attributes.
		$attr .= $this->element['class'] ? ' class="jmgooglefontselector ' . (string) $this->element['class'] . '"' : 'class="jmgooglefontselector"';

		// To avoid user's confusion, readonly="true" should imply disabled="true".
		if ((string) $this->element['readonly'] == 'true' || (string) $this->element['disabled'] == 'true')
		{
			$attr .= ' disabled="disabled"';
		}

		$attr .= $this->element['size'] ? ' size="' . (int) $this->element['size'] . '"' : '';
		$attr .= $this->multiple ? ' multiple="multiple"' : '';
		$attr .= $this->required ? ' required="required" aria-required="true"' : '';

		// Initialize JavaScript field attributes.
		$attr .= ' onchange="JMThemeCustomiser.enableGoogleFont(this.options[this.selectedIndex].value)"';

		// Get the field options.
		$options = (array) $this->getOptions();

		// Create a read-only list (no name) with a hidden input to store the value.
		if ((string) $this->element['readonly'] == 'true')
		{
			$html[] = JHtml::_('select.genericlist', $options, '', trim($attr), 'value', 'text', $this->value, $this->id);
			$html[] = '<input type="hidden" name="' . $this->name . '" value="' . $this->value . '"/>';
		}
		// Create a regular list.
		else
		{
			$html[] = JHtml::_('select.genericlist', $options, $this->name, trim($attr), 'value', 'text', $this->value, $this->id);
		}

		return implode($html);
	}

	/**
	 * Method to get the field options.
	 *
	 * @return  array  The field option objects.
	 *
	 * @since   11.1
	 */
	protected function getOptions()
	{
		$options = array();
		
		$fonts = array(
            array('',''),
            array('Allerta', 'Allerta'),
            array('Allerta Stencil', 'Allerta Stencil'),
            array('Anonymous Pro', 'Anonymous Pro'),
            array('Arimo', 'Arimo'),
            array('Arvo', 'Arvo'),
            array('Bentham', 'Bentham'), 
            array('Cantarell','Cantarell'),
            array('Cardo','Cardo'),
            array('Copse','Copse'),
            array('Cousine','Cousine'),
            array('Covered By Your Grace','Covered By Your Grace'),
            array('Crimson Text','Crimision Text'),
            array('Cuprum','Cuprum'),
            array('Droid Sans','Droid Sans'),
            array('Droid Sans Mono','Droid Sans Mono'),
            array('Droid Serif','Droid Serif'),
            array('Geo', 'Geo'),
            array('Gruppo', 'Gruppo'), // 82
			array('Iceland','Iceland'),
			array('Iceberg','Iceberg'),
            array('IM Fell DW Pica','IM Fell DW Pica'),
            array('IM Fell DW Pica SC','IM Fell DW Pica SC'),
            array('IM Fell Double Pica','IM Fell Double Pica'),
            array('IM Fell Double Pica SC','IM Fell Double Pica SC'),
            array('IM Fell English','IM Fell English'),
            array('IM Fell English SC','IM Fell English SC'),
            array('IM Fell French Canon','IM Fell French Canon'),
            array('IM Fell French Canon SC','IM Fell French Canon SC'), 
            array('IM Fell Great Primer','IM Fell Great Primer'),
            array('IM Fell Great Primer SC','IM Fell Great Primer SC'), 
            array('Inconsolata','Inconsolata'),
            array('Just Another Hand', 'Just Another Hand'), // 83
            array('Just Me Again Down Here','Just Me Again Down Here'), 
            array('Kenia','Kenia'), 
            array('Kristi', 'Kristi'), // 84
            array('Lekton:italic', 'Lekton (italic)'), // 86
            array('Lobster','Lobster'),
            array('Merriweather', 'Merriweather'), // 88
            array('Molengo','Molengo'),
            array('Mountains of Christmas','Mountains of Christmas'), 
            array('Neucha','Neucha'),
            array('Neuton','Neuton'),
            array('Nobile','Nobile'),
            array('OFL Sorts Mill Goudy TT','OFL Sorts Mill Goudy TT'),
            array('Old Standard TT','Old Standard TT'),
            array('PT Sans','PT Sans'),
            array('PT Sans Caption','PT Sans Caption'),
            array('PT Sans Narrow','PT Sans Narrow'),
            array('Philosopher','Philosopher'),
            array('Puritan', 'Puritan'), 
            array('Reenie Beanie','Reenie Beanie'),
            array('Syncopate', 'Syncopate'), 
            array('Tangerine','Tangerine'),
            array('Tinos', 'Tinos'), 
            array('Ubuntu', 'Ubuntu'), // 89
            array('Ubuntu Condensed','Ubuntu Condensed'),
            array('UnifakturMaguntia', 'UnifakturMaguntia'), 
            array('Vibur', 'Vibur'), 
            array('Vollkorn','Vollkorn'),
            array('Yanone Kaffeesatz','Yanone Kaffeesatz')
        );
		
        foreach ($fonts as $option) {
           $options[] = JHTML::_('select.option', $option[0], JText::_($option[1]));
        }

		return $options;
	}
}
