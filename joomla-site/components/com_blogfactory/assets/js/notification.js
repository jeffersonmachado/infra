(function ($) {
    var timer = null;
    var tooltip = null;

    $.fn.extend({
        FactoryTooltipNotification: function (response) {
            return this.each(function () {
                var elem = $(this);
                var className = response.status ? '' : 'tooltip-error';
                var message = response.message;

                if (response.error) {
                    message += '&nbsp;' + response.error
                }

                if (null != tooltip) {
                    tooltip.FactoryTooltip('hide');
                }

                elem
                    .FactoryTooltip('destroy')
                    .FactoryTooltip({title: message, trigger: 'manual', className: className})
                    .FactoryTooltip('show');

                if (null != timer) {
                    clearTimeout(timer);
                }

                timer = setTimeout(function () {
                    elem.FactoryTooltip('hide');
                }, 5 * 1000);

                tooltip = elem;
            });
        }
    });

    $('html').click(function () {
        if (null != tooltip) {
            tooltip.FactoryTooltip('hide');
        }
    });
})(window.jQuery);
