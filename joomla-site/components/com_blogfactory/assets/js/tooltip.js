(function ($) {
    var FactoryTooltip = function (element, options) {
        this.init('tooltip', element, options);
    }

    FactoryTooltip.prototype = $.extend({}, $.fn.tooltip.Constructor.prototype, {
        constructor: FactoryTooltip,
        tip: function () {
            var tip = this.$tip = this.$tip || $(this.options.template);

            if (undefined !== this.options.className || '' !== this.options.className) {
                tip.addClass(this.options.className);
            }

            return tip;
        }
    });

    $.fn.FactoryTooltip = function (option) {
        return this.each(function () {
            var $this = $(this)
                , data = $this.data('tooltip')
                , options = typeof option == 'object' && option;
            if (!data) $this.data('tooltip', (data = new FactoryTooltip(this, options)));
            if (typeof option == 'string') data[option]();
        });
    }
})(window.jQuery);
