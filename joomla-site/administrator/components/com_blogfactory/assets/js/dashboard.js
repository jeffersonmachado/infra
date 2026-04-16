jQuery(document).ready(function ($) {
    // Toggle panel statue.
    $('a', '.view-dashboard .panel-heading').click(function (event) {
        event.preventDefault();

        var $element = $(this),
            panel = $element.parents('.panel:first'),
            id = panel.attr('id'),
            body = panel.find('.panel-body:first');
        body.toggle();

        dashboardSaveState();
    });

    // Sortable panels.
    $('.dashboard-panels .span6').sortable({
        connectWith: '.dashboard-panels .span6',
        handle: '.fa-ellipsis-v',
        placeholder: 'portlet-placeholder',
        forcePlaceholderSize: true,
        tolerance: 'pointer',
        start: function (event, ui) {
            $('.portlet-placeholder').height($(ui.helper).height());
            ui.item.css('opacity', .5);
        },
        stop: function (event, ui) {
            ui.item.css('opacity', 1);

            dashboardSaveState();
        }
    });

    function dashboardSaveState() {
        var array = [];

        $('.dashboard-panels .span6 .panel').each(function (index, element) {
            var elem = $(element);
            var column = elem.parents('.span6:first').index();

            array.push(elem.attr('id').replace('panel-', '') + '/' + (elem.find('.panel-body').is(':visible') ? 1 : 0) + '/' + column);
        });

        $.cookie('com_blogfactory_backend_dashboard', array.join(';'), {path: '/'});
    }
});
