/*global VuFind, finna */
finna.encapsulatedRecords = (function encapsulatedRecords() {
  var my = {
    init: function init() {
      $('.encapsulated-records.show-all').not(':data(inited)').each(function initRecords() {
        var records = $(this);
        records.data('inited', '1');

        var showMore = records.find('.show-more');
        var spinner = records.find('.fa-spinner');
        records.find('.btn.load-more').on('click', function initLoadMore() {
          spinner.removeClass('hide').show();

          var btn = $(this);

          var id = btn.data('id');
          var offset = btn.data('offset');
          var indexStart = btn.data('start-index');
          var view = btn.data('view');
          var sort = btn.data('sort');

          btn.hide();

          var resultsContainer = records.find(
            view === 'grid' ? '.search-grid' : '.result-view-' + view
          );

          $.getJSON(
            VuFind.path + '/AJAX/JSON?method=getEncapsulatedRecords',
            {
              id: id,
              offset: offset,
              indexStart: indexStart,
              view: view,
              sort: sort,
              method: 'getEncapsulatedRecords'
            }
          )
            .done(function onRecordsLoaded(response) {
              showMore.remove();
              $(VuFind.updateCspNonce(response.data.html)).find('.result').each(function appendResult(/*index*/) {
                resultsContainer.append($(this));
              });

              finna.myList.init();
              finna.layout.initCondensedList(resultsContainer);
              finna.layout.initTruncate();
              finna.layout.initImagePaginators();
              finna.openUrl.initLinks(resultsContainer);
              finna.videoPopup.initIframeEmbed(resultsContainer);
              finna.videoPopup.initVideoPopup(resultsContainer);
              VuFind.itemStatuses.init(resultsContainer);
              finna.itemStatus.initDedupRecordSelection(resultsContainer);
              VuFind.recordVersions.init(resultsContainer);
              VuFind.lightbox.bind(resultsContainer);
              VuFind.cart.init(resultsContainer);
              $.fn.finnaPopup.reIndex();
              VuFind.saveStatuses.init(resultsContainer);
            })
            .fail(function onLoadRecordsFail() {
              btn.show();
              spinner.hide();
            });

          return false;
        });
      });
    }
  };

  return my;
})();
