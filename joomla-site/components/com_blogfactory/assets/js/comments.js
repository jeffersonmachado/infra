jQuery(document).ready(function ($) {
    // Voting buttons.
    $('.comment-ratings a[class^="comment-vote-"]').click(function (event) {
        event.preventDefault();

        var elem = $(this);
        var vote = elem.hasClass('comment-vote-up') ? 1 : -1;
        var comment = elem.parents('.blogfactory-post-comment:first');
        var commentId = comment.attr('id').replace('blogfactory-post-comment-', '');
        var iconClass = 'factory-icon-thumb-' + (1 == vote ? 'up' : 'down');

        elem.find('i:first').toggleClass(iconClass + ' factory-icon-loader');

        var data = {
            id: commentId,
            vote: vote
        }

        $.post(base_url + 'index.php?option=com_blogfactory&task=comment.vote', {
            format: 'raw',
            data: data
        }, function (response) {
            elem.find('i:first').toggleClass(iconClass + ' factory-icon-loader');

            elem.FactoryTooltipNotification(response);

            if (response.status) {
                comment.find('.comment-vote-up span').html(response.rating.up);
                comment.find('.comment-vote-down span').html(response.rating.down);
            }
        }, 'json');
    });

    // Delete button.
    $('a.comment-delete').click(function (event) {
        event.preventDefault();

        var elem = $(this);
        var url = elem.attr('href');
        var loader = elem.find('span:first');

        loader.show();

        $.get(url, function (response) {
            loader.hide();

            if (response.status) {
                elem.parents('.comment-bubble:first').html(response.message);
            }
            else {
                elem.FactoryTooltipNotification(response);
            }
        }, 'json');
    });

    // Report button.
    $('a.comment-report').click(function (event) {
        event.preventDefault();

        var elem = $(this);

        $.FactoryModal({
            url: $(this).attr('href'),
            onSubmit: function (modal) {
                var form = modal.content.find('form');

                $.ajax({
                    url: form.attr('action'),
                    type: form.attr('method'),
                    data: form.serialize(),
                    dataType: 'json',
                    success: function (response) {
                        modal.close();
                        elem.FactoryTooltipNotification(response);
                    }
                });
            }
        });
    });
});
