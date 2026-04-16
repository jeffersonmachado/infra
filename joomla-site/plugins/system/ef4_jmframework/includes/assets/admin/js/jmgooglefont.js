/*--------------------------------------------------------------
# Copyright (C) joomla-monster.com
# License: http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 only
# Website: http://www.joomla-monster.com
# Support: info@joomla-monster.com
---------------------------------------------------------------*/

var JMGoogleFontHelper = function(selector) {
	
	this.selector = selector;
	
	this.initialise = function() {
		var self = this;
		jQuery(document).find(selector).each(function(index, element){
			jQuery(element).change(function(event){
				return self.applyLink(jQuery(event.target).val());
			});
			jQuery(element).on('paste', function(event){
				return setTimeout(function(){self.applyLink(jQuery(event.target).val())},0);
			});
			jQuery(element).click(function(event){
				this.select();
			});
			
			jQuery(element).trigger('change');
		});
	};
	
	this.applyLink = function(url){
		if (!url) {
			return false;
		}
		
		if (url.indexOf('fonts.googleapis.com') == -1) {
			return false;
		}
		
		var alreadySet = false;
		jQuery(document).find('link').each(function(index, link){
			if (jQuery(link).attr('href') == url) {
				alreadySet = true;
			}
		});
		
		if (alreadySet) {
			return true;
		}
		
		var newLink = jQuery('<link/>', {
			href: url,
			rel: 'stylesheet',
			type: 'text/css'
		}).appendTo(document.head);
		
		return true;
	}
}