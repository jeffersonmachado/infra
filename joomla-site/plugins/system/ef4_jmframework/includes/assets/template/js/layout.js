jQuery(document).ready(function($) {

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
		timer = window.setTimeout(changeClasses, 50);
	});
	
	// init layout
	changeClasses();

});