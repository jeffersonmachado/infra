<?php
/**
 * @version $Id: jmplupload.php 163 2017-10-17 12:48:27Z szymon $
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
 * Form field that renders PLUploader for uploading files in the back-end
 */
class JFormFieldJmplupload extends JFormField
{
		protected $type = 'Jmplupload';

		protected function getInput()
		{
			JHtml::_('jquery.framework');
			JHtml::_('script', 'system/html5fallback.js', false, true);

			$path = JUri::root().'plugins/system/ef4_jmframework/includes/assets/admin/formfields/jmplupload';

			$document = JFactory::getDocument();
			$document->addScript($path.'/js/plupload.full.min.js');

			$browse_button = (!empty($this->element['browse_button'])) ? JText::_($this->element['browse_button']) : JText::_('PLG_SYSTEM_JMFRAMEWORK_PLUPLOAD_BROWSE');
			$upload_button = (!empty($this->element['upload_button'])) ? JText::_($this->element['upload_button']) : JText::_('PLG_SYSTEM_JMFRAMEWORK_PLUPLOAD_UPLOAD');

			$browse_button_id 	= $this->id.'_browse';
			$upload_button_id 	= $this->id.'_upload';
			$container_id 		= $this->id.'_container';
			$filelist_id 		= $this->id.'_files';
			$console_id 		= $this->id.'_console';

			$flash_url = $path.'/js/Moxie.swf';
			$silverlight_url = $path.'/js/Moxie.xap';

			$extensions = (!empty($this->element['extensions'])) ? $this->element['extensions'] : 'json,svg,eot,woff,ttf,otf,zip,jpg,jpeg,png,css';

			$uri = JUri::getInstance();

			$myuri = new JUri($uri->toString());
			$myuri->setVar('jmajax', 'plupload');
			$myuri->setVar('jmtask', (string)$this->element['task']);
			$myuri->setVar('jmpluploadid', $this->id);

			$url = $myuri->toString();

			//JURI::reset();

			$js ='
					jQuery(document).ready(function(){
						var JMPLUpload_'.$this->id.' = new plupload.Uploader({
						runtimes : \'html5,flash,silverlight,html4\',
						browse_button : \''.$browse_button_id.'\',
						container: \''.$container_id.'\',
						url : \''.$url.'\',
						flash_swf_url : \''.$flash_url.'\',
						silverlight_xap_url : \''.$silverlight_url.'\',

						filters : {
							max_file_size : \'10mb\',
							mime_types: [
								{title : "Allowed files", extensions : "'.$extensions.'"}
							]
						},

						init: {
							PostInit: function() {
								document.getElementById(\''.$filelist_id.'\').innerHTML = \'\';

								document.getElementById(\''.$upload_button_id.'\').onclick = function() {
									JMPLUpload_'.$this->id.'.start();
									return false;
								};
							},

							FilesAdded: function(up, files) {
								plupload.each(files, function(file) {
									document.getElementById(\''.$filelist_id.'\').innerHTML += \'<div id="\' + file.id + \'">\' + file.name + \' (\' + plupload.formatSize(file.size) + \') <b></b></div>\';
									document.getElementById(\''.$upload_button_id.'\').parentElement.style.display = "block";
								});
							},

							UploadProgress: function(up, file) {
								document.getElementById(file.id).getElementsByTagName(\'b\')[0].innerHTML = \'<span>\' + file.percent + "%</span>";
							},

							Error: function(up, err) {
								document.getElementById(\''.$console_id.'\').innerHTML += "\nError #" + err.code + ": " + err.message;
							},

							UploadComplete: function(up, file, undef) {
								jQuery(document).trigger("jmplupload_'.$this->id.'", [up, file, undef]);
								jQuery(\'#'.$console_id.'\').prepend("<span class=\'jm-alert alert alert-success\'>'.JText::_('PLG_SYSTEM_JMFRAMEWORK_CONFIG_UPLOAD_COMPLETE_INFO').'</span>");
								setTimeout(function(){ jQuery(\'#'.$console_id.' .jm-alert\').remove() }, 10000);
							}
						}
					});

					JMPLUpload_'.$this->id.'.init();
					});
					';

			$document->addScriptDeclaration($js);

			$html = '
				<div id="'.$container_id.'">
						<span id="'.$browse_button_id.'" class="button btn">'.$browse_button.'</span>
						<div class="jm-upload-wraper" style="display: none;">
							<p id="'.$filelist_id.'" class="jm-upload-filelist">Your browser doesn\'t have Flash, Silverlight or HTML5 support.</p>
							<span id="'.$upload_button_id.'" class="button btn">'.$upload_button.'</span>
							<p id="'.$console_id.'" class="jm-upload-console"></p>
						</div>
				</div>
			';

			return $html;
		}
}
