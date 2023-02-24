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
   * Constructor
   */
  constructor() {
    super();
    this.isTouchDevice = finna.layout.isTouchDevice() ? 1 : 0;
    this.slideHeight = undefined;
    this.onFeedLoaded = undefined;
    this.cache = [];
  }

  /**
   * Calculate feed scroll speed for splide.
   *
   * @param {number} scrollCnt   Amount of slides to scroll
   * @param {number} scrollSpeed Default scroll speed to multiply
   *
   * @return {number}
   */
  calculateScrollSpeed(scrollCnt, scrollSpeed) {
    return scrollSpeed * Math.max(1, (scrollCnt / 5));
  }

  /**
   * Adjust slides. Useful when the screen size changes so the elements
   * look as they should.
   */
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

  /**
   * Adjust titles. Useful when the screen size changes so the elements
   * look as they should.
   */
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

  /**
   * Get splide js settings.
   *
   * @param {object} settings Settings obtained from JSON request to backend.
   * @returns 
   */
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

  /**
   * When the feed is loaded or found from the internal cache.
   * Constructs the feed into the dom.
   *
   * @param {object} jsonResponse The response obtained from the backend.
   */
  buildFeedDom(jsonResponse) {
    const holder = this;
    if (jsonResponse.data) {
      holder.innerHTML = VuFind.updateCspNonce(jsonResponse.data.html);
      var settings = jsonResponse.data.settings;
      settings.height = settings.height || 300;
      const type = settings.type;
      const carousel = ['carousel', 'carousel-vertical'].includes(type);
      if (carousel) {
        const noContent = holder.querySelectorAll('.feed-item-holder').length === 0;
        if (noContent) {
          holder.style.display = 'none';
          holder.innerHTML = `<!-- No content received -->`;
          return;
        }
        const vertical = 'carousel-vertical' === settings.type;
        settings.vertical = vertical;
        const feedObject = holder.querySelector('.carousel-feed');

        // Slick depends on jquery
        $(feedObject).slick(this.getCarouselSettings(settings));

        var titleBottom = typeof settings.titlePosition !== 'undefined' && settings.titlePosition === 'bottom';

        var callbacks = {};
        callbacks.resize = () => {
          this.adjustSlides();
          if (titleBottom) {
            this.adjustTitles();
          }
        };

        // Call resize on fixed intervals
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
            el.classList.add('carousel-non-touch-device');
          });
        }
      }

      // Bind lightbox if feed content is shown in modal
      if (typeof settings.modal !== 'undefined' && settings.modal) {
        const onClickHolderLink = function onClickHolderLink() {
          const modal = document.getElementById('modal');
          if (modal) {
            modal.classList.add('feed-content');
          }
        };
        holder.querySelectorAll('a').forEach(el => {
          el.addEventListener('click', onClickHolderLink);
        });
        VuFind.lightbox.bind(holder);
      }
    }
    const truncatedGrid = holder.querySelectorAll('.grid-item.truncate');
    if (truncatedGrid.length) {
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
    const feedGrid = holder.querySelector('.feed-grid:not(.news-feed .feed-grid, .events-feed .feed-grid)');
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
  }

  /**
   * Starts process to load the proper feed.
   */
  loadFeed() {
    const cacheItem = this.cache.find(c => { return c.id === this.feedId; });
    if (cacheItem) {
      this.buildFeedDom(cacheItem.responseJSON);
    } else {
      const holder = this;
      // Prepend spinner
      const spinner = document.createElement('i');
      spinner.className = 'fa fa-spin fa-spinner';
      holder.insertAdjacentElement('afterbegin', spinner);

      const url = VuFind.path + '/AJAX/JSON?' + new URLSearchParams({
        method: 'getFeed',
        id: this.feedId,
        'touch-device': this.isTouchDevice
      });
      fetch(url)
        .then(response => response.json())
        .then(responseJSON => {
          const cacheObject = {
            id: this.feedId,
            responseJSON: responseJSON
          };
          this.cache.push(cacheObject);
          this.buildFeedDom(responseJSON);
        }).catch((responseJSON) => {
          holder.innerHTML = `<!-- Feed could not be loaded: ${responseJSON.data || ''} -->`;
        });
    }

  }

  /**
   * When the element is removed from the dom
   */
  disconnectedCallback() {
    this.innerHTML = '';
  }
  
  /**
   * Observed attribute value changed
   *
   * @param {string} name     Name of the attribute
   */
  attributeChangedCallback(name) {
    if ('feed-id' === name) {
      this.addToObserver();
    }
  }

  /**
   * Add this element to an intersection observer.
   */
  addToObserver() {
    VuFind.observerManager.createIntersectionObserver(
      'FeedElements',
      (el) => {
        el.loadFeed();
      },
      [this]
    );
  }
}

customElements.define('finna-feed', FinnaFeedElement);
