/*global VuFind, finna */
finna.itemStatus = (function finnaItemStatus() {
  function initDedupRecordSelection(_holder) {
    let holder = document;
    if (typeof _holder !== 'undefined' && _holder instanceof jQuery) {
      holder = _holder[0];
    }
    const selects = holder.querySelectorAll('.dedup-select');
    selects.forEach(select => {
      select.addEventListener('change', function onChangeDedupSelection() {
        const id = this.value;
        const selected = this.querySelector('option:checked');
        const source = selected ? selected.dataset.source : '';

        const placeholder = this.querySelector('.js-dedup-placeholder');
        if (placeholder) {
          placeholder.remove();
        }

        finna.common.setCookie('preferredRecordSource', source);
        const recordContainer = this.closest('.record-container');
        if (recordContainer) {
          const hiddenID = recordContainer.querySelector('.hiddenId');
          const hiddenSource = recordContainer.querySelector('.hiddenSource');
          if (hiddenID) {
            const oldRecordId = hiddenID.value;
            hiddenID.value = id;
            recordContainer.querySelectorAll(`[id="${oldRecordId}"]`).forEach(el => {
              el.attr('id', id);
            });
            recordContainer.querySelectorAll('a[href]').forEach(link => {
              link.setAttribute('href', link.getAttribute('href').replace(oldRecordId, id));
            });
          }

          const callnumAndLocation = recordContainer.querySelector('.callnumAndLocation');
          const callnumber = recordContainer.querySelector('.callnumber');
          if (callnumber) {
            callnumber.classList.remove('hidden');
          }

          if (callnumAndLocation) {
            const loading = document.createElement('span');
            loading.className = 'location ajax-availability';
            loading.innerHTML = VuFind.loading();
            callnumAndLocation.innerHTML = '';
            callnumAndLocation.appendChild(loading);
          }
          recordContainer.classList.remove('js-item-done');
          VuFind.itemStatuses.checkRecord(recordContainer);
          const recordUrls = recordContainer.querySelector('.available-online-links');
          if (recordUrls && hiddenID && hiddenSource) {
            recordUrls.innerHtml = VuFind.loading();
            const params = new URLSearchParams({
              method: 'getRecordData',
              data: 'onlineUrls',
              id: hiddenID.value,
              source: hiddenSource.value
            });
            fetch(`${VuFind.path}/AJAX/JSON?${params}`)
              .then(response => response.json())
              .then((jsonResponse) => {
                if (!jsonResponse.data && !jsonResponse.data.html) {
                  return;
                }
                recordUrls.innerHTML = VuFind.updateCspNonce(jsonResponse.data.html);
                finna.layout.initTruncate(recordContainer);
                recordContainer.querySelectorAll('.openUrlEmbed a').forEach(url => {
                  VuFind.openurl.embedOpenUrlLinks(url);
                });
              });
          }
        }
      });
    }); 
    selects.forEach(select => {
      if (typeof select.dataset.overrideid !== 'undefined') {
        select.value = select.dataset.overrideid;
        const event = new Event('change');
        select.dispatchEvent(event);
      }
    });
  }

  var my = {
    initDedupRecordSelection: initDedupRecordSelection,
    init: function init() {
      if (!document.querySelector('.results.result-view-condensed')) {
        initDedupRecordSelection();
      }
    }
  };

  return my;
})();
