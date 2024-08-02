/*global finnaCustomInit */
/*exported finna */

var finna = (function finnaModule() {

  let resolves = {};

  /**
   * Object which holds promises, key is the name for the promise to wait for.
   *
   * @var {Object}
   */
  let promises = {
    lazyImages: new Promise((resolve) => { resolves.lazyImages = resolve; })
  };

  /**
   * Object which holds resolves, key is the name for the promise to resolve.
   */

  var my = {
    init: function init() {
      // List of modules to be inited
      var modules = [
        'advSearch',
        'authority',
        'autocomplete',
        'contentFeed',
        'common',
        'changeHolds',
        'dateRangeVis',
        'feedback',
        'fines',
        'itemStatus',
        'layout',
        'menu',
        'myList',
        'openUrl',
        'primoAdvSearch',
        'record',
        'searchTabsRecommendations',
        'StreetSearch',
        'finnaSurvey',
        'multiSelect',
        'finnaMovement',
        'mdEditable',
        'a11y'
      ];

      $.each(modules, function initModule(ind, module) {
        if (typeof finna[module] !== 'undefined') {
          finna[module].init();
        }
      });
    },
    resolvePromise: (name) => {
      if (resolves[name]) {
        resolves[name]();
      }
    },
    getPromise: (name) => {
      return promises[name];
    },
  };

  return my;
})();

$(function onReady() {
  finna.init();

  // init custom.js for custom theme
  if (typeof finnaCustomInit !== 'undefined') {
    finnaCustomInit();
  }
});
