<?php
/**
 * @version $Id: jmlayout.php 163 2017-10-17 12:48:27Z szymon $
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

/**
 * Layout builder field class
 */

class JFormFieldJmlayout extends JFormField
{
		protected $type = 'Jmlayout';
		protected static $loaded = false;


		protected function getInput()
		{
				if (!self::$loaded && defined('JMF_EXEC')) {

					self::$loaded = true;
					$app = JFactory::getApplication();
						$doc = JFactory::getDocument();

						JHtml::_('jquery.ui', array('core', 'sortable'));
						$doc->addStyleSheet(JMF_ASSETS . 'css/layout.css');
						$doc->addScript(JMF_ASSETS . 'js/jmlayout.js');

						JFactory::getDocument()->addScriptDeclaration ("
				jQuery.extend(JMLayoutBuilder, {
								url: '" . JFactory::getURI()->toString() . "',
							field: '".$this->id."',
							lang: ".$this->addLanguage()."
						});
				jQuery(document).ready(function() {
					jQuery(document.body).addClass('jmframework');
				});
			");

						$options = $this->getOptions();
						$loadOptions = JHtml::_('select.genericlist', $options, $this->name, 'onchange="JMLayoutBuilder.loadLayout()"', 'value', 'text', $this->value, $this->id);

						$layoutbuilder_path = JPath::clean(JMF_FRAMEWORK_PATH.'/includes/assets/admin/layouts/layoutbuilder.php');

						ob_start();
			if (JFile::exists($layoutbuilder_path)) {
				include($layoutbuilder_path);
			} else {
				throw new Exception('Missing file: '.$layoutbuilder_path, 500);
			}
			$html = ob_get_contents();
			ob_end_clean();

			return $html;
				}
		}

		private function addLanguage(){

			$langs = array(
			'PLG_SYSTEM_JMFRAMEWORK_EMPTY_POSITION' => JText::_('PLG_SYSTEM_JMFRAMEWORK_EMPTY_POSITION'),
			'PLG_SYSTEM_JMFRAMEWORK_SELECT_MODULE_POSITION' => JText::_('PLG_SYSTEM_JMFRAMEWORK_SELECT_MODULE_POSITION'),
			'PLG_SYSTEM_JMFRAMEWORK_EDIT_MODULE_POSITION' => JText::_('PLG_SYSTEM_JMFRAMEWORK_EDIT_MODULE_POSITION'),
			'PLG_SYSTEM_JMFRAMEWORK_ELEMENT_WIDTH' => JText::_('PLG_SYSTEM_JMFRAMEWORK_ELEMENT_WIDTH'),
			'PLG_SYSTEM_JMFRAMEWORK_MODULE_POSITION_NAME' => JText::_('PLG_SYSTEM_JMFRAMEWORK_MODULE_POSITION_NAME'),
			'PLG_SYSTEM_JMFRAMEWORK_ELEMENT_DRAG_TO_RESIZE' => JText::_('PLG_SYSTEM_JMFRAMEWORK_ELEMENT_DRAG_TO_RESIZE'),
			'PLG_SYSTEM_JMFRAMEWORK_HIDE_POSITION' => JText::_('PLG_SYSTEM_JMFRAMEWORK_HIDE_POSITION'),
			'PLG_SYSTEM_JMFRAMEWORK_SHOW_POSITION' => JText::_('PLG_SYSTEM_JMFRAMEWORK_SHOW_POSITION'),
			'PLG_SYSTEM_JMFRAMEWORK_HIDDEN_POSITION_DESC' => JText::_('PLG_SYSTEM_JMFRAMEWORK_HIDDEN_POSITION_DESC'),
			'PLG_SYSTEM_JMFRAMEWORK_CONFIRM_COPY_LAYOUT' => JText::_('PLG_SYSTEM_JMFRAMEWORK_CONFIRM_COPY_LAYOUT'),
			'PLG_SYSTEM_JMFRAMEWORK_CONFIRM_DELETE_LAYOUT' => JText::_('PLG_SYSTEM_JMFRAMEWORK_CONFIRM_DELETE_LAYOUT'),
			'PLG_SYSTEM_JMFRAMEWORK_CORRECT_LAYOUT_NAME' => JText::_('PLG_SYSTEM_JMFRAMEWORK_CORRECT_LAYOUT_NAME'),
			'PLG_SYSTEM_JMFRAMEWORK_UNKNOWN_WIDTH' => JText::_('PLG_SYSTEM_JMFRAMEWORK_UNKNOWN_WIDTH'),
			'PLG_SYSTEM_JMFRAMEWORK_CHANGE_POSITOIN_NUMBER' => JText::_('PLG_SYSTEM_JMFRAMEWORK_CHANGE_POSITOIN_NUMBER'),
			'PLG_SYSTEM_JMFRAMEWORK_CANT_LOAD_LAYOUT' => JText::_('PLG_SYSTEM_JMFRAMEWORK_CANT_LOAD_LAYOUT'),
			'PLG_SYSTEM_JMFRAMEWORK_DRAG_TO_RESIZE' => JText::_('PLG_SYSTEM_JMFRAMEWORK_DRAG_TO_RESIZE'),
			'PLG_SYSTEM_JMFRAMEWORK_MODULES_CHROME' => JText::_('PLG_SYSTEM_JMFRAMEWORK_MODULES_CHROME'),
			'PLG_SYSTEM_JMFRAMEWORK_SORT_BLOCKS' => JText::_('PLG_SYSTEM_JMFRAMEWORK_SORT_BLOCKS'),
			'PLG_SYSTEM_JMFRAMEWORK_HIDE_BLOCK' => JText::_('PLG_SYSTEM_JMFRAMEWORK_HIDE_BLOCK'),
			'PLG_SYSTEM_JMFRAMEWORK_SHOW_BLOCK' => JText::_('PLG_SYSTEM_JMFRAMEWORK_SHOW_BLOCK'),
			'PLG_SYSTEM_JMFRAMEWORK_SORT_MAIN_COLUMNS' => JText::_('PLG_SYSTEM_JMFRAMEWORK_SORT_MAIN_COLUMNS'),
			'PLG_SYSTEM_JMFRAMEWORK_FULLWIDTH_ON' => JText::_('PLG_SYSTEM_JMFRAMEWORK_FULLWIDTH_ON'),
			'PLG_SYSTEM_JMFRAMEWORK_FULLWIDTH_OFF' => JText::_('PLG_SYSTEM_JMFRAMEWORK_FULLWIDTH_OFF'),
			'PLG_SYSTEM_JMFRAMEWORK_LAYOUTBUILDER_RESTORE_LAYOUT_DONE' => JText::_('PLG_SYSTEM_JMFRAMEWORK_LAYOUTBUILDER_RESTORE_LAYOUT_DONE'),
			'PLG_SYSTEM_JMFRAMEWORK_LAYOUTBUILDER_RESTORE_MODULE_POS_DONE' => JText::_('PLG_SYSTEM_JMFRAMEWORK_LAYOUTBUILDER_RESTORE_MODULE_POS_DONE'),
			'PLG_SYSTEM_JMFRAMEWORK_LAYOUTBUILDER_RESTORE_ORDER_DONE' => JText::_('PLG_SYSTEM_JMFRAMEWORK_LAYOUTBUILDER_RESTORE_ORDER_DONE'),
			'PLG_SYSTEM_JMFRAMEWORK_LAYOUTBUILDER_RESTORE_SCREEN_DONE' => JText::_('PLG_SYSTEM_JMFRAMEWORK_LAYOUTBUILDER_RESTORE_SCREEN_DONE'),
			'PLG_SYSTEM_JMFRAMEWORK_COLUMN_SIZE_SWITCHER' => JText::_('PLG_SYSTEM_JMFRAMEWORK_COLUMN_SIZE_SWITCHER'),
			'PLG_SYSTEM_JMFRAMEWORK_COLUMN_SIZE_MAX' => JText::_('PLG_SYSTEM_JMFRAMEWORK_COLUMN_SIZE_MAX'),
			'PLG_SYSTEM_JMFRAMEWORK_FULLWIDTH_SECTION_OFF' => JText::_('PLG_SYSTEM_JMFRAMEWORK_FULLWIDTH_SECTION_OFF'),
			'PLG_SYSTEM_JMFRAMEWORK_FULLWIDTH_SECTION_ON' => JText::_('PLG_SYSTEM_JMFRAMEWORK_FULLWIDTH_SECTION_ON')
			);

			return json_encode($langs);
		}

		protected function getLabel(){
			return ''; //'<span class="jm-alert alert alert-info"><label id="' . $this->id . '-lbl"">' . JText::_('PLG_SYSTEM_JMFRAMEWORK_LAYOUTBUILDER_INFO') . '</label></span>';
		}

		protected function getOptions() {
				$options = array();
				if (defined('JMF_TPL_PATH')) {
					$path = JMF_TPL_PATH.DIRECTORY_SEPARATOR.'tpl';

					$files = JFolder::files($path, '.php');

					if (is_array($files)) {

						$app = JFactory::getApplication();
						$styleid = $app->input->get('id', null, 'int');

						$file = JPath::clean(JMF_TPL_PATH . '/assets/style/assigns-' . $styleid . '.json');

						if (!is_dir(dirname($file))) {
							JFolder::create(dirname($file));
						}

						$assigns = new JRegistry;
						// get current layout assigns settings
				if(JFile::exists($file)) {
					$assigns->loadString(JFile::read($file));
				} else {
					$assigns->set(0, !empty($this->value) ? $this->value : 'default');
					$data = $assigns->toString();
					if (!@JFile::write($file, $data)) {
						$app->enqueueMessage(JText::sprintf('PLG_SYSTEM_JMFRAMEWORK_CAN_NOT_WRITE_TO_FILE', $file),'error');
					}
				}
						$arr_assigns = $assigns->toArray();

							foreach($files as $file) {
								$name = JFile::stripExt($file);
								$options[] = JHtml::_('select.option', $name, $name.($name == $arr_assigns[0] ? ' [DEFAULT]':''));
							}
					}
				}

				return $options;
		}

		private function debug($msg, $type = 'message') {

			$app = JFactory::getApplication();
			$app->enqueueMessage("<pre>".print_r($msg, true)."</pre>", $type);

		}
}
?>
