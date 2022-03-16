/*global VuFind, finna*/
finna.common = (function finnaCommon() {
  let cookieSettings = {
    path: '/',
    domain: false,
    SameSite: 'Lax'
  };

  let lazyImageObserver;

  function decodeHtml(str) {
    return $("<textarea/>").html(str).text();
  }

  function getField(obj, field) {
    if (field in obj && typeof obj[field] != 'undefined') {
      return obj[field];
    }
    return null;
  }

  function initQrCodeLink(_holder) {
    var holder = typeof _holder === 'undefined' ? $(document) : _holder;
    // handle finna QR code links
    holder.find('a.finnaQrcodeLink').on('click', function qrcodeToggle() {
      var qrLink = $(this);
      var isActive = qrLink.hasClass('active');
      qrLink.html(isActive ? "<i class='fa fa-qr-code' aria-hidden='true'></i>" : VuFind.translate('qrcode_hide'));
      qrLink.toggleClass('active', !isActive);
      qrLink.parent().toggleClass('qr-box', !isActive);

      var qrholder = qrLink.next('.qrcode');
      if (qrholder.find('img').length === 0) {
        // We need to insert the QRCode image
        qrholder.html(qrholder.find('.qrCodeImgTag').html());
      }
      qrholder.toggleClass('hidden');
      return false;
    });

    $('a.finnaQrcodeLinkRecord').on('click', function qrcodeToggleRecord() {
      var qrholder = $(this).parent().find('li');
      if (qrholder.find('img').length === 0) {
        // We need to insert the QRCode image
        qrholder.html(qrholder.find('.qrCodeImgTag').html());
      }
      return true;
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

  /**
   * Start observing given nodelist. Used for lazyloading images.
   * Images must contain data-src attribute.
   * 
   * @param {NodeList} images 
   */
  function observeImages(images) {
    if (!images.length) {
      return;
    }
    if (!('IntersectionObserver' in window) ||
      !('IntersectionObserverEntry' in window) ||
      !('isIntersecting' in window.IntersectionObserverEntry.prototype) ||
      !('intersectionRatio' in window.IntersectionObserverEntry.prototype)
    ) {
      // Fallback: display images instantly on browsers that don't support the observer properly
      images.forEach((image) => {
        image.src = image.dataset.src;
        delete image.dataset.src;
      });
    } else {
      if (!lazyImageObserver) {
        lazyImageObserver = new IntersectionObserver((entries, obs) => {
          entries.forEach((entry) => {
            if (entry.isIntersecting) {
              let lazyImage = entry.target;
              lazyImage.src = lazyImage.dataset.src;
              delete lazyImage.dataset.src;
              obs.unobserve(lazyImage);
            }
          }); 
        });
      }
      images.forEach((image) => {
        lazyImageObserver.observe(image);
      });
    }
  }

  var my = {
    decodeHtml: decodeHtml,
    getField: getField,
    initQrCodeLink: initQrCodeLink,
    init: function init() {
      initQrCodeLink();
    },
    getCookie: getCookie,
    setCookie: setCookie,
    removeCookie: removeCookie,
    setCookieSettings: setCookieSettings,
    observeImages: observeImages
  };

  return my;
})();
