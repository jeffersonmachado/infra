jQuery(document).ready(function ($) {
    // Edit button.
    $('.blogfactory-calendar-edit').click(function (event) {
        event.preventDefault();

        var elem = $(this);
        var calendar = elem.parents('.blogfactory-calendar:first');

        elem.hide();
        calendar.find('.edit').show('fast');
    });

    // Cancel button.
    $('.blogfactory-calendar-cancel').click(function (event) {
        event.preventDefault();

        var elem = $(this);
        var calendar = elem.parents('.blogfactory-calendar:first');

        calendar.find('.edit').hide('fast');
        calendar.find('.text .blogfactory-calendar-edit').show();
    });

    // Immediately or Never buttons.
    $('.blogfactory-calendar-extra').click(function (event) {
        event.preventDefault();

        var elem = $(this);
        var calendar = elem.parents('.blogfactory-calendar:first');

        calendar.find('.edit').hide('fast');
        calendar.find('.text .blogfactory-calendar-edit').show();
        calendar.find('.text span').html(elem.html());
        calendar.find('input[type="hidden"]').val('');
    });

    // Save button.
    $('.blogfactory-calendar-save').click(function (event) {
        event.preventDefault();

        var elem = $(this);
        var calendar = elem.parents('.blogfactory-calendar:first');
        var data = {};
        var input = calendar.find('input[type="hidden"]');
        var label = calendar.find('.text span');

        calendar.find('.edit').hide('fast');
        calendar.find('.text .blogfactory-calendar-edit').show();
        label.attr('data-original', label.html());
        label.html('<i class="factory-icon-loader"></i>');

        calendar.find('[name^="date"]').each(function (index, element) {
            var elem = $(element);
            data[elem.attr('name')] = elem.val();
        });

        $.get(base_url + 'index.php?option=com_blogfactory&task=helper.parsedate&format=raw', data, function (response) {
            if (response.status) {
                label.html(response.label);
                input.val(response.date);
            }
            else {
                label.html(label.attr('data-original'));
            }
        }, 'json');
    });
});
