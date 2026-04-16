(function ($) {
    $.extend({
        BlogFactoryMediaManager: function () {
            var manager = this;
            var initialised = false;
            var uploading = false;
            var wrapper, overlay, media, activeFolder, activeFile, xmlHttpRequest;
            var options = {
                mode: 'editor'
            };

            this.init = function () {
                initialised = true;

                wrapper = $('<div id="blogfactory-media-manager"></div>');
                media = $('<div class="blogfactory-media-explorer blogfactory-media-pane loading"></div>');
                overlay = $('<div class="blogfactory-media-manager-overlay"></div>');

                wrapper.append(overlay, media);
                $('body').append(wrapper);

                media.load(base_url + 'index.php?option=com_blogfactory&view=media&format=raw', function () {
                    // Remove loading class.
                    media.removeClass('loading');

                    // Close button.
                    $('.pane-buttons')
                        .on('click', '.media-close', function (event) {
                            event.preventDefault();

                            manager.close();
                        })
                        .on('click', '.button-new-folder', function (event) {
                            event.preventDefault();

                            media.find('.pane-new-folder').show();
                        })
                        .on('click', '.button-delete', function (event) {
                            event.preventDefault();

                            var elem = $(this);
                            var icon = elem.find('i:first');
                            var active = media.find('.active');

                            if (!active.length) {
                                return false;
                            }

                            // Check if we are removing the user folder.
                            if (active.find('i:eq(1)').hasClass('factory-icon-blue-folder')) {
                                return false;
                            }

                            icon.toggleClass('factory-icon-cross factory-icon-loader');

                            if (active.parents('.pane-folders').length) {
                                // We are removing a folder.
                                var folder = active.attr('href').replace('#', '');

                                $.post(base_url + 'index.php?option=com_blogfactory&task=media.removefolder&format=raw', {folder: folder}, function (response) {
                                    icon.toggleClass('factory-icon-cross factory-icon-loader');

                                    if (response.status) {
                                        active.parents('li:eq(1)').find('a:first').trigger('activate');
                                        active.parents('li:first').remove();

                                        // Update free space.
                                        $('.space-remaining-value').html(response.free_space);
                                    }
                                }, 'json');
                            }
                            else if (active.parents('.pane-files').length) {
                                // We are removing a file.
                                var file = active.find('.title').text()

                                $.post(base_url + 'index.php?option=com_blogfactory&task=media.removefile&format=raw', {
                                    folder: activeFolder,
                                    file: file
                                }, function (response) {
                                    icon.toggleClass('factory-icon-cross factory-icon-loader');

                                    $('.details', '.pane-file-details').hide();

                                    if (response.status) {
                                        active.remove();
                                        $('.pane-status').trigger('updateFile');

                                        // Update free space.
                                        $('.space-remaining-value').html(response.free_space);
                                    }
                                }, 'json');
                            }

                            return true;
                        })
                        .on('click', '.button-upload', function (event) {
                            event.preventDefault();

                            media.find('.pane-files .active').removeClass('active');
                            $('.pane-status').trigger('updateFile');

                            media.find('.pane-files, .pane-folders, .pane-file-details').hide();
                            media.find('.pane-upload').show();
                        })
                    ;

                    $('.pane-new-folder')
                        .on('click', '.button-cancel', function (event) {
                            event.preventDefault();

                            $(this).parents('.pane-new-folder').hide();
                        })
                        .on('click', '.button-submit', function (event) {
                            event.preventDefault();

                            var elem = $(this);
                            var loader = elem.find('i:first');
                            var pane = $('.pane-new-folder');
                            var title = pane.find('#folder_title');

                            loader.show();

                            $.post(
                                base_url + 'index.php?option=com_blogfactory&task=media.createfolder&format=raw',
                                {title: title.val(), folder: activeFolder},
                                function (response) {
                                    loader.hide();

                                    if (response.status) {
                                        title.val('');
                                        pane.hide();

                                        var active = $('.pane-folders').find('a[href="#' + activeFolder + '"]');
                                        var fold = active.find('i:first');
                                        var ul = active.parents('li:first').find('ul:first');

                                        active.addClass('foldable');

                                        if (fold.hasClass('factory-icon-blank')) {
                                            fold.toggleClass('factory-icon-blank factory-icon-toggle-small-expand')
                                        }

                                        ul.append('<li><a href="#' + response.path + '" class="foldable"><i class="factory-icon-blank" rel="fold-handle"></i><i class="factory-icon-folder"></i>' + response.title + '</a><ul style="display: none;"></ul></li>')
                                    }
                                }, 'json');
                        })
                    ;

                    $('.pane-folders')
                        .on('click', 'a', function (event) {
                            event.preventDefault();

                            var elem = $(this);

                            if ($(event.target).attr('rel') == 'fold-handle') {
                                elem.trigger('fold');
                            }
                            else {
                                elem.trigger('activate');
                            }
                        })
                        .on('dblclick', 'a', function (event) {
                            event.preventDefault();

                            $(this).trigger('fold');
                        })
                        .on('fold', 'a', function (event) {
                            event.preventDefault();

                            var elem = $(this);
                            var icon = elem.find('i:first');

                            if (!elem.hasClass('foldable')) {
                                return false;
                            }

                            elem.parents('li:first').find('ul:first').toggle();
                            icon.toggleClass('factory-icon-toggle-small factory-icon-toggle-small-expand');

                            return true;
                        })
                        .on('activate', 'a', function (event) {
                            event.preventDefault();

                            var elem = $(this);

                            if (elem.hasClass('active')) {
                                return true;
                            }

                            var icon = elem.find('i:eq(1)');
                            icon.data('original-icon', icon.attr('class'));
                            icon.attr('class', 'factory-icon-loader');

                            media.find('.active').removeClass('active');
                            $(this).addClass('active');

                            $('.details', '.pane-file-details').hide();

                            $('.pane-status').trigger('update');

                            activeFolder = elem.attr('href').replace('#', '');

                            $.get(base_url + 'index.php?option=com_blogfactory&view=media&format=raw&layout=files', {folder: activeFolder}, function (response) {
                                icon.attr('class', icon.data('original-icon'));
                                $('.pane-files').html(response);
                            });

                            return true;
                        })
                    ;

                    $('.pane-status')
                        .on('click', 'a', function (event) {
                            event.preventDefault();

                            var elem = $(this);
                            var path = elem.attr('href');

                            $('.pane-folders a[href="' + path + '"]').trigger('activate');
                        })
                        .bind('update', function () {
                            var elem = $(this);
                            var active = $('.pane-folders a.active');
                            var path = [];

                            active.parents('li').each(function (index, element) {
                                var a = $(element).find('a:first');
                                var text = a.text().trim();
                                var href = a.attr('href');

                                path.push('<a href="' + href + '">' + text + '</a>');
                            });

                            path.reverse();

                            elem.html(path.join(' <span class="muted">/</span> '));
                        })
                        .bind('updateFile', function () {
                            var active = $('.pane-files .active .title').text();
                            var elem = $(this);
                            var file = elem.find('span.file');

                            if (!active.length) {
                                file.remove();
                            }

                            if (!file.length) {
                                elem.append('<span class="file"> <span class="muted">/</span> <b></b></span>');
                                file = elem.find('span.file');
                            }

                            file.find('b').text(active);
                        })
                    ;

                    $('.pane-upload')
                        .on('click', '.button-select-files', function (event) {
                            event.preventDefault();

                            $('#upload_files').click();
                        })
                        .on('change', '#upload_files', function (event) {
                            var elem = $(this);
                            var status = $('.upload-status', '.pane-upload');

                            for (var i = 0, count = elem.get(0).files.length; i < count; i++) {
                                var file = elem.get(0).files[i];

                                status.append('<li>' +
                                    '<a href="#" class="btn btn-mini btn-danger"><i class="icon-delete"></i></a>' +
                                    '<div class="info">' +
                                    '<div>' + file.name +
                                    '<span class="muted small upload-file-status">Pending</span>' +
                                    '</div>' +
                                    '<progress value="0" max="100" style="display: none;"></progress>' +
                                    '</div>' +
                                    '</li>');

                                status.find('li:last').data('file', file).data('folder', activeFolder).addClass('pending');
                            }

                            manager.upload();
                        })
                        .on('click', '.upload-status li a', function (event) {
                            event.preventDefault();

                            var elem = $(this);
                            var li = elem.parents('li:first');

                            if (li.hasClass('uploading')) {
                                xmlHttpRequest.abort();
                            }

                            $(this).parent('li:first').remove();
                        })
                        .on('click', '.button-close', function (event) {
                            event.preventDefault();

                            media.find('.pane-files, .pane-folders').show();
                            media.find('.pane-upload').hide();

                            if ('editor' == options.mode) {
                                media.find('.pane-file-details').show();
                            }

                            $('.pane-folders').find('a[href="#' + activeFolder + '"]').removeClass('active').trigger('activate');
                        })
                        .on('click', '.button-clear-list', function (event) {
                            event.preventDefault();

                            $('li', '.pane-upload .upload-status').remove();
                        })
                    ;

                    $('.pane-files')
                        .on('click', 'div.file', function (event) {
                            $(this).trigger('activate');
                        })
                        .on('activate', 'div.file', function (event) {
                            var elem = $(this);
                            var title = elem.find('div.title').text();
                            var type = elem.attr('data-file-type');
                            var extension = title.split('.').pop();
                            title = title.replace('.' + extension, '');

                            activeFile = elem;

                            media.find('.active').removeClass('active');
                            elem.addClass('active');

                            $('.pane-status').trigger('updateFile');

                            if ('manager' == options.mode) {
                                return true;
                            }

                            $('.details', '.pane-file-details').show();
                            $('#details_title, #details_alt_text', '.pane-file-details').val(title);

                            $('#details_link_type').val('media').change();

                            if ('image' == type) {
                                $('#details_size_wrapper, #details_alt_text_wrapper', '.pane-file-details').show();
                                $('#details_title_wrapper', '.pane-file-details').hide();
                            }
                            else {
                                $('#details_size_wrapper, #details_alt_text_wrapper', '.pane-file-details').hide();
                                $('#details_title_wrapper', '.pane-file-details').show();
                            }

                            return true;
                        })
                    ;

                    $('.pane-file-details')
                        .on('click', 'a.button-insert-file', function (event) {
                            event.preventDefault();

                            var type = activeFile.attr('data-file-type');
                            var url = activeFile.attr('data-url');
                            var title = $('#details_title').val();
                            var alt = $('#details_alt_text').val();
                            var size = $('#details_size').val();
                            var align = $('#details_align').val();
                            var linkType = $('#details_link_type').val();
                            var link = $('#details_link').val();
                            var content, src, style, href;

                            href = 'media' == linkType ? url : link;

                            if ('image' == type) {
                                src = url;

                                if ('original' != size) {
                                    var extension = src.split('.').pop();
                                    src = src.replace('.' + extension, '_' + size + '.' + extension);
                                }

                                if ('right' == align || 'left' == align) {
                                    style = 'float: ' + align + ';';
                                }

                                if ('none' == linkType) {
                                    content = '<img src="' + src + '" alt="' + alt + '" style="' + style + '" />';
                                }
                                else {
                                    content = '<a href="' + href + '"><img src="' + src + '" alt="' + alt + '" style="' + style + '" /></a>';
                                }
                            }
                            else {
                                if ('none' == linkType) {
                                    content = title;
                                }
                                else {
                                    content = '<a href="' + href + '">' + title + '</a>';
                                }
                            }

                            tinyMCE.activeEditor.execCommand('mceInsertContent', false, content);

                            manager.close();
                        })
                        .on('change', 'select#details_link_type', function (event) {
                            var elem = $(this);
                            var link = $('#details_link');

                            switch (elem.val()) {
                                case 'none':
                                    link.hide();
                                    break;

                                case 'media':
                                    link.val(activeFile.attr('data-url')).attr('readonly', 'readonly').show();
                                    break;

                                case 'custom':
                                    link.val('').attr('readonly', false).show();
                                    break;
                            }
                        })
                    ;

                    $('.pane-folders a:first').trigger('activate');

                    if ('manager' == options.mode) {
                        media.find('.pane-file-details').hide();
                        media.find('.pane-files').addClass('extended');
                    }

                    return true;
                });
            };

            this.open = function (opts) {
                options = $.extend(options, opts);

                if (!initialised) {
                    this.init();
                }

                wrapper.show();
            };

            this.close = function () {
                wrapper.hide();
            };

            this.upload = function () {
                if (uploading) {
                    return true;
                }

                var item = media.find('.pane-upload .upload-status li.pending:first');

                if (!item.length) {
                    return true;
                }

                var progress = item.find('progress');
                var file = item.data('file');
                var folder = item.data('folder');
                var formData = new FormData();

                xmlHttpRequest = new XMLHttpRequest();

                formData.append('file', file);
                formData.append('folder', folder);
                xmlHttpRequest.open('POST', base_url + 'index.php?option=com_blogfactory&task=media.upload&format=raw', true);

                // On progress event.
                xmlHttpRequest.upload.onprogress = function (e) {
                    if (e.lengthComputable) {
                        var percentComplete = Math.ceil((e.loaded / e.total) * 100);
                        progress.val(percentComplete);
                    }
                };

                // On start event.
                xmlHttpRequest.upload.onloadstart = function (e) {
                    progress.show();

                    item.removeClass('pending').addClass('uploading');
                    item.find('.upload-file-status').text('Uploading');
                }

                // On success event
                xmlHttpRequest.onreadystatechange = function (e) {
                    if (xmlHttpRequest.readyState == 4) {
                        uploading = false;

                        item.removeClass('uploading').addClass('completed');
                        progress.hide();

                        if (xmlHttpRequest.status == 200) {
                            var response = $.parseJSON(xmlHttpRequest.responseText);
                            var button = item.find('a:first');
                            var text = response.status ? response.message : response.error;

                            item.find('.upload-file-status').text(text);

                            if (response.status) {
                                button.removeClass('btn-danger').addClass('btn-success')
                                    .find('i:first').removeClass('icon-delete').addClass('icon-ok');

                                // Update free space.
                                $('.space-remaining-value').html(response.free_space);
                            }
                            else {
                                button.removeClass('btn-danger').addClass('btn-warning')
                                    .find('i:first').removeClass('icon-delete').addClass('icon-unpublish');
                            }
                        }

                        manager.upload();
                    }
                };

                // Send Ajax form
                xmlHttpRequest.setRequestHeader('X_REQUESTED_WITH', 'XMLHttpRequest');
                xmlHttpRequest.send(formData);

                return true;
            };

            return this;
        }
    });
}(jQuery));
