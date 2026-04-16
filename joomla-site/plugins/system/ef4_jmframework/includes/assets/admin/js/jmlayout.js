/* Admin JMLayoutBuilder */

!function($) {

	var JMLayoutBuilder = window.JMLayoutBuilder = window.JMLayoutBuilder || {

		loadLayout : function() {

			$('#' + JMLayoutBuilder.field).after('<span class="jmajax-loader"></span>');
			$.ajax({
				async: false,
				url : JMLayoutBuilder.url,
				data : {
					jmajax : 'layout',
					jmtask: 'display',
					jmlayout : $('#'+JMLayoutBuilder.field).val(),
					jmt: Date.now()
				}
			}).done(function(response) {
				JMLayoutBuilder.parseLayout(response);
				JMLayoutBuilder.preview.parent().find('.jm_layoutbuilder_build_positions').trigger('click');
				JMLayoutBuilder.getDefault();
				JMLayoutBuilder.loadParams();
				if($('#jm_layoutbuilder_assigns').hasClass('changed')) {
					JMLayoutBuilder.loadAssigns();
				}
			}).fail(function(xhr, status, error) {
				JMLayoutBuilder.alert(error, 'error', "Status: " + status);
			}).always(function() {
				$('.jmajax-loader').remove();
			});

		},

		layout: {
			screens: ['default', 'wide', 'normal', 'xtablet', 'tablet', 'mobile'],
			maxcol: {
				'default': 6,
				'normal': 6,
				'wide': 6,
				'xtablet': 4,
				'tablet': 2,
				'mobile': 2
			},
			minspan: {
				'default': 2,
				'normal': 2,
				'wide': 2,
				'xtablet': 3,
				'tablet': 6,
				'mobile': 6
			},
			unitspan: {
				'default': 1,
				'normal': 1,
				'wide': 1,
				'xtablet': 1,
				'tablet': 6,
				'mobile': 6
			},
			dlayout: 'default',
			clayout: 'default',
			nlayout: 'default',
			maxgrid: 12,
			maxcols: 6,
			build: 0,
			spanX: /(\s*)span(\d+)(\s*)/g,
			spanptrn: 'span{size}',
			hiddenptrn: 'hidden',
			firstptrn: 'first-span',
			span: 'span',
			rspace: /\s+/,
			rclass: /[\t\r\n]/g
		},

		initSubmit: function(){

			var form = document.adminForm;
			if(!form){
				return false;
			}

			var onsubmit = form.onsubmit;

			form.onsubmit = function(e){

				(form.task.value && form.task.value.indexOf('.cancel') != -1) ?
					($.isFunction(onsubmit) ? onsubmit() : false) : JMLayoutBuilder.saveLayout(onsubmit);
			};
		},

		initLayout: function(){
			var jmlayout = $('#jm_layoutbuilder_container'),
				preview = $('#jm_layoutbuilder_preview'),
				screens = jmlayout.find('.jm_layoutbuilder_screen'),
				restoreScreen = jmlayout.find('.jm_layoutbuilder_restore_screen'),
				restorePositions = jmlayout.find('.jm_layoutbuilder_restore_positions'),
				restoreOrder = jmlayout.find('.jm_layoutbuilder_restore_order'),
				fullRestore = jmlayout.find('.jm_layoutbuilder_full_restore'),
				posList = $('#jm_layoutbuilder_module_positions');

			screens.on('click', 'a', function(e){
				e.preventDefault();

				if( !$(this).parent().hasClass('active') ) {

					$(this).parent().addClass('active').removeClass('next prev').siblings().removeClass('active next prev');
					$(this).parent().prev().addClass('prev');
					$(this).parent().next().addClass('next');

					$('.jm_layoutbuilder_build_res_tab').addClass('active').siblings().removeClass('active');

					var nlayout = $(this).attr('data-screen');

					if( nlayout ) {
						preview.removeClass(JMLayoutBuilder.layout.clayout);
						preview.addClass(nlayout);

						JMLayoutBuilder.updateScreen(nlayout);
						//console.log(nlayout);
					}

				}

				if ( $(this).hasClass('jm_layoutbuilder_device') ) { //responsive views
					preview.removeClass('jm_layoutbuilder_build_hide').removeClass('jm_layoutbuilder_build_pos').addClass('jm_layoutbuilder_build_res');

					JMLayoutBuilder.layout.build = 1;

					posList.hide();

					preview.find('.jm_layoutbuilder_visible').each(JMLayoutBuilder.updateVisible);
					preview.find('.jm_layoutbuilder_block').each(JMLayoutBuilder.updateBlockVisible);

				} else { //structure view
					preview.removeClass('jm_layoutbuilder_build_hide').removeClass('jm_layoutbuilder_build_res').addClass('jm_layoutbuilder_build_pos');

					JMLayoutBuilder.layout.build = 0;

					preview.removeClass(JMLayoutBuilder.layout.clayout).addClass(JMLayoutBuilder.layout.dlayout);
					JMLayoutBuilder.updateScreen(JMLayoutBuilder.layout.dlayout);
				}

			});

			JMLayoutBuilder.fullRestore = fullRestore.on('click', JMLayoutBuilder.fullRestore);
			JMLayoutBuilder.restorePositions = restorePositions.on('click', JMLayoutBuilder.restorePositions);
			JMLayoutBuilder.restoreOrder = restoreOrder.on('click', JMLayoutBuilder.restoreOrder);
			JMLayoutBuilder.restoreScreen = restoreScreen.on('click', JMLayoutBuilder.restoreScreen);
			JMLayoutBuilder.posList = posList.appendTo(document.body).on('click', function(e){ return false; });
			JMLayoutBuilder.posSelect = posList.find('select');

			JMLayoutBuilder.posSelect.on('change', function(){

				var current = JMLayoutBuilder.current;

				if(current){
					$(current).parent().removeClass('noname el-active').find('h4').html(this.value || JMLayoutBuilder.lang.PLG_SYSTEM_JMFRAMEWORK_EMPTY_POSITION);
					$(this).closest('.popover').hide();

					var jflxb = $(current).parent().parent().parent();
					if(jflxb.attr('data-flexiblock')){
						var spanidx = $(current).closest('.jm_layoutbuilder_column').index();
						jflxb.nextAll('.jm_layoutbuilder_hidden_elems').children().eq(spanidx).html((this.value || JMLayoutBuilder.lang.PLG_SYSTEM_JMFRAMEWORK_EMPTY_POSITION) + '<i class="icon-eye-close">');
					} else {
						$(current).parent().next('.jm_layoutbuilder_hidden_elems').children().html((this.value || JMLayoutBuilder.lang.PLG_SYSTEM_JMFRAMEWORK_EMPTY_POSITION) + '<i class="icon-eye-close">');
					}

					if(!this.value){
						$(current).parent().addClass('noname');
					}

					$(this)
						.next('.jm_layoutbuilder_remove_pos').toggleClass('disabled', !this.value)
						.next('.jm_layoutbuilder_default_pos').toggleClass('disabled', this.value == $(current).closest('[data-name]').attr('data-name'));
				}

				return false;
			}).on('mousedown', 'optgroup', function(e){

				if(e.target && e.target.tagName.toLowerCase() == 'optgroup'){
					return false;
				}
			});

			posList.find('.jm_layoutbuilder_remove_pos, .jm_layoutbuilder_default_pos')
				.on('click', function(){
					var current = JMLayoutBuilder.current;

					if(current && !$(this).hasClass('disabled')){
						var vdef = $(this).hasClass('jm_layoutbuilder_default_pos') ? $(current).closest('[data-name]').attr('data-name') : '';
						JMLayoutBuilder.posSelect.val(vdef).trigger('change');
					}

					return false;
				});

			$(document).off('click.jmlayout').on('click.jmlayout', function(){
				var current = JMLayoutBuilder.current;

				if(current){
					$(current).parent().removeClass('el-active');
				}

				posList.hide();
			});

		},

		alert: function(msg, type, title, placeholder){
			//add new
			var alrt = $([
				'<div class="alert alert-', (type || 'info'), ' fade in">',
					'<button type="button" class="close" data-dismiss="alert">&times;</button>',
					(title ? '<h4 class="alert-heading">' + title + '</h4>' : ''),
					'<div>', msg, '</div>',
				'</div>'].join(''));
			alrt.appendTo(placeholder || $('#layoutbuilder_msg'))
			alrt.alert();
			setTimeout(function(){ alrt.alert('close') }, 10000);
		},

		resetLayout: function(){
			//console.log('resetlayout');
			var jmlayout = $('#jm_layoutbuilder_container'),
				preview = $('#jm_layoutbuilder_preview');

			preview.removeClass('jm_layoutbuilder_build_res').addClass('jm_layoutbuilder_build_pos');
			preview.removeClass(JMLayoutBuilder.layout.clayout).addClass(JMLayoutBuilder.layout.dlayout);

			JMLayoutBuilder.layout.build = 0;
			JMLayoutBuilder.layout.clayout = JMLayoutBuilder.layout.dlayout;
		},

		initActions: function(){
			//console.log('initactions');
			$('#jm_layoutbuilder_copy_modal')
			.appendTo(document.body)
			.prop('hide', false) //remove mootool hide function
			.on('show', function(){
				$('#jm_layoutbuilder_layout_copy_name').val($('#'+JMLayoutBuilder.field).val() + '-clone').focus();
			})
			.find('.btn-success').on('click', JMLayoutBuilder.copyLayout);

			$('#jm_layoutbuilder_remove_modal')
			.appendTo(document.body)
			.prop('hide', false) //remove mootool hide function
			.find('.btn-danger').on('click', JMLayoutBuilder.removeLayout);

			$('#jm_layoutbuilder_copy').on('click', function(){
				$('#jm_layoutbuilder_copy_modal').modal('show');
				return false;
			});

			$('#jm_layoutbuilder_remove').on('click', function(){
				$('#jm_layoutbuilder_remove_modal').modal('show');
				return false;
			});

			$('#jform_params_responsiveLayout').click(function(e){
				var val = $('input[name="jform[params][responsiveLayout]"]:checked').val();
				if(val == 1) {
					$('#jm_layoutbuilder_container').removeClass('responsive-disabled');
				} else {
					$('#jm_layoutbuilder_container').addClass('responsive-disabled');
					if(!e.isTrigger){
						$('#jm_layoutbuilder_container').find('.jm_layoutbuilder_screen [data-screen]:eq(1)').removeClass('active').trigger('click');
						$('#jm_layoutbuilder_container').find('.jm_layoutbuilder_restore_screen').trigger('click');
					}
				}
			});
			$('#jform_params_responsiveLayout').trigger('click');

			$('#jm_layoutbuilder_save').on('click', function(){
				JMLayoutBuilder.saveLayout();
				return false;
			});

			$('#jm_layoutbuilder_setdefault').on('click', function(){
				JMLayoutBuilder.setDefault();
				return false;
			});

			$('#jm_layoutbuilder_assigns').on('click', function(){
				if(!$(this).hasClass('active')) {
					$(this).addClass('active');
					$('.jm_layoutbuilder_assigns_tab').addClass('active').siblings().removeClass('active');
				} else {
					$(this).removeClass('active');
					$('.jm_layoutbuilder_assigns_tab').removeClass('active');
					$('.jm_layoutbuilder_build_res_tab').addClass('active');
				}

				if(!$(this).hasClass('changed')) {
					JMLayoutBuilder.loadAssigns();
					$(this).addClass('changed');
				}
				return false;
			});

		},

		saveLayout: function(callback){
			//console.log('saveLayout');
			if($('#jm_layoutbuilder_assigns').hasClass('changed')) {
				JMLayoutBuilder.saveAssigns(JMLayoutBuilder.saveLayout.bind(callback));
				return false;
			}

			$('#' + JMLayoutBuilder.field).after('<span class="jmajax-loader"></span>');

			JMLayoutBuilder.submit({
				jmajax: 'layout',
				jmtask: 'save',
				jmlayout: $('#'+JMLayoutBuilder.field).val()

			}, JMLayoutBuilder.getLayoutData(), function(json){

				if(typeof json == 'object'){

					JMLayoutBuilder.alert(json.msg, json.type);

					if(json && json.type == 'success') {

						if($.isFunction(callback)){
							callback();
						}

					}
				}
			});

			return false;
		},

		setDefault: function(){
			//console.log('setDefault');
			$('#jm_layoutbuilder_setdefault').after('<span class="jmajax-loader"></span>');

			JMLayoutBuilder.submit({
				jmajax: 'layout',
				jmtask: 'setdefault',
				jmlayout: $('#'+JMLayoutBuilder.field).val()

			}, function(json){

				if(typeof json == 'object'){

					JMLayoutBuilder.alert(json.msg, json.type);

					if(json && json.type == 'success') {

						$('#jm_layoutbuilder_setdefault').attr('disabled', true);
						$('#jm_layoutbuilder_setdefault').tooltip('hide');

						if($('#jm_layoutbuilder_assigns').hasClass('changed')) {
							JMLayoutBuilder.loadAssigns();
						}

						$('#'+JMLayoutBuilder.field).find('option').each(function(idx){
							if($('#'+JMLayoutBuilder.field).val() == $(this).val()) {
								$(this).text($(this).text() + ' [DEFAULT]');
							} else {
								$(this).text($(this).text().replace(' [DEFAULT]',''));
							}
						});

						$('#'+JMLayoutBuilder.field).trigger("liszt:updated.chosen");
					}
				}
			});

			return false;

		},

		getDefault: function(){
			//console.log('getDefault');
			var default_layout = $('#'+JMLayoutBuilder.field).val();

			$('#'+JMLayoutBuilder.field).find('option').each(function(idx){
				if($(this).text().search('[DEFAULT]')>0) {
					default_layout = $(this).val();
				}
			});

			if(default_layout == $('#'+JMLayoutBuilder.field).val()) {
				$('#jm_layoutbuilder_setdefault').attr('disabled', true);
			} else {
				$('#jm_layoutbuilder_setdefault').attr('disabled', false);
			}
		},

		loadAssigns : function() {
			//console.log('loadAssigns');
			$('#jm_layoutbuilder_assigns').after('<span class="jmajax-loader"></span>');
			$.ajax({
				async: false,
				url : JMLayoutBuilder.url,
				data : {
					jmajax : 'layout',
					jmtask: 'load_assigns',
					jmlayout : $('#'+JMLayoutBuilder.field).val(),
					jmt: Date.now()
				}
			}).done(function(response) {
				$('#layout_assigns').empty().html(response);
				JMLayoutBuilder.updateTooltips();
			}).fail(function(xhr, status, error) {
				JMLayoutBuilder.alert(error, 'error', "Status: " + status);
			}).always(function() {
				$('.jmajax-loader').remove();
			});

		},

		saveAssigns: function(callback){
			//console.log('saveAssigns');
			$('#jm_layoutbuilder_assigns').after('<span class="jmajax-loader"></span>');

			var checked = {};
			$('#layout_assigns input:checked').each(function(idx){
				checked[idx] = $(this).val();
			});

			JMLayoutBuilder.submit({
				jmajax: 'layout',
				jmtask: 'save_assigns',
				jmlayout: $('#'+JMLayoutBuilder.field).val()

			}, checked, function(json){

				$('#layout_assigns').empty();
				$('#jm_layoutbuilder_assigns').removeClass('changed active');

				if(typeof json == 'object'){

					JMLayoutBuilder.alert(json.msg, json.type);

					if(json && json.type == 'success') {

						if($.isFunction(callback)){
							callback();
						}

					}
				}
			});

			return false;
		},

		loadParams: function(){
			//console.log('loadParams');
			JMLayoutBuilder.submit({
				jmajax: 'layout',
				jmtask: 'load_params',
				jmlayout: $('#'+JMLayoutBuilder.field).val()

			}, function(json){
				//console.log(json);
				if(typeof json == 'object'){

					$('#layoutbuilder_tmpl_width').val(json['#tmplWidth']);
					$('#layoutbuilder_tmpl_space').val(json['#tmplSpace']);
				}
			});

		},

		copyLayout: function(){
			//console.log('copyLayout');
			var cname = $('#jm_layoutbuilder_layout_copy_name').val();
			if(cname){
				cname = cname.replace(/[^0-9a-zA-Z_-]/g, '').replace(/ /, '').toLowerCase();
			}

			if(cname == ''){
				JMLayoutBuilder.alert(JMLayoutBuilder.lang.PLG_SYSTEM_JMFRAMEWORK_CORRECT_LAYOUT_NAME, 'warning', '', $('#jm_layoutbuilder_layout_copy_name').parent().parent());
				return false;
			}

			$('#jm_layoutbuilder_copy_modal .btn-success').after('<span class="jmajax-loader"></span>');

			JMLayoutBuilder.submit({
				jmajax: 'layout',
				jmtask: 'copy',
				jmlayout: $('#'+JMLayoutBuilder.field).val(),
				jmcname: cname
			}, JMLayoutBuilder.getLayoutData(), function(json){
				if(typeof json == 'object'){

					if(json && json.type == 'success') {
						var layout = document.getElementById(JMLayoutBuilder.field);
						layout.options[layout.options.length] = new Option(json.data.layout, json.data.layout);
						layout.options[layout.options.length - 1].selected = true;
						$('#'+JMLayoutBuilder.field).trigger("liszt:updated.chosen");

						$('#jm_layoutbuilder_copy_modal').modal('hide');
						JMLayoutBuilder.alert(json.msg, json.type);
						JMLayoutBuilder.loadLayout();
					} else {
						JMLayoutBuilder.alert(json.msg, json.type, null, $('#jm_layoutbuilder_layout_copy_name').parent().parent());
					}
				}
			});
		},

		removeLayout: function(){
			//console.log('removeLayout');
			$('#jm_layoutbuilder_remove_modal .modal-body').append('<span class="jmajax-loader"></span>');

			JMLayoutBuilder.submit({
				jmajax: 'layout',
				jmtask: 'remove',
				jmlayout: $('#'+JMLayoutBuilder.field).val()
			}, function(json){
				if(typeof json == 'object'){

					if(json && json.type == 'success') {
						var layout = document.getElementById(JMLayoutBuilder.field),
							options = layout.options,
							dlayout = 0;

						for(var j = 0, jl = options.length; j < jl; j++){
							if(options[j].value == json.data.layout){
								layout.remove(j);
								break;
							}
						}
						for(var j = 0, jl = options.length; j < jl; j++){
							if(options[j].value == json.data.default_layout){
								dlayout = j;
								break;
							}
						}

						options[dlayout].selected = true;

						if(options[dlayout].text.search('[DEFAULT]') < 0) {
							JMLayoutBuilder.setDefault();
						}
						$('#'+JMLayoutBuilder.field).trigger("liszt:updated.chosen");

						$('#jm_layoutbuilder_remove_modal').modal('hide');
						JMLayoutBuilder.alert(json.msg, json.type);
						JMLayoutBuilder.loadLayout();
					} else {
						JMLayoutBuilder.alert(json.msg, json.type, null, $('#jm_layoutbuilder_remove_modal .modal-body'));
					}
				}
			});
		},

		getLayoutData: function(){
			//console.log('getLayoutData');
			var json = {},
				jcontainer = $('#jm_layoutbuilder_container'),
				jblocks = jcontainer.find('.jm_layoutbuilder_block').not('#jm_layoutbuilder_excluded_blocks > .jm_layoutbuilder_block, .jm_layoutbuilder_block .jm_layoutbuilder_block'),
				jmodules = jcontainer.find('.jm_layoutbuilder_element.type-modules').not('#jm_layoutbuilder_excluded_blocks .jm_layoutbuilder_element.type-modules'),
				jflxbs = jcontainer.find('[data-flexiblock]').not('#jm_layoutbuilder_excluded_blocks [data-flexiblock]'),
				jflxblocks = jflxbs.find('.jm_layoutbuilder_element.type-modules'),
				screens = jcontainer.find('.jm_layoutbuilder_screen').find('[data-screen]');

			// update grid for each screen
			screens.each(function(index){
				$(this).trigger('click');
			});
			JMLayoutBuilder.preview.parent().find('.jm_layoutbuilder_build_positions').trigger('click');

			json['____#scheme'] = JMLayoutBuilder.layout.scheme;

			json['____#tmplWidth'] = $('#layoutbuilder_tmpl_width').val();
			json['____#tmplSpace'] = $('#layoutbuilder_tmpl_space').val();

			json['____#leftWidth'] = parseInt($('[data-column="l"]').data('default').replace(/(.*?)span(\d+)(.*)/, "$2"));
			json['____#rightWidth'] = parseInt($('[data-column="r"]').data('default').replace(/(.*?)span(\d+)(.*)/, "$2"));

			jblocks.each(function(index){

				var name = $(this).attr('data-block'),
					visible = $(this).find('[data-block-visible]').first().data('data-visible'),
					info = JMLayoutBuilder.emptyScreen();

				info.ordering = index;
				info.fixedWidth = $(this).hasClass('fixed-width') ? 1 : 0;
				info.fullWidth = $(this).hasClass('full-width') ? 1 : 0;

				if(visible){
					visible = JMLayoutBuilder.visible(0, visible.vals);
					JMLayoutBuilder.parseVisible(info, visible);
				}

				//optimize
				JMLayoutBuilder.parseParams(info);

				json['block#' + name] = info;
			});

			jmodules.not(jflxbs).not(jflxblocks).not('.jm_layoutbuilder_constpos').each(function(){
				var name = $(this).attr('data-name'),
					val = $(this).find('.jm_layoutbuilder_el_name').html(),
					visible = $(this).closest('[data-visible]').data('data-visible'),
					others = $(this).closest('[data-others]').data('data-others'),
					info = JMLayoutBuilder.emptyScreen();

				info.position = val ? val : '';

				if(visible){
					visible = JMLayoutBuilder.visible(0, visible.vals);
					JMLayoutBuilder.parseVisible(info, visible);
					JMLayoutBuilder.parseOthers(info, others);
				}

				//optimize
				JMLayoutBuilder.parseParams(info);

				json[name] = info;
			});

			jflxbs.each(function(){
				var name = $(this).attr('data-flexiblock'),
					vis = $(this).data('data-visible'),
					widths = $(this).data('data-sizes'),
					firsts = $(this).data('data-firsts'),
					others = $(this).data('data-others');

				$(this).children().each(function(idx){
					var jpos = $(this),
						//pname = jpos.find('.jm_layoutbuilder_element.type-modules').attr('data-name'),
						val = jpos.find('.jm_layoutbuilder_el_name').html(),
						info = JMLayoutBuilder.emptyScreen(),
						width = JMLayoutBuilder.getSize(idx, widths),
						visible = JMLayoutBuilder.visible(idx, vis.vals),
						first = JMLayoutBuilder.doFirst(idx, firsts),
						other = JMLayoutBuilder.others(idx, others);

					info.position = val ? val : '';

					JMLayoutBuilder.parseSize(info, width);
					JMLayoutBuilder.parseVisible(info, visible);
					JMLayoutBuilder.parseFirst(info, first);
					JMLayoutBuilder.parseOthers(info, other);

					//optimize
					JMLayoutBuilder.parseParams(info);

					json['column' + (idx + 1) + '#' + name] = info;

				});
			});
			//console.log(json);
			return json;
		},

		submit: function(params, data, callback){
			//console.log('submit');
			if(!callback){
				callback = data;
				data = null;
			}

			$.ajax({
				async: false,
				url: JMLayoutBuilder.mergeurl($.param(params)),
				type: data ? 'post' : 'get',
				data: data,
				success: function(rsp){

					rsp = $.trim(rsp);
					if(rsp){
						var json = rsp;
						if(rsp.charAt(0) != '[' && rsp.charAt(0) != '{'){
							json = rsp.match(/{.*?}/);
							if(json && json[0]){
								json = json[0];
							}
						}

						if(json && typeof json == 'string'){
							json = $.parseJSON(json);
						}
					}

					if($.isFunction(callback)){
						callback(json || rsp);
					} else {
						JMLayoutBuilder.alert(json.msg, json.type);
					}
				},
				error: function(xhr, status, error) {
					JMLayoutBuilder.alert(error, 'error', "Status: " + status);
				},
				complete: function(){
					$('.jmajax-loader').remove();
				}
			});
		},

		mergeurl: function(query, base){
			base = base || window.location.href;
			var urlparts = base.split('#');

			if(urlparts[0].indexOf('?') == -1){
				urlparts[0] += '?' + query;
			} else {
				urlparts[0] += '&' + query;
			}

			return urlparts.join('#');
		},

		jmcopy: function(dst, src, valueonly){
			for(var p in src){
				if(src.hasOwnProperty(p)){
					if(!dst[p]){
						dst[p] = [];
					}

					for(var i = 0, s = src[p], il = s.length; i < il; i++){
						if(!valueonly || valueonly && s[i]){
							dst[p][i] = s[i];
						}
					}
				}
			}

			return dst;
		},

		equalHeights: function(){
			// Store the tallest element's height
			$(JMLayoutBuilder.preview.find('.row-fluid').not('.jm-flexiblock, .ui-sortable').get().reverse()).each(function(){
				var jrow = $(this),
					jchilds = jrow.children(),
					//offset = jrow.offset().top,
					height = 0,
					maxHeight = 0;

				jchilds.each(function () {
					height = $(this).css('height', '').css('min-height', '').height();
					maxHeight = (height > maxHeight) ? height : maxHeight;
				});

				if(!JMLayoutBuilder.layout.build){
					jchilds.css('min-height', maxHeight);
				}
			});
		},

		removeClass: function(clslist, clsremove){
			var removes = ( clsremove || '' ).split( JMLayoutBuilder.layout.rspace ),
				lookup = (' '+ clslist + ' ').replace( JMLayoutBuilder.layout.rclass, ' '),
				result = [];

			for ( var c = 0, cl = removes.length; c < cl; c++ ) {
				if ( lookup.indexOf(' '+ removes[ c ] + ' ') == -1 ) {
					result.push(removes[c]);
				}
			}

			return result.join(' ');
		},

		parseParams: function(pos){

				//optimize
				var defdv  = JMLayoutBuilder.layout.dlayout,
					defcls = pos[defdv];

				for(var p in pos){
					if(pos.hasOwnProperty(p) && pos[p] === defcls && p != defdv){
						pos[p] = JMLayoutBuilder.removeClass(defcls, pos[p]);
					}
				}

				//if(pos.mobile){
					//pos.mobile = JMLayoutBuilder.removeClass('span100 ' + JMLayoutBuilder.firstClass('mobile'), pos.mobile);
				//}

				//if(pos.tablet){
					//pos.tablet = JMLayoutBuilder.removeClass('span100 ' + JMLayoutBuilder.firstClass('tablet'), pos.tablet);
				//}

			//remove empty property
			for(var p in pos){
				if(pos[p] === ''){
					delete pos[p];
				} else {
					pos[p] = $.trim(pos[p]);
				}
			}
		},

		parseSize: function(result, info){
			for(var p in info){
				if(info.hasOwnProperty(p)){
					result[p] += this.sizeClass(p, JMLayoutBuilder.sizeConvert(info[p], p));
				}
			}
		},

		parseVisible: function(result, info){
			for(var p in info){
				if(info.hasOwnProperty(p) && info[p] == 1){
					result[p] += ' ' + JMLayoutBuilder.hiddenClass(p);
				}
			}
		},

		parseFirst: function(result, info){
			for(var p in info){
				if(info.hasOwnProperty(p) && info[p] == 1){
					result[p] += ' ' + JMLayoutBuilder.firstClass(p);
				}
			}
		},

		parseOthers: function(result, info){
			for(var p in info){
				if(info.hasOwnProperty(p) && info[p] != ''){
					result[p] += ' ' + info[p];
				}
			}
		},

		calculateSize: function(numpos){
			var result = [],
				avg = Math.floor(JMLayoutBuilder.layout.maxgrid / numpos),
				sum = 0;

			for(var i = 0; i < numpos - 1; i++){
				result.push(avg);
				sum += avg;
			}

			result.push(JMLayoutBuilder.layout.maxgrid - sum);

			return result;
		},

		generateSize: function(layout, numpos){
			var cminspan = JMLayoutBuilder.layout.minspan[layout],
				total = cminspan * numpos;

			if(total <= JMLayoutBuilder.layout.maxgrid) {
				return JMLayoutBuilder.calculateSize(numpos);
			} else {

				var result = [],
					rows = Math.ceil(total / JMLayoutBuilder.layout.maxgrid),
					cols = Math.ceil(numpos / rows);

				for(var i = 0; i < rows - 1; i++){
					result = result.concat(JMLayoutBuilder.calculateSize(cols));
					numpos -= cols;
				}

				result = result.concat(JMLayoutBuilder.calculateSize(numpos));
			}

			return result;
		},

		sizeVisible: function(widths, visibles, numpos){
			var i, dv, nvisible,
				width, visible, visibleIdxs = [];

			for(dv in widths){
				if(widths.hasOwnProperty(dv)){
					visible = visibles[dv],
					visibleIdxs.length = 0,
					nvisible = 0;

					for(i = 0; i < numpos; i++){
						if(visible[i] == 0 || visible[i] == undefined){
							visibleIdxs.push(i);
						}
					}

					width = JMLayoutBuilder.generateSize(dv, visibleIdxs.length);

					for(i = 0; i < visibleIdxs.length; i++){
						widths[dv][visibleIdxs[i]] = width[i];
					}
				}
			}
		},

		getSize: function(pidx, widths){
			var result = this.emptyScreen(0),
				dv;

			for(dv in widths){
				if(widths.hasOwnProperty(dv)){
					result[dv] = widths[dv][pidx];
				}
			}

			return result;
		},

		sizeConvert: function(span, layout){
			return ((layout || JMLayoutBuilder.layout.clayout) == 'mobile' || (layout || JMLayoutBuilder.layout.clayout) == 'tablet') ? Math.floor(span / JMLayoutBuilder.layout.maxgrid * 100) : span;
		},

		visible: function(pidx, visible){
			var result = this.emptyScreen(0),
				dv;

			for(dv in visible){
				if(visible.hasOwnProperty(dv)){
					result[dv] = visible[dv][pidx] || 0;
				}
			}

			return result;
		},

		doFirst: function(pidx, firsts){
			var result = this.emptyScreen(0),
				dv;

			for(dv in firsts){
				if(firsts.hasOwnProperty(dv)){
					result[dv] = firsts[dv][pidx] || 0;
				}
			}

			return result;
		},

		others: function(pidx, others){
			var result = this.emptyScreen(),
				dv;

			for(dv in others){
				if(others.hasOwnProperty(dv)){
					result[dv] = others[dv][pidx] || '';
				}
			}

			return result;
		},

		// change the grid limit
		updateGrid: function (flxb) {
			//update grid and limit for resizable
			var jflxb = $(flxb);
			var layout = JMLayoutBuilder.layout.clayout;
			var cmaxcol = JMLayoutBuilder.layout.maxcol[layout];
			var columnspan = $('<div class="' + JMLayoutBuilder.sizeClass(layout, JMLayoutBuilder.layout.unitspan[layout]) + '"></div>').appendTo(jflxb);
			var jminspan = $('<div class="' + JMLayoutBuilder.sizeClass(layout, JMLayoutBuilder.layout.minspan[layout]) + '"></div>').appendTo(jflxb);
			var gridsize = Math.floor(columnspan.outerWidth());
			var minsize = Math.floor(jminspan.outerWidth());
			var widths = jflxb.data('data-sizes');
			var firsts = jflxb.data('data-firsts');
			var visible = jflxb.data('data-visible').vals[layout];
			var width = widths[layout];
			var first = firsts[layout];
			var needfirst = visible[0] == 1;
			var sum = 0;

			columnspan.remove();
			jminspan.remove();

			jflxb.data('rzdata', {
				grid: gridsize,
				gap: 0,
				minwidth: gridsize,
				maxwidth: minsize * cmaxcol
			});

			jflxb.find('.jm_layoutbuilder_column').each(function(idx){
				if(visible[idx] == 0 || visible[idx] == undefined){ //ignore all hidden spans
					if(needfirst || (sum + parseInt(width[idx]) > JMLayoutBuilder.layout.maxgrid)){
						$(this).addClass(JMLayoutBuilder.firstClass(layout));
						sum = parseInt(width[idx]);
						first[idx] = 1;
						needfirst = false;
					} else {
						$(this).removeClass(JMLayoutBuilder.firstClass(layout));
						sum += parseInt(width[idx]);
						first[idx] = 0;
					}
				}
			});

		},

		// apply the visibility value for current screen - trigger when change screen
		updateVisible: function(index, item){
			var jvis = $(item);
			var jpos = jvis.parent();
			var jdata = jvis.closest('[data-visible]');
			var visible = jdata.data('data-visible').vals[JMLayoutBuilder.layout.clayout];
			var state, idx = 0;
			var flexiblock = jdata.attr('data-flexiblock');

			//if flexiblock -> get the index
			if(flexiblock){
				idx = jvis.closest('.jm_layoutbuilder_column').index();
			}

			state = visible[idx] || 0;

			if(flexiblock){
				jvis.closest('.jm_layoutbuilder_column').toggle(state == 0);

				var jhiddenpos = jdata.nextAll('.jm_layoutbuilder_hidden_elems');
				jhiddenpos.children().eq(idx).toggleClass('hide', state == 0);
				jhiddenpos.toggleClass('has-elems', !!(jhiddenpos.children().not('.hide, .jm-hide').length));
			} else {
				var jhiddenpos = jpos.next('.jm_layoutbuilder_hidden_elems');
				if(jhiddenpos.length){
					jhiddenpos.toggleClass('has-elems', state != 0);
					jpos.toggleClass('hide', state != 0);
				}
			}

			jvis.parent().toggleClass('element-hidden', state == 1 && JMLayoutBuilder.layout.build);
			jvis.children().removeClass('icon-eye-close icon-eye-open').addClass(state == 1 ? 'icon-eye-close' : 'icon-eye-open');
		},

		updateBlockVisible: function(index, item){

			var block = $(item);
			var mainblock = block.find('[data-block-visible]');

			if(mainblock.length == 0) return;

			var visible = mainblock.first().data('data-visible').vals[JMLayoutBuilder.layout.clayout];
			var state = visible || 0;

			block.toggleClass('block-hidden', state != 0);
		},

		// apply the change (width, columns) of flexiblock when change screen
		updateFlexiblock: function(si, flxb){

			var jflxb = $(flxb);
			var layout = JMLayoutBuilder.layout.clayout;
			var width = jflxb.data('data-sizes')[layout];

			jflxb.children().each(function(idx){
				//remove all class and reset style width
				this.className = this.className.replace(JMLayoutBuilder.layout.spanX, ' ');
				$(this).css('width', '').addClass(JMLayoutBuilder.sizeClass(layout, JMLayoutBuilder.sizeConvert(width[idx]))).find('.jm_layoutbuilder_el_size').html(width[idx]);
			});

			JMLayoutBuilder.updateGrid(flxb);
		},

		updateScreen: function(nlayout){

			var clayout = JMLayoutBuilder.layout.clayout;

			JMLayoutBuilder.elements.each(function(){
				var element = $(this);
				// no override for all screens
				if (!element.data('default')){
					return;
				}

				// keep default
				if (!element.data(nlayout) && (!clayout || !element.data(clayout))){
					return;
				}

				// remove current
				if (element.data(clayout)){
					element.removeClass(element.data(clayout));
				} else {
					element.removeClass (element.data('default'));
				}

				// add new
				if (element.data(nlayout)){
					element.addClass (element.data(nlayout));
				} else{
					element.addClass (element.data('default'));
				}
			});

			JMLayoutBuilder.layout.clayout = nlayout;

			//apply width from previous settings
			JMLayoutBuilder.preview
			.find('.jm_layoutbuilder_element.type-modules .jm_layoutbuilder_el_size')
			.each(function(){
				var jparent = $(this).parentsUntil('.row-fluid, .row, .container-fluid, .container').last();
				var span = parseInt(jparent.prop('className').replace(/(.*?)span(\d+)(.*)/, "$2"));

				if(isNaN(span)){
					span = JMLayoutBuilder.lang.PLG_SYSTEM_JMFRAMEWORK_UNKNOWN_WIDTH;
				} else {
					if(span == 100) span = 12;
					if(span == 50) span = 6;
				}

				$(this).text(span);
			});

			JMLayoutBuilder.jflxbs.each(JMLayoutBuilder.updateFlexiblock);
			JMLayoutBuilder.preview.find('.jm_layoutbuilder_visible').each(JMLayoutBuilder.updateVisible);
			JMLayoutBuilder.preview.find('.jm_layoutbuilder_block').each(JMLayoutBuilder.updateBlockVisible);

			JMLayoutBuilder.equalHeights();
			JMLayoutBuilder.updateSortable();
		},

		restoreScreen: function(){
			//console.log('restoreScreen');
			var layout = JMLayoutBuilder.layout.clayout;
			var jcontainer = JMLayoutBuilder.preview;
			var jmodules = jcontainer.find('.jm_layoutbuilder_element.type-modules');
			var jflxbs = jcontainer.find('[data-flexiblock]');
			var jflxblocks = jflxbs.find('.jm_layoutbuilder_element.type-modules');
			var blocks = jcontainer.find('[data-block-visible]');

			blocks.each(function(){
				var vis = $(this).data('data-visible');
				if(layout) {
					$.extend(true, vis.vals[layout], vis.deft[layout]);
				}
			});

			jmodules.not(jflxbs).not(jflxblocks).not('.jm_layoutbuilder_constpos').each(function(){
				var name = $(this).attr('data-name');
				var vis = $(this).closest('[data-visible]').data('data-visible');

				if(layout && vis){
					$.extend(true, vis.vals[layout], vis.deft[layout]);
				}
			});

			jflxbs.each(function(){
				var name = $(this).attr('data-flexiblock');
				var vis = $(this).data('data-visible');
				var widths = $(this).data('data-sizes');
				var owidths = $(this).data('data-osizes');
				var firsts = $(this).data('data-firsts');
				var ofirsts = $(this).data('data-ofirsts');

				$.extend(true, vis.vals[layout], vis.deft[layout]);
				$.extend(true, widths[layout], widths[layout].length == owidths[layout].length ? owidths[layout] : JMLayoutBuilder.generateSize(layout, widths[layout].length));
				$.extend(true, firsts[layout], ofirsts[layout]);

				for(var i = vis.deft[layout].length; i < JMLayoutBuilder.layout.maxcols; i++){
					vis.vals[layout][i] = 0;
				}

				for(var i = firsts[layout].length; i < JMLayoutBuilder.layout.maxcols; i++){
					firsts[layout][i] = '';
				}
			});

			jflxbs.each(JMLayoutBuilder.updateFlexiblock);
			jcontainer.find('.jm_layoutbuilder_visible').each(JMLayoutBuilder.updateVisible);
			jcontainer.find('.jm_layoutbuilder_block').each(JMLayoutBuilder.updateBlockVisible);

			JMLayoutBuilder.alert(JMLayoutBuilder.lang.PLG_SYSTEM_JMFRAMEWORK_LAYOUTBUILDER_RESTORE_SCREEN_DONE, 'success');

			return false;
		},

		fullRestore: function(){
			var layout = JMLayoutBuilder.layout.clayout;
			var jcontainer = JMLayoutBuilder.preview;
			var jmodules = jcontainer.find('.jm_layoutbuilder_element.type-modules');
			var jflxbs = jcontainer.find('[data-flexiblock]');
			var jflxblocks = jflxbs.find('.jm_layoutbuilder_element.type-modules');
			var blocks = jcontainer.find('[data-block-visible]');

			blocks.each(function(){
				var vis = $(this).data('data-visible');
				if(layout) {
					$.extend(true, vis.vals, vis.deft);
				}
			});

			jmodules.not(jflxbs).not(jflxblocks).not('.jm_layoutbuilder_constpos').each(function(){
				if($(this).find('[data-name]').length){
					return;
				}

				var name = $(this).attr('data-name');
				var vis = $(this).closest('[data-visible]').data('data-visible');

				//change the name
				$(this).find('.jm_layoutbuilder_el_name').html(name);
				if(vis){
					$.extend(true, vis.vals, vis.deft);
				}
			});

			jflxbs.each(function(){
				var jflxb = $(this);
				var jhides = jflxb.nextAll('.jm_layoutbuilder_hidden_elems').children();
				var vis = jflxb.data('data-visible');
				var widths = jflxb.data('data-sizes');
				var name = jflxb.attr('data-name').split(',');
				var owidths = jflxb.data('data-osizes');
				var numcols = owidths[JMLayoutBuilder.layout.dlayout].length;
				var html = [];

				for(var i = 0; i < numcols; i++){
					html = html.concat([
					'<div class="jm_layoutbuilder_column ', JMLayoutBuilder.sizeClass(JMLayoutBuilder.layout.clayout, owidths[JMLayoutBuilder.layout.dlayout][i]), '">', //we do not need convert width here
						'<div class="jm_layoutbuilder_element type-modules ', (name[i] == JMLayoutBuilder.lang.PLG_SYSTEM_JMFRAMEWORK_EMPTY_POSITION ? ' noname' : ''), '" data-name="', (name[i] || ''), '">',
							'<span class="jm_layoutbuilder_edit hasTooltipRight" title="' + JMLayoutBuilder.lang.PLG_SYSTEM_JMFRAMEWORK_EDIT_MODULE_POSITION + '"><i class="icon-edit"></i></span>',
							'<span class="jm_layoutbuilder_el_size hasTooltipRight" title="', JMLayoutBuilder.lang.PLG_SYSTEM_JMFRAMEWORK_ELEMENT_WIDTH, '">', owidths[JMLayoutBuilder.layout.dlayout][i], '</span>',
							'<h4 class="jm_layoutbuilder_el_name hasTooltip" title="', JMLayoutBuilder.lang.PLG_SYSTEM_JMFRAMEWORK_ELEMENT_WIDTH, '">', name[i], '</h4>',
							'<br /><span class="modules-chrome hasTooltipBottom" title="', JMLayoutBuilder.lang.PLG_SYSTEM_JMFRAMEWORK_MODULES_CHROME, '">', jflxb.attr('data-chrome') , '</span>',
							'<span class="jm_layoutbuilder_visible hasTooltip" title="', JMLayoutBuilder.lang.PLG_SYSTEM_JMFRAMEWORK_HIDE_POSITION, '"><i class="icon-eye-open"></i></span>',
						'</div>',
						'<div class="jm_layoutbuilder_resizehandler hasTooltipBottom" title="', JMLayoutBuilder.lang.PLG_SYSTEM_JMFRAMEWORK_DRAG_TO_RESIZE, '"></div>',
					'</div>']);

					jhides.eq(i).html(name[i] + '<i class="icon-eye-close">').removeClass('jm-hide');
				}

				for(var i = numcols; i < JMLayoutBuilder.layout.maxcols; i++){
					jhides.eq(i).addClass('jm-hide');
				}

				//reset value
				$(this)
					.empty()
					.html(html.join(''));

				$.extend(true, vis.vals, vis.deft);
				$.extend(true, widths, owidths);

				$(this).nextAll('.jm_layoutbuilder_cols_number').children().eq(owidths[JMLayoutBuilder.layout.dlayout].length - 1).trigger('click');
			});

			//change to default view
			jcontainer.parent().find('.jm_layoutbuilder_build_positions').trigger('click');
			jcontainer.parent().find('.jm_layoutbuilder_restore_order').trigger('click');
			JMLayoutBuilder.updateTooltips();

			JMLayoutBuilder.alert(JMLayoutBuilder.lang.PLG_SYSTEM_JMFRAMEWORK_LAYOUTBUILDER_RESTORE_LAYOUT_DONE, 'success');

			return false;
		},

		restorePositions: function(){
			var layout = JMLayoutBuilder.layout.clayout;
			var jcontainer = JMLayoutBuilder.preview;
			var jmodules = jcontainer.find('.jm_layoutbuilder_element.type-modules');
			var jflxbs = jcontainer.find('[data-flexiblock]');
			var jflxblocks = jflxbs.find('.jm_layoutbuilder_element.type-modules');

			jmodules.not(jflxbs).not(jflxblocks).not('.jm_layoutbuilder_constpos').each(function(){
				//reset position
				$(this).find('.jm_layoutbuilder_el_name')
					.html(
						$(this).attr('data-name')
					)
					.parent()
					.removeClass('noname el-active');
			});

			jflxbs.each(function(){
				var name = $(this).attr('data-name').split(',');
				var jhides = $(this).nextAll('.jm_layoutbuilder_hidden_elems').children();

				$(this).find('.jm_layoutbuilder_element.type-modules').each(function(idx){
					if(name[idx] != undefined){
						$(this).toggleClass('noname', name[idx] == JMLayoutBuilder.lang.PLG_SYSTEM_JMFRAMEWORK_EMPTY_POSITION)
						.find('.jm_layoutbuilder_el_name')
						.html(name[idx]);

						jhides.eq(idx).html(name[idx] + '<i class="icon-eye-close">');
					} else {
						$(this).addClass('noname').find('.jm_layoutbuilder_el_name').html(JMLayoutBuilder.lang.PLG_SYSTEM_JMFRAMEWORK_EMPTY_POSITION);
					}
				});
			});

			JMLayoutBuilder.alert(JMLayoutBuilder.lang.PLG_SYSTEM_JMFRAMEWORK_LAYOUTBUILDER_RESTORE_MODULE_POS_DONE, 'success');

			return false;
		},

		onVisible: function(){
			var jvis = $(this);
			var jpos = jvis.parent();
			var jdata = jvis.closest('[data-visible]');
			var junits = null;
			var layout = JMLayoutBuilder.layout.clayout;
			var state = jpos.hasClass('element-hidden');
			var visible = jdata.data('data-visible').vals[layout];
			var flexiblock = jdata.attr('data-flexiblock');
			var idx = 0;

			//if flexiblock -> the name is based on block, else use the name property
			if(flexiblock){
				idx = jvis.closest('.jm_layoutbuilder_column').index();
				junits = jdata.children();
			}

			//toggle state
			state = 1 - state;

			if(flexiblock){
				jvis.closest('.jm_layoutbuilder_column')[state == 0 ? 'show' : 'hide']();

				var jhiddenpos = jdata.nextAll('.jm_layoutbuilder_hidden_elems');
				jhiddenpos.children().eq(idx).toggleClass('hide', state == 0);
				jhiddenpos.toggleClass('has-elems', !!(jhiddenpos.children().not('.hide, .jm-hide').length));

				var visibleIdxs = [];
				for(var i = 0, il = junits.length; i < il; i++){
					if(junits[i].style.display != 'none'){
						visibleIdxs.push(i);
					}
				}

				if(visibleIdxs.length){
					var widths = jdata.data('data-sizes')[layout];
					var width = JMLayoutBuilder.generateSize(layout, visibleIdxs.length);
					var vi = 0;

					for(var i = 0, il = visibleIdxs.length; i < il; i++){
						vi = visibleIdxs[i];
						widths[vi] = width[i];
						junits[vi].className = junits[vi].className.replace(JMLayoutBuilder.layout.spanX, ' ');
						junits.eq(vi).addClass(JMLayoutBuilder.sizeClass(layout, JMLayoutBuilder.sizeConvert(width[i]))).find('.jm_layoutbuilder_el_size').html(width[i]);
					}
				}
			} else {
				var jhiddenpos = jpos.next('.jm_layoutbuilder_hidden_elems');
				if(jhiddenpos.length){
					jhiddenpos.toggleClass('has-elems', state != 0);
					jpos.toggleClass('hide', state != 0);
				}
			}

			jpos.toggleClass('element-hidden', state == 1);
			jvis.children().removeClass('icon-eye-close icon-eye-open').addClass(state == 1 ? 'icon-eye-close' : 'icon-eye-open');

			visible[idx] = state;

			if(flexiblock){
				JMLayoutBuilder.updateGrid(jdata);
			}

			return false;
		},

		onBlockVisible: function(){

			var btn = $(this);
			var block = btn.closest('.jm_layoutbuilder_block');
			var mainblock = block.find('[data-block-visible]');

			if(mainblock.length == 0) return;

			var state = block.hasClass('block-hidden');
			var visible = mainblock.first().data('data-visible').vals[JMLayoutBuilder.layout.clayout];

			state = 1 - state;
			block.toggleClass('block-hidden');
			visible[0] = state;

			return false;
		},

		onBlockFullwidth: function(){

			var btn = $(this);
			var block = btn.closest('.jm_layoutbuilder_block');

			block.toggleClass('fixed-width');

			return false;
		},

		onSectionFullwidth: function(){

			var btn = $(this);
			var block = btn.closest('.jm_layoutbuilder_block');

			block.toggleClass('full-width');

			return false;
		},

		emptyScreen: function(val){
			var result = {};
			var screens = JMLayoutBuilder.layout.screens;

			val = typeof val != 'undefined' ? val : '';

			for(var i = 0; i < screens.length; i++){
				result[screens[i]] = val;
			}

			return result;
		},

		sizeClass: function(screen, width){
			return JMLayoutBuilder.layout.spanptrn.replace('{screen}', screen).replace('{size}', width);
		},

		hiddenClass: function(screen){
			return JMLayoutBuilder.layout.hiddenptrn.replace('{screen}', screen);
		},

		firstClass: function(screen){
			return JMLayoutBuilder.layout.firstptrn.replace('{screen}', screen);
		},

		parseLayout: function(response){

			if(response){
				var bdhtml = response.match(/<body[^>]*>([\w|\W]*)<\/body>/im);

				//stripScripts
				if(bdhtml){
					bdhtml = bdhtml[1].replace(new RegExp('<script[^>]*>([\\S\\s]*?)<\/script\\s*>', 'img'), '');
				}

				if(bdhtml){
					//clean bootstrap fixed class
					bdhtml = bdhtml.replace(/navbar-fixed-(top|bottom)/gi, '');

					var	current = JMLayoutBuilder.current = null;
					var preview = JMLayoutBuilder.preview = $('#jm_layoutbuilder_preview').empty().html(bdhtml);
					var elements = JMLayoutBuilder.elements = preview.find('[class*="span"]').each(function(){
							var element = $(this);
							element.data();
							element.removeAttr('data-default data-wide data-normal data-xtablet data-tablet data-mobile');
							if (!element.data('default')){
								element.data('default', element.attr('class'));
							}
						});
					preview.find('[data-block-visible]').each(function(){
						var element = $(this);
						element.data();
						element.removeAttr('data-default data-wide data-normal data-xtablet data-tablet data-mobile');
					});
					var posList = JMLayoutBuilder.posList;
					var posSelect = JMLayoutBuilder.posSelect;
					var jflxbs = JMLayoutBuilder.jflxbs = preview.find('[data-flexiblock]');
					var subBlocks = preview.find('.jm_layoutbuilder_block .jm_layoutbuilder_block');
					var blocksWrapper = $('#jm-blocks').length;

					//reset
					JMLayoutBuilder.resetLayout();

					preview
						.find('.jm_layoutbuilder_block').not(subBlocks).each(function(){
							var block = $(this);
							if(!blocksWrapper || block.parents('#jm-blocks').length || block.parents('#jm_layoutbuilder_excluded_blocks').length)
								block.append('<span class="sort-handle hasTooltipLeft" title="' + JMLayoutBuilder.lang.PLG_SYSTEM_JMFRAMEWORK_SORT_BLOCKS + '"><i class="icon-move"></i></span>');

							block.append('<span class="fullsection-off hasTooltipLeft" title="' + JMLayoutBuilder.lang.PLG_SYSTEM_JMFRAMEWORK_FULLWIDTH_SECTION_OFF + '"><i class="icon-contract-2"></i></span>')
							.append('<span class="fullsection-on hasTooltipLeft" title="' + JMLayoutBuilder.lang.PLG_SYSTEM_JMFRAMEWORK_FULLWIDTH_SECTION_ON + '"><i class="icon-expand-2"></i></span>')
							.on('click', '.fullsection-off, .fullsection-on', JMLayoutBuilder.onSectionFullwidth);

							var mainblock = block.find('[data-block-visible]');
							if(mainblock.length > 0) {

								block.append('<span class="block-hide hasTooltipLeft" title="' + JMLayoutBuilder.lang.PLG_SYSTEM_JMFRAMEWORK_HIDE_BLOCK + '"><i class="icon-eye-open"></i></span>')
								.append('<span class="block-show hasTooltipLeft" title="' + JMLayoutBuilder.lang.PLG_SYSTEM_JMFRAMEWORK_SHOW_BLOCK + '"><i class="icon-eye-close"></i></span>')
								.on('click', '.block-hide, .block-show', JMLayoutBuilder.onBlockVisible);

								var jdata = $(mainblock.first());
								jdata.data('data-visible', $.parseJSON(jdata.attr('data-visible'))).attr('data-visible', '');

							}

						});

					preview
						.find('.jm_layoutbuilder_element.type-modules')
						.not('.jm_layoutbuilder_constpos')
						.prepend('<span class="jm_layoutbuilder_edit hasTooltipRight" title="' + JMLayoutBuilder.lang.PLG_SYSTEM_JMFRAMEWORK_EDIT_MODULE_POSITION + '"><i class="icon-edit"></i></span>');

					preview
						.find('[data-visible]')
						.not('[data-flexiblock],[data-block-visible]')
						.each(function(){
							$(this)
								.data('data-visible', $.parseJSON($(this).attr('data-visible')))
								.data('data-others', $.parseJSON($(this).attr('data-others')))
								.attr('data-visible', '')
								.attr('data-others', '')
						})
						.find('.jm_layoutbuilder_element.type-modules')
						.each(function(){
							var jpos = $(this);

							jpos
							.append('<span class="jm_layoutbuilder_visible hasTooltip" title="' + JMLayoutBuilder.lang.PLG_SYSTEM_JMFRAMEWORK_HIDE_POSITION + '"><i class="icon-eye-open"></i></span>')
							.after(['<div class="jm_layoutbuilder_hidden_elems hasTooltip" title="', JMLayoutBuilder.lang.PLG_SYSTEM_JMFRAMEWORK_HIDDEN_POSITION_DESC, '">',
									'<span class="element-hidden hasTooltipBottom" title="', JMLayoutBuilder.lang.PLG_SYSTEM_JMFRAMEWORK_SHOW_POSITION, '">', jpos.find('h4').html() ,'<i class="icon-eye-close"></i></span>',
								'</div>'].join(''))
							.next()
							.find('.element-hidden')
								.on('click', function(){
									JMLayoutBuilder.onVisible.call(jpos.find('.jm_layoutbuilder_visible'));
									return false;
								});
						});

					preview
						.find('.jm_layoutbuilder_element.type-modules')
						.find('h4, h1')
						.addClass('jm_layoutbuilder_el_name')
						.attr('title', JMLayoutBuilder.lang.PLG_SYSTEM_JMFRAMEWORK_MODULE_POSITION_NAME)
						.each(function(){
							var jparent = $(this).parentsUntil('.row-fluid, .row, .container-fluid, .container').last();
							var span = parseInt(jparent.prop('className').replace(/(.*?)span(\d+)(.*)/, "$2"));

							if(isNaN(span)){
								span = JMLayoutBuilder.lang.PLG_SYSTEM_JMFRAMEWORK_UNKNOWN_WIDTH;
							}

							$(this).before('<span class="jm_layoutbuilder_el_size hasTooltipRight" title="' + JMLayoutBuilder.lang.PLG_SYSTEM_JMFRAMEWORK_ELEMENT_WIDTH + '">' + span + '</span>');
						});

					preview
						.off('click.jmvisible').off('click.jmedit')
						.on('click.jmvisible', '.jm_layoutbuilder_visible', JMLayoutBuilder.onVisible)
						.on('click.jmedit', '.jm_layoutbuilder_edit', function(e){
							if(current){
								$(current).parent().removeClass('el-active');
							}
							current = JMLayoutBuilder.current = this;

							var jspan = $(this);
							var offs = $(this).offset();

							jspan.parent().addClass('el-active');

							posList.removeClass('right').addClass('top');
							var top = offs.top - posList.height() -10;
							var left = offs.left + jspan.width() - posList.width() / 2 - 10;

							if(left < 0){
								posList.removeClass('top').addClass('right');
								top = offs.top - posList.height() /2;
								left = offs.left + jspan.width();
							}

							posList.css({
								top: top,
								left: left
							}).show()
								.find('select')
								.val(jspan.siblings('h4').html())
								.next('.jm_layoutbuilder_remove_pos').toggleClass('disabled', !posSelect.val())
								.next('.jm_layoutbuilder_default_pos').toggleClass('disabled', jspan.siblings('h4').html() == jspan.closest('[data-name]').attr('data-name'));

							posSelect.scrollTop(Math.min(posSelect.prop('scrollHeight') - posSelect.height(), posSelect.prop('selectedIndex') * (posSelect.prop('scrollHeight') / posSelect[0].options.length)));

							return false;
						});

						jflxbs.each(function(){

							var jncols = $([
								'<div class="btn-group jm_layoutbuilder_cols_number hasTooltip" title="',
								JMLayoutBuilder.lang.PLG_SYSTEM_JMFRAMEWORK_CHANGE_POSITOIN_NUMBER + '">',
								'<span class="btn btn-small btn-info">1</span>',
								'<span class="btn btn-small btn-info">2</span>',
								'<span class="btn btn-small btn-info">3</span>',
								'<span class="btn btn-small btn-info">4</span>',
								'<span class="btn btn-small btn-info">5</span>',
								'<span class="btn btn-small btn-info">6</span>',
								'</div>'].join('')).appendTo(this.parentNode);

							var jcols = $(this).children();
							var numpos = jcols.length;
							var flexiblock = this;
							var positions = [];
							var defpos = $(this).attr('data-name').replace(/\s+/g, '').split(',');
							var visibles = $.parseJSON($(this).attr('data-visible'));
							var twidths = $.parseJSON($(this).attr('data-sizes'));
							var widths = {};
							var owidths = $.parseJSON($(this).attr('data-osizes'));
							var ofirsts = $.parseJSON($(this).attr('data-ofirsts'));
							var firsts = $.parseJSON($(this).attr('data-firsts'));

							$(flexiblock).closest('.jm_layoutbuilder_block').append('<span class="fullwidth-off hasTooltipLeft" title="' + JMLayoutBuilder.lang.PLG_SYSTEM_JMFRAMEWORK_FULLWIDTH_OFF + '"><i class="icon-grid-2"></i></span>')
							.append('<span class="fullwidth-on hasTooltipLeft" title="' + JMLayoutBuilder.lang.PLG_SYSTEM_JMFRAMEWORK_FULLWIDTH_ON + '"><i class="icon-list"></i></span>')
							.on('click', '.fullwidth-off, .fullwidth-on', JMLayoutBuilder.onBlockFullwidth);

							$(flexiblock)
								.data('data-sizes', widths).removeAttr('data-sizes', '') //store and clean the data
								.data('data-osizes', owidths).removeAttr('data-osizes', '') //store and clean the data
								.data('data-visible', visibles).attr('data-visible', '') //store and clean the data - keep the marker for selector
								.data('data-ofirsts', ofirsts).removeAttr('data-ofirsts', '') //store and clean the data
								.data('data-firsts', firsts).removeAttr('data-firsts', '') //store and clean the data
								.data('data-others', $.parseJSON($(this).attr('data-others'))).removeAttr('data-others', '') //store and clean the data
								.parent().addClass('jm_layoutbuilder_flxbgroup');

							jcols.each(function(idx){
								positions[idx] = $(this).find('h4').html();

								$(this)
								.addClass('jm_layoutbuilder_column')
								.find('.jm_layoutbuilder_element.type-modules')
								.attr('data-name', defpos[idx])
								.append('<span class="jm_layoutbuilder_visible hasTooltip" title="' + JMLayoutBuilder.lang.PLG_SYSTEM_JMFRAMEWORK_HIDE_POSITION + '"><i class="icon-eye-open"></i></span>');
							});

							for(var i = numpos; i < 6; i++){
								positions[i] = defpos[i] || JMLayoutBuilder.lang.PLG_SYSTEM_JMFRAMEWORK_EMPTY_POSITION;
							}

							var jhides = $([
								'<div class="jm_layoutbuilder_hidden_elems hasTooltip" title="', JMLayoutBuilder.lang.PLG_SYSTEM_JMFRAMEWORK_HIDDEN_POSITION_DESC, '">',
									'<span class="element-hidden hasTooltipBottom" title="', JMLayoutBuilder.lang.PLG_SYSTEM_JMFRAMEWORK_SHOW_POSITION, '">', positions[0], '<i class="icon-eye-close"></i></span>',
									'<span class="element-hidden hasTooltipBottom" title="', JMLayoutBuilder.lang.PLG_SYSTEM_JMFRAMEWORK_SHOW_POSITION, '">', positions[1], '<i class="icon-eye-close"></i></span>',
									'<span class="element-hidden hasTooltipBottom" title="', JMLayoutBuilder.lang.PLG_SYSTEM_JMFRAMEWORK_SHOW_POSITION, '">', positions[2], '<i class="icon-eye-close"></i></span>',
									'<span class="element-hidden hasTooltipBottom" title="', JMLayoutBuilder.lang.PLG_SYSTEM_JMFRAMEWORK_SHOW_POSITION, '">', positions[3], '<i class="icon-eye-close"></i></span>',
									'<span class="element-hidden hasTooltipBottom" title="', JMLayoutBuilder.lang.PLG_SYSTEM_JMFRAMEWORK_SHOW_POSITION, '">', positions[4], '<i class="icon-eye-close"></i></span>',
									'<span class="element-hidden hasTooltipBottom" title="', JMLayoutBuilder.lang.PLG_SYSTEM_JMFRAMEWORK_SHOW_POSITION, '">', positions[5], '<i class="icon-eye-close"></i></span>',
								'</div>'].join('')).appendTo(this.parentNode),
								jhcols = jhides.children();

							for(var i = 0; i < JMLayoutBuilder.layout.maxcols; i++){
								jhcols.eq(i).toggleClass('jm-hide', i >= numpos);
							}

							//temporary calculate the widths for each screens size
							JMLayoutBuilder.jmcopy(widths, twidths); //first - clone the current object
							JMLayoutBuilder.sizeVisible(widths, visibles.vals, numpos); //then extend it with autogenerate width
							JMLayoutBuilder.jmcopy(widths, twidths); // if widths has value, it should be priority

							$(flexiblock).xresize({
								grid: false,
								gap: 0,
								selector: '.jm_layoutbuilder_column'
							});

							jncols.on('click', '.btn', function(e){

								if(!e.isTrigger){
									numpos = $(this).index() + 1;
									for(var i = 0; i < numpos; i++){
										if(!positions[i] || positions[i] == JMLayoutBuilder.lang.PLG_SYSTEM_JMFRAMEWORK_EMPTY_POSITION){
											positions[i] = defpos[i] || JMLayoutBuilder.lang.PLG_SYSTEM_JMFRAMEWORK_EMPTY_POSITION;
										}

										jhcols.eq(i).html(positions[i] + '<i class="icon-eye-close">').removeClass('jm-hide');
									}

									for(var i = numpos; i < JMLayoutBuilder.layout.maxcols; i++){
										jhcols.eq(i).addClass('jm-hide');
									}

									//automatic re-calculate the widths for each screens size
									JMLayoutBuilder.sizeVisible(widths, visibles.vals, numpos);

									var html = [];
									for(i = 0; i < numpos; i++){
										html = html.concat([
										'<div class="jm_layoutbuilder_column ', JMLayoutBuilder.sizeClass(JMLayoutBuilder.layout.clayout, widths[JMLayoutBuilder.layout.dlayout][i]), '">',
											'<div class="jm_layoutbuilder_element type-modules block-', positions[i], (positions[i] == JMLayoutBuilder.lang.PLG_SYSTEM_JMFRAMEWORK_EMPTY_POSITION ? ' noname' : ''), '" data-name="', (defpos[i] || ''), '">',
												'<span class="jm_layoutbuilder_edit hasTooltipRight" title="' + JMLayoutBuilder.lang.PLG_SYSTEM_JMFRAMEWORK_EDIT_MODULE_POSITION + '"><i class="icon-edit"></i></span>',
												'<span class="jm_layoutbuilder_el_size hasTooltipRight" title="', JMLayoutBuilder.lang.PLG_SYSTEM_JMFRAMEWORK_ELEMENT_WIDTH, '">', widths[JMLayoutBuilder.layout.dlayout][i], '</span>',
												'<h4 class="jm_layoutbuilder_el_name hasTooltip" title="', JMLayoutBuilder.lang.PLG_SYSTEM_JMFRAMEWORK_MODULE_POSITION_NAME, '">', positions[i], '</h4>',
												'<br /><span class="modules-chrome hasTooltipBottom" title="', JMLayoutBuilder.lang.PLG_SYSTEM_JMFRAMEWORK_MODULES_CHROME, '">', $(flexiblock).attr('data-chrome') , '</span>',
												'<span class="jm_layoutbuilder_visible hasTooltip" title="', JMLayoutBuilder.lang.PLG_SYSTEM_JMFRAMEWORK_HIDE_POSITION, '"><i class="icon-eye-open"></i></span>',
											'</div>',
											'<div class="jm_layoutbuilder_resizehandler hasTooltipBottom" title="', JMLayoutBuilder.lang.PLG_SYSTEM_JMFRAMEWORK_DRAG_TO_RESIZE, '"></div>',
										'</div>']);
									}

									//reset value
									$(flexiblock)
										.empty()
										.html(html.join(''));
								}

								//change gridsize for resize
								JMLayoutBuilder.updateGrid(flexiblock);

								$(this).addClass('active').siblings().removeClass('active');

							}).children().removeClass('active').eq(numpos -1).addClass('active').trigger('click');

							jhides.on('click', 'span', function(){
								JMLayoutBuilder.onVisible.call($(flexiblock).children().eq($(this).index()).find('.jm_layoutbuilder_visible'));
								return false;
							});
						});

					JMLayoutBuilder.initSortable();
					JMLayoutBuilder.equalHeights();
					JMLayoutBuilder.updateTooltips();

					$('#jm_layoutbuilder_container').removeClass('hide');

				} else {
					JMLayoutBuilder.alert(JMLayoutBuilder.lang.PLG_SYSTEM_JMFRAMEWORK_CANT_LOAD_LAYOUT, 'error');
				}
			}
		},

		initSortable: function(){

			var maincol = $('#jm-blocks');
			if(!maincol.length) maincol = $('.jm_layoutbuilder_block').first().parent();

			maincol.sortable({axis: 'y', handle: '.sort-handle', scroll: true, items: '> .jm_layoutbuilder_block', connectWith: '#jm_layoutbuilder_excluded_blocks'});
			$('#jm_layoutbuilder_excluded_blocks').sortable({axis: 'y', handle: '.sort-handle', scroll: true, items: '> .jm_layoutbuilder_block', connectWith: maincol});

			JMLayoutBuilder.layout.storedBlocks = maincol.sortable( "toArray", { attribute: "data-block" } );
			JMLayoutBuilder.layout.excludedBlocks = $('#jm_layoutbuilder_excluded_blocks').sortable( "toArray", { attribute: "data-block" } );

			// main columns sortable, let's do the magic
			var mainrow = $('#jm-content').parent();
			JMLayoutBuilder.layout.scheme = 'lcr';

			mainrow.children().each(function(){
				var col = $(this);
				col.prepend('<span class="sort-handle-col hasTooltip" title="' + JMLayoutBuilder.lang.PLG_SYSTEM_JMFRAMEWORK_SORT_MAIN_COLUMNS + '"><i class="icon-move"></i></span>');
				if(col.data('column') == 'c') JMLayoutBuilder.layout.scheme = col.data('scheme');
				else { // column size switcher
					col.append([
						'<div class="col_size_switcher hasTooltip" title="',
						JMLayoutBuilder.lang.PLG_SYSTEM_JMFRAMEWORK_COLUMN_SIZE_SWITCHER + '">',
						'<span class="btn col_decrease">-</span>',
						'<span class="btn col_increase">+</span>',
						'</div>'].join(''));
					col.find('.col_size_switcher span').each(function(){
						$(this).on('click', function(){
							JMLayoutBuilder.colSizeSwitch(col, ($(this).hasClass('col_increase') ? 1 : -1));
						});
					});
				}
			});

			mainrow.sortable({axis: 'x', handle: '.sort-handle-col', tolerance: 'pointer', update: function(){

				var scheme = '';
				mainrow.children().each(function(){
					var col = $(this);
					scheme += col.data('column');
				});
				JMLayoutBuilder.layout.scheme = scheme;

			}});

			JMLayoutBuilder.updateSortable();
		},

		updateSortable: function(){

			// main columns sortable, let's do the magic
			var mainrow = $('#jm-content').parent(),
				cols = [];

			mainrow.children().each(function(){
				var col = $(this);
				cols[col.data('column')] = col;
			});

			var scheme = JMLayoutBuilder.layout.scheme;
			if(JMLayoutBuilder.layout.build) {
				if(JMLayoutBuilder.layout.clayout != 'normal' && JMLayoutBuilder.layout.clayout != 'wide') scheme = 'clr';
				//console.log(cols['c'].data(JMLayoutBuilder.layout.clayout) != undefined);
			}

			mainrow.append(cols[scheme.charAt(0)]);
			mainrow.append(cols[scheme.charAt(1)]);
			mainrow.append(cols[scheme.charAt(2)]);

		},

		colSizeSwitch: function(column, dir) {

			var content = $('#jm-content');

			var mspan = parseInt(content.data('default').replace(/(.*?)span(\d+)(.*)/, "$2"));
			var cspan = parseInt(column.data('default').replace(/(.*?)span(\d+)(.*)/, "$2"));

			content.removeClass('span'+mspan);
			column.removeClass('span'+cspan);

			if(dir > 0) {
				mspan--;
				cspan++;
			} else {
				mspan++;
				cspan--;
			}

			if(mspan > 0 && cspan > 0) {

				content.data('default', content.data('default').replace(/(.*?)span(\d+)(.*)/, '$1span'+mspan+'$3'));
				column.data('default', column.data('default').replace(/(.*?)span(\d+)(.*)/, '$1span'+cspan+'$3'));

				content.addClass('span'+mspan);
				column.addClass('span'+cspan);

				$('#jm-content').parent()
				.find('.jm_layoutbuilder_element.type-modules .jm_layoutbuilder_el_size')
				.each(function(){
					var jparent = $(this).parentsUntil('.row-fluid, .row, .container-fluid, .container').last();
					var span = parseInt(jparent.prop('className').replace(/(.*?)span(\d+)(.*)/, "$2"));

					if(isNaN(span)){
						span = JMLayoutBuilder.lang.PLG_SYSTEM_JMFRAMEWORK_UNKNOWN_WIDTH;
					}

					$(this).text(span);
				});

			} else {

				if(dir > 0) {
					mspan++;
					cspan--;
				} else {
					mspan--;
					cspan++;
				}

				content.addClass('span'+mspan);
				column.addClass('span'+cspan);

				alert(JMLayoutBuilder.lang.PLG_SYSTEM_JMFRAMEWORK_COLUMN_SIZE_MAX);
			}
		},

		restoreOrder: function(){

			var maincol = $('#jm-blocks');
			if(!maincol.length) maincol = $('.jm_layoutbuilder_block').first().parent();

			$.each(JMLayoutBuilder.layout.storedBlocks, function(idx, block){
				maincol.append($('[data-block='+block+']'));
			});

			$.each(JMLayoutBuilder.layout.excludedBlocks, function(idx, block){
				$('#jm_layoutbuilder_excluded_blocks').append($('[data-block='+block+']'));
			});

			var mainrow = $('#jm-content').parent();

			mainrow.children().each(function(){
				var col = $(this);
				if(col.data('column') == 'c') JMLayoutBuilder.layout.scheme = col.data('scheme');
			});

			JMLayoutBuilder.updateSortable();

			JMLayoutBuilder.alert(JMLayoutBuilder.lang.PLG_SYSTEM_JMFRAMEWORK_LAYOUTBUILDER_RESTORE_ORDER_DONE, 'success');

			return false;
		},

		updateTooltips: function(){

			$('.hasTooltip').tooltip({'html': true,'container': 'body'});
			$('.hasTooltipLeft').tooltip({'html': true,'container': 'body', 'placement': 'left'});
			$('.hasTooltipRight').tooltip({'html': true,'container': 'body', 'placement': 'right'});
			$('.hasTooltipBottom').tooltip({'html': true,'container': 'body', 'placement': 'bottom'});

		}

	}

	$(document).ready(function(){
		JMLayoutBuilder.initLayout();
		JMLayoutBuilder.initActions();
		JMLayoutBuilder.initSubmit();
		JMLayoutBuilder.loadLayout();
		$('.jm_layoutbuilder_build_positions').trigger('click');
	});

}(jQuery);

