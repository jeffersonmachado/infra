jQuery(document).ready(function ($) {
    // Bookmark delete button.
    $('.item-delete').click(function (event) {
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
                elem.parents('tr:first').removeClass('bookmark-notification').html('<td colspan="10">' + response.message + '</td>');
            }
        }, 'json');
    });
});
