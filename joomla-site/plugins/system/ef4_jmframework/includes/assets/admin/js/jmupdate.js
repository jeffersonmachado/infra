(function($){
	var JMFrameworkUpdate = window.JMFrameworkUpdate = window.JMFrameworkUpdate || {
		checkUpdates: function(settings) {
			$.ajax({
	    		url: settings.url
	    	}).done(function(response) {
	    		try {
	    			var output = $.parseJSON(response.data);
	    		} catch(e) {
	    			return false;
	    		}
	    		
	    		var updCnt = parseInt(output.updates);
	    		var htmlContent = output.html;
	    		if (updCnt == 0 || htmlContent == '') {
	    			return false;
	    		}
	    		
				var info = '<span class="label label-important">' + output.updates + '</span> ' 
						+ settings.lang.updates_available
						+ ' <button class="btn btn-primary" data-toggle="modal" data-target="#jmf_updates_modal">'
						+ settings.lang.update_button + '</button>';
				
				var modal = '<div class="modal fade hide" id="jmf_updates_modal">'
						+ '<div class="modal-header">'
						+ '<button type="button" class="close" data-dismiss="modal">×</button>'
						+ '<h3>' + settings.lang.modal_header + '</h3>'
						+ '</div>'
						+ '<div class="modal-body">'
						+ htmlContent
						+ '</div>'
						+ '<div class="modal-footer">'
						+ settings.lang.modal_footer
						+ '</div>'
						+ '</div>';
				
				if ($('.alert-joomlaupdate').length == 0) {
					$('#system-message-container').prepend(
						'<div class="alert alert-error alert-joomlaupdate">'
						+ info
						+ '</div>'
					);
				}
				else {
					$('#system-message-container').prepend(
						'<div class="alert alert-error alert-joomlaupdate span6">'
						+ info
						+ '</div>'
					);
				}
				
				$(document.body).append(modal);
	    	});
		}
	};
})(jQuery);