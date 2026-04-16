<?php
/**
 * @package JMFramework
 * @copyright Copyright (C) 2020 DJ-Extensions.com, All rights reserved.
 * @license http://www.gnu.org/licenses GNU/GPL
 * @author url: https://dj-extensions.com
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

defined('_JEXEC') or die('Restricted access');

class plgSystemEF4_JMFramework extends JPlugin
{
	private $template;
	private $updatesURL = 'https://www.joomla-monster.com/updates/templates.xml';
	protected $debug = false;
	protected $_debug = array();
	
	public function __construct(&$subject, $config = array()) {
		if (!defined('DS')) {
			define('DS', DIRECTORY_SEPARATOR);
		}
		parent::__construct($subject, $config);
		
	}
	
	/**
	 * Check ordering of the plugins
	 */
	function onAfterInitialise(){
		
		$app = JFactory::getApplication();
		$db = JFactory::getDbo();
		
		// check and change the order of the plugins only in the back-end
		if($app->isAdmin()) {
			
			$db->setQuery("SELECT extension_id, element, ordering FROM #__extensions WHERE folder='system' AND element IN ('ef4_jmframework','djjquerymonster')");
			$plugins = $db->loadObjectList('element');
			
			if(isset($plugins['djjquerymonster'])) {
				
				if($plugins['djjquerymonster']->ordering >= $plugins['ef4_jmframework']->ordering) {
					
					$db->setQuery("UPDATE #__extensions SET ordering=".($plugins['ef4_jmframework']->ordering - 1)." WHERE extension_id=".$plugins['djjquerymonster']->extension_id);
					$db->query();
					//$app->enqueueMessage('DJ-jQueryMonster plugin order ');
				}
			}
		} /*else if(JPluginHelper::isEnabled('system', 'cache')) {
		// If system page cache plugin is enabled we need to force to disable it when Theme Customizer is enabled
		$tc = $app->input->post->get('tc','-1');
		if($tc=='-1') $tc = $app->getUserState('themer.switch');
		if($tc === '1') {
		$app->enqueueMessage('Page cache disabled');
		}
		}*/
	}
	
	/**
	 *
	 * We need to specially prepare the form because we're merging templateDetails.xml from a template and params.xml from the plugin.
	 * @param JForm $form
	 * @param mixed $data
	 */
	function onContentPrepareForm($form, $data)
	{
		$app = JFactory::getApplication();
		$doc = JFactory::getDocument();
		$this->template = $this->getTemplateName();
		
		if ($this->template && ( ($app->isAdmin() && $form->getName() == 'com_templates.style') || ($app->isSite() && ($form->getName() == 'com_config.templates' || $form->getName() == 'com_templates.style')) )) {
			jimport('joomla.filesystem.path');
			//JForm::addFormPath( dirname(__FILE__) . DS. 'includes' . DS .'assets' . DS . 'admin' . DS . 'params');
			$plg_file = JPath::find(dirname(__FILE__) . DS. 'includes' . DS .'assets' . DS . 'admin' . DS . 'params', 'template.xml');
			$tpl_file = JPath::find(JPATH_ROOT . DS. 'templates' . DS . $this->template, 'templateDetails.xml');
			$default_settings_file = JPATH_ROOT . DS. 'templates' . DS . $this->template . DS . 'templateDefaults.json';
			
			if (!$plg_file) {
				return false;
			}
			
			// params.xml should be loaded first and templateDetails.xml afterwards
			if ($tpl_file) {
				$form->loadFile($plg_file, false, '//form');
				$form->loadFile($tpl_file, false, '//config');
				$form->loadFile($tpl_file, false, '//tplconfig//fields[@name="params"]');
			} else {
				$form->loadFile($plg_file, false, '//form');
			}
			
			// for users' own safety, we don't allow some things to be changed in the front-end
			if ($app->isSite()) {
				$jmstorage_fields = $form->getFieldset('jmstorage');
				foreach ($jmstorage_fields as $name => $field){
					$form->removeField($name, 'params');
				}
				$form->removeField('config', 'params');
				
				$jmlayoutbuilder_fields = $form->getFieldset('jmlayoutbuilder');
				foreach ($jmlayoutbuilder_fields as $name => $field){
					$form->removeField($name, 'params');
				}
				$form->removeField('layout', 'params');
			}
			
			// Hiding a notice to enable this plugin. If plugin is disabled then the notice is visible. That's it.
			if ($app->isAdmin()) {
				$doc->addStyleDeclaration('#jm-ef3plugin-info, .jm-row > .jm-notice {display: none !important}');
			}
			
			if (JFile::exists($default_settings_file)) {
				$settings_json = JFile::read($default_settings_file);
				if ($settings_json) {
					$defaults = json_decode($settings_json, true);
					if ($defaults && is_array($defaults)) {
						foreach ($form->getFieldset() as $field) {
							$field_name = $field->__get('fieldname');
							if (array_key_exists($field_name, $defaults) && is_scalar($defaults[$field_name])) {
								$form->setFieldAttribute($field_name, 'default', $defaults[$field_name], $field->__get('group'));
							}
						}
						/*if (!empty($data) && isset($data->params)) {
						 foreach ($data->params as $param_name => $param_value) {
						 if (empty($param_value) && array_key_exists($param_name, $defaults)  && is_scalar($defaults[$param_name])) {
						 $data->params[$param_name] = $defaults[$param_name];
						 }
						 }
						 }*/
					}
				}
			}
		}
	}
	
	/**
	 *
	 * Preparing default values
	 * @param string $context
	 * @param mixed $data
	 */
	function onContentPrepareData($context, $data)
	{
		$app = JFactory::getApplication();
		$doc = JFactory::getDocument();
		$this->template = $this->getTemplateName();
		
		if ($this->template && ( ($app->isAdmin() && $context == 'com_templates.style') || ($app->isSite() && $context == 'com_config.templates') )) {
			jimport('joomla.filesystem.path');
			
			$default_settings_file = JPATH_ROOT . DS. 'templates' . DS . $this->template . DS . 'templateDefaults.json';
			
			if (JFile::exists($default_settings_file)) {
				$settings_json = JFile::read($default_settings_file);
				if ($settings_json) {
					
					$defaults = json_decode($settings_json, true);
					if ($defaults && is_array($defaults)) {
						if (!empty($data) && isset($data->params)) {
							
							if (!is_array($data->params)) {
								if (is_object($data->params)) {
									$data->params = JArrayHelper::fromObject($data->params);
								} else {
									$data->params = array();
								}
							}
							
							foreach ($defaults as $param_name => $param_value) {
								if (empty($data->params[$param_name])) {
									$data->params[$param_name] = $defaults[$param_name];
								}
							}
						}
					}
				}
			}
		}
	}
	
	
	/**
	 * After the routing we can determine which template is being used.
	 * The plugin works only with specially prepared Joomla Monster templates.
	 */
	function onAfterRoute(){
		$app = JFactory::getApplication();

		if ($this->params->get('cfg_check_updates', true)) {
			$this->checkUpdates();
		}
		
		// If it's not Joomla Monster template, the $template will be false.
		$template = $this->getTemplateName();
		if ($template) {
			
			// This plugin's directory
			define('JMF_FRAMEWORK_PATH', dirname(__FILE__));
			
			// Plugin's URL
			define('JMF_FRAMEWORK_URL', JURI::root(true).'/plugins/system/ef4_jmframework');
			
			// Name of the template
			define('JMF_TPL', $template);
			
			// Path to template's directory
			define('JMF_TPL_PATH', JPATH_ROOT.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.$template);
			
			// Template directory's URL
			define('JMF_TPL_URL', JURI::root(true). '/templates/' . $template);
			
			// Flag that informs that plugin is active
			define('JMF_EXEC', 'JMF');
			
			// Admin assets' URL
			define('JMF_ASSETS', JURI::root(true).'/plugins/system/ef4_jmframework/includes/assets/admin/');
			
			// Flag for DJ-jQueryMonster plugin compatibility
			define('JMF_JQUERYMONSTER', 1);
			
			// Clearing ThemeCustomiser settings and removing data from local storage.
			// The sooner we do that the better. Afterwards user will be redirected to a proper URL - without "tcr" parameter
			if ($app->input->getInt('tcr') == 1) {
				$app->setUserState(JMF_TPL.'.themer.state', null);
				JURI::reset();
				$uri = JURI::getInstance();
				$uri->delVar('tcr');
				$redir = $uri->toString();
				JURI::reset();
				
				$html = '';
				$redirScr = "
				if (typeof(window.localStorage) !== 'undefined') {
					try {
						window.localStorage.clear();
					} catch(_) {}
				}
				document.location.href='" . str_replace("'", '&apos;', $redir) . "';
				";
				
				if (headers_sent()) {
					$html = '<script>'.$redirScr.'</script>';
				} else {
					$html = '<html><head>';
					$html .= '<meta http-equiv="content-type" content="text/html; charset=' . JFactory::getApplication()->charSet . '" />';
					$html .= '<script>'.$redirScr.'</script>';
					$html .= '</head><body></body></html>';
				}
				
				echo $html;
				
				$app->close();
				return true;
			}
			
			$this->loadLanguage();
			
			$this->template = $template;
			
			if ($app->isSite()) {
				require_once dirname(__FILE__).DIRECTORY_SEPARATOR.'includes'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'template.php';
				include_once JMF_TPL_PATH.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'jm_template.php';
				$className = false;
				if (class_exists('JMTemplate')) {
					$className = 'JMTemplate';
				} else if (class_exists('JMTemplate'.ucfirst(str_replace('-', '', JMF_TPL)))) {
					$className = 'JMTemplate'.ucfirst(str_replace('-', '', JMF_TPL));
				}
				
				$lang = JFactory::getLanguage();
				
				$lang->load('tpl_'.$this->template, JPATH_ADMINISTRATOR, 'en-GB', false, true)
				||  $lang->load('tpl_'.$this->template, JMF_TPL_PATH, 'en-GB', false, true);
				
				$lang->load('tpl_'.$this->template, JPATH_ADMINISTRATOR, null, true, true)
				||  $lang->load('tpl_'.$this->template, JMF_TPL_PATH, null, true, true);
				
				if ($className !== false) {
					$doc = JFactory::getDocument();
					if ($doc instanceof JDocumentHTML) {
						$jmf = new $className($doc, true);
						$jmf->ajax(); // check for ajax requests
					}
				}
				
				$this->debug = $app->getTemplate(true)->params->get('debug', 0) ? true : false;
				
			} else {
				require_once dirname(__FILE__).DIRECTORY_SEPARATOR.'includes'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'template.php';
				require_once dirname(__FILE__).DIRECTORY_SEPARATOR.'includes'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'admin.php';
				$doc = JFactory::getDocument();
				$jmf = new JMFAdminTemplate($doc);
				$jmf->ajax(); // check for ajax requests
				
				$queue = $app->getMessageQueue();
				
				if ($app->input->getString('view') == 'style'
					&& $app->input->getString('option') == 'com_templates'
					&& $app->input->getCmd('jmajax', false) == false
					&& $app->input->getCmd('jmtask', false) == false) {
						
						if ($jmf->params->get('devmode', false)) {
							$app->enqueueMessage(JText::_('PLG_SYSTEM_JMFRAMEWORK_WARNING_DEV_MODE_ENABLED'), 'message');
						}
						$unwritable = $this->checkTemplateFolders();
						if ($unwritable && is_array($unwritable)) {
							$message = JText::_('PLG_SYSTEM_JMFRAMEWORK_WARNING_FOLDER_ISSUES');
							foreach ($unwritable as $folder) {
								$message .= '<br />' . $folder;
							}
							$app->enqueueMessage($message, 'error');
						}
					}
			}
		}
	}
	
	/**
	 * Loading template's language file
	 */
	function onAfterRender() {
		$app = JFactory::getApplication();

		if (!$this->template) {
			return;
		}
		
		if ($app->isAdmin()) {
			$this->loadLanguage('tpl_'.$this->template, JPATH_ROOT);
			return;
		}
		
		$params = $app->getTemplate(true)->params;
		$htmlCompress = (int)$params->get('htmlCompress', 0);
		$lazyLoading = (int)$params->get('lazyLoading', 0);

		$documentFormat = $app->input->getCmd('format', 'html');
		
		// preparing images and iframes tags for lazy loading
		if($lazyLoading && $app->input->get('tmpl')!='component' && ($documentFormat == 'html' || is_null($documentFormat))) {

			//$timer_start = microtime();

			$body = JResponse::getBody();

			$skips = explode("\n", $params->get('lazyExclude'));
			$skips[] = 'data-src='; // djmediatools or other with own lazy images loading implemented
			$skips[] = 'data-lazy=';
			$skips[] = 'lazyOff'; // class name to disable lazy images loading

			// Images lazy loading
			preg_match_all('/<img[^>]*>/i', $body, $matches);

			$srv_host = parse_url(str_ireplace('//www.', '//', JURI::root()), PHP_URL_HOST);

			//supported images extensions
			$images = array(
				'jpg',
				'jpeg',
				'png',
				'gif',
				'bmp',
				'webp',
				'tiff',
				'swf'
			);

			foreach($matches[0] as $key => $img) {

				$exclude = false;

				foreach($skips as $skip) {
					$skip = trim($skip);
					if(empty($skip)) continue;
					if(stripos($img, $skip) !== FALSE) {
						$exclude = true;
						break;
					}
				}

				if($exclude) continue;

				$size = false;
		
				// get the src of the image
				preg_match('#\ssrc="(/|[a-zA-Z0-9\-]+:)?([^"]+)"#', $img, $match);
				if( empty($match[2]) ) continue;
				
				$src = $match[1].$match[2];
				$path = parse_url($src, PHP_URL_PATH);
				$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

				if( in_array($ext, $images) ) {
					$path = str_replace(JURI::root(true), '', $path);
					$path = JPath::clean(JPATH_ROOT.'/'.$path);

					if( filter_var($src, FILTER_VALIDATE_URL) || substr($src, 0, 2) === '//' ) { // http(s) urls
						$src_host = parse_url(str_ireplace('//www.', '//', $src), PHP_URL_HOST);
						if ( strcasecmp($srv_host, $src_host) == 0 && file_exists($path) ) {
							$size = @getimagesize($path);
						}
					} else { // local images
						// add root to the src
						if( empty($match[1]) ) {
							$src = JURI::root(true).'/'.$src;
						}
	
						if( file_exists($path) ) {
							$size = @getimagesize($path);
						}
					}
				}

				// we create the svg inline blank image with proper dimensions and aspect ratio
				if( !empty($size) ) {
					// skip small images
					if((int)$size[0] < (int)$params->get('lazyWidth', 50) || (int)$size[1] < (int)$params->get('lazyHeight', 50)) continue;
					$lazyimg = ' src="data:image/svg+xml,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22'.$size[0].'%22%20height%3D%22'.$size[1].'%22%20viewBox%3D%220%200%20'.$size[0].'%20'.$size[1].'%22%2F%3E" data-original="'.$src.'" loading="lazy"';
				} else { // svg without dimensions
					$lazyimg = ' src="data:image/svg+xml,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%221%22%20height%3D%221%22%20viewBox%3D%220%200%201%201%22%2F%3E" data-original="'.$src.'" loading="lazy"';
				}

				$lazyimg = str_replace($match[0], $lazyimg, $img);
				$body = str_replace($img, $lazyimg, $body);
			}
			
			// Iframe lazy loading
			preg_match_all('/<iframe[^>]*>/i', $body, $matches);
			
			$blank = ' src="about:blank"';

			foreach($matches[0] as $iframe) {
					
				$exclude = false;
				foreach($skips as $skip) {
					$skip = trim($skip);
					if(empty($skip)) continue;
					if(stripos($iframe, $skip) !== FALSE) {
						$exclude = true;
						break;
					}
				}
				if($exclude) continue;
					
				preg_match('/\ssrc="([^"]+)"/', $iframe, $match);
				if(empty($match[1])) continue;
				$src = $match[1];
		
				$lazyiframe = $blank.' data-original="'.$src.'"';
				$lazyiframe = str_replace($match[0], $lazyiframe, $iframe);
				$body = str_replace($iframe, $lazyiframe, $body);
			}
			
			JResponse::setBody($body);
			
			//$timer_stop = microtime();
			//die($timer_stop - $timer_start);
		}
				
		if ($htmlCompress > 0 && ($documentFormat == 'html' || is_null($documentFormat))) {
			if (version_compare(JVERSION, '3.2.3', '>=')) {
				$html = $app->getBody();
			} else {
				$html = JResponse::getBody();
			}
			
			if (!class_exists('Minify_HTML')) {
				require_once JPath::clean(JMF_FRAMEWORK_PATH.'/includes/libraries/minify/HTMLMin.php');
			}
			
			$options = array();
			$options['jsCleanComments'] = true;
			
			$this->debug('Response size before HTML compression', number_format(strlen($html) / 1024, 2) .' KB');
			
			try {
				$optimizedHtml = Minify_HTML::minify($html, $options);
			} catch(Exception $e) {
				$optimizedHtml = $html;
			}
			
			$this->debug('Response size after HTML compression', number_format(strlen($optimizedHtml) / 1024, 2) .' KB');
			
			/*if ($htmlCompress > 1) {
			 $optimizedHtml = str_replace("\n", ' ', $optimizedHtml);
			 }*/
			
			if (version_compare(JVERSION, '3.2.3', '>=')) {
				$app->setBody($optimizedHtml);
			} else {
				JResponse::setBody($optimizedHtml);
			}
		}
		
		if($this->debug && ($documentFormat == 'html' || is_null($documentFormat))) {
			$body = JResponse::getBody();
			$this->renderDebug($body);
			JResponse::setBody($body);
		}
	}
	
	/**
	 * Adding some scripts required in template's configuration
	 */
	function onBeforeRender(){
		$app = JFactory::getApplication();
		$template = $this->getTemplateName();

		if($app->input->get('tmpl') == 'component') return;

		if ($template && ($app->isAdmin() || ($app->input->get('option') == 'com_config' && $app->input->get('view') == 'templates' ) )) {
			
			$document = JFactory::getDocument();
			
			if ($app->isAdmin()) {
				$document->addStyleSheet(JMF_ASSETS . 'css/admin.css');
			}
			$document->addScript(JMF_ASSETS . 'js/jmoptiongroups.js');
			//$document->addScript(JMF_ASSETS . 'js/jmspacer.js');
			//$document->addScript(JMF_TPL_ASSETS . 'js/jmconfig.js');
			$document->addScript(JMF_ASSETS . 'js/jscolor.js');
			//$document->addScript(JMF_ASSETS . 'js/misc.js');
			
			//$document->addScript('http://code.jquery.com/jquery-latest.js');
		}
		
	}
	
	/**
	 * Here go all the actions that have to be performed right before document's HEAD has been rendered.
	 */
	function onBeforeCompileHead(){
		
		$app = JFactory::getApplication();
		$document = JFactory::getDocument();
		
		// Don't proceed when current template is not compatible with EF4 Framework or we are in the Joomla back-end
		if (empty($this->template) || $app->isAdmin()) {
			return true;
		}

		// Set HTML5 Document Output
		$document->setHtml5(true);

		$params = $app->getTemplate(true)->params;
		
		// Handling Facebook's Open Graph
		if ((bool)$params->get('facebookOpenGraph', true)) {
			$fbAppId = $params->get('facebookOpenGraphAppID', false);
			$this->addOpenGraph($fbAppId);
		}

		$template_name = JFactory::getApplication()->getTemplate();

		// Add Google Web Fonts as local fonts
		if ($params->get('localGoogleFonts', false) && $app->isSite()) {
			$loaded_files = $document->_styleSheets;
			$loaded_files_keys = array_keys($loaded_files);

			$newCSS = array();
			foreach($loaded_files_keys as $script) {
				if (strpos($script, 'fonts.googleapis.com') !== false && strpos($script, '//fonts.googleapis.com/icon?family=Material+Icons') === false) {
					$files = array();
					$url = parse_url($script, PHP_URL_QUERY);
					parse_str($url, $urlParams);
					if (isset($urlParams['family'])) {
						$md5cssURI = JURI::base().'templates/'.$template_name.'/cache/'.md5($urlParams['family']).'.css';
						$md5css = JPATH_ROOT.'/templates/'.$template_name.'/cache/'.md5($urlParams['family']).'.css';
						if (!file_exists($md5css)) {
							if ($url = parse_url($script)) {
								if (!isset($url['scheme'])) {
									$script = "https:{$script}";
								}
							}
							$content = $this->httpGetContents($script);
							$exp = explode("\n", $content);
							foreach ($exp as $line) {
								if (strpos($line, 'url(') !== false) {
									$files[] = $this->getStringBetween($line, 'url(', ')');
								}
							}
							foreach ($files as $file) {
								$exp = explode('/', $file);
								$rfile = $exp[count($exp) - 1];
								if (!file_exists(JPATH_ROOT.'/templates/'.$template_name.'/cache/' . $rfile)) {
									JFile::write(JPATH_ROOT.'/templates/'.$template_name.'/cache/' . $rfile, $this->httpGetContents($file));
								}
								$content = str_replace($file, JURI::base().'templates/'.$template_name.'/cache/'.$rfile, $content);
							}
							JFile::write($md5css, $content);
						}
						unset($loaded_files[$script]);
						$newCSS[] = $md5cssURI;
					}
				}
			}
			$document->_styleSheets = $loaded_files;
			foreach($newCSS as $css) {
				$document->addStyleSheet($css);
			}
		}

		// Removing obsolete CSS stylesheets
		$css_to_remove = $app->get('jm_remove_stylesheets', array());
		if (!empty($css_to_remove) && is_array($css_to_remove)) {
			foreach($document->_styleSheets as $url => $cssData) {
				foreach($css_to_remove as $oursUrl => $replacement) {
					if (strpos($url, $oursUrl) !== false) {
						unset($document->_styleSheets[$url]);
						if ($replacement && is_array($replacement) && isset($replacement['url']) && isset($replacement['type'])) {
							switch($replacement['type']) {
								case 'css' : $document->addStyleSheet($replacement['url'], 'text/css'); break;
								case 'less' : $document->addHeadLink($replacement['url'], 'stylesheet/less'); break;
								default: break;
							}
						}
					}
				}
			}
			$app->set('jm_remove_stylesheets', false);
		}
		
		$themer = false;
		if ($tpl = JMFTemplate::getInstance()) {
			$themer = ($tpl->params->get('themermode', false) == '1') ? true : false;
			$customLess = JPath::clean(JMF_TPL_PATH.'/less/custom.less');
			$customCss = JPath::clean(JMF_TPL_PATH.'/css/custom.css');
			if (JFile::exists($customLess)) {
				$tpl->addCompiledStyleSheet($customLess, true, $themer);
			} else if (JFile::exists($customCss)) {
				$tpl->addStyleSheet(JMF_TPL_URL.'/css/custom.css');
			}
			$customJs = JPath::clean(JMF_TPL_PATH.'/js/custom.js');
			if (JFile::exists($customJs)) {
				$tpl->addScript(JMF_TPL_URL.'/js/custom.js');
			}
		}
		
		$cssCompress = $params->get('cssCompress','0')=='1' ? true : false;
		$jsCompress = $params->get('jsCompress','0')=='1' ? true : false;
		
		// Don't compress CSS/JS when Development Mode or Joomla Debugging is enabled
		if($themer || $params->get('devmode',0) || JDEBUG || $app->input->get('option')=='com_config') {
			return true;
		}
		
		$this->debug('onBeforeCompileHead event START');
		
		if($jsCompress) { // it's used only for backward compatibility with DJ-jQueryMonster plugin less than 1.3.1
			$scripts = $document->_scripts;
			$newscripts = array();
			foreach($scripts as $url => $data) {
				// remove DJ-jQueryMonster placeholder for compressed javascript
				if(strstr($url, 'DJHOLDER_EF4COMPRESS') === false) {
					$newscripts[$url] = $data;
				}
			}
			$document->_scripts = $newscripts;
		}
		
		// Defer scripts loading excluding the jquery, mootools and selected scripts
		$canDefer = preg_match('/(?i)msie [6-9]/',$_SERVER['HTTP_USER_AGENT']) ? false : $params->get('jsDefer','0')=='1';
		//$excludeViews = array();//array('edit','form','additem','itemform','cart','checkout','contact','profileedit','renewitem','query');
		if($canDefer) { //&& !in_array($app->input->get('view'), $excludeViews)) {
			
			$scripts = $document->_scripts;
			$newscripts = array();
			$skips = explode("\n", $params->get('skipDefer'));
			$skips[] = 'DJHOLDER_JQUERY';
			$skips[] = 'DJHOLDER_NOCONFLICT';
			$skips[] = 'DJHOLDER_EF4COMPRESS';
			$skips[] = 'media/jui/js/jquery.min.js';
			$skips[] = 'media/jui/js/jquery-noconflict.js';
			$skips[] = 'media/system/js/mootools-core.js';
			$skips[] = 'media/system/js/core.js';
			$skips[] = 'media/system/js/calendar.js';
			$skips[] = 'media/system/js/calendar-setup.js';
			$skips[] = 'media/editors';
			$skips[] = 'components/com_jce/editor';
			$skips[] = 'components/com_virtuemart/assets/js';
			$skips[] = 'modules/mod_virtuemart_cart/assets/js';
			$skips[] = '//maps.google.com/maps/api/js';
			
			$_defered = array();
			$_nodefered = array();
			
			foreach($scripts as $url => $data) {
				
				$defer = true;
				// skip excluded scripts from defer loading
				foreach($skips as $skip) {
					$skip = trim($skip);
					if(empty($skip)) continue;
					//$this->debug("URL: ".$url."\nSKIP: ".$skip."\nCMP: ".(strstr($url, $skip)!==false ? 'TRUE':'FALSE'));
					if(stristr($url, $skip)!==false) {
						$defer = false;
						break;
					}
				}
				if($defer) {
					if(isset($data['defer']) && $data['defer'] != true) $_defered[] = $url;
					$data['defer'] = true;
				} else {
					$_nodefered[] = $url;
				}
				$newscripts[$url] = $data;
			}
			
			$this->debug('Defer attribute added to the following scripts', $_defered);
			$this->debug('Following scripts are excluded from defer loading', $_nodefered);
			
			$document->_scripts = $newscripts;
		}
		
		// Preparing cache folder for CSS/JS compressed files
		if (JFolder::exists(JMF_TPL_PATH.DIRECTORY_SEPARATOR.'cache') == false) {
			if (!JFolder::create(JMF_TPL_PATH.DIRECTORY_SEPARATOR.'cache')) {
				if (JDEBUG) {
					throw new Exception(JText::_('PLG_SYSTEM_JMFRAMEWORK_CACHE_FOLDER_NOT_ACCESSIBLE'));
				} else {
					return false;
				}
			}
		}
		
		// Handling CSS minifications and compression.
		if($cssCompress) {
			
			$styles = $document->_styleSheets;
			$compress = array();
			$mtime = 0;
			
			//$this->debug('JDocument::_styleSheets array', $styles);
			$this->debug('JDocument::_styleSheets array before compression', array_keys($styles));
			
			foreach($styles as $url => $style) {
				
				// Getting stylesheet path
				$path = $this->getPath($url);
				if(!$path || !JFile::exists($path)) continue;
				
				// Getting the last modification time of stylesheet
				$ftime = filemtime($path);
				if($ftime > $mtime) $mtime = $ftime;
				
				$compress[$url] = $path;
			}
			
			$this->debug('Style sheets to be compressed and merged', array_keys($compress));
			
			$key = md5(serialize($compress));
			
			$stylepath = JPath::clean(JMF_TPL_PATH.'/cache/jmf_'.$key.'.css');
			$cachetime = JFile::exists($stylepath) ? filemtime($stylepath) : 0;
			$styleurl  = JMF_TPL_URL.'/cache/jmf_'.$key.'.css';
			
			// Minify and merge stylesheets only if minified stylesheet isn't cached already or one of the stylesheets was modified
			if(!JFile::exists($stylepath) || $mtime > $cachetime) {
				
				require_once JPath::clean(JMF_FRAMEWORK_PATH.'/includes/libraries/minify/CSSmin.php');
				$cssmin = new CSSmin();
				$css = array();

				foreach($compress as $url => $path) {
					$src = JFile::read($path);
					$src = $this->updateUrls($src, dirname($url));
					$css[] = $cssmin->run($src, 1024);
				}
				
				$css = implode("\n", $css);
				JFile::write($stylepath, $css);
				
				$this->debug('New merged style sheet has been created', $styleurl);
			} else {
				$this->debug('Merged style sheet exists and it\'s up to date', $styleurl);
			}
			
			// Removing all merged stylesheets from the head and adding the minified stylesheet instead
			if(JFile::exists($stylepath)) {
				
				$newstyles = array();
				if($app->input->get('inlinecss')=='1') {
					$document->_style['text/css'] .= file_get_contents($stylepath);
				} else {
					$newstyles[$styleurl.'?v='.$mtime] = array('mime' => 'text/css');
				}
				
				foreach ($styles as $url => $data) {
					if(!array_key_exists($url, $compress)) $newstyles[$url] = $data;
				}
				
				$this->debug('JDocument::_styleSheets array after compression', array_keys($newstyles));
				
				$document->_styleSheets = $newstyles;
			}
			
		}
		
		// Handling JS minifications and compression.
		if($jsCompress) {
			
			require_once JPath::clean(JMF_FRAMEWORK_PATH.'/includes/libraries/minify/JSMin.php');
			
			$scripts = $document->_scripts;
			$newscripts = array();
			$compress = array('noattr' => array(), 'async' => array(), 'defer' => array());
			$mtime = array('noattr' => 0, 'async' => 0, 'defer' => 0);
			
			//$this->debug('JDocument::_scripts array', $scripts);
			$this->debug('JDocument::_scripts array before compression', array_keys($scripts));
			
			foreach($scripts as $url => $data) {
				
				// Getting script path
				$path = $this->getPath($url);
				if(!$path) { // external or excluded from merging
					if(count($compress['noattr'])) {
						$this->debug('Scripts to be compressed and merged', array_keys($compress['noattr']));
						$mergedUrl = $this->compressJS($compress['noattr'], $mtime['noattr']);
						$newscripts[$mergedUrl] = array('mime' => 'text/javascript', 'defer' => false, 'async' => false);
						$compress['noattr'] = array();
						$mtime['noattr'] = 0;
					}
					$newscripts[$url] = $data;
					continue;
				} else if(!JFile::exists($path)) continue;
				
				$idx = isset($data['async']) && $data['async'] ? 'async' : (isset($data['defer']) && $data['defer'] ? 'defer' : 'noattr');

				// Getting the last modification time of script
				$ftime = filemtime($path);
				if($ftime > $mtime[$idx]) $mtime[$idx] = $ftime;
				
				$compress[$idx][$url] = $path;
			}
			
			if(count($compress['noattr'])) {
				$this->debug('Scripts to be compressed and merged', array_keys($compress['noattr']));
				$mergedUrl = $this->compressJS($compress['noattr'], $mtime['noattr']);
				$newscripts[$mergedUrl] = array('mime' => 'text/javascript', 'defer' => false, 'async' => false);
			}
			
			if(count($compress['defer'])) {
				$this->debug('Scripts with DEFER attribute to be compressed and merged', array_keys($compress['defer']));
				$mergedUrl = $this->compressJS($compress['defer'], $mtime['defer']);
				$newscripts[$mergedUrl] = array('mime' => 'text/javascript', 'defer' => true, 'async' => false);
			}
			
			if(count($compress['async'])) {
				$this->debug('Scripts with ASYNC attribute to be compressed and merged', array_keys($compress['async']));
				$mergedUrl = $this->compressJS($compress['async'], $mtime['async']);
				$newscripts[$mergedUrl] = array('mime' => 'text/javascript', 'defer' => true, 'async' => true);
			}
			
			$this->debug('JDocument::_scripts array after compression', array_keys($newscripts));
			
			$document->_scripts = $newscripts;
		}
		
		$this->debug('onBeforeCompileHead event END');
	}
	
	/**
	 * Merging and compressing passed scripts into one script and returning url of merged script with timestamp
	 * @param array $scripts
	 * @param int $mtime
	 * @return string
	 */
	function compressJS($scripts, $mtime) {
		
		$key = md5(serialize($scripts));
		
		$scriptpath = JPath::clean(JMF_TPL_PATH.'/cache/jmf_'.$key.'.js');
		$cachetime = JFile::exists($scriptpath) ? filemtime($scriptpath) : 0;
		$scripturl  = JMF_TPL_URL.'/cache/jmf_'.$key.'.js?v='.$mtime;
		
		// Minify and merge scripts only if minified script isn't cached already or one of the scripts was modified
		if(!JFile::exists($scriptpath) || $mtime > $cachetime) {
			
			$js = array();

			foreach($scripts as $url => $path) {
				$src = JFile::read($path);
				$js[] = \JShrink\Minifier::minify($src).';';
			}
			
			$js = implode("\n", $js);
			JFile::write($scriptpath, $js);
			$this->debug('New merged script has been created', $scripturl);
		} else {
			$this->debug('Merged script exists and it\'s up to date', $scripturl);
		}
		
		return $scripturl;
	}
	
	/**
	 * Updating the URLs inside stylesheets for compatibility with minified stylesheet location
	 */
	function updateUrls($src, $url){
		
		$app = JFactory::getApplication();
		
		// make sure url is root relative or absolute
		$url = ($url[0] === '/' || strpos($url, '://') !== false) ? $url : JURI::root(true) . '/' . $url;
		
		// replace image urls
		preg_match_all('/url\\(\\s*([^\\)\\s]+)\\s*\\)/', $src, $matches, PREG_SET_ORDER);
		
		foreach($matches as $match) {
			
			$uri = $match[1];
			
			if($uri[0] === "'" || $uri[0] === '"') {
				$uri = substr($uri, 1, strlen($uri) - 2);
			}
			
			if ($uri[0] !== '/' && strpos($uri, '://') === false && strpos($uri, 'data:') !== 0) {
				
				$uri = $url . '/' . $uri;
				// replace the url
				$src = str_replace($match[0], "url('$uri')", $src);
			}
		}
		
		// replace imported stylesheet urls
		preg_match_all('/@import\\s+[\'"](.*?)[\'"]/', $src, $matches, PREG_SET_ORDER);
		
		foreach($matches as $match) {
			
			$uri = $match[1];
			
			if($uri[0] === "'" || $uri[0] === '"') {
				$uri = substr($uri, 1, strlen($uri) - 2);
			}
			
			if ($uri[0] !== '/' && strpos($uri, '://') === false && strpos($uri, 'data:') !== 0) {
				
				$uri = $url . '/' . $uri;
				// replace the url
				$src = str_replace($match[0], "@import '$uri'", $src);
			}
		}
		
		return $src;
	}
	
	/**
	 * Getting the fixed path to the CSS/JS file which is allowed to be merged
	 */
	function getPath($url) {
		
		$app = JFactory::getApplication();
		$params = $app->getTemplate(true)->params;
		$skips = explode("\n", $params->get('skipCompress'));
		$skips[] = 'media/editors';
		$skips[] = 'plugins/editors';
		$skips[] = 'media/system/js/validate.js';
		
		foreach($skips as $skip) {
			$skip = trim($skip);
			if(empty($skip)) continue;
			
			if(stristr($url, $skip)!==false) {
				$this->debug('Script/style sheet is excluded from compression', $url);
				return false;
			}
		}
		
		if(substr($url, 0, 2) === '//'){
			$url = JURI::getInstance()->getScheme() . ':' . $url;
		}
		
		if (preg_match('/^https?\:/', $url)) {
			if (strpos($url, JURI::base()) === false){
				// external css
				$this->debug('External script/style sheet is excluded from compression', $url);
				return false;
			}
			$path = JPath::clean(JPATH_ROOT . '/' . substr($url, strlen(JURI::base())));
		} else {
			$path = JPath::clean(JPATH_ROOT . '/' . (JURI::root(true) && strpos($url, JURI::root(true)) === 0 ? substr($url, strlen(JURI::root(true))) : $url));
		}
		
		$path = preg_replace('/\?.*/', '', $path);
		
		if(is_file($path)) {
			return $path;
		}
		
		$this->debug('Script/style sheet doesn\'t exist', $url);
		return false;
	}
	
	/**
	 * Establishing the current template in testing if it supports EF4 Framework
	 */
	function getTemplateName() {
		$app = JFactory::getApplication();
		$template = false;
		if ($app->isSite()) {
			$template = $app->getTemplate(null);
			
			$activeMenu = JFactory::getApplication()->getMenu()->getActive();
			$template_style_id = 0;
			if ( !is_null($activeMenu) ) {
				$template_style_id = (int) $activeMenu->template_style_id;
			}
			
			if( $template_style_id > 0 ){
				JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_templates/tables');
				$style = JTable::getInstance('Style', 'TemplatesTable');
				if ($style->load($template_style_id)) {
					$template = $style->template;
					JFactory::getApplication()->setTemplate($style->template, $style->params);
				}
			}
			
		} else {
			$option = $app->input->get('option', null, 'string');
			$view = $app->input->get('view', null, 'string');
			$task = $app->input->get('task', '', 'string');
			$controller = current(explode('.',$task));
			$id = $app->input->get('id', null, 'int');
			if ($option == 'com_templates' && ($view == 'style' || $controller == 'style' || $task == 'apply' || $task == 'save' || $task == 'save2copy') && $id > 0) {
				$db = JFactory::getDbo();
				
				$query = $db->getQuery(true);
				
				$query->select('template');
				$query->from('#__template_styles');
				$query->where('id='.$id);
				
				$db->setQuery($query);
				$template = $db->loadResult();
			}
		}
		
		if ($template) {
			jimport('joomla.filesystem.file');
			$path = JPATH_ROOT.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.$template.DIRECTORY_SEPARATOR.'templateDetails.xml';
			if (JFile::exists($path)) {
				$xml = JInstaller::parseXMLInstallFile($path);
				if (array_key_exists('group', $xml)){
					if ($xml['group'] == 'jmf-ef4') {
						return $template;
					}
				}
			}
		}
		
		return false;
	}
	
	public function checkTemplateFolders() {
		if (!defined('JMF_TPL_PATH')) {
			return false;
		}
		
		$folders = array(
			'assets',
			'assets/config',
			'assets/layout',
			'assets/style',
			'cache',
			'css',
			'fonts',
			'images',
			'less'
		);
		$errors = array();
		foreach($folders as $folder) {
			$path = JPath::clean(JMF_TPL_PATH . '/' . $folder);
			$nicePath = '/templates/'.JMF_TPL.'/' . $folder;
			if (JFolder::exists($path) == false) {
				$errors[] = '<strong>' . JText::_('PLG_SYSTEM_JMFRAMEWORK_WARNING_FOLDER_NOT_EXISTS') . '</strong>: <code>' . $nicePath . '</code>';
			} else if (is_writable($path) == false) {
				$errors[] = '<strong>' . JText::_('PLG_SYSTEM_JMFRAMEWORK_WARNING_FOLDER_NOT_WRITABLE') . '</strong>: <code>' . $nicePath . '</code>';
			}
		}
		
		return (count($errors) > 0) ? $errors : false;
	}

	/**
	 * Get and return a string between two other strings
	 * @param string $string
	 * @param string $start
	 * @param string $end
	 * @return string
	 */
	protected function getStringBetween($string, $start, $end) {
		$string = ' ' . $string;
		$ini    = strpos($string, $start);
		if ($ini == 0) return '';
		$ini += strlen($start);
		$len = strpos($string, $end, $ini) - $ini;

		return substr($string, $ini, $len);
	}

	/**
	 * Get and return content from URL
	 * @param string $url
	 * @return string
	 */
	protected function httpGetContents($url) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_TIMEOUT, 1);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		if (false === ($retval = curl_exec($ch))) {
			error_log(curl_error($ch));
		} else {
			return $retval;
		}
	}

	/**
	 * Initialising JMFOpenGraph class
	 */
	protected function addOpenGraph($appId = null) {
		require_once JMF_FRAMEWORK_PATH.JPath::clean('/includes/libraries/opengraph/opengraph.php');
		JMFOpenGraph::applyTags($appId);
	}
	
	/**
	 * Checking for templates updates - pop-up/modal interface
	 */
	protected function checkUpdates($force = false) {
		$app = JFactory::getApplication();
		if ($app->isSite()) {
			return;
		}
		
		$option = $app->input->getCmd('option', null);
		$view = $app->input->getCmd('view', null);
		$task = $app->input->getCmd('task', null);
		$user = JFactory::getUser();
		
		if ($view || $option || $task || $user->guest) {
			return;
		}
		
		$autorised = (bool)($user->authorise('core.admin') || $user->authorise('core.manage'));
		if (!$autorised) {
			return;
		}
		
		$document = JFactory::getDocument();
		if ($document instanceof JDocumentHTML) {
			$this->loadLanguage();
			
			JHtml::_('jquery.framework');
			JHtml::_('bootstrap.framework');
			
			$document->addScript(JUri::root() . 'plugins/system/ef4_jmframework/includes/assets/admin/js/jmupdate.js');
			$settings = array();
			$settings['url'] = JUri::base(false).'index.php?option=com_ajax&group=system&plugin=EF4_JMFrameworkCheckUpdates&format=json&action=check_updates';
			$settings['lang'] = array();
			$settings['lang']['updates_available'] = JText::_('PLG_SYSTEM_EF4_JMFRAMEWORK_UPDATES_AVAILABLE');
			$settings['lang']['update_button'] = JText::_('PLG_SYSTEM_EF4_JMFRAMEWORK_UPDATES_BUTTON');
			$settings['lang']['modal_header'] = JText::_('PLG_SYSTEM_EF4_JMFRAMEWORK_UPDATES_HEADER');
			$plgLink = JRoute::_('index.php?option=com_plugins&view=plugins&filter_search=EF4');
			$settings['lang']['modal_footer'] = JText::sprintf('PLG_SYSTEM_EF4_JMFRAMEWORK_UPDATES_FOOTER', $plgLink);
			
			$script = '
				jQuery(document).ready(function(){
						JMFrameworkUpdate.checkUpdates('.json_encode($settings).');
				});
			';
			$document->addScriptDeclaration($script);
		}
		
	}
	
	/*
	 * Checking for templates updates - retrieving data from remote XML.
	 */
	public function onAjaxEF4_JMFrameworkCheckUpdates(){
		$app = JFactory::getApplication();
		$db = JFactory::getDbo();
		$this->loadLanguage();
		
		$updates = array();
		$updatesCount = 0;
		$customInfo = null;
		
		$output = array(
			'updates' => 0,
			'html' => '',
			'error' => null
		);
		
		
		$query = $db->getQuery(true)
		->select('*')
		->from('#__extensions')
		->where('type='.$db->quote('template').' AND enabled=1')
		->where('manifest_cache LIKE '.$db->quote('%"group":"jmf-ef4"%'))
		->order('name asc');
		
		$db->setQuery($query);
		$templates = $db->loadObjectList();
		
		if (empty($templates)) {
			return $output;
		}
		
		$cacheOpt = array(
			'defaultgroup' => 'plugin',
			'browsercache' => false,
			'caching'	  => true
		);
		
		$cacheKey = 'plg_system_ef4_jmframework.updates';
		$cache = JCache::getInstance('output', $cacheOpt);
		// 60 minutes
		$cache->setLifeTime(60);
		
		$body = $cache->get($cacheKey);
		
		if ($body === false) {
			
			// We need to refresh Manifest cache in order to be sure
			// that we are comparing the most accurate template version.
			// We do it here, when XML updates cache is empty,
			// so that database is not too exploited.
			$installer = JInstaller::getInstance();
			foreach ($templates as $template) {
				$installer->refreshManifestCache($template->extension_id);
			}
			
			// Second run - after Manifest Cache has been refreshed.
			$db->setQuery($query);
			$templates = $db->loadObjectList();
			
			if (empty($templates)) {
				return $output;
			}
			
			$http = JHttpFactory::getHttp();
			$response = $http->get($this->updatesURL);
			if (200 != $response->code || empty($response->body))
			{
				$output['error'] = 'HTTP Error ' . $response->code;
				return json_encode($output);
			}
			
			$body = $response->body;
			$cache->store($body, $cacheKey);
		}
		
		try {
			$xml = new SimpleXMLElement($body);
		}
		catch (Exception $e)
		{
			$output['error'] = 'XML is empty';
			return json_encode($output);
		}
		
		if (isset($xml->information) && !empty($xml->information)) {
			$customInfo = trim($xml->information);
		}
		
		if (!isset($xml->templates) || empty($xml->templates)) {
			$output['error'] = 'There are no templates';
			return json_encode($output);
		}
		
		if (!isset($xml->templates->template) || empty($xml->templates->template)) {
			$output['error'] = 'There are no templates';
			return json_encode($output);
		}
		
		$version = new JVersion;
		$joomlaVersion = explode('.', $version->getShortVersion());
		
		foreach($templates as $key => $template) {
			$item = new stdClass();
			
			$item->name = $template->name;
			$item->link = null;
			$item->custom_info = null;
			
			$registry = new JRegistry();
			$registry->loadString($template->manifest_cache, 'JSON');
			
			$item->current_version = $item->version = $registry->get('version', '-');
			
			$found = $xml->xpath('//templates//template[@name="'.$template->element.'"]');
			if ($found) {
				foreach($found as $element) {
					if ($element->joomlaversion == $joomlaVersion[0]) {
						$item->name = $element->title;
						$item->version = $element->version;
						$item->link = isset($element->changelog) ? $element->changelog : null;
						$item->custom_info = isset($element->info) ? $element->info : null;
						if (version_compare($item->current_version, $item->version, '<')) {
							$updatesCount++;
						}
						break;
					}
				}
				// we are not adding templates which do not exist in XML log
				$updates[] = $item;
			}
		}
		
		$html = '';
		
		if ($customInfo) {
			$html .= '<div class="alert alert-info">'.$customInfo.'</div>';
		}
		
		if ($updatesCount > 0) {
			$html .= '<table class="table table-striped">';
			$html .= '<thead><tr>';
			$html .= '<th width="1%" class="center">#</th>';
			$html .= '<th class="nowrap">'.JText::_('PLG_SYSTEM_EF4_JMFRAMEWORK_UPDATES_TABLE_NAME').'</th>';
			$html .= '<th width="5%" class="center nowrap">'.JText::_('PLG_SYSTEM_EF4_JMFRAMEWORK_UPDATES_TABLE_YOUR_VERSION').'</th>';
			$html .= '<th width="5%" class="center nowrap">'.JText::_('PLG_SYSTEM_EF4_JMFRAMEWORK_UPDATES_TABLE_LATEST_VERSION').'</th>';
			$html .= '<th width="5%" class="center nowrap">'.JText::_('PLG_SYSTEM_EF4_JMFRAMEWORK_UPDATES_TABLE_LINK').'</th>';
			$html .= '</tr></thead>';
			$html .= '<tfoot></tfoot>';
			$html .= '<tbody>';
			
			foreach($updates as $k => $update) {
				$html .= '<tr class="row'.($k%2).'">';
				$html .= '<td class="nowrap center">'.($k+1).'</td>';
				$html .= '<td class="nowrap"><strong>'.$update->name.'</strong>';
				if ($update->custom_info) {
					$html .= '<br /><span class="small">'.$update->custom_info.'</span>';
				}
				$html .= '</td>';
				$diffVer = version_compare($update->current_version, $update->version, '<');
				$html .= '<td class="nowrap center"><span class="badge '.($diffVer ? 'badge-warning' : 'badge-success').'">'.$update->current_version.'</span></td>';
				$html .= '<td class="nowrap center"><span class="badge '.($diffVer ? 'badge-important' : 'badge-success').'">'.$update->version.'</span></td>';
				if ($diffVer && $update->link) {
					$html .= '<td class="nowrap center"><a class="btn btn-mini btn-primary" href="'.$update->link.'" target="_blank">'.JText::_('PLG_SYSTEM_EF4_JMFRAMEWORK_UPDATES_TABLE_BUTTON').'</a></td>';
				} else {
					$html .= '<td class="nowrap center"><span class="badge badge-success">'.JText::_('PLG_SYSTEM_EF4_JMFRAMEWORK_UPDATES_TABLE_NOUPDATE').'</span></td>';
				}
				$html .= '</tr>';
			}
			$html .= '</tbody>';
			$html .= '</table>';
		}
		
		$output['updates'] = $updatesCount;
		$output['html'] = $html;
		
		return json_encode($output);
	}
	
	/**
	 * Utility class for quick debugging.
	 */
	private function debug($msg, $data = null) {
		
		if(!$this->debug) return;
		
		$this->_debug[] = array('msg'=>$msg, 'data'=>$data);
	}
	
	/**
	 * Utility class for rendering and displaying debug information.
	 */
	private function renderDebug(&$body) {
		
		if(!$this->debug) return;
		
		$html = '
		<script type="text/javascript">
			function toggleDJQData(name)
			{
				var e = document.getElementById(name);
				e.style.display = (e.style.display == \'none\') ? \'block\' : \'none\';
			}
		</script>
		';
		
		$html .= '<h3>EF4 JOOMLA-MONSTER FRAMEWORK DEBUG INFORMATION</h3>';
		
		foreach($this->_debug as $no => $debug) {
			
			$html .= '<h4>'.($no+1).'. '.$debug['msg'];
			
			if(!empty($debug['data'])) {
				if(is_array($debug['data'])) {
					$html .= ' <a href="#" onclick="toggleDJQData(\'jmfdebug-'.$no.'\'); return false;" class="btn btn-mini">toggle data</a></h4>';
					$html .= ' <pre id="jmfdebug-'.$no.'" style="display: none;">'.print_r($debug['data'], true).'</pre>';
				} else {
					$html .= ' <code>'.$debug['data'].'</code></h4>';
				}
			} else {
				$html .= '</h4>';
			}
		}
		
		$html = '<div class="container well">'.$html.'</div>';
		
		$body = str_replace('</body>', $html . '</body>', $body);
	}
}
