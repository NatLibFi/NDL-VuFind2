/*global VuFind, finna, checkSaveStatuses */
finna.searchTabsRecommendations = (() => {
  function initSearchTabsRecommendations() {
    const holder = document.getElementById('search-tabs-recommendations-holder');
    if (!holder || !holder.dataset.searchId) {
      return;
    }
    const params = new URLSearchParams({
      searchId: holder.dataset.searchId,
      limit: holder.dataset.limit
    });
    const url = `${VuFind.path}/AJAX/JSON?method=getSearchTabsRecommendations&${params}`;
    fetch(url)
      .then(response => response.json())
      .then((data) => {
        if (data.html) {
          holder.innerHTML = VuFind.updateCspNonce(data.html);
          finna.layout.initTruncate(holder);
          finna.openUrl.initLinks();
          VuFind.lightbox.bind(holder);
          VuFind.itemStatuses.check(holder);
          finna.itemStatus.initDedupRecordSelection(holder);
          checkSaveStatuses(holder);
        }
      });
  }

  var my = {
    init: () => {
      initSearchTabsRecommendations();
    }
  };

  return my;
})();
