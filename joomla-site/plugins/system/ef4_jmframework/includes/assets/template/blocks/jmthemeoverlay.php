<?php
/**
 * @version $Id: jmthemeoverlay.php 157 2017-03-30 14:48:16Z michal $
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

defined('_JEXEC') or die;

$app = JFactory::getApplication();
$doc = JFactory::getDocument();
$switch = ($this->params->get('themerswitch', false) == '1') ? true : false;
$themer = ($this->params->get('themermode', false) == '1') ? true : false;

if($switch) { 
	$doc->addStyleDeclaration("
	#jmthemeoverlay {
		visibility: hidden;
		position: fixed;
		left: 50%;
		top: 50%;
		z-index: 10000;
		width: 0%;
		height: 0%;
		background: #383e49;
		vertical-align: middle;
		overflow: hidden;
	}	
	#jmthemeoverlay.visible {
		visibility: visible;
		left: 0%;
		top: 0%;
		width: 100%;
		height: 100%;
		-webkit-transition-property: left, width, top, height;
		transition-property: left, width, top, height;
		-webkit-transition-duration: 0.6s;
		transition-duration: 0.6s;
		-webkit-transition-timing-function: cubic-bezier(.75,.5,0,1), cubic-bezier(.75,.5,0,1), cubic-bezier(1,0,.5,.75), cubic-bezier(1,0,.5,.75);
		transition-timing-function: cubic-bezier(.75,.5,0,1), cubic-bezier(.75,.5,0,1), cubic-bezier(1,0,.5,.75), cubic-bezier(1,0,.5,.75);
	}	
	#jmthemeoverlay p {
		position: relative;
		top: 50%;
		font-size: 20px;
		margin: -0.5em 0 0 0;
		color: #fff;
		text-align: center;
		font-style: italic;
		opacity: 0;
		transition: opacity 0.2s 0.4s;
	}
	#jmthemeoverlay.visible p {
		opacity: 1;
	}
	.jmthemeloader {
	  position: absolute;
	  left: 50%;
	  top: 50%;
	  margin-top: -80px;
	  transform: translate(-50%,0);
	  transform: -webkit-translate(-50%,0);
	}
");

}

if($themer || $switch) {

JURI::reset();
$uri = JURI::getInstance();
$uri->delVar('tc');
$action = $uri->toString();
JURI::reset();
?>

<form id="jmthemetogglerform" action="<?php echo $action ?>" method="post" style="display: none;">
	<input type="hidden" name="tc" value="<?php echo $switch ? '1' : '0'; ?>" />
</form>

<div id="jmthemeoverlay" class="<?php echo $switch ? '' : 'visible'; ?>">
	<svg width='64px' height='64px' xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" preserveAspectRatio="xMidYMid" class="jmthemeloader">
		<rect x="0" y="0" width="100" height="100" fill="none" class="bk"></rect>
		<g>
			<animate attributeName="opacity" dur="2s" repeatCount="indefinite" begin="0s" keyTimes="0;0.33;1" values="1;1;0"></animate>
			<circle cx="50" cy="50" r="40" stroke="#5aa000" fill="none" stroke-width="6" stroke-linecap="round">
				<animate attributeName="r" dur="2s" repeatCount="indefinite" begin="0s" keyTimes="0;0.33;1" values="0;22;44"></animate>
			</circle>
		</g>
		<g>
			<animate attributeName="opacity" dur="2s" repeatCount="indefinite" begin="1s" keyTimes="0;0.33;1" values="1;1;0"></animate>
			<circle cx="50" cy="50" r="40" stroke="#ffffff" fill="none" stroke-width="6" stroke-linecap="round">
				<animate attributeName="r" dur="2s" repeatCount="indefinite" begin="1s" keyTimes="0;0.33;1" values="0;22;44"></animate>
			</circle>
		</g>
	</svg>
	<p><?php echo JText::_('PLG_SYSTEM_JMFRAMEWORK_THEMER_WAIT'); ?></p>
</div>
<?php
}