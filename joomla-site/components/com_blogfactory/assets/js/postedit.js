jQuery(document).ready(function ($) {
    // Initialise variables.
    var MediaManager = $.BlogFactoryMediaManager();

    // Submit form buttons.
    $('.button-draft, .button-save', '.blogfactory-post-submit').click(function (event) {
        event.preventDefault();

        var elem = $(this);
        var mode = elem.attr('rel');

        $('#mode').val(mode);
        $('form#post-edit').submit();
    });

    // Add media button.
    $('.button-add-media').click(function (event) {
        event.preventDefault();

        MediaManager.open();
    });

    var form = $('form#post-edit');
    if ('0' != form.attr('data-id')) {
        setInterval(function () {
            if ('true' == form.attr('data-revision')) {
                return true;
            }

            tinyMCE.triggerSave();

            var id = form.attr('data-id');
            var data = {id: id, content: $('#jform_content').val(), title: $('#jform_title').val()};

            $('.status', 'div.autosave').hide();
            $('.loader', 'div.autosave').show();

            $.post(base_url + 'index.php?option=com_blogfactory&task=post.autosave&format=raw', {jform: data}, function (response) {
                $('.status', 'div.autosave').show();
                $('.loader', 'div.autosave').hide();

                if (response.status) {
                    $('.status', 'div.autosave').text(response.date);
                }

                $('.newer-autosave-warning').remove();
            }, 'json');

            return true;
        }, 60 * 1000);
    }

    // Preview button.
    $('a.button-preview').click(function (event) {
        event.preventDefault();

        tinyMCE.triggerSave();

        var id = form.attr('data-id');
        var data = {id: id, content: $('#jform_content').val(), title: $('#jform_title').val()};

        $.post(base_url + 'index.php?option=com_blogfactory&task=post.preview&format=raw', {jform: data}, function (response) {
            if (response.status) {
                var preview = window.open(base_url + 'index.php?option=com_blogfactory&view=post&preview=' + response.preview, 'com_blogfactory_preview');
                preview.focus();
            }
        }, 'json');
    });
});
