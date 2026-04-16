jQuery(document).ready(function ($) {
    // Submit form on filter changes.
    $('select, input', 'form.blogfactory-blog-filters').change(function (event) {
        $('form.blogfactory-blog-filters').submit();
    });

    // Remove empty inputs when submitting filter form.
    $('form.blogfactory-blog-filters').submit(function () {
        $(this).find('select, input').each(function (index, element) {
            var elem = $(element);

            if ('' == elem.val()) {
                //elem.remove();
                elem.attr('disabled', 'disabled');
            }
        });
    });

    // Button bookmark.
    $('.button-bookmark').click(function (event) {
        event.preventDefault();

        var elem = $(this);
        var icon = elem.find('i:first');
        var url = elem.attr('href');
        var bookmarked = elem.attr('rel');

        if (icon.hasClass('factory-icon-loader')) {
            return false;
        }

        icon.toggleClass('factory-icon-loader factory-icon-bookmark');

        $.post(url, {bookmarked: bookmarked}, function (response) {
            icon.toggleClass('factory-icon-loader factory-icon-bookmark');

            elem.FactoryTooltipNotification(response);

            if (response.status) {
                elem.find('span:first').html(response.text);
                elem.attr('rel', (1 == bookmarked ? 0 : 1));
                elem.parent().find('.blog-followers span').html(response.bookmarks);
            }
        }, 'json');

        return true;
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

    $(document).ajaxError(function (event, jqxhr, settings, exception) {
        alert('There was an error performing the AJAX request.' + "\n\n" + '"' + exception + '"');
    });
});
