/*global VuFind, finna */
finna.itemStatus = (function finnaItemStatus() {

  /**
   * Finds the closest record-container and sets element ids to match
   * desired record id.
   *
   * @param {HTMLSelectElement} element 
   * @returns void
   */
  function updateElement(element) {
    var id = $(element).val();
    if (!id) {
      return;
    }
    var recordContainer = $(element).closest('.record-container');
    recordContainer.data('ajaxAvailabilityDone', 0);
    var oldRecordId = recordContainer.find('.hiddenId')[0].value;
    if (id === oldRecordId) {
      return;
    }
    // Update IDs of elements
    recordContainer.find('.hiddenId').val(id);
    var hiddenId = recordContainer.find('.hiddenId');
    hiddenId.val(id);
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
    if (recordContainer.hasClass('js-item-done')) {
      $(element).trigger('change', [true]);
    }
  }

  /**
   * Finds all the elements with dedup-select class and updates their ids.
   *
   * @param {HTMLElement} holder 
   */
  function updateElementIDs(holder) {
    var selects = $(holder).find('.dedup-select');
    selects.each((ind, element) => {
      updateElement(element);
    });
  }

  /**
   * Assigns a change eventlistener to all elements with class dedup-select
   *
   * @param {HTMLElement|null} _holder 
   */
  function initDedupRecordSelection(_holder) {
    var holder = typeof _holder === 'undefined' ? $(document) : _holder;
    var selects = $(holder).find('.dedup-select');

    selects.on('change', function onChangeDedupSelection(e, auto_selected) {
      var source = $(this).find('option:selected').data('source');
      var recordContainer = $(this).closest('.record-container');
      var hiddenId = recordContainer.find('.hiddenId');
      // prefer 3 latest sources
      var cookie = finna.common.getCookie('preferredRecordSource');
      if (cookie) {
        cookie = JSON.parse(cookie);
      }
      if (!Array.isArray(cookie)) {
        // If no cookie is set, assign the source as a default for all dedups
        cookie = [];
      } else if (cookie.length > 2) {
        cookie.slice(0, 2);
      }
      cookie.unshift(source);
      finna.common.setCookie('preferredRecordSource', JSON.stringify(cookie));

      selects.each(function setValues() {
        var elem = $(this).find(`option[data-source='${source}']`);
        if (elem.length) {
          $(this).val(elem.val());
        }
      });

      const placeholder = $(this).find('.js-dedup-placeholder');
      if (placeholder) {
        placeholder.remove();
      }
      // Update deduplication elements to match only if done with user input
      if (!auto_selected) {
        updateElementIDs(holder);
      }
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
            id: hiddenId.val()
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
    updateElementIDs: updateElementIDs,
    init: function init() {
      if (!$('.results').hasClass('result-view-condensed')) {
        initDedupRecordSelection();
      }
    }
  };

  return my;
})();
