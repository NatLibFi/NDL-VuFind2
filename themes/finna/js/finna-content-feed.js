/*global VuFind*/
finna.contentFeed = (function() {
    var loadFeed = function(holder, modal) {
        var id = holder.data('feed');
        var num = holder.data('num');

        var contentHolder = holder.find('.holder');
        // Append spinner
        contentHolder.append('<i class="fa fa-spin fa-spinner"></i>');
        contentHolder.find('.fa-spin').fadeOut(0).delay(1000).fadeIn(100);

        var url = VuFind.path + '/AJAX/JSON?method=getContentFeed&id=' + id + '&num=' + num;

        $.getJSON(url)
        .done(function(response) {
            if (response.data) {
                contentHolder.html(response.data.html);
                var title = response.data.title;

                if (!modal) {
                    $('.content-header').text(title);
                    document.title = title + ' | ' + document.title;
                }
            }
        })
        .fail(function(response, textStatus, err) {
            contentHolder.html('<!-- Feed could not be loaded: ' + response.responseJSON.data + ' -->');
        });

        $('#modal').one('hidden.bs.modal', function() {
            $(this).removeClass('feed-content');
        });
    };

    var my = {
        load: loadFeed,
        init: function() {
            loadFeed($('.feed-content'), false);
        }
    };

    return my;
})(finna);
