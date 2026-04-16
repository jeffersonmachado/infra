<?php/** * @version $Id: jmiriscolor.php 38 2014-10-29 07:42:48Z michal $ * @package JMFramework * @copyright Copyright (C) 2012 DJ-Extensions.com LTD, All rights reserved. * @license http://www.gnu.org/licenses GNU/GPL * @author url: http://dj-extensions.com * @author email contact@dj-extensions.com * @developer Michal Olczyk - michal.olczyk@design-joomla.eu * * JMFramework is free software: you can redistribute it and/or modify * it under the terms of the GNU General Public License as published by * the Free Software Foundation, either version 3 of the License, or * (at your option) any later version. * * JMFramework is distributed in the hope that it will be useful, * but WITHOUT ANY WARRANTY; without even the implied warranty of * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the * GNU General Public License for more details. * * You should have received a copy of the GNU General Public License * along with JMFramework. If not, see <http://www.gnu.org/licenses/>. * */defined('JPATH_BASE') or die;jimport('joomla.form.formfield');/** * Advanced color picker field, based on Iris */ class JFormFieldJmiriscolor extends JFormField{    protected $type = 'jmiriscolor';    protected static $loaded = false;    protected function getInput()    {        if (!self::$loaded) {            JHtml::_('jquery.framework');            JHtml::_('jquery.ui');            $document = JFactory::getDocument();            $document->addStyleSheet(JURI::root(false).'plugins/system/ef4_jmframework/includes/assets/admin/formfields/jmiriscolor/iris.min.css');            $document->addScript(JURI::root(false).'plugins/system/ef4_jmframework/includes/assets/admin/js/jquery/jquery.ui.draggable.js');            $document->addScript(JURI::root(false).'plugins/system/ef4_jmframework/includes/assets/admin/js/jquery/jquery.ui.slider.js');            $document->addScript(JURI::root(false).'plugins/system/ef4_jmframework/includes/assets/admin/formfields/jmiriscolor/iris.js');                        $document->addScriptDeclaration("
	            		jQuery(document).on('JMFrameworkInit', function(){            				jQuery('.jmirispicker').each(function() {
								jQuery(this).iris({
									hide: true,
	    							palettes: true
								});
							});
		            		jQuery(document).on('click',function(event){
								jQuery('.jmirispicker').each(function() {
									if (event.target != this && typeof jQuery(this).iris != 'undefined') {
										jQuery(this).iris('hide');
									} else {        								event.target.select();        							}
								});
							});
            			});
	            ");                        if (JFactory::getApplication()->isAdmin()) {
            	$document->addScriptDeclaration("
            		jQuery(document).ready(function(){
						jQuery(document).trigger('JMFrameworkInit');
						});
            		");
            }                        self::$loaded = true;        }        // Initialize some field attributes.        $size       = $this->element['size'] ? ' size="'.(int) $this->element['size'].'"' : '';        $maxLength  = $this->element['maxlength'] ? ' maxlength="'.(int) $this->element['maxlength'].'"' : '';        $id         = $this->element['id'] ? ' id="'.(string) $this->element['id'].'"' : '';        $previewid  = $this->element['previewid'] ? ' id="'.(string) $this->element['previewid'].'"' : '';        $readonly   = ((string) $this->element['readonly'] == 'true') ? ' readonly="readonly"' : '';        $disabled   = ((string) $this->element['disabled'] == 'true') ? ' disabled="disabled"' : '';
        // Initialize JavaScript field attributes.        $onchange   = $this->element['onchange'] ? ' onchange="'.(string) $this->element['onchange'].'"' : '';
        $html = array();        $class = $this->element['class'] ? (string) $this->element['class'].'  jmirispicker' : 'jmirispicker';                if (empty($this->value)) {        	$this->value = $this->default;        }                return '<input type="text" name="'.$this->name.'" id="'.$this->id.'" class="'.$class.'" value="'.htmlspecialchars($this->value, ENT_COMPAT, 'UTF-8').'" autocomplete="off" placeholder="(default)" />';    }}