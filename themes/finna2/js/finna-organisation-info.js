/*global VuFind, finna, Donut */
finna.organisationInfo = (function finnaOrganisationInfo() {
  let params = null;
  let container = null;
  let detailsEl = null;
  let mapContainer = null;
  let map = null;

  let mapTileUrl = 'https://map-api.finna.fi/v1/rendered/{z}/{x}/{y}.png';

  /**
   * Reset search field
   */
  function resetSearch() {
    $(container).find('.js-location-select').val('').trigger('change');
  }

  /**
   * Update URL hash ensuring that the change event is triggered
   *
   * @param {String} hash
   */
  function updateURLHash(hash) {
    if (('#' + hash) === window.location.hash) {
      // Set hash first to empty value, so that the change event is triggered when
      // the same menu item is re-selected.
      window.location.hash = '';
    }
    window.location.hash = '#' + hash;
  }

  /**
   * Get location from URL hash
   *
   * @returns string
   */
  function getLocationFromURLHash() {
    if (window.location.hash !== '') {
      return window.location.hash.replace('#', '');
    }
    return '';
  }

  // Forward declaration
  let showLocationDetails = function () {};

  /**
   * Update selected location
   *
   * @param {String|null} locationId
   * @param {boolean} clearSearch
   */
  function updateSelectedLocation(locationId, clearSearch) {
    showLocationDetails(locationId);
    if (clearSearch) {
      resetSearch();
    }
  }

  /**
   * Get location from URL hash and display it
   */
  function updateLocationFromURLHash() {
    let location = getLocationFromURLHash();
    if (location) {
      updateSelectedLocation(location, false);
    }
  }

  /**
   * Initialize map
   *
   * @param {Object} data Organisation info response for info-location-selection request
   */
  function initMap(data) {
    if (Object.keys(data.mapData).length === 0) {
      return;
    }

    mapContainer = container.querySelector('.js-location-info-map');
    if (!mapContainer) {
      return;
    }
    let mapWidget = mapContainer.querySelector('.js-map-widget');
    let mapTooltip = mapContainer.querySelector('.js-marker-tooltip');
    let mapAttributionTemplate = mapContainer.querySelector('.js-map-attribution');
    let mapBubbleTemplate = mapContainer.querySelector('.js-map-bubble-template');
    if (!mapWidget || !mapTooltip || !mapAttributionTemplate || !mapBubbleTemplate) {
      console.error('Map element(s) not found');
      return;
    }

    mapContainer.classList.remove('hidden');

    map = finna.organisationMap;
    map.init(mapWidget, mapTileUrl, mapAttributionTemplate.innerHTML);

    function hideMapMarker() {
      mapTooltip.classList.add('hidden');
    }

    $(map).on('marker-click', function onClickMarker(ev, id) {
      window.location.hash = id;
      hideMapMarker();
    });

    $(map).on('marker-mouseout', function onMouseOutMarker(/*ev*/) {
      hideMapMarker();
    });

    $(map).on('marker-mouseover', function onMouseOverMarker(ev, mapData) {
      if (mapData.id in data.mapData) {
        var name = data.mapData[mapData.id].name;
        mapTooltip.classList.remove('hidden');
        mapTooltip.textContent = name;
        mapTooltip.style.left = mapWidget.offsetLeft + mapData.x + 35 + 'px';
        mapTooltip.style.top = mapWidget.offsetTop + mapData.y + 'px';
      }
    });

    let showLocationEl = mapContainer.querySelector('.js-map-controls .js-show-location');
    if (showLocationEl) {
      showLocationEl.addEventListener('click', (ev) => {
        let id = getLocationFromURLHash();
        if (id && id in data.mapData) {
          map.reset();
          map.selectMarker(id);
        }
        ev.preventDefault();
      });
    }

    let showAllEl = mapContainer.querySelector('.js-map-controls .js-show-all');
    if (showAllEl) {
      showAllEl.removeAttribute('disabled');
      showAllEl.addEventListener('click', (ev) => {
        map.resize();
        map.reset();
        updateSelectedLocation(null, true);
        ev.preventDefault();
      });
    }

    for (const id in data.mapData) {
      if (!Object.hasOwn(data.mapData, id)) {
        continue;
      }
      // Map data (info bubble, icon)
      let locationData = data.mapData[id];
      let bubble = mapBubbleTemplate.cloneNode(true);
      let nameEl = bubble.content.querySelector('.js-name');
      if (nameEl) {
        nameEl.textContent = locationData.name;
      }
      let addressEl = bubble.content.querySelector('.js-address');
      if (addressEl) {
        addressEl.textContent = '';
        let streetEl = document.createElement('span');
        streetEl.textContent = locationData.address.street;
        addressEl.append(streetEl);
        let cityEl = document.createElement('span');
        cityEl.textContent = locationData.address.zipcode + ' ' + locationData.address.city;
        addressEl.append(document.createElement('br'));
        addressEl.append(cityEl);
      }

      data.mapData[id].map = {
        info: bubble.innerHTML
      };
    }

    map.draw(data.mapData);
  }

  /**
   * Initialize location selection
   *
   * @param {Object} data Organisation info response for info-location-selection request
   */
  function initLocationSelection(data) {
    const select = document.querySelector('.js-location-select');
    if (select) {
      const placeholder = select.querySelector('option').textContent;
      $(select).select2({
        placeholder: placeholder,
        width: '100%'
      }).on('select2:select', function updateHash(e) {
        updateURLHash(encodeURIComponent(e.params.data.id || 'undefined'));
      });
    }

    initMap(data);
  }

  /**
   * Initialize opening times week navigation
   *
   * @param {String} locationId
   */
  function initWeekNavi(locationId) {
    container.querySelectorAll('.js-week-navi-btn').forEach((btn) => {
      if (!btn.dataset.dir) {
        return;
      }
      btn.addEventListener('click', () => {
        let widgetContainer = btn.closest('.js-schedules');
        if (!widgetContainer) {
          console.error('Schedule widget not found');
          return;
        }
        let indicatorEl = widgetContainer.querySelector('.js-loader');
        let weekNaviEl = btn.closest('.js-week-navi');
        let timesEl = widgetContainer.querySelector('.js-opening-times-week');
        if (!indicatorEl || !weekNaviEl || !timesEl) {
          console.error('Week navi, times or loading indicator not found');
          return;
        }
        let weekTextEl = weekNaviEl.querySelector('.js-week-text');
        if (!weekTextEl) {
          console.error('Week text not found');
          return;
        }
        let weekNumEl = weekTextEl.querySelector('.js-num');
        if (!weekNumEl) {
          console.error('Week num not found');
          return;
        }
        let isoDate = weekNaviEl.dataset.date;
        if (!isoDate) {
          console.error('Current date not found');
          return;
        }
        let date = new Date(isoDate);
        let delta = parseInt(btn.dataset.dir) < 0 ? -7 : 7;
        date.setDate(date.getDate() + delta);
        let newIsoDate = date.toISOString().substring(0, 10);
        indicatorEl.classList.remove('hidden');
        fetch(VuFind.path + '/AJAX/JSON?' + new URLSearchParams({
          method: 'getOrganisationInfo',
          element: 'schedule',
          id: params.id,
          locationId: locationId,
          sectors: params.sectors || '',
          buildings: params.buildings || '',
          date: newIsoDate
        }))
          .then(response => {
            indicatorEl.classList.add('hidden');
            if (!response.ok) {
              timesEl.textContent = VuFind.translate('error_occurred');
            } else {
              response.json().then((result) => {
                weekNaviEl.dataset.date = newIsoDate;
                weekNumEl.textContent = result.data.weekNum;
                weekTextEl.setAttribute('aria-live', 'polite');
                let prevBtnEl = weekNaviEl.querySelector('.js-week-navi-btn.prev-week');
                if (prevBtnEl) {
                  prevBtnEl.disabled = result.data.currentWeek;
                }
                timesEl.outerHTML = result.data.widget;
              });
            }
          });
      });
    });
  }

  /**
   * Initialize coverage display
   */
  function initCoverageGauge() {
    let gaugeEl = container.querySelector('.js-finna-coverage-gauge');
    if (gaugeEl) {
      let opts = {
        lines: 0,
        angle: 0.1,
        lineWidth: 0.09,
        limitMax: 'true',
        colorStart: '#00A2B5',
        colorStop: '#00A2B5',
        strokeColor: '#e5e5e5',
        generateGradient: true
      };

      var gauge = new Donut(gaugeEl).setOptions(opts);
      gauge.maxValue = 100;
      gauge.animationSpeed = 20;
      gauge.set(gaugeEl.dataset.coverage);
    }
  }

  /**
   * Initialize location details container
   *
   * @param {String} locationId
   */
  function initLocationDetails(locationId) {
    detailsEl.querySelectorAll('[data-truncate]').forEach((elem) => {
      VuFind.truncate.initTruncate(elem);
    });
    finna.layout.initToolTips($(detailsEl));
    initWeekNavi(locationId);
    if (map) {
      map.selectMarker(locationId);
    }
  }

  /**
   * Show location details
   *
   * @param {String} locationId
   */
  showLocationDetails = function showLocationDetailsImpl(locationId) {
    const indicatorEl = container.querySelector('.js-location-loader');
    if (!indicatorEl) {
      console.error('Location load indicator element not found');
      return;
    }
    if (!indicatorEl.classList.contains('hidden')) {
      // Already loading
      return;
    }

    const notificationEl = container.querySelector('.js-location-unavailable');
    if (notificationEl) {
      notificationEl.classList.add('hidden');
    }
    detailsEl.innerHTML = '';
    const infoEl = container.querySelector('.js-location-quick-information');
    if (infoEl) {
      infoEl.innerHTML = '&nbsp;';
      infoEl.classList.toggle('hidden', null === locationId);
    }

    if (mapContainer) {
      let showLocationEl = mapContainer.querySelector('.js-map-controls .js-show-location');
      if (showLocationEl) {
        showLocationEl.toggleAttribute('disabled', null === locationId);
      }
    }
    if (map) {
      map.resize();
    }
    if (null === locationId) {
      return;
    }

    indicatorEl.classList.remove('hidden');
    fetch(VuFind.path + '/AJAX/JSON?' + new URLSearchParams({
      method: 'getOrganisationInfo',
      element: 'location-details',
      id: params.id,
      locationId: locationId,
      sectors: params.sectors || '',
      buildings: params.buildings || ''
    }))
      .then(response => {
        indicatorEl.classList.add('hidden');
        if (!response.ok) {
          detailsEl.textContent = VuFind.translate('error_occurred');
        } else {
          response.json().then((result) => {
            if (!result.data.found) {
              if (notificationEl) {
                notificationEl.classList.remove('hidden');
              }
              resetSearch();
              if (map) {
                map.reset();
              }
              return;
            }
            detailsEl.innerHTML = result.data.details;
            if (infoEl) {
              infoEl.innerHTML = result.data.info;
            }
            initLocationDetails(locationId);
          });
        }
      });
  };

  /**
   * Initialize organisation info page
   *
   * @param {Object} _params Organisation info page params
   */
  function init(_params) {
    params = _params;

    container = document.querySelector('.js-organisation-info-container');
    if (!container) {
      console.error('Organisation info container element not found');
      return;
    }
    detailsEl = container.querySelector('.js-location-details-container');
    if (!detailsEl) {
      console.error('Location details element not found');
      return;
    }
    const infoEl = container.querySelector('.js-consortium-info-container');
    if (!infoEl) {
      console.error('Consortium info element not found');
      return;
    }
    const selectionEl = container.querySelector('.js-location-selection-container');
    if (!selectionEl) {
      console.error('Location selection element not found');
      return;
    }
    const loadIndicatorEl = container.querySelector('.js-location-selection-loader');
    if (!loadIndicatorEl) {
      console.error('Location load indicator element not found');
      return;
    }

    addEventListener('hashchange', () => { updateLocationFromURLHash(); });

    loadIndicatorEl.classList.remove('hidden');
    fetch(VuFind.path + '/AJAX/JSON?' + new URLSearchParams({
      method: 'getOrganisationInfo',
      element: 'info-location-selection',
      id: params.id,
      locationId: getLocationFromURLHash(),
      sectors: params.sectors || '',
      buildings: params.buildings || '',
      consortiumInfo: params.consortiumInfo
    }))
      .then((response) => {
        loadIndicatorEl.classList.add('hidden');
        if (!response.ok) {
          selectionEl.textContent = VuFind.translate('error_occurred');
        } else {
          response.json().then((result) => {
            infoEl.innerHTML = result.data.consortiumInfo;
            selectionEl.innerHTML = result.data.locationSelection;
            initLocationSelection(result.data);
            initCoverageGauge();
            if (result.data.defaultLocationId) {
              updateURLHash(result.data.defaultLocationId);
            }
          });
        }
      });
  }

  /**
   * Load location into the widget
   *
   * @param {String} locationId
   */
  function loadWidgetLocation(locationId) {
    let openStatusEl = container.querySelector('.js-open-status');
    let scheduleEl = container.querySelector('.js-opening-times');
    let selectedLocationEl = container.querySelector('.js-location-dropdown .js-selected');
    if (!openStatusEl || !scheduleEl || !selectedLocationEl) {
      console.error('Organisation info widget open status, schedule or selected location element not found');
      return;
    }
    const loadIndicatorEl = container.querySelector('.js-loader');
    if (!loadIndicatorEl) {
      console.error('Organisation info widget load indicator element not found');
      return;
    }

    loadIndicatorEl.classList.remove('hidden');
    fetch(VuFind.path + '/AJAX/JSON?' + new URLSearchParams({
      method: 'getOrganisationInfo',
      element: 'widget-location',
      id: params.id,
      locationId: locationId,
      buildings: params.buildings || '',
      details: params.details || '1'
    }))
      .then((response) => {
        loadIndicatorEl.classList.add('hidden');
        if (!response.ok) {
          scheduleEl.textContent = VuFind.translate('error_occurred');
        } else {
          response.json().then((result) => {
            selectedLocationEl.textContent = result.data.locationName;
            let ariaEl = container.querySelector('.js-location-dropdown .js-aria');
            if (ariaEl) {
              ariaEl.setAttribute('aria-live', 'polite');
            }
            openStatusEl.innerHTML = result.data.openStatus;
            scheduleEl.innerHTML = result.data.schedule;
            if (result.data.details) {
              if (detailsEl) {
                detailsEl.innerHTML = result.data.details;
              }
            }
            initWeekNavi(result.data.locationId);
            finna.layout.initToolTips($(detailsEl));
          });
        }
      });
  }

  /**
   * Initialize organisation info widget
   *
   * @param {Object} _params Widget parameters
   */
  function initWidget(_params) {
    params = _params;
    container = document.querySelector('.js-organisation-info-widget');
    if (!container) {
      console.error('Organisation info widget element not found');
      return;
    }

    let contentEl = container.querySelector('.js-content');
    if (!contentEl) {
      console.error('Organisation info widget content element not found');
      return;
    }
    const loadIndicatorEl = container.querySelector('.js-loader');
    if (!loadIndicatorEl) {
      console.error('Organisation info widget load indicator element not found');
      return;
    }

    loadIndicatorEl.classList.remove('hidden');
    fetch(VuFind.path + '/AJAX/JSON?' + new URLSearchParams({
      method: 'getOrganisationInfo',
      element: 'widget',
      id: params.id,
      buildings: params.buildings || '',
      details: params.details || '1'
    }))
      .then((response) => {
        loadIndicatorEl.classList.add('hidden');
        if (!response.ok) {
          contentEl.textContent = VuFind.translate('error_occurred');
        } else {
          response.json().then((result) => {
            contentEl.innerHTML = result.data.widget;
            detailsEl = container.querySelector('.js-details');
            initWeekNavi(result.data.locationId);
            finna.layout.initToolTips($(contentEl));
            contentEl.querySelectorAll('.js-location-dropdown ul.dropdown-menu li').forEach((el) => {
              el.addEventListener('click', () => {
                loadWidgetLocation(el.dataset.id);
              });
            });
            contentEl.querySelectorAll('.js-location-dropdown li').forEach((el) => {
              el.addEventListener('keydown', (ev) => {
                if (ev.key === 'Enter') {
                  el.click();
                }
              });
            });
          });
        }
      });
  }

  return {
    init: init,
    initWidget: initWidget
  };
})();
