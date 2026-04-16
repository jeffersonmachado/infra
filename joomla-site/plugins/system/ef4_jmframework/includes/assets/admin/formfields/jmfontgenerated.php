<?php
/**
 * @version $Id: jmfontgenerated.php 163 2017-10-17 12:48:27Z szymon $
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
 * Field that lists generated custom fonts in a template
 */

class JFormFieldJmfontgenerated extends JFormField
{
	/**
	 * The form field type.
	 *
	 * @var    string
	 * @since  11.1
	 */
	protected $type = 'Jmfontgenerated';

	/**
	 * Method to get the field input markup for a generic list.
	 * Use the multiple attribute to enable multiselect.
	 *
	 * @return  string  The field input markup.
	 *
	 * @since   11.1
	 */
	protected function getInput()
	{
		$html = array();
		$attr = '';

		// Initialize some field attributes.
		$attr .= !empty($this->class) ? ' class="' . $this->class . ' jmgeneratedwebfonts"' : 'jmgeneratedwebfonts';
		$attr .= !empty($this->size) ? ' size="' . $this->size . '"' : '';
		$attr .= $this->required ? ' required aria-required="true"' : '';

		// Get the field options.
		$options = (array) $this->getOptions();

		$html[] = JHtml::_('select.genericlist', $options, $this->name, trim($attr), 'value', 'text', $this->value, $this->id);

		$document = JFactory::getDocument();
		$document->addScriptDeclaration('
			jQuery(document).ready(function(){
				var select_element = jQuery("#'.$this->id.'");
				var options = select_element.find("option");
					if( options.length == 0 ) {
						jQuery(select_element).parent().prepend("<span class=\'jm-alert alert alert-info\'>'.JText::_('PLG_SYSTEM_JMFRAMEWORK_CONFIG_GENERATEDWEBFONT_EMPTY_INFO').'</span>");
					}
			});

			jQuery(document).on("jmplupload_jform_params_font_upload", function(event, up, file, undef){
				var pattern = /\.[^.]+$/;
				for (var i = 0; i < up.files.length; i++) {
					var f = up.files[i];
					var name = f.name.replace(pattern, "");
					if (f.name != undef && f.percent == 100) {
						var select_element = jQuery("#'.$this->id.'");
						var found = false;

						select_element.find("option").each(function(index2, option_element){
							if (jQuery(option_element).val() == name) {
								found = true;
							}
						});

						if (found == false) {
							jQuery("<option/>",{value: name, html: name}).appendTo(select_element);
							jQuery(select_element).trigger("liszt:updated");
							jQuery(select_element).parent().find(".jm-alert").remove();
						}
					}
				}
			});
		');

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

		$path = JPATH_ROOT . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . JMF_TPL . DIRECTORY_SEPARATOR.'fonts';

		if (JFolder::exists($path) == false ){
			JFolder::create($path);
		}

		if (!is_dir($path))
		{
			$path = JPATH_ROOT . '/' . $path;
		}


		// Get a list of folders in the search path with the given filter.
		$folders = JFolder::folders($path, $this->filter);

		// Build the options list from the list of folders.
		if (is_array($folders))
		{
			foreach ($folders as $folder)
			{
				// Check to see if the file is in the exclude mask.
				if ($this->exclude)
				{
					if (preg_match(chr(1) . $this->exclude . chr(1), $folder))
					{
						continue;
					}
				}

				$options[] = JHtml::_('select.option', $folder, $folder);
			}
		}

		return $options;
	}
}
