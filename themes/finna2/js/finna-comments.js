/*global VuFind, finna, registerAjaxCommentRecord:true, refreshCommentList, refreshRecordRating */
finna.comments = (function finnaComments() {
  function requestRefreshCommentsAndRating() {
    let recordId = $('input.hiddenId').val();
    let recordSource = $('input.hiddenSource').val();
    var tab = $('.usercomments-tab');
    refreshCommentList(tab, recordId, recordSource);
    refreshRecordRating(recordId, recordSource);
  }

  function initCommentForm(parentMethod) {
    $('.comment-list .delete').off('click');
    parentMethod();

    $('form.comment-form').off('submit').on('submit', function onCommentSubmit(event) {
      var form = this;
      var data = {};
      $(form).find("input,textarea").each(function appendCaptchaData() {
        var input = $(this);
        if (input.attr('type') === 'radio' && !input.prop('checked')) {
          return true;
        }
        data[input.attr('name')] = input.val();
      });

      if (typeof form.checkValidity == 'function') {
        // This is for Safari, which doesn't validate forms on submit
        if (!form.checkValidity()) {
          event.preventDefault();
          return;
        }
      } else {
        // JS validation for browsers that don't support form validation
        if (form.comment.value === '') {
          $(form.comment).addClass('invalid');
          event.preventDefault();
          return;
        }
        $(form.comment).removeClass('invalid');
      }

      $(this).find('input.cancel').toggleClass('hide', true);
      $(this).find('input[type="submit"]').attr('disabled', true).button('loading');

      var url = VuFind.path + '/AJAX/JSON?' + $.param({method: 'commentRecord'});
      $.ajax({
        type: 'POST',
        url: url,
        data: data,
        dataType: 'json'
      })
        .done(function onCommentSubmitDone() {
          requestRefreshCommentsAndRating();
          $(form).find('textarea[name="comment"]').val('');
        })
        .fail(function onCommentSubmitFail(response/*, textStatus*/) {
          if (typeof response.responseJSON !== 'undefined') {
            alert(response.responseJSON.data);
          } else {
            alert(VuFind.translate('error_occurred'));
          }
          $(form).find('input.cancel').toggleClass('hide', false);
          $(form).find('input[type="submit"]').attr('disabled', false).button('reset');
        });
      return false;
    });
  }

  function initEditComment(allowCommenting) {
    $('.comment-list .edit').off('click').on('click', function onCommentEditClick() {
      var comment = $(this).closest('.comment');
      var form = $('form.comment-form');
      form.toggle(true);

      var save = form.find('input.save');
      save.val(save.data('label-edit'));

      form.find('textarea[name="comment"]').val(comment.find('.comment-text').text());
      form.find('input[type=hidden][name=commentId]').val(comment.data('id'));

      form.find('input.cancel').toggleClass('hide', false);
      return false;
    });

    $('form.comment-form input.cancel').off('click').on('click', function onCommentCancelClick() {
      var form = $('form.comment-form');
      form.toggle(allowCommenting);
      form.find('textarea[name="comment"]').val('');
      form.find('input[type=hidden][name=commentId]').val('');

      var save = form.find('input.save');
      save.val(save.data('label-new'));
      $(this).toggleClass('hide', true);
      return false;
    });
  }

  function initCommentList(allowCommenting, allowRating, commentCount) {
    $('.recordTabs #usercomments .count').text(commentCount);

    var form = $('form.comment-form');
    form.toggle(allowCommenting);
    form.find('input[type=hidden][name=commentId]').val('');

    initEditComment(allowCommenting);

    // Override global method
    var _registerAjaxCommentRecord = registerAjaxCommentRecord;
    registerAjaxCommentRecord = function registerAjaxCommentRecord() { // eslint-disable-line no-global-assign
      initCommentForm(_registerAjaxCommentRecord);
    };
    registerAjaxCommentRecord();
    VuFind.lightbox.bind($('.usercomments-tab'));
  }

  var my = {
    initCommentList: initCommentList,
    requestRefreshCommentsAndRating: requestRefreshCommentsAndRating,
    init: function init() {
    }
  };

  return my;
})();
