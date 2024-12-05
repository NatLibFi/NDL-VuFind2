/*global finna,VuFind */
finna.finnaSurvey = (function finnaSurvey() {
  var _cookieName = 'finnaSurvey';

  /**
   * Initialize finna survey events
   */
  function init() {
    var cookie = VuFind.cookie.get(_cookieName);
    if (typeof cookie !== 'undefined' && cookie === '1') {
      return;
    }

    var holder = $('#finna-survey');
    holder.find('a').on('click', function onClickHolder(/*e*/) {
      holder.fadeOut(100);
      VuFind.cookie.set(_cookieName, '1');

      if ($(this).hasClass('close-survey')) {
        return false;
      }
    });

    setTimeout(function timeoutCallback() {
      holder.fadeIn(300).css({bottom: 0});
    }, 150);
  }

  var my = {
    init: init
  };

  return my;
})();
