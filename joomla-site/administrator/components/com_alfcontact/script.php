<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');
 
/**
 * Script file of AlfContact component
 */
class com_AlfContactInstallerScript
{
	/**
	 * method to install the component
	 *
	 * @return void
	 */
	function install($parent) 
	{
		// Shows after install
		echo '<div class="well"><img style="float: left; margin-left: 15px; margin-right: 15px; margin-bottom: 10px;" src="' . JURI::root() . 'media/com_alfcontact/images/' . 'alfcontact-48.png' . '" alt="ALFContact logo" />';
		echo '<h2 style="color: #FEA23B; margin: 0pt; padding: 15px;">' . JText::_('COM_ALFCONTACT') . ' v3.4.0</h2>';
		echo '<div style="width: 50em; margin: 0pt; padding: 0.5em;">';
		echo '<p><br>' . JText::_('COM_ALFCONTACT_DESCRIPTION') . '</p>';
		echo '<p>' . JText::_('COM_ALFCONTACT_INSTALL_TEXT') . '</p>';
		echo '<p><a style="font-weight: bold; color: #FEA23B; font-size: 1.1em;" href="' . JRoute::_('index.php?option=com_alfcontact') . '" title="">' . JText::_('COM_ALFCONTACT_GOTO_ADMIN') . '</a></p>';
		echo '</div></div>';
	}
	
	/**
	 * method to uninstall the component
	 *
	 * @return void
	 */
	function uninstall($parent)
	{
		// Shows after uninstall
		echo '<div class="well"><img style="float: left; margin: 10px;" src="http://www.alfsoft.com/images/alfcontact-48.png" alt="ALFContact logo" />';
		echo '<h2 style="color: #FEA23B; margin-top: 15px; padding: 15px;">' . JText::_('COM_ALFCONTACT') . '</h2>';
		echo '<div>';
		echo '<p>' . JText::_('COM_ALFCONTACT_UNINSTALL_TEXT') . '</p>';
		echo '</div></div>';
	}
 
	/**
	 * method to update the component
	 *
	 * @return void
	 */
	function update($parent) 
	{
		// Shows after update
		echo '<div class="well"><img style="float: left; margin: 10px;" src="http://www.alfsoft.com/images/alfcontact-48.png" alt="ALFContact logo" />';
		echo '<h2 style="color: #FEA23B; margin-top: 15px; padding: 15px;">' . JText::_('COM_ALFCONTACT') . '</h2>';
		echo '<div>';
		echo '<p>' . JText::_('COM_ALFCONTACT_UPDATE_TEXT') . '</p>';
		echo '</div></div>';
	}
 
	/**
	 * method to run before an install/update/uninstall method
	 *
	 * @return void
	 */
	function preflight($type, $parent) 
	{
		// $parent is the class calling this method
		// $type is the type of change (install, update or discover_install)

		echo '<p>' . JText::_('COM_ALFCONTACT_PREFLIGHT_' . $type . '_TEXT') . '</p>';
	}
 
	/**
	 * method to run after an install/update/uninstall method
	 *
	 * @return void
	 */
	function postflight($type, $parent) 
	{
		// $parent is the class calling this method
		// $type is the type of change (install, update or discover_install)

        echo '<p><b>' . 'NOTICE: GPDR info and label texts have been moved to the menu-item options to allow for localization.' .'</p></b></br>';

        echo '<p>' . JText::_('COM_ALFCONTACT_POSTFLIGHT_' . $type . '_TEXT') . '</p>';
	}
}