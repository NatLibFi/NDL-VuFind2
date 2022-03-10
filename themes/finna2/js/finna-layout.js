/*global VuFind, videojs, checkSaveStatuses, finna, initFacetTree, priorityNav */
finna.layout = (function finnaLayout() {
  var currentOpenTooltips = [];

  function initResizeListener() {
    var intervalId = false;
    $(window).on('resize', function onResizeWindow(/*e*/) {
      clearTimeout(intervalId);
      intervalId = setTimeout(function onTimeout() {
        var data = {
          w: $(window).width(),
          h: $(window).height()
        };
        $(window).trigger('throttled-resize.finna', [data]);
      }, 100);
    });
  }

  function isTouchDevice() {
    return (('ontouchstart' in window)
      || (navigator.maxTouchPoints > 0)
      || (navigator.msMaxTouchPoints > 0)); // IE10, IE11, Edge
  }

  // Append current anchor (location.hash) to selected links
  // in order to preserve the anchor when the link is clicked.
  // This is used in top header language links.
  function initAnchorNavigationLinks() {
    $('a.preserve-anchor').each(function addAnchors() {
      var hash = location.hash;
      if (hash.length === 0) {
        return;
      }
      $(this).attr('href', $(this).attr('href') + hash);
    });
  }

  function initLocationService(_holder) {
    var holder = typeof _holder === 'undefined' ? $(document) : _holder;

    holder.find('a.location-service.location-service-modal').on('click', function onClickModalLink(/*e*/) {
      var modal = $('#modal');
      var dialog = modal.find('.modal-dialog');
      modal.addClass('location-service');
      dialog.addClass('modal-lg');

      modal.one('hidden.bs.modal', function onHiddenModal() {
        modal.removeClass('location-service location-service-qrcode');
        dialog.removeClass('modal-lg');
      });
      VuFind.loadHtml(modal.find('.modal-body'), $(this).data('lightbox-href') + '&layout=lightbox');
      modal.modal();
      return false;
    });
  }

  function initTruncate(_holder) {
    var holder = typeof _holder === 'undefined' ? $(document) : _holder;

    var truncation = [];
    var rowHeight = [];
    holder.find('.truncate-field').not('.truncate-done').each(function handleTruncate(index) {
      var self = $(this);
      self.addClass('truncate-done');

      if (typeof(self.data('row-height')) !== 'undefined') {
        rowHeight[index] = self.data('row-height');
      } else if (self.children().length > 0) {
        // use first child as the height element if available
        var heightElem = self.children().first();
        var outer = self.hasClass('outer');
        if (heightElem.is('div') || outer) {
          rowHeight[index] = parseFloat(outer ? heightElem.outerHeight(true) : heightElem.height());
        } else {
          rowHeight[index] = parseFloat(heightElem.css('line-height').replace('px', ''));
        }
      } else {
        rowHeight[index] = parseFloat(self.css('line-height').replace('px', ''));
      }

      var rowCount = self.data('rows') || 3;
      // get the line-height of first element to determine each text line height
      truncation[index] = rowHeight[index] * rowCount;
      // truncate only if there's more than one line to hide
      if (self.height() > (truncation[index] + rowHeight[index] + 1)) {
        var topLink = self.height() > (rowHeight[index] * 30);
        self.css('height', truncation[index] - 1 + 'px');
        var moreLabel = self.data('label') || VuFind.translate('show_more');
        var lessLabel = self.data('label') || VuFind.translate('show_less');

        var moreLink = $('<button type="button" class="more-link">' + moreLabel + ' <i class="fa fa-arrow-down" aria-hidden="true"></i></button>');
        var lessLink = $('<button type="button" class="less-link">' + lessLabel + ' <i class="fa fa-arrow-up" aria-hidden="true"></i></button>');

        var linkClass = self.data('button-class') || '';
        if (linkClass) {
          moreLink.addClass(linkClass);
          lessLink.addClass(linkClass);
        }
        lessLink.on('click', function showLess() {
          self.siblings('.less-link').hide();
          self.siblings('.more-link').show();
          self.css('height', truncation[index] - 1 + 'px');
        });
        moreLink.on('click', function showMore() {
          self.siblings('.more-link').hide();
          self.siblings('.less-link').show();
          self.css('height', 'auto');
        });
        lessLink.hide();

        if (self.data('button-placement') === 'top') {
          self.before([moreLink, lessLink]);
        } else {
          if (topLink) {
            self.before(lessLink.clone(true));
          }
          self.after([moreLink, lessLink]);
        }
        self.addClass('truncated');
      }
    });
  }

  function initContentNavigation() {
    if ($('.content-navigation-menu')[0]) {
      $('.content-section').each(function initContentSection(index) {
        var link = '#' + $(this).attr('id');
        var $p = $('<p>')
          .addClass('nav-' + index)
          .appendTo($('.content-navigation-menu'));
        $('<a>')
          .attr('href', link)
          .text($('h2', this).first().text())
          .appendTo($p);
      });
    }
  }

  function initMobileNarrowSearch() {
    $('.mobile-navigation .sidebar-navigation, .sidebar h1').off('click').on('click', function onClickMobileNav(e) {
      if ($(e.target).attr('class') !== 'fa fa-info-big') {
        $('.sidebar').toggleClass('open');
      }
      $('.mobile-navigation .sidebar-navigation i').toggleClass('fa-arrow-down');
      $('body').toggleClass('prevent-scroll');
    });
    $('.mobile-navigation .sidebar-navigation .active-filters').off('click').on('click', function onClickMobileActiveFilters() {
      $('.sidebar').scrollTop(0);
    });
  }

  function initMobileCartIndicator() {
    $('.btn-bookbag-toggle a').on('click', function onClickMobileCart() {
      if ($(this).hasClass('cart-add')){
        $('.navbar-toggle').removeClass('activated');
        setTimeout(function triggerAnimation() {
          $('.navbar-toggle').addClass('activated');
        }, 100);
      }
    });
  }

  function initCheckboxClicks() {
    $('.template-name-mylist input.checkbox-select-item').on('click', function onClickCheckbox() {
      var actions = $('.mylist-functions button, .mylist-functions select');
      var noneChecked = $('.template-name-mylist input.checkbox-select-item:checked').length === 0;
      if (noneChecked) {
        actions.attr('disabled', true);
      } else {
        actions.removeAttr('disabled');
      }
    });
  }

  function initScrollLinks() {
    $('.library-link').on('click', function onClickLibraryLink() {
      $('html, body').animate({
        scrollTop: $('.recordProvidedBy').offset().top
      }, 500);
    });
    var feedbackBtn = $('.floating-feedback-btn');
    if (feedbackBtn.length) {
      var feedbackBtnOffset = feedbackBtn.offset().top;
      $(window).scroll(function onScrollWindow(/*event*/) {
        feedbackBtn.toggleClass('fixed', $(window).scrollTop() > feedbackBtnOffset);
      });
    }
    var backUp = $('.template-dir-record .back-to-up');
    if (backUp.length) {
      $(window).on('scroll', function onScrollWindow(/*event*/) {
        backUp.toggleClass('hidden', $(window).scrollTop() <= 2000);
      });
    }
  }

  function initSearchboxFunctions() {
    var searchForm = document.querySelector('.searchForm.navbar-form');
    if (searchForm) {
      var submitButton = searchForm.querySelector('button[type="submit"]');
      if (submitButton) {
        var mouseUp = function onMouseUp(ev) {
          if (1 === ev.button && ev.target === submitButton || submitButton.contains(ev.target)) {
            searchForm.setAttribute('target', '_blank');
            searchForm.submit();
            searchForm.removeAttribute('target');
          }
          document.removeEventListener('mouseup', mouseUp);
        };
        submitButton.addEventListener('mousedown', function listenToMiddleClick(e) {
          if (1 === e.button) {
            document.removeEventListener('mouseup', mouseUp);
            document.addEventListener('mouseup', mouseUp);
          }
        });
      }
    }

    if ($('.navbar-form .checkbox')[0]) {
      $('.autocomplete-results').addClass('checkbox-active');
    }
    $('.searchForm_lookfor').on('input', function onInputLookfor() {
      var lfor = $(this);
      lfor.closest('.searchForm').find('.clear-button').toggleClass('hidden', lfor.val() === '');
    });

    $('.clear-button').on('click', function onClickClear() {
      var btn = $(this);
      btn.closest('.searchForm').find('.searchForm_lookfor').val('').focus();
      btn.addClass('hidden');
    });

    $('.searchForm_lookfor').bind('autocomplete:select', function onAutocompleteSelect() {
      $('.navbar-form').submit();
    });

    $('.select-type').on('click', function onClickSelectType(event) {
      event.preventDefault();
      var dropdownToggle = $('.type-dropdown .dropdown-toggle');

      $('input[name=type]:hidden').val($(this).siblings().val());
      dropdownToggle.find('span').text($(this).text());
      dropdownToggle.attr('aria-label', ($(this).text()));
      dropdownToggle.dropdown('toggle');
      dropdownToggle.focus();
    });

    if (sessionStorage.getItem('vufind_retain_filters')) {
      $('.searchFormKeepFilters').closest('.checkbox').toggleClass('checked', sessionStorage.getItem('vufind_retain_filters') === 'true');
    }
  }

  function initToolTips(_holder) {
    var holder = typeof _holder === 'undefined' ? $(document) : _holder;
    // other tooltips
    holder.find('[data-toggle="tooltip"]')
      .on('show.bs.tooltip', function onShowTooltip() {
        var self = $(this);
        $(currentOpenTooltips).each(function hideOtherTooltips() {
          if ($(this)[0] !== self[0]) {
            $(this).tooltip('hide');
          }
        });
        currentOpenTooltips = [self];
      })
      .on('hidden.bs.tooltip', function onHideTooltip(e) {
        $(e.target).data('bs.tooltip').inState.click = false;
      })
      .tooltip({trigger: 'click', viewport: '.container'});
    // prevent link opening if tooltip is placed inside link element
    holder.find('[data-toggle="tooltip"] > i').on('click', function onClickTooltip(event) {
      event.preventDefault();
    });
    // close tooltip if user clicks anything else than tooltip button
    $('html').on('click', function onClickHtml(e) {
      if (typeof $(e.target).parent().data('original-title') == 'undefined' && typeof $(e.target).data('original-title') == 'undefined') {
        $('[data-toggle="tooltip"]').tooltip('hide');
        currentOpenTooltips = [];
      }
    });
  }

  function initModalToolTips() {
    $('#modal').on('show.bs.modal', function onShowModal() {
      initToolTips($(this));
    });
  }

  function initCondensedList(_holder) {
    var holder = typeof _holder === 'undefined' ? $(document) : _holder;

    holder.find('.condensed-collapse-toggle').off('click').on('click', function onClickCollapseToggle(event) {
      if ((event.target.nodeName) !== 'A' && (event.target.nodeName) !== 'MARK') {
        holder = $(this).parent().parent();
        holder.toggleClass('open');

        var onSlideComplete = null;
        if (holder.hasClass('open') && !holder.hasClass('opened')) {
          holder.addClass('opened');
          VuFind.itemStatuses.check(holder);
          finna.itemStatus.initDedupRecordSelection(holder);
        }

        $(this).nextAll('.condensed-collapse-data').first().slideToggle(120, 'linear', onSlideComplete);

        var icon = $(this).find('.condensed-body > i');
        if (icon.length === 0) {
          icon = $(this).find('.condensed-col-title > i');
        }
        icon.toggleClass('fa-arrow-right').toggleClass('fa-arrow-down');
      }
    });
  }

  function initTouchDeviceGallery() {
    if ($('.result-view-grid')[0] != null && isTouchDevice()) {
      $('.result-view-grid').addClass('touch-device');
    }
  }

  function initBuildingFilter() {
    $('#building_filter').on('keyup', function onKeyUpFilter() {
      var valThis = this.value.toLowerCase();
      $('#facet_building>ul>li>a .text').each(function doBuildingSearch() {
        var text = $(this).text().toLowerCase();
        if (text.indexOf(valThis) !== -1) {
          $(this).closest('li').show();
        } else {
          $(this).closest('li').hide();
        }
      });
    });
  }

  function renderFacetSRLabel(tree) {
    // Add count descriptor to every facet value node for accessibility
    tree.find('.facet').each(function appendDescriptors() {
      var badge = $(this).find('.badge');
      if (badge.length === 0) {
        return;
      }
      badge.attr('aria-hidden', 'true');
      if ($(this).find('.facet-value .sr-only').length > 0) {
        return;
      }
      $(this).find('.facet-value').append('<span class="sr-only">, ' + VuFind.translate('result_count', {'%%count%%': badge.text()}) + '</span>');
    });
  }

  function addJSTreeListeners(treeNode) {
    treeNode.on('ready.jstree', function onReadyJstree() {
      var tree = $(this);
      // if hierarchical facet contains 2 or less top level items, it is opened by default
      if (tree.find('ul > li').length <= 2) {
        tree.find('ul > li.jstree-node.jstree-closed > i.jstree-ocl').each(function openNode() {
          tree.jstree('open_node', this, null, false);
        });
      }
      // show filter if 15+ organisations
      if (tree.parent().parent().attr('id') === 'side-panel-building' && tree.find('ul.jstree-container-ul > li').length > 15) {
        $(this).prepend('<div class="building-filter"><label for="building_filter" class="sr-only">' + VuFind.translate('Organisation') + '</label><input class="form-control" id="building_filter" placeholder="' + VuFind.translate('Organisation') + '..."></input></div>');
        initBuildingFilter();
      }

      renderFacetSRLabel(tree);

      // open facet if it has children and it is selected
      tree.find('.jstree-node.active.jstree-closed').each(function openNode() {
        tree.jstree('open_node', this, null, false);
      });
    });

    // Update screen reader labels when opening nodes
    treeNode.on('after_open.jstree', function afterOpenJstree() {
      renderFacetSRLabel($(this));
    });
  }

  function initHierarchicalFacet(treeNode, inSidebar) {
    addJSTreeListeners(treeNode);
    initFacetTree(treeNode, inSidebar);
  }

  function initJumpMenus(_holder) {
    var holder = typeof _holder === 'undefined' ? $('body') : _holder;
    holder.find('select.jumpMenu').off('change').on('change', function onChangeJumpMenu() { $(this).closest('form').submit(); });
    holder.find('select.jumpMenuUrl').off('change').on('change', function onChangeJumpMenuUrl(e) { window.location.href = $(e.target).val(); });
  }

  function initSecondaryLoginField() {
    // This function exists for back-compatibility only
  }

  function initILSPasswordRecoveryLink(links, idPrefix) {
    var searchPrefix = idPrefix ? '#' + idPrefix : '#';
    $(searchPrefix + 'target').on('change', function onChangeLoginTargetLink() {
      var target = $(searchPrefix + 'target').val();
      if (links[target]) {
        $('#login_library_card_recovery').attr('href', links[target]).show();
      } else {
        $('#login_library_card_recovery').hide();
      }
    }).change();
  }

  function initILSSelfRegistrationLink(links, idPrefix) {
    var searchPrefix = idPrefix ? '#' + idPrefix : '#';
    $(searchPrefix + 'target').on('change', function onChangeLoginTargetLink() {
      var target = $(searchPrefix + 'target').val();
      if (links[target]) {
        $('#login_library_card_register').attr('href', links[target]).show();
      } else {
        $('#login_library_card_register').hide();
      }
    }).change();
  }

  function initSideFacets() {
    if (!document.addEventListener) {
      return;
    }
    document.addEventListener('VuFind.sidefacets.loaded', function onSideFacetsLoaded() {
      finna.dateRangeVis.init();
      initToolTips($('.sidebar'));
      initMobileNarrowSearch();
      VuFind.lightbox.bind($('.sidebar'));
    });
    document.addEventListener('VuFind.sidefacets.treenodeloaded', function onTreeNodeLoaded(e) {
      addJSTreeListeners(e.detail.node);
    });
  }

  function initPiwikPopularSearches() {
    var $container = $('.piwik-popular-searches');
    if ($container.length === 0) {
      return;
    }
    $container.find('.load-indicator').removeClass('hidden');
    $.getJSON(VuFind.path + '/AJAX/JSON?method=getPiwikPopularSearches')
      .done(function onGetPiwikSearchesDone(response) {
        $container.html(VuFind.updateCspNonce(response.data.html));
      })
      .fail(function onGetPiwikSearchesFail() {
        $container.find('.load-indicator').addClass('hidden');
        $container.find('.load-failed').removeClass('hidden');
      });
  }

  function initAutoScrollTouch() {
    if (!navigator.userAgent.match(/iemobile/i) && isTouchDevice() && $(window).width() < 1025) {
      $('.search-query').on('click', function onClickSearchQuery() {
        $('html, body').animate({
          scrollTop: $(this).offset().top - 75
        }, 200);
      });
    }
  }

  function initIpadCheck() {
    if (navigator.userAgent.match(/iPad/i)) {
      if (navigator.userAgent.match(/OS 6_\d(_\d) like Mac OS X/i)) {
        $('body').addClass('ipad-six');
      }
    }
  }

  function initScrollRecord() {
    if (!$('section.main').is('.template-name-search, .template-name-results')) {
      return;
    }

    var target = null;
    var identifier = decodeURIComponent(window.location.hash);
    if (identifier === '') {
      // Scroll to search box
      if ($(window).height() < 960 && $(window).scrollTop() === 0) {
        target = $('.search-form-container');
      }
    } else {
      // Scroll to record
      var result = $('.hiddenId[value="' + identifier.substr(1) + '"]');
      if (result.length) {
        target = result.closest('.result');
      }
    }
    if (target && target.length) {
      $('html').animate({scrollTop: target.offset().top}, 100);
    }
  }

  function initLightboxLogin() {
    if (!document.addEventListener) {
      return;
    }
    document.addEventListener('VuFind.lightbox.login', function onLightboxLogin(e) {
      if ($('body').hasClass('template-name-home') && !e.detail.formUrl.match(/catalogLogin/) && !e.detail.formUrl.match(/\Save/) && !e.detail.formUrl.match(/%2[fF]Save/)) {
        window.location.href = VuFind.path + '/MyResearch/Home';
        e.preventDefault();
      }
    });
    $('#modal').on('show.bs.modal', function onShowModal() {
      if ($('#modal').find('#authcontainer').length > 0) {
        $('#modal .modal-dialog').addClass('modal-lg modal-lg-dynamic');
      }
    });
    $('#modal').on('hidden.bs.modal', function onHiddenModal() {
      $('#modal .modal-dialog.modal-lg-dynamic').removeClass('modal-lg');
    });
  }

  function showPostLoginLightbox(url) {
    VuFind.lightbox.ajax({url: url});
  }

  function getOrganisationPageLink(organisation, organisationName, link, callback) {
    var params = {
      url: VuFind.path + '/AJAX/JSON?method=getOrganisationInfo',
      dataType: 'json',
      method: 'POST',
      data: {
        method: 'getOrganisationInfo',
        'params[action]': 'lookup',
        link: link ? '1' : '0',
        parent: organisation
      }
    };
    if (organisationName) {
      params.data.parentName = String(organisationName);
    }
    $.ajax(params)
      .done(function onGetOrganisationInfoDone(response) {
        callback(response.data);
      })
      .fail(function onGetOrganisationInfoFail() {
        callback(false);
      });
  }

  function initOrganisationPageLinks() {
    $('.organisation-page-link').not('.done').each(function setupOrganisationPageLinks() {
      $(this).one('inview', function onInViewLink() {
        var holder = $(this);
        var organisationId = $(this).data('organisation');
        var organisationName = $(this).data('organisationName');
        var organisationSector = $(this).data('organisationSector');
        var organisation = {'id': organisationId, 'sector': organisationSector};
        getOrganisationPageLink(organisation, organisationName, true, function organisationPageCallback(response) {
          holder.toggleClass('done', true);
          if (response) {
            $.each(response, function handleLinks(id, item) {
              holder.html(item).closest('li.record-organisation').toggleClass('organisation-page-link-visible', true);
            });
          }
        });
      });
    });
  }

  function initOrganisationInfoWidgets() {
    $('.organisation-info[data-init="1"]').each(function setupOrganisationInfo() {
      var widget = finna.organisationInfoWidget;
      widget.init($(this), finna.organisationInfo);
      widget.loadOrganisationList();
    });
  }

  function initAudioButtons() {
    $('.audio-accordion .audio-item-wrapper').each(function initAudioPlayer() {
      var self = $(this);
      var play = self.find('.play');
      var source = self.find('source');
      play.on('click', function onPlay() {
        self.find('.audio-player-wrapper').removeClass('hide');
        var audio = self.find('audio');
        audio.removeClass('hide').addClass('video-js');
        source.attr('src', source.data('src'));
        finna.layout.loadScripts(
          $(this).data('scripts'),
          function onVideoJsLoaded() {
            videojs(
              audio.attr('id'),
              { controlBar: { volumePanel: false, muteToggle: false } },
              function onVideoJsInited() {}
            );
          }
        );
        play.remove();
      });
    });
  }

  function initVideoButtons() {
    finna.videoPopup.initVideoPopup($('body'));
    finna.videoPopup.initIframeEmbed($('body'));
  }

  function loadScripts(scripts, callback) {
    var needed = {};
    // Check for required scripts that are not yet loaded
    if (scripts) {
      for (var item in scripts) {
        if (scripts.hasOwnProperty(item) && $('#' + item).length === 0) {
          needed[item] = scripts[item];
        }
      }
    }
    var loadCount = Object.keys(needed).length;
    if (loadCount) {
      // Load scripts and initialize player when all are loaded
      var scriptLoaded = function onScriptLoaded() {
        if (--loadCount === 0) {
          if (typeof callback === 'function') {
            callback();
          }
        }
      };
      for (var itemNeeded in needed) {
        if (needed.hasOwnProperty(itemNeeded)) {
          $(needed[itemNeeded])
            .on('load', scriptLoaded)
            .attr('async', 'true')
            .appendTo($('head'))
            .trigger('load');
        }
      }
    } else if (typeof callback === 'function') {
      callback();
    }
  }

  function initKeyboardNavigation() {
    $(window).keyup(function onKeyUp(e) {
      var $target = $(e.target);
      // jsTree link target navigation
      if ((e.which === 13 || e.which === 32)
          && $target.hasClass('jstree-anchor') && $target.find('.main').length > 0
      ) {
        $target.find('.main').click();
        e.preventDefault();
        return false;
      }
      return true;
    });
  }

  function initPriorityNav() {
    priorityNav.init({
      mainNavWrapper: ".nav-wrapper",
      mainNav: ".nav-ul",
      navDropdownLabel: VuFind.translate('other_records'),
      navDropdownClassName: "dropdown-menu",
      navDropdownBreakpointLabel: VuFind.translate('records'),
      navDropdownToggleClassName: "nav-dropdown-toggle",
      breakPoint: 400
    });
  }

  function initFiltersToggle () {
    var win = $(window);

    if (win.width() <= 991) {
      $('.finna-filters .filters').addClass('hidden');
      $('.finna-filters .filters-toggle .toggle-text').html(VuFind.translate('show_filters'));
    }

    win.on('throttled-resize.finna', function checkFiltersEnabled(e, data) {
      var filters = $('.finna-filters .filters');
      if (data.w > 991 && filters.hasClass('hidden')) {
        filters.removeClass('hidden');
      }

    });

    $('.filters-toggle').on('click', function filterToggleClicked() {
      var button = $(this);
      var filters = button.closest('.finna-filters').find('.filters');

      function setState(setHidden, arrowClass, text) {
        filters.toggleClass('hidden', setHidden);
        button.find('.fa').attr('class', arrowClass);
        button.find('.toggle-text').html(VuFind.translate(text));
      }

      if (filters.hasClass('hidden')) {
        setState(false, 'fa fa-arrow-up', 'hide_filters');
      } else {
        setState(true, 'fa fa-arrow-down', 'show_filters');
      }
    });
  }

  function initCookieConsent() {
    var state = finna.common.getCookie('cookieConsent');
    if ('undefined' === typeof state || !state) {
      $('.cookie-consent-dismiss').on('click', function dismiss() {
        finna.common.setCookie('cookieConsent', 1, { expires: 365 });
        $('.cookie-consent').addClass('hidden');
      });
      $('.cookie-consent').removeClass('hidden');
    }
  }

  // The accordion has a delicate relationship with the tabs. Handle with care!
  function _toggleLoginAccordion(tabId) {
    var $accordionHeading = $('.login-accordion .accordion-heading a[data-tab="' + tabId + '"]').closest('.accordion-heading');
    var $loginTabs = $('.login-tabs');
    var $tabContent = $loginTabs.find('.tab-content');
    if ($accordionHeading.hasClass('active')) {
      $accordionHeading.removeClass('active');
      // Hide tab from accordion
      $loginTabs.find('.tab-pane.active').removeClass('active');
      // Deactivate any tab since it can't follow the state of a collapsed accordion
      $loginTabs.find('.nav-tabs li.active').removeClass('active');
      // Move tab content out from accordions
      $tabContent.insertAfter($('.login-accordion .accordion-heading').last());
    } else {
      // Move tab content under the correct accordion toggle
      $tabContent.insertAfter($accordionHeading);
      $('.login-accordion').find('.accordion-heading.active').removeClass('active');
      $accordionHeading.addClass('active');
      $loginTabs.find('.tab-pane.active').removeClass('active');
      $loginTabs.find('.' + tabId + '-tab').addClass('active');
    }
  }

  function _activateLoginTab(tabId) {
    var $top = $('.login-tabs');
    $top.find('.tab-pane.active').removeClass('active');
    $top.find('li.' + tabId).tab('show');
    $top.find('.' + tabId + '-tab').addClass('active');
    _toggleLoginAccordion(tabId);
  }

  function initLoginTabs() {
    // Tabs
    $('.login-tabs .nav-tabs a').on('click', function recordTabsClick() {
      if (!$(this).closest('li').hasClass('active')) {
        _activateLoginTab(this.className);
      }
      return false;
    });

    // Accordion
    $('.login-accordion .accordion-toggle').on('click', function accordionClicked() {
      _activateLoginTab($(this).find('a').data('tab'));
    });
    // Call activation to position the initial content properly
    _activateLoginTab($('.login-tabs .accordion-heading.initiallyActive a').data('tab'));
  }

  function setImagePaginatorTranslations() {
    $.fn.setPaginatorTranslations({
      image: VuFind.translate('Image'),
      close: VuFind.translate('close'),
      next: VuFind.translate('Next Record'),
      previous: VuFind.translate('Previous Record'),
      no_cover: VuFind.translate('No Cover Image')
    });
  }

  function initImagePaginators() {
    $('.image-popup-trigger.init').each(function initImages() {
      $(this).finnaPaginator($(this).data('settings'), $(this).data('images'));
    });
  }

  // Used in custom themes
  function initHelpTabs() {
    if ($('.help-tabs')[0]) {
      $('.help-tab').each(function initHelpTab() {
        if ($(this).hasClass('active')) {
          $(this).focus();
        }
        var url = $(this).data('url');
        $(this).keydown(function onTabEnter(event) {
          if (event.which === 13) {
            window.location.href = url;
          }
        });
        $(this).click(function onTabClick() {
          window.location.href = url;
        });
      });
    }
  }

  function initPrintTriggers() {
    $('[data-trigger-print]').off('click').on(
      'click',
      function printWindow() {
        window.print();
        return false;
      }
    );
  }

  var my = {
    getOrganisationPageLink: getOrganisationPageLink,
    isTouchDevice: isTouchDevice,
    initCondensedList: initCondensedList,
    initTruncate: initTruncate,
    initLocationService: initLocationService,
    initHierarchicalFacet: initHierarchicalFacet,
    initJumpMenus: initJumpMenus,
    initMobileNarrowSearch: initMobileNarrowSearch,
    initOrganisationPageLinks: initOrganisationPageLinks,
    initSecondaryLoginField: initSecondaryLoginField,
    initILSPasswordRecoveryLink: initILSPasswordRecoveryLink,
    initILSSelfRegistrationLink: initILSSelfRegistrationLink,
    initLoginTabs: initLoginTabs,
    loadScripts: loadScripts,
    initToolTips: initToolTips,
    initImagePaginators: initImagePaginators,
    init: function init() {
      initResizeListener();
      initScrollRecord();
      initJumpMenus();
      initAnchorNavigationLinks();
      initTruncate();
      initContentNavigation();
      initMobileNarrowSearch();
      initMobileCartIndicator();
      initCheckboxClicks();
      initToolTips();
      initModalToolTips();
      initScrollLinks();
      initSearchboxFunctions();
      initCondensedList();
      if (typeof checkSaveStatuses !== 'undefined') { checkSaveStatuses(); }
      initTouchDeviceGallery();
      initSideFacets();
      initPiwikPopularSearches();
      initAutoScrollTouch();
      initIpadCheck();
      initLightboxLogin();
      initOrganisationInfoWidgets();
      initOrganisationPageLinks();
      initAudioButtons();
      initVideoButtons();
      initKeyboardNavigation();
      initPriorityNav();
      initFiltersToggle();
      initCookieConsent();
      setImagePaginatorTranslations();
      initImagePaginators();
      initHelpTabs();
      initPrintTriggers();
    },
    showPostLoginLightbox: showPostLoginLightbox
  };

  return my;
})();

