/* global finna, VuFind */

class FinnaFeedElement extends HTMLElement {

  /**
   * Observed attributes
   */
  static get observedAttributes() {
    return ['feed-id'];
  }

  /**
   * Get feed id
   *
   * @return {string}
   */
  get feedId() {
    return this.getAttribute('feed-id') || '';
  }
  
  /**
   * Set feed id
   *
   * @param {string} newValue Value to set
   */
  set feedId(newValue) {
    this.setAttribute('feed-id', newValue);
  }

  /**
   * Get feed id. Used to load feeds with observermanagers etc.
   *
   * @return {string}
   */
  get feedIdLazy() {
    return this.getAttribute('feed-id-lazy') || '';
  }
    
  /**
   * Set feed lazy id. Used to load feeds with observermanagers etc.
   *
   * @param {string} newValue Value to set
   */
  set feedIdLazy(newValue) {
    this.setAttribute('feed-id-lazy', newValue);
  }

  /**
   * Constructor
   */
  constructor() {
    super();
    this.isTouchDevice = finna.layout.isTouchDevice() ? 1 : 0;
    this.slideHeight = undefined;
  }

  calculateScrollSpeed(scrollCnt, scrollSpeed) {
    return scrollSpeed * Math.max(1, (scrollCnt / 5));
  }

  adjustSlides() {
    const slide = this.querySelectorAll('.slick-slide');
    const newWidth = slide[0].getBoundingClientRect.width - 20;
    slide.forEach(el => {
      el.style.height = this.slideHeight;
      el.style.maxHeight = this.slideHeight;
      el.firstChild.style.height = '100%';
      el.classList.add('adjusted-height');
    });
    this.querySelectorAll('.carousel-slide-header p, .carousel-text').forEach (el => {
      el.getBoundingClientRect().width = newWidth;
    });
  }

  adjustTitles() {
    // Move title field below image
    let maxH = 0;
    this.querySelectorAll('.carousel-feed .slick-slide .carousel-slide-header p').forEach(el => {
      maxH = Math.max(maxH, el.getBoundingClientRect().height);
      el.classList.add('title-bottom');
    });
    this.querySelectorAll('.carousel-feed .slick-list').forEach(el => {
      el.style.paddingBottom = `${maxH}px`;
    });
    this.querySelectorAll('.carousel-feed .slick-slide .carousel-text').forEach(el => {
      el.classList.add('text-bottom');
    });
  }

  getCarouselSettings(settings) {
    var autoplay = typeof settings.autoplay !== 'boolean' ? parseInt(settings.autoplay, 10) : 0;
    return {
      dots: settings.dots,
      swipe: !settings.vertical,
      infinite: true,
      prevArrow: '<button class="slick-prev" type="button">'
        + '<span class="slick-prev-icon" aria-hidden="true"></span>'
        + '<span class="slick-sr-only">' + VuFind.translate("Prev") + '</span>'
        + '</button>',
      nextArrow: '<button class="slick-next" type="button">'
        + '<span class="slick-next-icon" aria-hidden="true"></span>'
        + '<span class="slick-sr-only">' + VuFind.translate("Next") + '</span>'
                + '</button>',
      regionLabel: VuFind.translate("Image Carousel"),
      customPaging: function initCustomPaging(slider, i) {
        return $('<button type="button">'
         + '<span class="slick-dot-icon" aria-hidden="true"></span>'
         + '<span class="slick-sr-only">' + VuFind.translate("Go to slide") + ' ' + (i + 1) + '</span>'
         + '</button>');
      },
      touchThreshold: 8,
      autoplay: autoplay !== 0,
      autoplaySpeed: autoplay,
      useAutoplayToggleButton: false,
      slidesToShow: settings.slidesToShow.desktop,
      slidesToScroll: settings.scrolledItems.desktop,
      speed: this.calculateScrollSpeed(settings.scrolledItems.desktop, settings.scrollSpeed),
      vertical: settings.vertical,
      lazyLoad: (typeof settings.lazyLoad !== 'undefined') ? settings.lazyLoad : 'ondemand',
      responsive: [
        {
          breakpoint: 1200,
          settings: {
            slidesToShow: settings.slidesToShow['desktop-small'],
            slidesToScroll: settings.scrolledItems['desktop-small'],
            speed: this.calculateScrollSpeed(settings.scrolledItems['desktop-small'], settings.scrollSpeed)
          }
        },
        {
          breakpoint: 992,
          settings: {
            slidesToShow: settings.slidesToShow.tablet,
            slidesToScroll: settings.scrolledItems.tablet,
            speed: this.calculateScrollSpeed(settings.scrolledItems.tablet, settings.scrollSpeed)
          }
        },
        {
          breakpoint: 768,
          settings: {
            slidesToShow: settings.slidesToShow.mobile,
            slidesToScroll: settings.scrolledItems.mobile,
            speed: this.calculateScrollSpeed(settings.scrolledItems.mobile, settings.scrollSpeed)
          }
        }
      ]
    };
  }

