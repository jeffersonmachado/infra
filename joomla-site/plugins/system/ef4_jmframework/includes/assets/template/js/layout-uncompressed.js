/* JMFLayoutBuilder */
jQuery(document).ready(function($){
	var currentScreen = 'default';
	var newScreen = 'normal';
	var screens = {	wide: 1200,	normal: 980, xtablet: 768, tablet: 481, mobile: 0 };
	var layoutElems = $('[class*="span"], .jm-responsive');
	
	layoutElems.each (function(){
		var elem = $(this);
		elem.data();
		// clean layout data and jm-responsive class
		elem.removeAttr('data-default data-wide data-normal data-xtablet data-tablet data-mobile');
		elem.removeClass('jm-responsive');
		// store default classes
		if (!elem.data('default')) elem.data('default', elem.attr('class'));
	});
	
	var changeClasses = function (){
		
		// we need to hide scrollbar to get real window width		
		$('body').css('overflow', 'hidden');
		var width = $(window).innerWidth();
		$('body').css('overflow', '');
		//console.log(width);
		for (var screen in screens) {
			if (width >= screens[screen]) {
				newScreen = screen;
				break;
			}
		}

		if (newScreen == currentScreen) return;
		
		layoutElems.each(function(){
			var elem = $(this);
			// no override for all screens - default data is always set
			//if (!elem.data('default')) return;
			// keep default 
			if (!elem.data(newScreen) && !elem.data(currentScreen)) return;
			// remove classes of current screen
			if (elem.data(currentScreen)) elem.removeClass(elem.data(currentScreen));
			else elem.removeClass (elem.data('default'));
			// add classes for new screen
			if (elem.data(newScreen)) elem.addClass (elem.data(newScreen));
			else elem.addClass (elem.data('default'));
		});
		
		currentScreen = newScreen;
	};
	
	// add trigger for resize event
	var timer;
	$(window).resize(function(){
		window.clearTimeout(timer);
		timer = window.setTimeout(changeClasses, 100);
	});
	
	// init layout
	changeClasses();
	
	var lazyLoading = function(loadInvisible) {
		
		var viewport = $(window).scrollTop() + $(window).height();
		
		jQuery('img[data-original], iframe[data-original]').each(function(){
			
			var img = jQuery(this);
			//if(loadInvisible) console.log('hidden:' + img.is(':hidden') + ' / ' + img.attr('data-original'));
			if(img.offset().top < viewport || (loadInvisible && img.is(':hidden'))) {
				img.data();
				var src = img.data('original');
				img.attr('data-lazy', 'loaded');
				img.removeAttr('data-original');
				img.attr('src', src);
			}
		});
		
		// remove event if all images have been loaded
		if(!jQuery('img[data-original], iframe[data-original]').length) {
			$(window).off('scroll', lazyLoading);
			$(window).off('resize', lazyLoading);
			$(document).off('click', lazyLoading);
		}
		
	};
	
	$(window).on('scroll', lazyLoading.bind(this, false));
	$(window).on('resize', lazyLoading.bind(this, false));
	$(document).on('click', lazyLoading.bind(this, true));
	lazyLoading(false);
});
