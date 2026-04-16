jQuery(document).ready(function ($) {
    // Post vote buttons.
    $('a', 'div.post-rating').click(function (event) {
        event.preventDefault();

        var elem = $(this);
        var url = elem.attr('href');

        $.post(url, function (response) {
            elem.parent().FactoryTooltipNotification(response);

            if (response.status) {
                var className = 'vote-' + response.vote;

                $('div.post-rating a.' + className + ' span').addClass('voted').text(response.total);

                $('div.post-rating a').each(function (index, element) {
                    $(element).replaceWith($(element).html());
                });
            }
        }, 'json');
    });

    // Button subscribe.
    $('.button-subscribe').click(function (event) {
        event.preventDefault();

        var elem = $(this);
        var icon = elem.find('i:first');
        var url = elem.attr('href');
        var subscribed = elem.attr('rel');

        if (icon.hasClass('factory-icon-loader')) {
            return false;
        }

        icon.toggleClass('factory-icon-loader factory-icon-mail');

        $.post(url, {subscribed: subscribed}, function (response) {
            icon.toggleClass('factory-icon-loader factory-icon-mail');

            elem.FactoryTooltipNotification(response);

            if (response.status) {
                elem.find('span:first').html(response.text);
                elem.attr('rel', (1 == subscribed ? 0 : 1));
            }
        }, 'json');

        return true;
    });
});
