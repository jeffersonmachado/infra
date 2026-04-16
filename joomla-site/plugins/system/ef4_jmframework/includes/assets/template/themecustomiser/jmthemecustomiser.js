var JMThemeCustomiser = {
	init: function(contents, tpl_name) {
		this.tpl_name = tpl_name;
		
		var wrapper = document.createElement('div');
		wrapper.setProperty('id', 'jmthemewrapper');
		//wrapper.setProperty('rel', this.tpl_name);
		document.body.adopt(wrapper);
		
		var toggler = document.createElement('span');
		toggler.setProperty('id', 'jmthemetoggler');
		wrapper.adopt(toggler);
		//toggler.set('html','&gt;');
		
		var formwrapper = document.createElement('div');
		formwrapper.setProperty('id', 'jmthemeform');
		wrapper.adopt(formwrapper);
		formwrapper.set('html', contents);
		
		toggler.addEvent('click', function(event){
			wrapper.toggleClass('active');
		});
		
		var overlay = document.createElement('div');
		overlay.setProperty('id', 'jmthemeoverlay');
		document.body.adopt(overlay);
		overlay.set('html', '<p>Applying changes. Please wait...</p>');
		
		var form = document.id('jmtheme');
		
		var styleSelector = document.id('themecustomiser_style');
		if (styleSelector){
			styleSelector.addEvent('change', function(evt){
				if (changeStyle != 'undefined') {
					overlay.addClass('visible');
					var cached_style = {};
					cached_style[JMThemeCustomiser.getName(this.name)] = this.value;
					changeStyle(this.options[this.selectedIndex].value); 
					JMThemeCustomiser.setCookie('JM_theme_vars_'+JMThemeCustomiser.tpl_name, '');
					JMThemeCustomiser.setCookie('JM_form_vars_'+JMThemeCustomiser.tpl_name, JSON.stringify(cached_style));
					document.location.reload();
				}
			});
		}
		
		new Fx.Accordion('.jmtheme-set-toggler','.jmtheme-set', 
		{
			alwaysHide : false,
			display : 0,
			duration : 150,
			onActive : function(toggler, element) {
				toggler.addClass('active');
				element.addClass('active');
			},
			onBackground : function(toggler, element) {
				toggler.removeClass('active');
				element.removeClass('active');
			}
		});
		
		new Fx.Accordion('.jmtheme-subset-toggler','.jmtheme-subset', 
		{
			alwaysHide : false,
			display : 0,
			duration : 150,
			onActive : function(toggler, element) {
				toggler.addClass('active');
				element.addClass('active');
			},
			onBackground : function(toggler, element) {
				toggler.removeClass('active');
				element.removeClass('active');
			}
		});
		
		var stored_settings = JMThemeCustomiser.getCookie('JM_form_vars_'+JMThemeCustomiser.tpl_name) || '';
		if (stored_settings && stored_settings != '') {
			form_cache = JSON.parse(stored_settings);
			for (var input_name in form_cache) {
				form.getElements('[type=text], textarea, select').each(function(element) {
					if (JMThemeCustomiser.getName(element.name) == input_name && form_cache[input_name] != '') {
						element.value = form_cache[input_name];
					}
				});
			}
		}
		
		var googleFontsSelectors = document.getElements('select.jmgooglefontselector');
		if (googleFontsSelectors) {
			googleFontsSelectors.each(function(el){
				if(el.options[el.selectedIndex].value != '') {
					JMThemeCustomiser.enableGoogleFont(el.options[el.selectedIndex].value);
				}
			});
		}
		
		var resetButton = document.id('jmtheme-reset');
		if (resetButton) {
			resetButton.addEvent('click', function(event){
				event.stop();
				overlay.addClass('visible');
				JMThemeCustomiser.setCookie('JM_theme_vars_'+JMThemeCustomiser.tpl_name, '');
				JMThemeCustomiser.setCookie('JM_form_vars_'+JMThemeCustomiser.tpl_name, '');
				document.location.reload();
				return false;
			});
		}
		
		form.addEvent('submit', function(evt){
			evt.preventDefault();
			
			var links = document.getElements('link');
			links.each(function(el){
				id = el.getProperty('id');
				if (id != null && id.match(/^style[0-9]{1}$/)){
					el.destroy();
				}
						
			});

			vars = {};
			cached_vars = {};
			form.getElements('[type=text], textarea, select').each(function(element) {
				if (element.value) {
					var name = element.name;
					vars['@'+JMThemeCustomiser.getName(name)] = element.value;
					cached_vars[JMThemeCustomiser.getName(name)] = element.value;
				}
			});
			if (vars && less) {
				
				vars_string = '';
				for (var name in vars) {
					vars_string += ((name.slice(0,1) === '@')? '' : '@') + name +': '+ ((vars[name].slice(-1) === ';')? decodeURIComponent(vars[name]) : decodeURIComponent(vars[name]) +';');
			    }
				JMThemeCustomiser.setCookie('JM_theme_vars_'+JMThemeCustomiser.tpl_name, vars_string);
				JMThemeCustomiser.setCookie('JM_form_vars_'+JMThemeCustomiser.tpl_name, JSON.stringify(cached_vars));
				
				//if (JMThemeCustomiser.timer) clearTimeout(JMThemeCustomiser.timer);
				
				overlay.addClass('visible');
				
				(function(){
					overlay.removeClass('visible');
				}).delay(6000);
				
				
				(function(){
					less.modifyVars(vars);
				}).delay(100);
				
			}
		});
	},
	getName: function(formelm){
		var matches = formelm.match('themecustomiser\\[([^\\]]*)\\]');
		if (matches){
			return matches[1];
		}
		
		return '';
	},
	setCookie: function (c_name, value, exdays)
	{
		var exdate=new Date();
		exdate.setDate(exdate.getDate() + exdays);
		var c_value=escape(value) + ((exdays==null) ? "" : "; expires="+exdate.toUTCString()) + "; path=/";
		document.cookie=c_name + "=" + c_value;
	},
	
	getCookie: function(c_name)
	{
		var c_value = document.cookie;
		var c_start = c_value.indexOf(" " + c_name + "=");
		if (c_start == -1)
		{
			c_start = c_value.indexOf(c_name + "=");
		}
		if (c_start == -1)
		{
			c_value = null;
		}
		else
		{
			c_start = c_value.indexOf("=", c_start) + 1;
			var c_end = c_value.indexOf(";", c_start);
			if (c_end == -1)
			{
				c_end = c_value.length;
			}
			c_value = unescape(c_value.substring(c_start,c_end));
		}
	return c_value;
	},
	
	enableGoogleFont: function(font) {
		if (!font || font == '') {
			return false;
		}
		var escapedFont = encodeURIComponent(font).replace(/%20/g,'+');
		var fontUrl = 'http://fonts.googleapis.com/css?family=' + escapedFont;
		var alreadySet = false;
		document.getElements('link').each(function(link){
			if (link.getProperty('href') == fontUrl) {
				alreadySet = true;
			}
		});
		if (alreadySet) {
			return true;
		}
		newLink = document.createElement('link');
		newLink.setProperty('href', fontUrl);
		newLink.setProperty('rel', 'stylesheet');
		newLink.setProperty('type', 'text/css');
		
		document.head.adopt(newLink);
		return true;
	}
};