  loadFeed() {
    const holder = this;
    const url = VuFind.path + '/AJAX/JSON?' + new URLSearchParams({
      method: 'getFeed',
      id: this.feedId,
      'touch-device': this.isTouchDevice
    });

    // Prepend spinner
    holder.prepend('<i class="fa fa-spin fa-spinner"></i>');

    /*fetch(url).then(response => response.json())
      .then(result => {
        rating.outerHTML = result.data.html;
        // Bind lightbox to the new content:
        VuFind.lightbox.bind(document.querySelector('.media-left .rating'));
      });*/


    $.getJSON(url)
      .done((response) => {
        if (response.data) {
          holder.innerHTML = VuFind.updateCspNonce(response.data.html)
          var settings = response.data.settings;
          if (typeof settings.height == 'undefined') {
            settings.height = 300;
          }
          var type = settings.type;

          var carousel = type === 'carousel' || type === 'carousel-vertical';

          if (carousel) {
            var vertical = type === 'carousel-vertical';
            settings.vertical = vertical;
            const feedObject = holder.querySelector('.carousel-feed');
            $(feedObject).slick(this.getCarouselSettings(settings));

            var titleBottom = typeof settings.titlePosition !== 'undefined' && settings.titlePosition === 'bottom';

            var callbacks = {};
            callbacks.resize = () => {
              this.adjustSlides();
              if (titleBottom) {
                this.adjustTitles();
              }
            };

            $(window).on('throttled-resize.finna', function resizeWindow() {
              callbacks.resize();
            });
            this.slideHeight = `${settings.height}px`;
            if (!vertical) {
              this.adjustSlides();

              if (titleBottom) {
                this.adjustTitles();
                holder.querySelectorAll('.carousel-hover-title, .carousel-hover-date').forEach(el => {
                  el.style.display = 'none';
                });
              } else {
                holder.querySelectorAll('.carousel-hover-date').forEach(el => {
                  el.style.display = 'none';
                });
              }
            }
            holder.querySelectorAll('.slick-track, .slick-slide').forEach(el => {
              el.style.height = this.slideHeight;
              el.style.maxHeight = this.slideHeight;
            });
            const sliderDots = this.querySelectorAll('ul.slick-dots li');
            holder.querySelector('.slick-slider').addEventListener('afterChange', function onAfterChange() {
              sliderDots.forEach(el => {
                el.removeAttribute('aria-current');
                if (el.classList.contains('active')) {
                  el.setAttribute('aria-current', true);
                }
              });
            });

            // Text hover for touch devices
            if (finna.layout.isTouchDevice() && typeof settings.linkText === 'undefined') {
              holder.querySelectorAll('.carousel-text').forEach(el => {
                el.style.paddingBottom = '30px';
              });
              const onSlideClick = function onSlideClick () {
                const slide = this.closest('.slick-slide');
                if (slide && !slide.classList.contains('clicked')) {
                  slide.classList.add('clicked');
                  return false;
                }
              };
              holder.querySelectorAll('.slick-slide a, .slick-slide').forEach(el => {
                el.addEventListener('click', onSlideClick);
              }); 
            } else {
              holder.querySelectorAll('.carousel').forEach(el => {
                el.classList.add('carousel-non-touch-device')
              });
            }
            // Force refresh to make sure that the layout is ok
            $(feedObject).slick('slickGoTo', 0, true);
          }

          // Bind lightbox if feed content is shown in modal
          if (typeof settings.modal !== 'undefined' && settings.modal) {
            const onClickHolderLink = function onClickHolderLink() {
              $('#modal').addClass('feed-content');
            };
            holder.querySelectorAll('a').forEach(el => {
              el.addEventListener('click', onClickHolderLink);
            });
            VuFind.lightbox.bind(holder);
          }
        }
        const truncatedGrid = holder.querySelectorAll('.grid-item.truncate');
        if (truncatedGrid) {
          holder.querySelectorAll('.show-more-feeds').forEach(el => {
            el.classList.remove('hidden');
          });
        }
        const showMoreFeeds = holder.querySelector('.show-more-feeds');
        const showLessFeeds = holder.querySelector('.show-less-feeds');
        if (showMoreFeeds) {
          showMoreFeeds.addEventListener('click', () => {
            truncatedGrid.forEach(el => {
              el.classList.remove('hidden');
            });
            showLessFeeds.classList.remove('hidden');
            showMoreFeeds.classList.add('hidden');
          });
        }
        if (showLessFeeds) {
          showLessFeeds.addEventListener('click', () => {
            truncatedGrid.forEach(el => {
              el.classList.add('hidden');
            });
            showMoreFeeds.classList.remove('hidden');
            showLessFeeds.classList.add('hidden');
          });
        }
        const feedGrid = holder.querySelector('.feed-grid:not(.news-feed .feed-grid, .events-feed .feed-grid)')
        if (feedGrid) {
          if (feedGrid.getBoundingClientRect().width <= 500) {
            feedGrid.querySelectorAll('.grid-item').forEach(el => {
              el.style.flexBasis = '100%';
            });
            feedGrid.find('.grid-item').css('flex-basis', '100%');
          } else if (feedGrid.getBoundingClientRect().width <= 800) {
            feedGrid.querySelectorAll('.grid-item').forEach(el => {
              el.style.flexBasis = '50%';
            });
          }
        }

        if (typeof holder.onFeedLoaded === 'function') {
          holder.onFeedLoaded();
        }
        VuFind.observerManager.observe(
          'LazyImages',
          holder.querySelectorAll('img[data-src]')
        );
      })
      .fail(function loadFeedFail(response/*, textStatus, err*/) {
        var err = '<!-- Feed could not be loaded';
        if (typeof response.responseJSON !== 'undefined') {
          err += ': ' + response.responseJSON.data;
        }
        err += ' -->';
        holder.html(err);
      });
  }

  /**
   * When the element is added to the dom
   */
  connectedCallback() {
    if (!this.feedId) {
      this.addToObserver();
    }
  }
  
  /**
   * Observed attribute value changed
   *
   * @param {string} name     Name of the attribute
   * @param {string} oldValue Attributes old value
   * @param {string} newValue Attributes new value
   */
  attributeChangedCallback(name, oldValue, newValue) {
    switch (name) {
    case 'feed-id':
      this.loadFeed();
      break;
    }
  }

  addToObserver() {
    VuFind.observerManager.createIntersectionObserver(
      'FeedElements',
      () => {
        this.feedId = this.feedIdLazy;
      },
      [this]
    );
  }
}

customElements.define('finna-feed', FinnaFeedElement);
