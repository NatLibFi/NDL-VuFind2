/*global VuFind, finna */
finna.openUrl = (function finnaOpenUrl() {
  // This function only exists for back-compatibility.
  /**
   * Init links for open url
   * @param {HTMLElement} _container Container of the links
   */
  function initLinks(_container) {
    VuFind.openurl.init(_container);
  }

  var my = {
    initLinks: initLinks,
    init: function init() {}
  };

  return my;
})();
