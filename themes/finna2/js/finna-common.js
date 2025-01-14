/*global VuFind, finna */
finna.common = (function finnaCommon() {
  /**
   * Decode an HTML string
   * @param {string} str String
   * @returns {string} Decoded string (text content)
   */
  function decodeHtml(str) {
    return $("<textarea/>").html(str).text();
  }

  /**
   * Get field from the object.
   * @param {object} obj   Object to search for the field
   * @param {string} field Field to look for
   * @returns {?*} The field found or null if undefined.
   */
  function getField(obj, field) {
    if (field in obj && typeof obj[field] != 'undefined') {
      return obj[field];
    }
    return null;
  }

  /**
   * Initialize Qr code link
   * @param {jQuery} [_holder] Container for the qr code link
   */
  function initQrCodeLink(_holder) {
    var holder = typeof _holder === 'undefined' ? $(document) : _holder;

    VuFind.setupQRCodeLinks(holder[0]);

    // Reposition the dropdown in location service to escape any truncated div:
    holder.find('.dropdown.location-service-qrcode').on('shown.bs.dropdown', function positionDropdown() {
      const button = $(this);
      var menu = button.find('.dropdown-menu');
      menu.css({
        top: button.offset().top - $(window).scrollTop() + button.height(),
        left: button.offset().left - $(window).scrollLeft() - menu.width(),
        position: 'fixed'
      });
    });
  }

  /**
   * Initialize result page scripts.
   * @param {string|jQuery} container     Container or selector holding results
   * @param {boolean}       includeVuFind Include VuFind initResultScripts
   */
  function initResultScripts(container, includeVuFind) {
    finna.layout.initCondensedList($(container));
    finna.layout.initTruncate();
    finna.layout.initImagePaginators();
    finna.itemStatus.initDedupRecordSelection(container);
    $.fn.finnaPopup.reIndex();
    if (typeof includeVuFind === 'undefined' || includeVuFind) {
      VuFind.initResultScripts(container);
    }
  }

  /**
   * Add event handlers for managing JS-loaded search results
   */
  function initResultsEventHandler() {
    VuFind.listen('results-load', () => {
      setTimeout(() => {
        var focusedEl = document.getElementById("results-heading");
        var storagedEl = window.sessionStorage.getItem('clickedMenu');
        if (storagedEl) {
          window.sessionStorage.removeItem('clickedMenu');
          focusedEl = document.querySelector(storagedEl);
        }
        if (focusedEl) {
          focusedEl.focus();
        }
      },
      200
      );
    });
    VuFind.listen('results-loaded', () => {
      initResultScripts(document.querySelector('.js-result-list'), false);
    });
    const dropdownMappings = {
      sort: {
        selector: '.search-controls form.search-sort select',
        label: 'Sort',
        sessionSelector: 'sort-dropdown a.dropdown-toggle',
      },
      limit: {
        selector: '.search-controls form.search-result-limit select',
        label: 'Results per page',
        sessionSelector: 'limit-dropdown a.dropdown-toggle',
      }
    };
    // Set up Finna's dropdown-based sort and limit controls:
    document.querySelectorAll('.search-controls .sort-option-container .dropdown, .search-controls .limit-option-container .dropdown').forEach(dropdown => {
      const dropdownMenu = dropdown.querySelector('.dropdown-menu');
      if (!dropdownMenu) {
        return;
      }
      const toggleDropdown = dropdown.querySelector('.dropdown-toggle');
      const toggleSpan = toggleDropdown ? toggleDropdown.querySelector(':scope > span') : undefined;
      const dropdownType = dropdown.closest('.sort-option-container') ? 'sort' : 'limit';
      const currentDropdownMapping = dropdownMappings[dropdownType];
      const ariaLabelTemplate = `${VuFind.translate(currentDropdownMapping.label)}: %%linkText%% ${VuFind.translate('selected')}`;
      const hiddenSelectElement = document.querySelector(currentDropdownMapping.selector);
      dropdownMenu.querySelectorAll('a').forEach((link) => {
        if (link.dataset.ajaxPagination) {
          return;
        }
        link.dataset.ajaxPagination = true;
        link.addEventListener('click', function handleClick(event) {
          event.preventDefault();
          window.sessionStorage.setItem('clickedMenu', currentDropdownMapping.sessionSelector);
          // Update button text:
          if (toggleDropdown) {
            toggleDropdown.ariaLabel = ariaLabelTemplate.slice(1).replace('%%linkText%%', VuFind.translate(link.innerText));
          }
          if (toggleSpan) {
            toggleSpan.innerText = link.innerText;
          }
          dropdownMenu.querySelectorAll('li > a').forEach(el => el.removeAttribute('aria-description'));
          link.setAttribute('aria-description', 'selected');
          // Get relevant data from the link and change the hidden field accordingly:
          const urlParts = link.getAttribute('href').split('?', 2);
          const query = new URLSearchParams(urlParts.length > 1 ? urlParts[1] : '');
          const newValue = query.get(dropdownType);
          if (hiddenSelectElement) {
            hiddenSelectElement.value = newValue;
            hiddenSelectElement.dispatchEvent(new Event('change'));
          }
        });
      });
    });
  }

  /**
   * Set cookie settings
   * @deprecated
   */
  function setCookieSettings() {
    // Exists for BC only
  }

  /**
   * Get cookie
   * @param {string} cookie Cookie
   * @returns {string} Cookie contents
   * @deprecated Use VuFind.cookie.get()
   */
  function getCookie(cookie) {
    return VuFind.cookie.get(cookie);
  }

  /**
   * Set cookie
   * @param {string} cookie Name
   * @param {string} value  Value
   * @deprecated Use VuFind.cookie.set()
   */
  function setCookie(cookie, value) {
    VuFind.cookie.set(cookie, value);
  }
  /**
   * Delete a cookie
   * @param {string} cookie Name
   * @deprecated Use VuFind.cookie.remove()
   */
  function removeCookie(cookie) {
    VuFind.cookie.remove(cookie);
  }

  /**
   * Track content impressions within a node with Matomo
   *
   * Needed for dynamically updated content. Static content gets tracked automatically.
   * @param {HTMLElement} node Node to track for impressions
   */
  function trackContentImpressions(node) {
    if (window._paq) {
      window._paq.push(['trackContentImpressionsWithinNode', node]);
    }
  }

  var my = {
    decodeHtml: decodeHtml,
    getField: getField,
    initQrCodeLink: initQrCodeLink,
    init: function init() {
      initQrCodeLink();
      initResultsEventHandler();
      VuFind.observerManager.createIntersectionObserver(
        'LazyImages',
        (element) => {
          element.src = element.dataset.src;
          delete element.dataset.src;
        }
      );
      finna.resolvePromise('lazyImages');
    },
    initResultScripts: initResultScripts,
    getCookie: getCookie,
    setCookie: setCookie,
    removeCookie: removeCookie,
    setCookieSettings: setCookieSettings,
    trackContentImpressions: trackContentImpressions,
  };

  return my;
})();
