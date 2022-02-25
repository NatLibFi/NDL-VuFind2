/*global finnaCustomInit */
/*exported finna */
var finna = (function finnaModule() {
  var checkBrowserSupport = function checkBrowserSupport() {
    /* jshint ignore:start */
    /* eslint-disable */
    try {
      () => {};
      // Class support
      class __ES6FeatureDetectionTest { };
      // Object initializer property and method shorthands
      let a = true;
      let b = { 
        a,
        c() { return true; },
        d: [1,2,3],
       };
      // Object destructuring
      let { c, d } = b;
      // Spread operator
      let e = [...d, 4];
      window.isES6 = true;
    } catch (error) {
      console.log(error);
      var oldBrowser = document.findElementById("#old-browser-message");
      if (oldBrowser) {
        oldBrowser.classList.remove('hidden');
      }
    };
    /* eslint-enable */
    /* jshint ignore:end */
  };

  var my = {
    init: function init() {
      // Check if the browser is supported
      checkBrowserSupport();
      // List of modules to be inited
      var modules = [
        'advSearch',
        'authority',
        'autocomplete',
        'contentFeed',
        'common',
        'changeHolds',
        'dateRangeVis',
        'feed',
        'feedback',
        'itemStatus',
        'layout',
        'menu',
        'myList',
        'openUrl',
        'organisationList',
        'primoAdvSearch',
        'record',
        'searchTabsRecommendations',
        'StreetSearch',
        'finnaSurvey',
        'multiSelect',
        'finnaMovement',
        'mdEditable'
      ];

      $.each(modules, function initModule(ind, module) {
        if (typeof finna[module] !== 'undefined') {
          finna[module].init();
        }
      });
    }
  };

  return my;
})();

$(document).ready(function onReady() {
  finna.init();

  // init custom.js for custom theme
  if (typeof finnaCustomInit !== 'undefined') {
    finnaCustomInit();
  }
});
