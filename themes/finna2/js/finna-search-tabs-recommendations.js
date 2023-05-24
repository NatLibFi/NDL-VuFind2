/*global VuFind, finna */
finna.searchTabsRecommendations = (() => {
  function initSearchTabsRecommendations() {
    const holder = document.getElementById('search-tabs-recommendations-holder');
    if (!holder || !holder.dataset.searchId) {
      return;
    }
    const params = new URLSearchParams({
      searchId: holder.dataset.searchId,
      limit: holder.dataset.limit || 20
    });
    const url = `${VuFind.path}/AJAX/JSON?method=getSearchTabsRecommendations&${params}`;
    fetch(url)
      .then(response => response.json())
      .then((jsonResponse) => {
        if (jsonResponse.data && jsonResponse.data.html) {
          holder.innerHTML = VuFind.updateCspNonce(jsonResponse.data.html);
          finna.layout.initTruncate(holder);
          finna.openUrl.initLinks();
          VuFind.lightbox.bind(holder);
          VuFind.itemStatuses.init(holder);
          finna.itemStatus.initDedupRecordSelection(holder);
          VuFind.saveStatuses.init(holder);
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
