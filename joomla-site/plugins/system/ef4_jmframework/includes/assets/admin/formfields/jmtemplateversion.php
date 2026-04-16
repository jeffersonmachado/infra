<?php
/**
 * @version $Id: jmtemplateversion.php 163 2017-10-17 12:48:27Z szymon $
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
defined ( 'JPATH_PLATFORM' ) or die ();

/**
 * Simply returns a version of the template and EF Framework's plugin.
 */
class JFormFieldJmtemplateversion extends JFormField {
	protected $type = 'Jmtemplateversion';
	protected static $loaded = false;
	protected $template_name = null;
	protected function getInput() {
		if (! self::$loaded && defined ( 'JMF_TPL_PATH' ) && defined ( 'JMF_TPL_URL' )) {
			self::$loaded = true;

			$app = JFactory::getApplication ();
			$db = JFactory::getDbo ();

			$formControl = $this->formControl;
			if ($this->group) {
				$formControl .= '_' . $this->group;
			}

			$db->setQuery ( 'SELECT * FROM #__extensions WHERE type=\'plugin\' AND ((element=\'ef4_jmframework\' AND folder=\'system\') OR name=\'plg_system_ef4_jmframework\') LIMIT 1' );
			$this->jmfplugin = $db->loadObject ();

			if (! $this->jmfplugin) {
				return '';
			}

			$registry = new JRegistry ();
			$registry->loadString ( $this->jmfplugin->manifest_cache, 'JSON' );
			$this->jmfplugin->manifest = $registry;

			$plgParams = new JRegistry ();
			$plgParams->loadString ( $this->jmfplugin->params, 'JSON' );
			$this->jmfplugin->params = $plgParams;

			$styleId = $app->input->get ( 'id', null, 'int' );

			if (( int ) $styleId > 0) {
				$db->setQuery ( 'SELECT ts.*, e.manifest_cache FROM #__extensions as e INNER JOIN #__template_styles AS ts ON ts.template = e.element AND e.type=\'template\' AND ts.id=' . ( int ) $styleId );
				$this->template = $db->loadObject ();
				$registry = new JRegistry ();
				$registry->loadString ( $this->template->manifest_cache, 'JSON' );
				$this->template->manifest = $registry;
			}

			if (! $this->template) {
				return '';
			}

			$tplversion = ($this->template->manifest->get ( 'version', 'undefined' ));

			$plgversion = ($this->jmfplugin->manifest->get ( 'version', 'undefined' ));

			$html = array ();
			// close control-group, etc. divs.
			$html [] = '</div></div>';

			$html [] = '<div class="control-group">';
			$html [] = '<div class="control-label">';
			$html [] = '<label id="' . $this->id . '-lbl">' . JText::_ ( 'PLG_SYSTEM_JMFRAMEWORK_CONFIG_UPDATES_PLUGIN_VERSION' ) . '</label>';
			$html [] = '</div>';
			$html [] = '<div class="controls">';
			$html [] = '<code class="jm_code">' . $plgversion . '</code>';
			$html [] = '</div>';
			$html [] = '</div>';

			$html [] = '<div class="control-group">';
			$html [] = '<div class="control-label">';
			$html [] = '<span class="jm-link">' . JText::_ ( 'PLG_SYSTEM_JMFRAMEWORK_CONFIG_UPDATES_CHECK_FOR_FRAMEWORK_UPDATES' ) . '</span>';
			$html [] = '</div>';
			$html [] = '</div>';

			$html [] = '<div class="control-group">';
			$html [] = '<div class="control-label">';
			$html [] = '<label id="' . $this->id . '-lbl">' . JText::_ ( 'PLG_SYSTEM_JMFRAMEWORK_CONFIG_UPDATES_TEMPLATE_VERSION' ) . '</label>';
			$html [] = '</div>';
			$html [] = '<div class="controls">';
			$html [] = '<code class="jm_code">' . $tplversion . '</code>';
			$html [] = '</div>';
			$html [] = '</div>';

			$html [] = '<div class="control-group">';
			$html [] = '<div class="control-label">';
			$html [] = '<span class="jm-link">' . JText::_ ( 'PLG_SYSTEM_JMFRAMEWORK_CONFIG_UPDATES_CHECK_FOR_UPDATES' ) . '</span>';
			$html [] = '</div>';
			$html [] = '</div>';

			$updatesNotifications = ( bool ) $this->jmfplugin->params->get ( 'cfg_check_updates', true );
			$updatesEnabled = $updatesNotifications ? JText::_('PLG_SYSTEM_JMFRAMEWORK_CONFIG_ENABLED') : JText::_('PLG_SYSTEM_JMFRAMEWORK_CONFIG_DISABLED');
			$updatesLink = JRoute::_('index.php?option=com_plugins&view=plugins&filter_search=EF4');

			$html [] = '<div class="control-group">';
			$html [] = '<div class="control-label">';
			$html [] = '<label id="' . $this->id . '-lbl-3">' . JText::_ ( 'PLG_SYSTEM_JMFRAMEWORK_CONFIG_UPDATES_NOTIFICATIONS_STATUS' ) . '</label>';
			$html [] = '</div>';
			$html [] = '<div class="controls">';
			$html [] = '<code class="jm_code">'.$updatesEnabled.'</code>';
			$html [] = '</div>';
			$html [] = '</div>';

			$html [] = '<div class="control-group">';
			$html [] = '<div class="control-label">';
			$html [] = '<span class="jm-alert alert alert-info">';
			$html [] = JText::sprintf( 'PLG_SYSTEM_JMFRAMEWORK_CONFIG_UPDATES_NOTIFICATIONS_ALERT', $updatesLink);
			$html [] = '</span>';
			$html [] = '</div>';
			$html [] = '</div>';

			// re-open control-group
			$html [] = '<div><div>';

			$html = implode ( PHP_EOL, $html );

			return $html;
		}
	}
	protected function getTitle() {
		return $this->getLabel ();
	}
}
?>
