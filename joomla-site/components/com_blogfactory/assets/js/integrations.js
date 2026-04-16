jQuery(document).ready(function ($) {
    $('a.data-item').click(function (event) {
        event.preventDefault();

        var elem = $(this);
        var data = elem.attr('data-item');

        window.parent.tinyMCE.activeEditor.execCommand('mceInsertContent', false, data)
    });
});
