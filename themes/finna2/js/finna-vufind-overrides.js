/*global VuFind*/

// Override VuFind itemstatuses init for dedup elements to properly load
VuFind.itemStatuses.init = function init(_container) {
  var container = typeof _container === 'undefined'
    ? document.body
    : _container;

  if (VuFind.isPrinting()) {
    VuFind.itemStatuses.check(container);
    return;
  }
  VuFind.observerManager.createIntersectionObserver(
    'itemStatuses',
    VuFind.itemStatuses.checkRecord,
    $(container).find('.ajaxItem:not(.js-sourceless)').toArray()
  );
};
