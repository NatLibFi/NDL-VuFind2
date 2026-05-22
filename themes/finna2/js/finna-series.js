/*global VuFind, finna*/
finna.series = (function finnaSeries() {
  /**
   * Initialize series tab
   */
  function initSeriesTab() {
    document.querySelectorAll(".series-header").forEach((el) => {
      el.querySelectorAll(".dropdown-item.dropdown__link").forEach((link) => {
        const container = document.querySelector(".record-tab-series-container");
        link.addEventListener("click", function onSeriesLabelClick(e) {
          e.preventDefault();
          $.ajax({
            url: VuFind.path + '/AJAX/JSON?method=getRecordSeries',
            dataType: 'json',
            data: {
              'id': link.getAttribute('data-id'),
              'source': link.getAttribute('data-source'),
              'seriesKey': link.getAttribute('data-serieskey')
            }
          }).done(function onGetRecordSeriesDone(response) {
            container.innerHTML = VuFind.updateCspNonce(response.data.html);
            finna.scrollableList.init();
            initSeriesTab();
            container.querySelector('.dropdown-toggle').focus();
          }).fail(function onGetRecordSeriesFail() {
            container.innerHTML = VuFind.translate('error_occurred');
          });
        });
      });
    });
  }

  /**
   * Initialize
   */
  function init() {
    initSeriesTab();
  }

  var my = {
    init: init
  };

  return my;
})();
