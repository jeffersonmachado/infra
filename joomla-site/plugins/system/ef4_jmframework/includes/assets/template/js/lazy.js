jQuery(document).ready(function($) {

	//lazyloading
	var observer;

	function loadImage( img ) {
		//console.log(img);
		img.data();
		var src = img.data('original');
		img.removeAttr('data-original');
		img.attr('src', src);
	}

	function lazyLoadImages() {

		var items = $('img[data-original], iframe[data-original]');

		if( ! items.length ) {
			return;
		}

		if ( 'loading' in HTMLImageElement.prototype ) { //native browser
			//console.log('native lazy load');

			//show items immediately
			items.each(function(){
				loadImage( $(this) );
			});
		} else if ( 'IntersectionObserver' in window && 'IntersectionObserverEntry' in window && 'intersectionRatio' in window.IntersectionObserverEntry.prototype ) { //observer
			//console.log('observer lazy load');

			var config = {
				rootMargin: '0px 0px 50px 0px',
				threshold: 0
			}

			if( typeof observer !== 'object' ) {
				observer = new IntersectionObserver(function (entries, self) {
					$.each(entries, function(i, entry) {
						if (entry.isIntersecting) { //show on viewport
							// load the image
							loadImage( $(entry.target) );
							// Stop watching
							self.unobserve(entry.target);
						}
					});
				}, config);
			}

			//observe images
			items.each(function() {
				observer.observe( this );
			});

		} else { // offset
			//console.log('offset lazy load');

			var offsetLoad = function(loadInvisible) {
				var viewport = $(window).scrollTop() + $(window).height();

				items = $('img[data-original], iframe[data-original]'); //update items

				items.each(function() {
					var img = $(this);
					if(img.offset().top < viewport || (loadInvisible && img.is(':hidden'))) {
						loadImage( img );
					}
				});

				// remove event if all images have been loaded
				if( ! items.length && observer === true ) {
					$(window).off('scroll', offsetLoad);
					$(window).off('resize', offsetLoad);
					$(document).off('click', offsetLoad);
					observer = false;
				}
			}

			//create events and load images
			$(window).on('scroll', offsetLoad.bind(this, false));
			$(window).on('resize', offsetLoad.bind(this, false));
			$(document).on('click', offsetLoad.bind(this, true));
			observer = true;
			offsetLoad(false);

		}
	}

	lazyLoadImages();
	
	var mutObserver = new MutationObserver(function(mutations){
		lazyLoadImages();
	});

	mutObserver.observe(document.body, {childList: true, subtree: true});
});
