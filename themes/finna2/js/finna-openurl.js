/*global VuFind, finna */
finna.openUrl = (function finnaOpenUrl() {
  function initLinks(_container) {
    var container = _container || $('body');
    VuFind.observerManager.createIntersectionObserver(
      'OpenUrl',
      (element) => {
        VuFind.openurl.embedOpenUrlLinks($(element));
      },
      $(container).find('.openUrlEmbed a').toArray()
    );
  }

  var my = {
    initLinks: initLinks,
    init: function init() {
      initLinks();
    }
  };

  return my;
})();
