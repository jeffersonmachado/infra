jQuery(document).ready(function ($) {
    // Remove tags.
    $('.tags').on('click', 'a', function (event) {
        event.preventDefault();

        $(this).remove();
    });

    // Add tag.
    $('.button-add-tag').click(function (event) {
        event.preventDefault();

        var input = $('#tag');
        var entry = input.val();
        var tags = [];

        input.val('');

        if ('' == entry) {
            return false;
        }

        var values = entry.split(',');

        $('.tags span.label').each(function (index, element) {
            tags.push($(element).text());
        });

        for (var i = 0, count = values.length; i < count; i++) {
            var tag = values[i].trim();
            tag = $('<div />').text(tag).html().replace('"', '&quot;');

            if ('' == tag || -1 != $.inArray(tag, tags)) {
                continue;
            }

            $('.tags').append('<a href="#"><span class="label label-info">' + tag + '</span><input type="hidden" name="jform[tags][]" value="' + tag + '" /></a>' + "\n");
        }

        return true;
    });
});
