/*--------------------------------------------------------------
# Copyright (C) joomla-monster.com
# License: http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 only
# Website: http://www.joomla-monster.com
# Support: info@joomla-monster.com
---------------------------------------------------------------*/

// adding the span tag to the specified field class
jQuery(document).ready(function(){
	jQuery('.unit-px').each(function(){
		var el = jQuery(this);
		el.parent().html(el.parent().html() + "<span class=\"unit\">px</span>");
	});
	jQuery('.unit-percent').each(function(){
		var el = jQuery(this);
		el.parent().html(el.parent().html() + "<span class=\"unit\">%</span>");
	});
});