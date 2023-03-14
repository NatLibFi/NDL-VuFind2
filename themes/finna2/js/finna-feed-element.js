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
   * Adjust titles. Useful when the screen size changes so the elements
   * look as they should.
   *
   * @param settings Carousel settings
   */
  setTitleBottom(settings) {
    // Move title field below image
    let maxH = 0;
    this.querySelectorAll('.carousel-slide-header p').forEach(el => {
      maxH = Math.max(maxH, el.getBoundingClientRect().height + 10);
      el.classList.add('title-bottom');
      el.parentNode.classList.add('title-bottom');
    });
    this.querySelectorAll('.carousel-slide-header, .carousel-slide-header p').forEach(el => {
      el.style.minHeight = el.style.height = `${maxH}px`;
    });
    this.querySelectorAll('.carousel-feed .carousel-text').forEach(el => {
      const textElement = el.querySelector('div.text p');
      if (!textElement) {
        return;
      }
      if (textElement.innerHTML.trim() !== '') {
        el.classList.add('text-bottom');
        el.style.maxHeight = `${settings.height}px`;
      } else {
        el.classList.add('no-text');
      }
    });
    settings.height = +settings.height + maxH;
  }

  /**
   * Create autoplay button.
   */
  createAutoplayButton() {
    const autoPlayButton = document.createElement('button');
    autoPlayButton.className = 'splide__toggle autoplay-button';
    autoPlayButton.type = 'button';

    const playSpan = document.createElement('span');
    playSpan.className = 'sr-only';
    playSpan.innerHTML = VuFind.translate('Carousel::Start Autoplay');
    const playIcon = document.createElement('i');
    playIcon.className = 'fa fa-play-circle splide__toggle__play play-icon';
    playIcon.append(playSpan);

    const pauseSpan = document.createElement('span');
    pauseSpan.className = 'sr-only';
    pauseSpan.innerHTML = VuFind.translate('Carousel::Stop Autoplay');
    const pauseIcon = document.createElement('i');
    pauseIcon.className = 'splide__toggle__pause fa fa-pause-circle pause-icon';
    pauseIcon.append(pauseSpan);

    autoPlayButton.append(playIcon, pauseIcon);
    this.append(autoPlayButton);
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
        this.classList.add('splide');

        if (settings.autoplay && settings.autoplay > 0) {
          this.createAutoplayButton();
        }

        const vertical = 'carousel-vertical' === settings.type;
        settings.vertical = vertical;
        this.splide = finna.carouselManager.createCarousel(this, settings);
        var titleBottom = typeof settings.titlePosition !== 'undefined' && settings.titlePosition === 'bottom';
        if (!vertical) {
          if (titleBottom) {
            holder.setTitleBottom(settings);
            holder.querySelectorAll('.carousel-hover-title').forEach(el => {
              el.style.display = 'none';
            });
            holder.querySelectorAll('.carousel-hover-date').forEach(el => {
              el.style.display = 'none';
            });
            // Update the height of the splide component for title-bottom to display properly
            this.splide.options = {
              height: settings.height
            };
          }
        }

        // Text hover for touch devices
        if (finna.layout.isTouchDevice() && typeof settings.linkText === 'undefined') {
          holder.querySelectorAll('.carousel-text').forEach(el => {
            el.style.paddingBottom = '30px';
          });
          const onSlideClick = function onSlideClick () {
            const slide = this.closest('.feed-item-holder');
            if (slide && !slide.classList.contains('clicked')) {
              slide.classList.add('clicked');
              return false;
            }
          };
          holder.querySelectorAll('.feed-item-holder a, .feed-item-holder').forEach(el => {
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
          // The catch will catch all the js errors in buildFeedDom, so display a warning
          // if something happens
          console.error(responseJSON);
          holder.innerHTML
            = `<!-- Feed could not be loaded: ${responseJSON.data || ''} -->`;
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
