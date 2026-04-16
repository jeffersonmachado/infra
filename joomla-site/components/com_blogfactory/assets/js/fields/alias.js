jQuery(document).ready(function ($) {
    $('.blogfactory-field-alias').each(function (index, element) {
        var elem = $(element);
        var observe = $('#' + elem.attr('data-observe'));
        var url = elem.attr('data-url');
        var view = elem.find('.view');
        var edit = elem.find('.edit');
        var input = elem.find('input[type="hidden"]:first');
        var request;

        // Edit button.
        elem.find('.button-edit').click(function (event) {
            event.preventDefault();

            var val = view.find('.current').text();

            view.hide();
            edit.css('display', 'inline-block').find('input:first').val(val).end()
        });

        // Cancel button.
        elem.find('.button-cancel').click(function (event) {
            event.preventDefault();

            view.show();
            edit.hide();
        });

        // Save button.
        elem.find('.button-save').click(function (event) {
            event.preventDefault();

            updateValue(elem.find('.edit input:first').val());

            observe.unbind('change');
            view.show();
            edit.hide();
        });

        if ('' == observe.val()) {
            observe.change(function () {
                updateValue($(this).val());
            });
        }

        function updateValue(query) {
            if (request) {
                request.abort();
            }

            request = $.get(url, {query: query}, function (response) {
                if (response.status) {
                    view.find('.current').text(response.alias);
                    input.val(response.alias);
                }
            }, 'json');
        }

        return true;
    });
});
