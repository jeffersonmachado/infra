(function ($) {
    $.extend({
        FactoryModal: function (options) {
            var modal = this;
            var defaults = {
                url: null,
                onSubmit: function (modal) {
                }
            };
            var opts = $.extend({}, defaults, options);

            this.init = function () {
                var body = $('body');
                var overlay = $('<div></div>');
                var wrapper = $('<div></div>');
                var content = $('<div></div>');

                var bodyHeight = body.outerHeight();

                overlay.addClass('factory-modal-overlay');
                wrapper.addClass('factory-modal-wrapper');
                content.addClass('factory-modal-content');

                wrapper.append(content);
                body.append(overlay);
                body.append(wrapper);

                content.html('<i class="factory-icon-loader"></i>');

                body.css({
                    overflow: 'hidden'
                });

                overlay.css({
                    height: bodyHeight
                });

                modal.wrapper = wrapper;
                modal.overlay = overlay;
                modal.content = content;

                this.resize();
            }

            this.resize = function () {
                var bodyWidth = $('body').outerWidth();

                modal.wrapper.css({
                    left: (bodyWidth - modal.wrapper.outerWidth()) / 2,
                    top: $(document).scrollTop() + ($(window).height() - modal.wrapper.outerHeight()) / 2
                });
            }

            this.load = function (url) {
                $.get(url, function (response) {
                    // Load data.
                    modal.content.html(response);

                    // Resize window.
                    modal.resize();

                    // Close button.
                    modal.content.find('a.factory-modal-close').click(function (event) {
                        event.preventDefault();

                        modal.close();
                    });

                    // Submit button.
                    modal.content.find('a.factory-modal-submit').click(function (event) {
                        event.preventDefault();

                        modal.content.addClass('factory-modal-loading');
                        opts.onSubmit.call(this, modal);
                    });
                });
            }

            this.close = function () {
                modal.overlay.remove();
                modal.wrapper.remove();

                $('body').css({
                    overflow: 'auto'
                });
            }

            this.init();
            this.load(opts.url);

            return modal;
        }
    });
})(window.jQuery);
