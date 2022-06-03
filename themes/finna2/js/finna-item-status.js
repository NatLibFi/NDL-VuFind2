/*global VuFind, finna */
finna.itemStatus = (function finnaItemStatus() {
  function initDedupRecordSelection(_holder) {
    var holder = typeof _holder === 'undefined' ? $(document) : _holder;

    $(holder).find('.dedup-select').on('change', function onChangeDedupSelection() {
      var id = $(this).val();
      var source = $(this).find('option:selected').data('source');
      finna.common.setCookie('preferredRecordSource', source);

      var recordContainer = $(this).closest('.record-container');
      recordContainer.data('ajaxAvailabilityDone', 0);
      var oldRecordId = recordContainer.find('.hiddenId')[0].value;

      // Update IDs of elements
      recordContainer.find('.hiddenId').val(id);

      // Update IDs of elements
      recordContainer.find('[id="' + oldRecordId + '"]').each(function updateElemId() {
        $(this).attr('id', id);
      });

      // Update links as well
      recordContainer.find('a').each(function updateLinks() {
        if (typeof $(this).attr('href') !== 'undefined') {
          $(this).attr('href', $(this).attr('href').replace(oldRecordId, id));
        }
      });

      // Item statuses
      var $loading = $('<span/>')
        .addClass('location ajax-availability hidden')
        .html(VuFind.loading());
      recordContainer.find('.callnumAndLocation')
        .empty()
        .append($loading);
      recordContainer.find('.callnumber').removeClass('hidden');
      recordContainer.find('.location').removeClass('hidden');
      recordContainer.removeClass('js-item-done');
      VuFind.itemStatuses.checkRecord(recordContainer);

      // Online URLs
      var $recordUrls = recordContainer.find('.available-online-links');
      if ($recordUrls.length) {
        $recordUrls.html(VuFind.loading());
        $.getJSON(
          VuFind.path + '/AJAX/JSON',
          {
            method: 'getRecordData',
            data: 'onlineUrls',
            source: recordContainer.find('.hiddenSource')[0].value,
            id: recordContainer.find('.hiddenId')[0].value
          }
        ).done(function onGetRecordLinksDone(response) {
          $recordUrls.replaceWith(VuFind.updateCspNonce(response.data.html));
          finna.layout.initTruncate(recordContainer);
          VuFind.openurl.embedOpenUrlLinks(recordContainer.find('.openUrlEmbed a'));
        }).fail(function onGetRecordLinksFail() {
          $recordUrls.html(VuFind.translate('error_occurred'));
        });
      }
    });
  }

  var my = {
    initDedupRecordSelection: initDedupRecordSelection,
    init: function init() {
      if (!$('.results').hasClass('result-view-condensed')) {
        initDedupRecordSelection();
      }
    }
  };

  return my;
})();
