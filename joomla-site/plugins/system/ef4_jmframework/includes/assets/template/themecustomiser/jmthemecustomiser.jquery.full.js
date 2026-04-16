!function($, undef) {
	var JMThemeCustomiser = window.JMThemeCustomiser = window.JMThemeCustomiser || {
			init: function(tpl_name) {
				this.tpl_name = tpl_name;
				
				/*
				 * Very useful function, but currently we're not using it. 
				 * But it's cool, so I'm leaving it for future reference.
				 */
				
				//if (less != undef) {
					//less.refresh(false, JMThemeCustomiser.lessVars);
				//}
			},
			
			// render function is executed after DOM ready
			render: function() {
				
				// whole bunch of elements that make Customiser's panel.
				this.wrapper = $('<div/>',{
					id: 'jmthemewrapper',
					dir: 'ltr'
				}).appendTo(document.body);
				
				this.toggler = $('<div/>',{
					id: 'jmthemetoggler'
				}).appendTo(this.wrapper);
				
				this.formwrapper = $('<div/>',{
					id: 'jmthemeform'
				}).appendTo(this.wrapper);
				
				this.toggler.click(function(){
					JMThemeCustomiser.wrapper.toggleClass('active');
				});
				
				this.overlay = $('#jmthemeoverlay');
				
				// AJAX call to render form layout
				$.ajax({
					async: false,
					url : JMThemeCustomiser.url,
					data : {
						jmajax : 'themer',
						jmtask: 'display',
						jmthemerlogin: JMThemeCustomiser.login_form,
						ts: new Date().getTime()
					}
				}).done(function(response) {
					JMThemeCustomiser.loadLayout(response);
					
				}).fail(function(xhr, status, error) {
					alert("error: " + status);
				}).always(function(){
					setTimeout(function(){
						JMThemeCustomiser.overlay.removeClass('visible');
					}, 100);
				});
				
				// There is no reason to display template's style switcher when Theme Customiser is enabled.
				// In fact it would cause problems
				if ($('#jm-styleswitcher') != undef) {
					$('#jm-styleswitcher').hide();
				}
				return;
			},
			
			// embeding the layout acquired by AJAX call
			loadLayout: function(response) {
				this.formwrapper.html(response);
				
				this.form = $('#jmtheme');
				
				// style selector is very special. We should reset any changes and reload whole page when style changes
				var styleSelector = $('#themecustomiser_templateStyle');
				if (styleSelector){
					styleSelector.change(function(){
						if (changeStyle != 'undefined') {
							JMThemeCustomiser.overlay.addClass('visible');
							var cached_style = {};
							cached_style[JMThemeCustomiser.getName(this.name)] = this.value;
							changeStyle(this.options[this.selectedIndex].value); 
							//JMThemeCustomiser.setCookie('JM_theme_vars_'+JMThemeCustomiser.tpl_name, '');
							JMThemeCustomiser.setThemerState(JSON.stringify(cached_style));
							document.location.reload();
						}
					});
				}
				
				// Accordion UI for from fields
				$(document).find('div.jmtheme-set-inside').accordion({ header: "h4", collapsible: true , heightStyle: "content"});
				$('#jmtheme').accordion({ header: "h3", collapsible: true, heightStyle: "content", active: false });
				
				// Form parameters' values are being stored in a Cookie and are being restored when user navigates to different page.
				var stored_settings = JMThemeCustomiser.getThemerState() || '';
				if (stored_settings && stored_settings != '') {
					form_cache = JSON.parse(stored_settings);
					for (var input_name in form_cache) {
						if (!form_cache.hasOwnProperty(input_name)) continue;
						
						if (form_cache[input_name] != '') {
							//form_cache[input_name] = decodeURIComponent(form_cache[input_name]).replace(/\+/g, ' ');
							//form_cache[input_name] = form_cache[input_name].toString().replace(/\+/g, ' ');
							form_cache[input_name] = form_cache[input_name].toString();
						}
						
						JMThemeCustomiser.form.find('[type=text], [type=hidden], textarea, select').each(function(index, element) {
							if (JMThemeCustomiser.getName(element.name) == input_name && form_cache[input_name] != '') {
								element.value = form_cache[input_name];
							}
						});
					}
				}
				
				// DEPRECATED. Leaving just in case.
				var googleFontsSelectors = $(document).find('select.jmgooglefontselector');
				if (googleFontsSelectors) {
					googleFontsSelectors.each(function(index, el){
						var optionNo = el.selectedIndex > 0 ? el.selectedIndex : 0;
						if(el.options[optionNo].value != '') {
							JMThemeCustomiser.enableGoogleFont(el.options[optionNo].value);
						}
					});
				}
				
				// Button which deletes the Cookie, restores all default settings and reloads the page.
				var resetButton = $('#jmtheme-reset');
				if (resetButton) {
					resetButton.click(function(event){
						event.preventDefault();
						JMThemeCustomiser.overlay.addClass('visible');
						setTimeout(function(){
							JMThemeCustomiser.setThemerState('');
							less.refresh(true);
							document.location.reload();
						}, 100);
						return false;
					});
				}
				
				// Saving settings directly into database
				var saveButton = $('#jmtheme-save');
				if (saveButton) {
					saveButton.click(function(event){
						event.preventDefault();
						JMThemeCustomiser.overlay.html('<p>'+JMThemeCustomiser.lang.LANG_PLEASE_WAIT_SAVING+'</p>');
						JMThemeCustomiser.overlay.addClass('visible');
						setTimeout(function(){
							JMThemeCustomiser.saveSettings(false);
						}, 100);
						//JMThemeCustomiser.overlay.removeClass('visible');
						return;
					});
				}
				
				// Saving settings to JSON file
				var saveFileButton = $('#jmtheme-save_file');
				if (saveFileButton) {
					saveFileButton.click(function(event){
						event.preventDefault();
						JMThemeCustomiser.overlay.html('<p>'+JMThemeCustomiser.lang.LANG_PLEASE_WAIT_SAVING+'</p>');
						JMThemeCustomiser.overlay.addClass('visible');
						setTimeout(function(){
							JMThemeCustomiser.saveSettings(true);
						}, 100);
						//JMThemeCustomiser.overlay.removeClass('visible');
						return;
					});
				}
				
				// Preview button
				var previewButton = $('#jmtheme-submit');
				if (previewButton) {
					var self = this;
					previewButton.click(function(event){
						event.preventDefault();
						self.form.submit();
					});
				}
				
				// Default submit action. Applying modifications to LESS JS compiler
				this.form.submit(function(event){
					event.preventDefault();
					JMThemeCustomiser.overlay.html('<p>'+JMThemeCustomiser.lang.LANG_PLEASE_WAIT_APPLYING+'</p>');
					JMThemeCustomiser.overlay.addClass('visible');
					setTimeout(function(){
						JMThemeCustomiser.applyChanges();
						JMThemeCustomiser.modifyVars();
					}, 100);
					return;
				});
			},
			
			// This only parses parameters and stores them in internal variable
			applyChanges: function() {
				var timestamp = JMThemeCustomiser.getCookie('JMTH_TIMESTAMP_'+JMThemeCustomiser.tpl_name) || '';
				if (timestamp == '' || timestamp == -1) {
					JMThemeCustomiser.overlay.html('<p>'+JMThemeCustomiser.lang.LANG_PLEASE_WAIT_RELOADING+'</p>');
					setTimeout(function(){
						document.location.reload();
					}, 100);
					return false;
				}
				
				var links = $(document).find('link');
				links.each(function(index, el){
					id = jQuery(el).attr('id');
					if (id != null && id.match(/^style[0-9]{1}$/)){
						el.destroy();
					}
							
				});

				/*
				 * vars vs. cached_vars - what's the difference?
				 * 
				 * - vars all start with @JM and are passed directly to LESS compiler. 
				 * 
				 * - cached_vars are the copy of all template's variables - even those 
				 * which are not directly related to LESS. cached_vars are being passed 
				 * to the plug-in when user decides to save the modifications. 
				 */
				JMThemeCustomiser.vars = {};
				JMThemeCustomiser.cached_vars = JMThemeCustomiser.lessVars || {};
				
				for (var m in JMThemeCustomiser.lessVars) {
					if (m.substring(0,2) == 'JM' && JMThemeCustomiser.lessVars[m].replace(/[^0-9a-z\#,]/g, '') != '') {
						JMThemeCustomiser.vars['@'+m] = JMThemeCustomiser.lessVars[m];
					}
				}
				
				JMThemeCustomiser.form.find('[type=text], [type=hidden], textarea, select').each(function(index, element) {
					if (element.value) {
						var name = element.name;
						var varname = JMThemeCustomiser.getName(name);
						
						if (element.value.replace(/[^0-9a-z]/gi, '') != '') {
							if (varname.substring(0,2) == 'JM') {
								JMThemeCustomiser.vars['@'+varname] = element.value;
							}
							
							JMThemeCustomiser.cached_vars[varname] = element.value;
						}
					}
				});
				
				return true;
			},
			
			// Stores params into Cookie and does the whole LESS magic.
			modifyVars: function(){
				if (JMThemeCustomiser.vars && less) {
					JMThemeCustomiser.setThemerState(JSON.stringify(JMThemeCustomiser.cached_vars));
					
					setTimeout(function(){
						less.modifyVars(JMThemeCustomiser.vars);
						JMThemeCustomiser.overlay.removeClass('visible');
					}, 100);
					
				} else {
					JMThemeCustomiser.overlay.removeClass('visible');
				}
			},
			
			// AJAX call which saves all the changes the user made.
			saveSettings: function(useFile) {
				if (this.applyChanges() == false){
					return false;
				}
				
				var task = (useFile) ? 'save_file' : 'save';
				
				$.ajax({
					type: 'POST',
					async: false,
					url : JMThemeCustomiser.url,
					data : {
						jmajax : 'themer',
						jmtask: task,
						jmstyleid: JMThemeCustomiser.styleId,
						jmtemplatename: JMThemeCustomiser.tpl_name,
						jmvars: JMThemeCustomiser.cached_vars,
						ts: new Date().getTime()
					}
				}).done(function(response) {
					alert(response);
					return;
				}).fail(function(xhr, status, error) {
					if (xhr.status == 403) {
						alert(JMThemeCustomiser.lang.LANG_ERROR_FORBIDDEN);
						document.location.reload();
						return false;
					} else if (xhr.status == 401) {
						alert(JMThemeCustomiser.lang.LANG_ERROR_UNAUTHORISED);
						return false;
					} else if (xhr.status == 400) {
						alert(JMThemeCustomiser.lang.LANG_ERROR_BAD_REQUEST);
						return false;
					}
				}).always(function(){
					JMThemeCustomiser.overlay.removeClass('visible');
				});
				
			},
			
			// parses forms variable name and returns LESS variable
			getName: function(formelm){
				var matches = formelm.match('themecustomiser\\[([^\\]]*)\\]');
				if (matches){
					return matches[1];
				}
				
				return '';
			},
			
			// guess :)
			setCookie: function (c_name, value, exdays)
			{
				var exdate = new Date();
				exdate.setDate(exdate.getDate() + exdays);
				var c_value = escape(value) + ((exdays==null) ? "" : "; expires="+exdate.toUTCString());
				c_value += "; path=" + this.cookie.path;
				document.cookie=c_name + "=" + c_value;
			},
			
			// guess again...
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
			
			setThemerState: function(value) {
				var ret = false;
				$.ajax({
					type: 'POST',
					async: false,
					url : JMThemeCustomiser.url,
					data : {
						jmajax : 'themer',
						jmtask: 'set_state',
						jmstyleid: JMThemeCustomiser.styleId,
						jmtemplatename: JMThemeCustomiser.tpl_name,
						jmvars: value,
						ts: new Date().getTime()
					}
				}).done(function(response) {
					ret = true; 
				}).fail(function(xhr, status, error) {
					ret = false;
				});
				
				return ret;
			},
			
			getThemerState: function(value) {
				var params = '';
				
				$.ajax({
					type: 'POST',
					async: false,
					url : JMThemeCustomiser.url,
					data : {
						jmajax : 'themer',
						jmtask: 'get_state',
						jmstyleid: JMThemeCustomiser.styleId,
						jmtemplatename: JMThemeCustomiser.tpl_name,
						ts: new Date().getTime()
					}
				}).done(function(response) {
					params = response; 
				}).fail(function(xhr, status, error) {
					params = '';
				});
				
				return params;
			},
			
			// DEPRECATED. Leaving just in case. 
			enableGoogleFont: function(font) {
				if (!font || font == '') {
					return false;
				}
				var escapedFont = encodeURIComponent(font).replace(/%20/g,'+');
				var fontUrl = 'http://fonts.googleapis.com/css?family=' + escapedFont;
				var alreadySet = false;
				jQuery(document).find('link').each(function(index, link){
					if (jQuery(link).getProperty('href') == fontUrl) {
						alreadySet = true;
					}
				});
				if (alreadySet) {
					return true;
				}
				var newLink = $('<link/>', {
					href: fontUrl,
					rel: 'stylesheet',
					type: 'text/css'
				}).appendTo(document.head);
				
				return true;
			}
		};
}(jQuery);