!function($){

	var isdown = false;
	var curelm = null;
	var opts;
	var memwidth;
	var memfirst;
	var memvisible;
	var owidth;
	var rzleft;
	var rzwidth;
	var rzlayout;
	var rzindex;
	var rzminspan;

	var snapoffset = function(grid, size) {
			var limit = grid / 2;
			if ((size % grid) > limit) {
				return grid-(size % grid);
			} else {
				return -size % grid;
			}
		}

	var spanfirst = function(rwidth){
			var sum = 0;
			var needfirst = (memvisible[0] == 1);

			$(curelm).parent().children().each(function(idx){
				if(memvisible[idx] == 0 || memvisible[idx] == undefined){
					if(needfirst || ((sum + parseInt(memwidth[idx]) > JMLayoutBuilder.layout.maxgrid) || (rzindex + 1 == idx && sum + parseInt(memwidth[idx]) == JMLayoutBuilder.layout.maxgrid && (rwidth > owidth)))){
						$(this).addClass(JMLayoutBuilder.firstClass(rzlayout));
						memfirst[idx] = 1;
						sum = parseInt(memwidth[idx]);
						needfirst = false;
					} else {
						$(this).removeClass(JMLayoutBuilder.firstClass(rzlayout));
						memfirst[idx] = 0;
						sum += parseInt(memwidth[idx]);
					}
				}
			});
		}

	var updatesize = function(e, togrid) {
			var mx = e.pageX;
			var width = rwidth = (mx - rzleft + rzwidth);

			if(opts.grid){
				width = width + snapoffset(opts.grid, width) - opts.gap;
			}

			if(rwidth < opts.minwidth){
				rwidth = opts.minwidth;
			} else if (rwidth > opts.maxwidth){
				rwidth = opts.maxwidth;
			}

			if(width < opts.minwidth){
				width = opts.minwidth;
			} else if (width > opts.maxwidth){
				width = opts.maxwidth;
			}

			if(owidth != width){
				memwidth[rzindex] = rzminspan * ((width + opts.gap) / opts.grid) >> 0;
				owidth = width;

				$(curelm).find('.jm_layoutbuilder_el_size').html(memwidth[rzindex]);
			}

			curelm.style['width'] = (togrid ? width : rwidth) + 'px';

			spanfirst(rwidth);
		}

	var updatecls = function(e){
			var mx = e.pageX;
			var width = (mx - rzleft + rzwidth);

			if(opts.grid){
				width = width + snapoffset(opts.grid, width) - opts.gap;
			}

			if(width < opts.minwidth){
				width = opts.minwidth;
			} else if (width > opts.maxwidth){
				width = opts.maxwidth;
			}

			curelm.className = curelm.className.replace(JMLayoutBuilder.layout.spanX, ' ');
			$(curelm).css('width', '').addClass(JMLayoutBuilder.sizeClass(rzlayout, JMLayoutBuilder.sizeConvert((rzminspan * ((width + opts.gap) / opts.grid) >> 0))));
			spanfirst(width);
		}

	var mousedown = function (e) {
			curelm = this.parentNode;
			isdown = true;
			rzleft = e.pageX;
			owidth = rzwidth  = $(curelm).outerWidth();

			var jdata = $(this).closest('.jm_layoutbuilder_resizeable');

			opts = jdata.data('rzdata');
			rzlayout = JMLayoutBuilder.layout.clayout;
			rzminspan = JMLayoutBuilder.layout.unitspan[rzlayout];
			rzindex = $(this).parent().index();
			memwidth = jdata.data('data-sizes')[rzlayout];
			memfirst = jdata.data('data-firsts')[rzlayout];
			memvisible = jdata.data('data-visible').vals[rzlayout];

			updatesize(e);

			$(document)
			.on('mousemove.xresize', mousemove)
			.on('mouseup.xresize', mouseup);

			return false;
		}

	var mousemove = function (e) {
			if(isdown) {
				updatesize(e);
				return false;
			}
		}

	var mouseup = function (e) {
			isdown = false;
			updatecls(e);
			$(document).unbind('.xresize');
		}

	$.fn.xresize = function(opts) {
		return this.each(function () {
			$(opts.selector ? $(this).find(opts.selector) : this).append('<div class="jm_layoutbuilder_resizehandler hasTooltipBottom" title="' + JMLayoutBuilder.lang.PLG_SYSTEM_JMFRAMEWORK_DRAG_TO_RESIZE + '"></div>');
			$(this)
			.addClass('jm_layoutbuilder_resizeable')
			.data('rzdata', $.extend({
				selector: '',
				minwidth: 0,
				maxwidth: 100000,
				minheight: 0,
				maxheight: 100000,
				grid: 0,
				gap: 0
			}, opts))
			.on('mousedown.wresize', '.jm_layoutbuilder_resizehandler', mousedown);
		});
	};

}(jQuery);
