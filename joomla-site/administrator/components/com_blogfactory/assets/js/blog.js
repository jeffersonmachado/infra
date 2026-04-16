jQuery(document).ready(function ($) {
    $('#blogTabs').on('click', 'a', function (event) {
        var elem = $(this);
        var tab = elem.attr('href').replace('#', '');

        $.cookie('com_blogfactory_blog_tab', tab);
    });
});
