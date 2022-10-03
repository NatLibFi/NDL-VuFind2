/*global VuFind, finna, Hunt*/

// Override VuFind itemstatuses init for dedup elements to properly load
VuFind.itemStatuses.init = function init(_container) {
  var container = typeof _container === 'undefined'
    ? document.body
    : _container;
  finna.itemStatus.updateElementIDs(container);
  if (typeof Hunt === 'undefined' || VuFind.isPrinting()) {
    VuFind.itemStatuses.check(container);
  } else {
    new Hunt(
      $(container).find('.ajaxItem:not(.js-sourceless)').toArray(),
      { enter: VuFind.itemStatuses.checkRecord }
    );
  }
};
