/*--------------------------------------------------------------
# Copyright (C) joomla-monster.com
# License: http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 only
# Website: http://www.joomla-monster.com
# Support: info@joomla-monster.com
---------------------------------------------------------------*/

var JMOptionGroups = function(fieldName, controlName, value) {
	
	this.name = fieldName;
	this.value = value;
	this.control = controlName;
	
	this.initialise = function() {
		this.groups = jQuery('#'+this.name).find('option');
		
		var rel = jQuery('#'+this.name).attr('data-target') || false;
		
		this.related = (rel ? jQuery('#'+this.name+'_target') : false) || false;
		
		if ((this.groups.length == 0)) {
			return;
		}
		this.groupFields = [];
		this.groupNames = [];
		this.groups.each(function(index, el){
			var elements = el.value.split(';');
			
			if (elements.length > 0) {
				var value = elements[0];
				this.groupFields[value] = [];
				this.groupNames[index] = value;
				for (var i = 1; i < elements.length; i++) {
					this.groupFields[value][i-1] = elements[i];
					var inputId = jQuery('#'+this.control + '_' + this.groupFields[value][i-1]);
					var labelId = jQuery('#'+this.control + '_' + this.groupFields[value][i-1] + '-lbl');
					if (inputId) {
						inputId.parents('.control-group').css('display', 'none');
					} else if (labelId) {
						labelId.parents('.control-group').css('display', 'none');
					}
					
				}
			}
		}.bind(this));
		
		jQuery('#'+this.name).on('change', function(){
			this.setFields();
		}.bind(this)); 
		
		if (this.related){
			for (var i = 0; i < this.groupNames.length; i++) {
				var group = (this.groupNames[i]);
				for (var j = 0; j < this.groupFields[group].length; j++) {
					var inputId = jQuery('#'+this.control + '_' + this.groupFields[group][j]);
					if (inputId && inputId.hasClass('src-option')) {
						inputId.on('change', function(evt){
							if (jQuery(evt.target).val()) {
								this.related.val(jQuery(evt.target).val());
							}
						}.bind(this));
					}
				}
			}
		}
		
		jQuery('#'+this.name).trigger('change');
	};
	
	this.setFields = function() {
		var elements = jQuery('#' + this.name).val().split(';');
		var value = elements[0];
		for (var i = 0; i < this.groupNames.length; i++) {
			var group = (this.groupNames[i]);
			for (var j = 0; j < this.groupFields[group].length; j++) {
				var inputId = jQuery('#'+this.control + '_' + this.groupFields[group][j]);
				var labelId = jQuery('#'+this.control + '_' + this.groupFields[group][j] + '-lbl');
				if (group == value) {
					if (inputId) {
						if (inputId.hasClass('src-option') && inputId.val() && this.related) {
							this.related.val(inputId.val());
						}
						//inputId.attr('required', 'required');
						inputId.prop('required', true);
						inputId.parents('.control-group').css('display', '');
					} else if (labelId) {
						labelId.parents('.control-group').css('display', '');
					}
				} else {
					if (inputId) {
						//inputId.removeAttr('required');
						inputId.prop('required', false);
						inputId.parents('.control-group').css('display', 'none');
					} else if (labelId) {
						labelId.parents('.control-group').css('display', 'none');
					}
				}
			}
		}
	}
	
	this.initialise();
};

function addTabs(element) {

	var fieldset = jQuery('.jmframework #myTabContent').find(element);
	var tabs = jQuery('<ul class="nav nav-tabs"></ul>');
	var tabContent = jQuery('<div class="tab-content"></div>');
	var spacer = fieldset.find('.field-spacer');

	if (fieldset.attr('id') == 'attrib-jmcolormodifications') {
		var spacer = spacer.slice(1);
	}

	spacer.each(function () {

		var label = jQuery(this).find('label').text();
		var labelId = jQuery(this).find('label').attr('id');
		var fields = jQuery(this).nextUntil('.field-spacer');
		var tabPane = jQuery('<div class="tab-pane" id="' + labelId + '"></div>');
		var tabList = jQuery('<li><a href="#' + labelId + '" data-toggle="tab">' + label + '</a></li>');

		tabPane.append(fields);
		tabContent.append(tabPane);
		tabs.append(tabList);

		jQuery(this).remove();

	});

	fieldset.append(tabs).append(tabContent);
	tabs.find('> li:first-child a').tab('show');
}

function addGroups() {

	var container = jQuery('.jmframework #myTabContent');
	var group = container.find('.jm-group');
	var groupLength = group.length;

	group.each(function (i) {

		if (i == groupLength - 1) {
			jQuery(this).remove();
			return false;
		}

		var fields = jQuery(this).nextUntil('.jm-group')
		var groupClass = jQuery(this).removeClass('control-group').attr('class');
		fields.wrapAll('<div class="' + groupClass + '"></div>');

		jQuery(this).remove();

	});

}

jQuery(window).load(function () {
	addTabs('#attrib-jmbasic');
	addTabs('#attrib-jmfont');
	addTabs('#attrib-jmcolormodifications');
	addTabs('#attrib-jmadvanced');
	addGroups();
	jQuery('.jmframework [id*="attrib-jm"] .nav-tabs > li a').contents().wrap('<span class="text"></span>');
});

// responsive tabs
jQuery(document).on('show', '.jmframework [id*="attrib-jm"] .nav-tabs [data-toggle="tab"]', function (e) {
	var target = jQuery(e.target);
	var tabs = target.closest('.nav-tabs');
	var current = target.closest('li');
	var next = current.next();
	var prev = current.prev();
	tabs.find('>li').removeClass('next prev');
	prev.addClass('prev');
	next.addClass('next');
});