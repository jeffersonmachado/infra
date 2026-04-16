jQuery(document).ready(function ($) {
    // Initialise variables.
    var MediaManager = $.BlogFactoryMediaManager();

    // Sortable columns.
    $('.blogfactory-dashboard-column').sortable({
        connectWith: '.blogfactory-dashboard-column',
        handle: '.blogfactory-portlet-header',
        placeholder: 'sortable-placeholder',
        tolerance: 'pointer',
        forcePlaceholderSize: true,
        start: function (event, ui) {
            $('.sortable-placeholder').height($(ui.helper).height());
            ui.item.css('opacity', .5);
        },
        stop: function (event, ui) {
            ui.item.css('opacity', 1);

            dashboardSaveState();
        }
    })

    function reconnect(columns) {
        var array = [];
        var i;

        for (i = 1; i <= columns; i++) {
            array.push('.blogfactory-dashboard-column:eq(' + (i - 1) + ')');
        }

        $('.blogfactory-dashboard-column').sortable({connectWith: array.join(',')});

        console.log(columns);

        switch (columns) {
            case '2':
                $('.blogfactory-dashboard-column:eq(2) .blogfactory-portlet').appendTo($('.blogfactory-dashboard-column:eq(1)'));
                break;

            case '1':
                $('.blogfactory-dashboard-column:eq(2) .blogfactory-portlet, .blogfactory-dashboard-column:eq(1) .blogfactory-portlet').appendTo($('.blogfactory-dashboard-column:eq(0)'));
                break;
        }
    }

    function dashboardSaveState() {
        var array = [];

        $('.blogfactory-portlet').each(function (index, element) {
            var elem = $(element);
            var column = elem.parents('.blogfactory-dashboard-column:first').index() + 1;

            array.push(elem.attr('id').replace('portlet-', '') + '/' + (elem.hasClass('portlet-minimized') ? 1 : 0) + '/' + column);
        });

        console.log(array);

        $.cookie('com_blogfactory_dashboard', array.join(';'), {path: '/'});
    }

    $('.blogfactory-portlet-toggle-handle').click(function (event) {
        event.preventDefault();

        var elem = $(this);
        elem.parents('.blogfactory-portlet:first').toggleClass('portlet-minimized');

        dashboardSaveState();
    });

    $('#options').click(function (event) {
        event.preventDefault();

        var elem = $(this);
        var url = elem.attr('href');

        $.FactoryModal({
            url: url,
            onSubmit: function (modal) {
                var form = modal.content.find('form');

                $.ajax({
                    url: form.attr('action'),
                    type: form.attr('method'),
                    data: form.serialize(),
                    dataType: 'json',
                    success: function (response) {
                        modal.close();

                        if (response.status) {
                            $('.blogfactory-dashboard-columns')
                                .removeClass('columns-1 columns-2 columns-3')
                                .addClass('columns-' + response.data.columns);

                            reconnect(response.data.columns);
                        }
                    }
                });
            }
        });
    })

    // Reset QuickPost form.
    $('.quickpost-action-reset').click(function (event) {
        event.preventDefault();

        var elem = $(this);
        var form = elem.parents('.blogfactory-portlet-content:first').find('form:first');

        form.find('input, textarea').val('');
    });

    // Save QuickPost form.
    $('.quickpost-action-save').click(function (event) {
        event.preventDefault();

        var elem = $(this);
        var loader = elem.find('i:first');
        var form = elem.parents('.blogfactory-portlet-content:first').find('form:first');
        var data = form.serialize();
        var url = elem.attr('href');
        var update = elem.parents('.blogfactory-portlet-content:first').find('.quickpost-save-result:first');

        loader.show();
        update.html('')

        $.post(url, data, function (response) {
            loader.hide();

            var html = [];
            var result = response.status ? 'success' : 'error';

            html.push('<div class="alert alert-' + result + '">');
            html.push('<button type="button" class="close" data-dismiss="alert">&times;</button>');
            html.push(response.message);

            if (!response.status && response.error) {
                html.push(response.error);
            }

            html.push('</div>');

            update.html(html.join("\n"));

            if (response.status) {
                $('.quickpost-action-reset').click();

                // Add post to the Recent Posts portlet.
                $('.blogfactory-portlet-content', '#portlet-posts')
                    .prepend(response.portlet_posts_update)
                    .find('.portlet-no-results').remove().end()
                    .find('.portlet-footer').show().end();
            }
        }, 'json');
    });

    // Media manager.
    $('.button-media-manager').click(function (event) {
        event.preventDefault();

        MediaManager.open({mode: 'manager'});
    });
});
