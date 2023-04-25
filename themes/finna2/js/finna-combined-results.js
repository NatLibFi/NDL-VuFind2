/*global VuFind, finna */
finna.combinedResults = (function finnaCombinedResults() {
  var my = {
    init: function init(container) {
      finna.layout.initTruncate();
      finna.layout.initImagePaginators();
      finna.openUrl.initLinks(container);
      finna.itemStatus.initDedupRecordSelection(container);
      VuFind.itemStatuses.init(container);
      VuFind.recordVersions.init(container);
      VuFind.lightbox.bind(container);
      VuFind.cart.init(container);
      VuFind.saveStatuses.init(container);
    }
  };

  return my;
})();
