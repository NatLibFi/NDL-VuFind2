/*global VuFind, finna*/
finna.common = (function finnaCommon() {
  let cookieSettings = {
    path: '/',
    domain: false,
    SameSite: 'Lax'
  };

  function decodeHtml(str) {
    return $("<textarea/>").html(str).text();
  }

  /**
   * Get field from the object.
   *
   * @param {object} obj   Object to search for the field
   * @param {string} field Field to look for
   * @returns The field found or null if undefined.
   */
  function getField(obj, field) {
    if (field in obj && typeof obj[field] != 'undefined') {
      return obj[field];
    }
    return null;
  }

  function initQrCodeLink(_holder) {
    var holder = typeof _holder === 'undefined' ? $(document) : _holder;

    // Handle finna QR code links
    // Note: a.finnaQrcodeLinkRecord is retained for backward compatibility
    holder.find('a.finnaQrcodeLink,a.finnaQrcodeLinkRecord').on('click', function qrcodeToggleRecord() {
      var qrholder = $(this).parent().find('li');
      if (qrholder.find('img').length === 0) {
        // We need to insert the QRCode image
        qrholder.html(qrholder.find('.qrCodeImgTag').html());
      }
      return true;
    });

    // Reposition the dropdown to escape any truncated div:
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

  function _getCookieSettings() {
    return cookieSettings;
  }

  function setCookieSettings(settings) {
    cookieSettings = settings;
  }

  function getCookie(cookie) {
    return window.Cookies.get(cookie);
  }

  function setCookie(cookie, value, settings) {
    window.Cookies.set(cookie, value, $.extend({}, _getCookieSettings(), settings));
  }
  function removeCookie(cookie) {
    window.Cookies.remove(cookie, _getCookieSettings());
  }

  var my = {
    decodeHtml: decodeHtml,
    getField: getField,
    initQrCodeLink: initQrCodeLink,
    init: function init() {
      initQrCodeLink();
      VuFind.observerManager.createIntersectionObserver(
        'LazyImages',
        (element) => {
          element.src = element.dataset.src;
          delete element.dataset.src;
        }
      );
    },
    getCookie: getCookie,
    setCookie: setCookie,
    removeCookie: removeCookie,
    setCookieSettings: setCookieSettings,
  };

  return my;
})();
