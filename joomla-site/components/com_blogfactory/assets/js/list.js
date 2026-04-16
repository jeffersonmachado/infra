jQuery(document).ready(function ($) {
    // Check all checkbox.
    $('input[type="checkbox"].check-all').change(function (event) {
        var elem = $(this);
        var checked = elem.is(':checked');

        $('input[type="checkbox"].check-all').attr('checked', checked);
        elem.parents('table:first').find('input[type="checkbox"][name="batch[]"]').attr('checked', checked);
    });

    // Batch actions button.
    $('.button-batch a').click(function (event) {
        event.preventDefault();

        var elem = $(this);
        var form = $('#blogfactory-form-list');

        // Check if any item was selected.
        var selected = $('input[type="checkbox"][name="batch[]"]:checked', 'table.blogfactory-list');

        if (!selected.length) {
            return true;
        }

        $('#task').val(elem.attr('data-task'));
        form.submit();

        return true;
    });
});
