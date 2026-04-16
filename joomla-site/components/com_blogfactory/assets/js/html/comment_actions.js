jQuery(document).ready(function ($) {
    // Approve / Unapprove comments button.
    $(document).on('click', 'a.comment-approve', function (event) {
        event.preventDefault();

        var elem = $(this);
        var approved = elem.attr('rel');
        var url = elem.attr('href');
        var loader = elem.find('i:first');

        loader.show();

        $.post(url, {approved: approved}, function (response) {
            var wrapper = elem.parents('.comment-wrapper:first');
            loader.hide();

            if (response.status) {
                var approval = wrapper.find('.comment-approval-info');

                elem.attr('rel', 0 == approved ? 1 : 0).find('span:first').text(response.text);
                wrapper.toggleClass('comment-pending');
                approval.toggle();
            }
            else {
                elem.FactoryTooltipNotification(response);
            }
        }, 'json');
    });

    // Resolve comment report button.
    $(document).on('click', 'a.comment-resolve', function (event) {
        event.preventDefault();

        var elem = $(this);
        var url = elem.attr('href');
        var loader = elem.find('i:first');

        loader.show();

        $.post(url, function (response) {
            var wrapper = elem.parents('.comment-wrapper:first');
            loader.hide();

            if (response.status) {
                wrapper.toggleClass('comment-reported');
                elem.prev().remove();
                elem.remove();
            }
            else {
                elem.FactoryTooltipNotification(response);
            }
        }, 'json');
    });

    // Delete comments button.
    $(document).on('click', 'a.comment-delete', function (event) {
        event.preventDefault();

        var elem = $(this);
        var url = elem.attr('href');
        var loader = elem.find('i:first');

        loader.show();

        $.post(url, function (response) {
            loader.hide();

            if (response.status) {
                var wrapper = elem.parents('.comment-wrapper:first');
                var text = 'TR' == wrapper[0].tagName ? '<td colspan="10">' + response.message + '</td>' : response.message;

                wrapper.html(text).removeClass('comment-pending comment-reported');
            }
            else {
                elem.FactoryTooltipNotification(response);
            }
        }, 'json');
    });
});
