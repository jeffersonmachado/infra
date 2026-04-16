jQuery(document).ready(function ($) {
    $('#settingsTabs').on('click', 'a', function (event) {
        var elem = $(this);
        var tab = elem.attr('href').replace('#', '');

        $.cookie('com_blogfactory_settings_tab', tab);
    });
});
