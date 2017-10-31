/*global VuFind, finna */
finna.record = (function finnaRecord() {
  function initDescription() {
    var description = $('#description_text');
    if (description.length) {
      var id = description.data('id');
      var url = VuFind.path + '/AJAX/JSON?method=getDescription&id=' + id;
      $.getJSON(url)
        .done(function onGetDescriptionDone(response) {
          if (response.data.length > 0) {
            description.html(response.data);

            // Make sure any links open in a new window
            description.find('a').attr('target', '_blank');

            description.wrapInner('<div class="truncate-field wide"><p class="summary"></p></div>');
            finna.layout.initTruncate(description);
            if (!$('.hide-details-button').hasClass('hidden')) {
              $('.record-information .description').addClass('too-long');
              $('.record-information .description .more-link.wide').click();
            }
          } else {
            description.hide();
          }
        })
        .fail(function onGetDescriptionFail() {
          description.hide();
        });
    }
  }

  function initHideDetails() {
    $('.show-details-button').click(function onClickShowDetailsButton() {
      $('.record-information .record-details-more').removeClass('hidden');
      $(this).addClass('hidden');
      $('.hide-details-button').removeClass('hidden');
      $('.record .description .more-link.wide').click();
      sessionStorage.setItem('finna_record_details', '1');
    });
    $('.hide-details-button').click (function onClickHideDetailsButton() {
      $('.record-information .record-details-more').addClass('hidden');
      $(this).addClass('hidden');
      $('.show-details-button').removeClass('hidden');
      $('.record .description .less-link.wide').click();
      sessionStorage.removeItem('finna_record_details');
    });
    if ($('.record-information').height() > 350 && $('.show-details-button')[0]) {
      $('.record-information .description').addClass('too-long');
      if (sessionStorage.getItem('finna_record_details')) {
        $('.show-details-button').click();
      } else {
        $('.hide-details-button').click();
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
    var recordId = elements[0].href.match(/\/Record\/([^/]+)\//)[1];

    var vars = [];
    $.each(elements, function handleElement(idx, element) {
      vars.push(getRequestLinkData(element, recordId));
    });


    var url = VuFind.path + '/AJAX/JSON?method=checkRequestsAreValid';
    $.ajax({
      dataType: 'json',
      data: {id: recordId, requestType: requestType, data: vars},
      method: 'POST',
      cache: false,
      url: url
    })
      .done(function onCheckRequestDone(responses) {
        $.each(responses.data, function handleResponse(idx, response) {
          var element = elements[idx];
          if (response.status) {
            $(element).removeClass('disabled')
              .html(response.msg);
          } else {
            $(element).remove();
          }
        });
      });
  }

  function setUpCheckRequest() {
    checkRequestsAreValid($('.expandedCheckRequest').removeClass('expandedCheckRequest'), 'Hold');
    checkRequestsAreValid($('.expandedCheckStorageRetrievalRequest').removeClass('expandedCheckStorageRetrievalRequest'), 'StorageRetrievalRequest');
    checkRequestsAreValid($('.expandedCheckILLRequest').removeClass('expandedCheckILLRequest'), 'ILLRequest');
  }

  function initHoldingsControls() {
    $('.holdings-container-heading').click(function onClickHeading(e) {
      if ($(e.target).hasClass('location-service') || $(e.target).parents().hasClass('location-service')) {
        return;
      }
      $(this).nextUntil('.holdings-container-heading').toggleClass('collapsed');
      if ($('.location .fa', this).hasClass('fa-arrow-down')) {
        $('.location .fa', this).removeClass('fa-arrow-down');
        $('.location .fa', this).addClass('fa-arrow-right');
      }
      else {
        $('.location .fa', this).removeClass('fa-arrow-right');
        $('.location .fa', this).addClass('fa-arrow-down');
        var rows = $(this).nextUntil('.holdings-container-heading');
        checkRequestsAreValid(rows.find('.collapsedCheckRequest').removeClass('collapsedCheckRequest'), 'Hold', 'holdBlocked');
        checkRequestsAreValid(rows.find('.collapsedCheckStorageRetrievalRequest').removeClass('collapsedCheckStorageRetrievalRequest'), 'StorageRetrievalRequest', 'StorageRetrievalRequestBlocked');
        checkRequestsAreValid(rows.find('.collapsedCheckILLRequest').removeClass('collapsedCheckILLRequest'), 'ILLRequest', 'ILLRequestBlocked');
      }
    });
  }

  function setupHoldingsTab() {
    initHoldingsControls();
    setUpCheckRequest();
    finna.layout.initLocationService();
    finna.layout.initJumpMenus($('.holdings-tab'));
    VuFind.lightbox.bind($('.holdings-tab'));
  }

  function initRecordNaviHashUpdate() {
    $(window).on('hashchange', function onHashChange() {
      $('.record-view-header .pager a').each(function updateHash(i, a) {
        a.hash = window.location.hash;
      });
    });
    $(window).trigger('hashchange');
  }

  /*function applyRecordTabHash() {
      var activeTab = $('.record-tabs .accordion a').attr('class');
      var $initiallyActiveTab = $('.record-tabs .accordion.initiallyActive a');
      var newTab = typeof window.location.hash !== 'undefined'
          ? window.location.hash.toLowerCase() : '';

      // Open tab in url hash
      if (newTab.length <= 1 || newTab === '#tabnav') {
          $initiallyActiveTab.click();
      } else if (newTab.length > 1 && '#' + activeTab !== newTab) {
          $('.' + newTab.substr(1)).click();
      }
  }

  $(window).on('hashchange', applyRecordTabHash);

  function removeHashFromLocation() {
    if (window.history.replaceState) {
      var href = window.location.href.split('#');
      window.history.replaceState({}, document.title, href[0]);
    } else {
      window.location.hash = '#';
    }
  }

  function ajaxLoadTab($newTab, tabid, setHash) {
    // Request the tab via AJAX:
    $.ajax({
        url: VuFind.path + getUrlRoot(document.URL) + '/AjaxTab',
        type: 'POST',
        data: {tab: tabid}
    })
      .always(function ajaxLoadTabDone(data) {
        if (typeof data === 'object') {
            $newTab.html(data.responseText ? data.responseText : VuFind.translate('error_occurred'));
        } else {
            $newTab.html(data);
        }
        registerTabEvents();
        if (typeof syn_get_widget === "function") {
            syn_get_widget();
        }
        if (typeof setHash == 'undefined' || setHash) {
            window.location.hash = tabid;
        } else {
            removeHashFromLocation();
        }
        setupJumpMenus($newTab);
      });
    return false;
  }

  function recordDocReady() {
      $('.record-tabs .accordion a').click(function recordTabsClick() {
          var $accordion = $(this).parent().parent();
          // If it's an active tab, click again to follow to a shareable link.
          if ($accordion.hasClass('active')) {
              return true;
          }
          var tabid = this.className;
          var $top = $(this).closest('.record-tabs');
          // if we're flagged to skip AJAX for this tab, we need special behavior:
          if ($accordion.hasClass('noajax')) {
              // if this was the initially active tab, we have moved away from it and
              // now need to return -- just switch it back on.
              if ($accordion.hasClass('initiallyActive')) {
                  $(this).tab('show');
                  $top.find('.tab-pane.active').removeClass('active');
                  $top.find('.' + tabid + '-tab').addClass('active');
                  window.location.hash = 'tabnav';
                  return false;
              }
              // otherwise, we need to let the browser follow the link:
              return true;
          }
          $top.find('.tab-pane.active').removeClass('active');
          $(this).tab('show');
          if ($top.find('.' + tabid + '-tab').length > 0) {
              $top.find('.' + tabid + '-tab').addClass('active');
              if ($(this).parent().hasClass('initiallyActive')) {
                  removeHashFromLocation();
              } else {
                  window.location.hash = tabid;
              }
              return false;
          } else {
              var newTab = getNewRecordTab(tabid).addClass('active');
              $accordion.find('.accordion-content').append(newTab);
              $accordion.find('.accordion-content')
              return ajaxLoadTab(newTab, tabid, !$(this).parent().hasClass('initiallyActive'));
          }
      });

      $('[data-background]').each(function setupBackgroundTabs(index, el) {
          backgroundLoadTab(el.className);
      });

      registerTabEvents();
      applyRecordTabHash();
  }*/

  function initRecordTabs() {
    $('.record-tabs .accordion-toggle').click(function(e){
      var accordion = $(e.target).closest('.accordion');
      e.preventDefault();
      if (accordion.hasClass('active')){
        return true;
      }
      if(accordion.hasClass('active')){
        $('.record-tabs').find('.accordion.active').removeClass('active');
      } else {
        $('.record-tabs').find('.accordion.active').removeClass('active');
        accordion.addClass('active');

        var tabid = accordion.find('.accordion-toggle a').attr('class');
        console.log(tabid);
        var newTab = getNewRecordTab(tabid).addClass('active');
        console.log($('.accordion-content').find('.tab-pane'));
        if(accordion.find('.accordion-content .tab-pane').length == 0) {
          accordion.find('.accordion-content').append(newTab);
          accordion.find('.accordion-content');
          console.log(accordion);
          return ajaxLoadTab(newTab, tabid, !$(this).parent().hasClass('initiallyActive'));
        }
      }
    });
  }

  var init = function init() {
    initHideDetails();
    initDescription();
    initRecordNaviHashUpdate();
    //recordDocReady();
    initRecordTabs();
  };

  var my = {
    checkRequestsAreValid: checkRequestsAreValid,
    init: init,
    setupHoldingsTab: setupHoldingsTab
  };

  return my;
})();
