/*--------------------------------------------------------------
# Copyright (C) joomla-monster.com
# License: http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 only
# Website: http://www.joomla-monster.com
# Support: info@joomla-monster.com
---------------------------------------------------------------*/

var JMSpacer = new Class({
	
	name: null,
	fields: null,
	control: null,
	open: true,
	
	initialize: function(fieldName, controlName, fields) {
		this.id = 'jmspacer-'+fieldName;
		this.name = fieldName;
		this.control = controlName;
		this.fieldNames = fields;
		this.toggler = document.id(fieldName).getParent('span.jmspacer');
		this.toggler.setAttribute('id',this.id);
		
		this.togglerWrapper = this.toggler.getParent('.control-group');
		this.togglerWrapper.addClass('jmspacer-wrapper');
		
		var adminformlist = document.createElement('ul');
		//adminformlist.setAttribute('class', 'adminformlist');
		adminformlist.setAttribute('id', 'jmspacer-'+fieldName+'-list');
		
		this.togglerWrapper.appendChild(adminformlist);
		
		document.id('jmspacer-'+fieldName+'-list').addClass('adminformlist');
		
		this.adminformlist = document.id('jmspacer-'+fieldName+'-list');
		this.defaultListStyle = this.adminformlist.getStyle('display');
		this.fields = [];
		
		this.fieldNames.each(function(el, ind){
			//console.log(this.control + '_' + el);
			if (document.id(this.control + '_' + el)) {
				this.fields[ind] = document.id(this.control + '_' + el).getParent('.control-group');
				this.adminformlist.adopt(this.fields[ind]);
			} else if (document.id(this.control + '_' + el + '-lbl')) {
				this.fields[ind] = document.id(this.control + '_' + el + '-lbl').getParent('.control-group');
				this.adminformlist.adopt(this.fields[ind]);
			}
		}.bind(this));
		
		this.togglerWrapper.addEvent('mouseover', function(){
			this.togglerWrapper.addClass('jmhover');
		}.bind(this));
		this.togglerWrapper.addEvent('mouseout', function(){
			this.togglerWrapper.removeClass('jmhover');
		}.bind(this));
		
		
		this.verticalSlide = new Fx.Slide(this.adminformlist,{resetHeight: true});
		this.toggler.addEvent('click', function(event){
		    event.stop();
		    
		    var allWrappers = document.getElements('span.jmspacer');
			allWrappers.each(function(el, ind){
				if (el.getAttribute('id') != this.id) {
					el.fireEvent('forceClose', el);
				}
			}.bind(this));
		    
		    this.verticalSlide.toggle();
		  }.bind(this));
		
		//this.toggler.addEvent('click', this.toggle.bind(this));
		this.toggler.addEvent('forceClose', this.closeGroup.bind(this));
		//this.toggler.fireEvent('click', this.toggler);
		
		this.verticalSlide.hide();
	},
	toggle: function() {
		if (this.open) {
			this.closeGroup();
		} else {
			this.openGroup();
		}
		var allWrappers = document.getElements('span.jmspacer');
		allWrappers.each(function(el, ind){
			if (el.getAttribute('id') != this.id) {
				el.fireEvent('forceClose', el);
			}
		}.bind(this));
	},
	closeGroup: function() {
		//this.adminformlist.setStyle('display', 'none');
		this.verticalSlide.slideOut();
		this.open = false;
	}	
});