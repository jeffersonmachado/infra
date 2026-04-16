try {
	if (typeof ($jppc) == 'undefined') {
		var $jppc = jQuery.noConflict();
	}
} catch (e) {
	if (console && console.log)
		console.log(e);
	else
		alert(e);
}
try {
	if (jQueryBefore && jQueryBefore.fn.jquery) {
		jQuery = jQueryBefore;
	}
} catch (e) {
	if (console && console.log)
		console.log(e);
	else
		alert(e);
}
