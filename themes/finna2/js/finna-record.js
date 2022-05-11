/*global VuFind, finna, removeHashFromLocation, getNewRecordTab, ajaxLoadTab */
finna.record = (() => {
  const accordionTitleHeight = 64;

  function initDescription() {
    const description = document.getElementById('description_text');
    if (description) {
      const id = description.dataset.id;
      const url = `${VuFind.path}/AJAX/JSON?method=getDescription&id=${id}`;
      fetch(url)
        .then(response => response.json())
        .then(responseJSON => {
          if (responseJSON.data.html.length) {
            description.innerHTML = VuFind.updateCspNonce(responseJSON.data.html);
          }
        })
        .catch(() => {
          description.style.display = 'none';
        });
    }
  }

  function initHideDetails() {
    const showButton = document.getElementsByClassName('show-details-button')[0];
    const hideButton = document.getElementsByClassName('hide-details-button')[0];
    if (!showButton && !hideButton) {
      return;
    }
    const moreArea = document.querySelector('.record-information .record-details-more');
    showButton.addEventListener('click', () => {
      if (moreArea) {
        moreArea.classList.remove('hidden');
      }
      showButton.classList.add('hidden');
      hideButton.classList.remove('hidden');
      sessionStorage.setItem('finna_record_details', '1');
    });
    hideButton.addEventListener('click', () => {
      if (moreArea) {
        moreArea.classList.add('hidden');
      }
      hideButton.classList.add('hidden');
      showButton.classList.remove('hidden');
      sessionStorage.removeItem('finna_record_details');
    });
    const recordInformation = document.querySelector('.record-information');
    if (recordInformation && recordInformation.clientHeight > 350) {
      if (sessionStorage.getItem('finna_record_details')) {
        showButton.click();
      } else {
        hideButton.click();
      }
    }
  }

  function getRequestLinkData(element, recordId) {
    var vars = {}, hash;
    var hashes = element.href.slice(element.href.indexOf('?') + 1).split('&');

    for (var i = 0; i < hashes.length; i++) {
      hash = hashes[i].split('=');
      var x = hash[0];
      var y = hash[1];
      vars[x] = y;
    }
    vars.id = recordId;
    return vars;
  }

  function checkRequestsAreValid(elements, requestType) {
    if (!elements[0]) {
      return;
    }
    const recordId = elements[0].href.match(/\/Record\/([^/]+)\//)[1];
    const vars = [];
    elements.forEach(element => vars.push(getRequestLinkData(element, recordId)));
    const params = {
      id: recordId,
      requestType: requestType,
      data: vars
    };
    console.log(vars);
    const url = `${VuFind.path}/AJAX/JSON?method=checkRequestsAreValid`;
    fetch(url, {
      method: 'POST',
      headers: {
        'Accept': 'application/json, text/plain, */*',
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(params)
    })
      .then(response => response.json())
      .then(jsonResponse => {
        console.log(jsonResponse.data);
        jsonResponse.data.forEach((result, index) => {
          const element = elements[index];
          if (response.status) {
            element.classList.remove('disabled');
            element.innerHTML = VuFind.updateCspNonce(result.msg);
          } else {
            element.remove();
          }
        });
      })
      .catch(reason => {
        console.error(reason);
      });
  }

  function startRequestCheck(group, identifier, requestType) {
    const found = group.querySelectorAll(`${identifier}`);
    if (found.length) {
      found.forEach(e => e.classList.remove(identifier));
      checkRequestsAreValid(found, requestType);
    }
  }

  function fetchHoldingsDetails(elements) {
    if (!elements[0]) {
      return;
    }
    elements.forEach((element) => {
      element.classList.remove('hidden');
      const url = `${VuFind.path}/AJAX/JSON?method=getHoldingsDetails`;
      params = {
        data: element.dataset,
      };
      fetch(url, {
        method: 'POST',
        body: JSON.stringify(params),
      }).then(response => response.json())
        .then(responseJSON => {
          element.classList.add('hidden');
          const group = element.closest('.holdings-group');
          const loading = group.getElementsByClassName('load-more-indicator-ajax')[0];
          if (loading) {
            loading.classList.add('hidden');
          }
          const details = group.getElementsByClassName('holdings-details-ajax hidden')[0];
          if (details) {
            details.innerHTML = VuFind.updateCspNonce(responseJSON.data.details);
            details.classList.remove('hidden');
          }
          const itemContainer = group.getElementsByClassName('holdings-items-ajax hidden')[0];
          if (itemContainer) {
            itemContainer.innerHTML = VuFind.updateCspNonce(responseJson.data.items);
            itemContainer.classList.remove('hidden');
          }
          startRequestCheck(group, 'expandedCheckRequest', 'Hold');
          startRequestCheck(group, 'expandedCheckStorageRetrievalRequest', 'StorageRetrievalRequest');
          startRequestCheck(group, 'expandedCheckILLRequest', 'ILLRequest');
          VuFind.lightbox.bind(itemContainer);
          const loadMoreItems = group.getElementsByClassName('load-more-items-ajax')[0];
          if (loadMoreItems) {
            loadMoreItems.addEventListener('click', (e) => {
              loadMoreItems.classList.add('hidden');
              loading.classList.remove('hidden');
              fetchHoldingsDetails(loadMoreItems.parentElement);
            });
          }
        })
        .catch(() => {
          element.textContent = VuFind.translate('error_occurred');
        });
    });
  }

  function setUpCheckRequest() {
    startRequestCheck(document, 'expandedCheckRequest', 'Hold');
    startRequestCheck(document, 'expandedCheckStorageRetrievalRequest', 'StorageRetrievalRequest');
    startRequestCheck(document, 'expandedCheckILLRequest', 'ILLRequest');
    const expandedGetDetails = document.querySelectorAll('.expandedGetDetails');
    expandedGetDetails.forEach(e => e.classList.remove('expandedGetDetails'));
    fetchHoldingsDetails(expandedGetDetails);
  }

  function initHoldingsControls() {
    const tables = document.querySelectorAll('.record-holdings-table:not(.electronic-holdings)');
    if (tables.length) {
      tables.forEach((table) => {
        const headings = table.querySelectorAll('.holdings-container-heading');
        headings.forEach((heading) => {
          heading.addEventListener('click', (e) => {
            if (e.target.closest('.location-service')) {
              return;
            }
            const siblings = (element, selector) => {
              let next = element.nextElementSibling;
              const found = [];
              while (next && !next.matches(selector)) {
                found.push(next);
                next = next.nextElementSibling;
              }
              return found;
            };
            const expandable = siblings(heading, '.holdings-container-heading');
            expandable.forEach(t => t.classList.toggle('collapsed'));
            const icon = heading.querySelector('.location .fa');
            if (icon) {
              icon.classList.toggle('fa-arrow-down');
              icon.classList.toggle('fa-arrow-right');
            }
            const collapsedCheckRequest = [];
            const collapsedCheckStorageRetrievalRequest = [];
            const collapsedCheckILLRequest = [];
            const filtered = [];
            expandable.forEach(e => {
              collapsedCheckRequest.push(...e.getElementsByClassName('collapsedCheckRequest'));
              collapsedCheckStorageRetrievalRequest.push(...e.getElementsByClassName('collapsedCheckStorageRetrievalRequest'));
              collapsedCheckILLRequest.push(...e.getElementsByClassName('collapsedCheckILLRequest'));
              if (e.classList.contains('collapsedGetDetails')) {
                filtered.push(e);
              }
            });
            checkRequestsAreValid(collapsedCheckRequest, 'Hold', 'holdBlocked');
            checkRequestsAreValid(collapsedCheckStorageRetrievalRequest, 'StorageRetrievalRequest', 'StorageRetrievalRequestBlocked');
            checkRequestsAreValid(collapsedCheckILLRequest, 'ILLRequest', 'ILLRequestBlocked');
            fetchHoldingsDetails(filtered); 
          });
        });
      });
    }
  }

  function augmentOnlineLinksFromHoldings() {
    const electronicLinks = document.querySelectorAll('.electronic-holdings a');
    const recordURLs = document.querySelector('.recordURLs');
    const template = recordURLs.querySelector('.url-template');
    const title = document.querySelector('.record-holdings-table:not(.electronic-holdings) .holdings-title');
    electronicLinks.forEach(link => {
      const exists = recordURLs.querySelector(`a[href="${link.href}"]`);
      if (!exists || exists.textContent !== link.textContent) {
        const newLink = template.cloneNode(true);
        let inner = newLink.innerHTML;
        inner = inner.replace('HREF', link.href);
        inner = inner.replace('DESC', link.textContent);
        inner = inner.replace('SOURCE', title ? title.textContent : '');
        newLink.innerHTML = inner;
        if (!exists) {
          recordURLs.prepend(newLink);
        } else {
          exists.parentNode.replaceWith(newLink);
        }
        newLink.classList.remove('url-template', 'hidden');
      }
    });
  }

  function setupHoldingsTab() {
    initHoldingsControls();
    setUpCheckRequest();
    augmentOnlineLinksFromHoldings();
    finna.layout.initLocationService();
    finna.layout.initJumpMenus($('.holdings-tab'));
    VuFind.lightbox.bind($('.holdings-tab'));
  }

  function setupLocationsEad3Tab() {
    const containers = document.getElementsByClassName('holdings-container-heading');
    containers.forEach(container => {
      container.addEventListener('click', () => {
        let elem = container.nextElementSibling;
        while (elem) {
          if (elem.matches('.holdings-container-heading')) {
            break;
          }
          elem.classList.toggleClass('collapsed');
          elem = elem.nextElementSibling;
        }
        const location = container.querySelector('.location .fa');
        if (!location) {
          return;
        }
        location.classList.toggle('fa-arrow-down');
        location.classList.toggle('fa-arrow-right');
      });
    });
  }

  function initRecordNaviHashUpdate() {
    window.addEventListener('hashchange', () => {
      const pagers = document.querySelectorAll('.pager a');
      if (pagers.length) {
        pagers.forEach(a => a.hash = window.location.hash);
      }
    });
    window.dispatchEvent(new HashChangeEvent('hashchange'));
  }

  function initAudioAccordion() {
    const audioWrappers = document.querySelectorAll('.audio-item-wrapper');
    audioWrappers.forEach((wrapper, index) => {
      if (0 === index) {
        wrapper.classList.add('active');
      }
      const title = wrapper.querySelector('.audio-title-wrapper');
      if (title) {
        title.addEventListener('click', () => {
          const active = document.querySelector('.audio-item-wrapper.active');
          if (active) {
            active.classList.remove('active');
          }
          wrapper.classList.add('active');
        });
      }
    });
  }

  // The accordion has a delicate relationship with the tabs. Handle with care!
  function _toggleAccordion(accordion, _initialLoad) {
    var initialLoad = typeof _initialLoad === 'undefined' ? false : _initialLoad;
    var tabid = accordion.find('.accordion-toggle a').data('tab');
    var $recordTabs = $('.record-tabs');
    var $tabContent = $recordTabs.find('.tab-content');
    if (initialLoad || !accordion.hasClass('active')) {
      // Move tab content under the correct accordion toggle
      $tabContent.insertAfter(accordion);
      if (accordion.hasClass('noajax') && !$recordTabs.find('.' + tabid + '-tab').length) {
        return true;
      }
      $('.record-accordions').find('.accordion.active').removeClass('active');
      accordion.addClass('active');
      $recordTabs.find('.tab-pane.active').removeClass('active');
      if (!initialLoad && $('.record-accordions').is(':visible')) {
        $('html, body').animate({scrollTop: accordion.offset().top - accordionTitleHeight}, 150);
      }

      if ($recordTabs.find('.' + tabid + '-tab').length > 0) {
        $recordTabs.find('.' + tabid + '-tab').addClass('active');
        if (accordion.hasClass('initiallyActive')) {
          removeHashFromLocation();
        } else {
          window.location.hash = tabid;
        }
        return false;
      } else {
        var newTab = getNewRecordTab(tabid).addClass('active');
        $recordTabs.find('.tab-content').append(newTab);
        return ajaxLoadTab(newTab, tabid, !$(this).parent().hasClass('initiallyActive'));
      }
    }
    return false;
  }

  function initRecordAccordion() {
    $('.record-accordions .accordion-toggle').on('click', function accordionClicked(e) {
      return _toggleAccordion($(e.target).closest('.accordion'));
    });
    if ($('.mobile-toolbar').length > 0 && $('.accordion-holdings').length > 0) {
      $('.mobile-toolbar .library-link li').removeClass('hidden');
      $('.mobile-toolbar .library-link li').on('click', function onLinkClick(e) {
        e.stopPropagation();
        $('html, body').animate({scrollTop: $('#tabnav').offset().top - accordionTitleHeight}, 150);
        _toggleAccordion($('.accordion-holdings'));
      });
    }
  }

  function applyRecordAccordionHash(callback) {
    var newTab = typeof window.location.hash !== 'undefined'
      ? window.location.hash.toLowerCase() : '';

    // Open tab in url hash
    var $tab = $("a:not(.feed-tab-anchor,.feed-accordion-anchor)[data-tab='" + newTab.substr(1) + "']");
    var accordion = (newTab.length <= 1 || newTab === '#tabnav' || $tab.length === 0)
      ? $('.record-accordions .accordion.initiallyActive')
      : $tab.closest('.accordion');
    if (accordion.length > 0) {
      //onhashchange is an object, so we avoid that later
      if (typeof callback === 'function') {
        callback(accordion);
      } else {
        var mobile = $('.mobile-toolbar');
        var initialLoad = mobile.length > 0 ? !mobile.is(':visible') : true;
        _toggleAccordion(accordion, initialLoad);
      }
    }
  }

  //Toggle accordion at the start so the accordions work properly
  function initialToggle(accordion) {
    var $recordTabs = $('.record-tabs');
    var $tabContent = $recordTabs.find('.tab-content');
    var tabid = accordion.find('.accordion-toggle a').data('tab');
    $tabContent.insertAfter(accordion);
    if (accordion.hasClass('noajax') && !$recordTabs.find('.' + tabid + '-tab').length) {
      return true;
    }

    $('.record-accordions').find('.accordion.active').removeClass('active');
    accordion.addClass('active');
    $recordTabs.find('.tab-pane.active').removeClass('active');
    if ($recordTabs.find('.' + tabid + '-tab').length > 0) {
      $recordTabs.find('.' + tabid + '-tab').addClass('active');
      if (accordion.hasClass('initiallyActive')) {
        removeHashFromLocation();
      }
    }
  }

  function loadRecommendedRecords(container, method)
  {
    if (container.length === 0) {
      return;
    }
    var spinner = container.find('.fa-spinner').removeClass('hide');
    var data = {
      method: method,
      id: container.data('id')
    };
    if ('undefined' !== typeof container.data('source')) {
      data.source = container.data('source');
    }
    $.getJSON(VuFind.path + '/AJAX/JSON', data)
      .done(function onGetRecordsDone(response) {
        if (response.data.html.length > 0) {
          container.html(VuFind.updateCspNonce(response.data.html));
        }
        spinner.addClass('hidden');
      })
      .fail(function onGetRecordsFail() {
        spinner.addClass('hidden');
        container.text(VuFind.translate('error_occurred'));
      });
  }

  function loadSimilarRecords()
  {
    loadRecommendedRecords($('.sidebar .similar-records'), 'getSimilarRecords');
  }

  function loadRecordDriverRelatedRecords()
  {
    loadRecommendedRecords($('.sidebar .record-driver-related-records'), 'getRecordDriverRelatedRecords');
  }

  function initRecordVersions(_holder) {
    VuFind.recordVersions.init(_holder);
  }

  function handleRedirect(oldId, newId) {
    if (window.history.replaceState) {
      var pathParts = window.location.pathname.split('/');
      pathParts.forEach(function handlePathPart(part, i) {
        if (decodeURIComponent(part) === oldId) {
          pathParts[i] = encodeURIComponent(newId);
        }
      });
      window.history.replaceState(null, document.title, pathParts.join('/') + window.location.search + window.location.hash);
    }
  }

  function init() {
    initHideDetails();
    initDescription();
    initRecordNaviHashUpdate();
    initRecordAccordion();
    initAudioAccordion();
    applyRecordAccordionHash(initialToggle);
    $(window).on('hashchange', applyRecordAccordionHash);
    loadSimilarRecords();
    loadRecordDriverRelatedRecords();
    finna.authority.initAuthorityResultInfo();
  }

  var my = {
    checkRequestsAreValid: checkRequestsAreValid,
    init: init,
    setupHoldingsTab: setupHoldingsTab,
    setupLocationsEad3Tab: setupLocationsEad3Tab,
    initRecordVersions: initRecordVersions,
    handleRedirect: handleRedirect
  };

  return my;
})();
