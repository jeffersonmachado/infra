jQuery(document).ready(function ($) {
    // Post publish / unpublish button.
    $('.post-publish').click(function (event) {
        event.preventDefault();

        var elem = $(this);
        var url = elem.attr('href');
        var loader = elem.find('i:first');
        var published = elem.attr('rel');

        loader.show();

        $.post(url, {published: published}, function (response) {
            loader.hide();

            if (response.status) {
                var tr = elem.parents('tr:first');
                var date = tr.find('.post-date');

                elem.find('span').html(response.text);
                elem.attr('rel', (1 == published ? 0 : 1));
                tr.toggleClass('post-pending');
                date.html(response.date);
            }
            else {
                elem.FactoryTooltipNotification(response);
            }
        }, 'json');
    });

    // Post delete button.
    $('.post-delete').click(function (event) {
        event.preventDefault();

        var elem = $(this);
        var url = elem.attr('href');
        var loader = elem.find('i:first');

        loader.show();

        $.post(url, function (response) {
            loader.hide();

            if (!response.status) {
                elem.FactoryTooltipNotification(response);
            }
            else {
                elem.parents('tr:first').html('<td colspan="10">' + response.message + '</td>');
            }
        }, 'json');
    });
});
