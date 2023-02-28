/* global VuFind, finna, Splide */
finna.splideManager = (() => {
  const breakpointSettingMappings = {
    desktop: 'perPage',
    'desktop-small': 1200,
    tablet: 992,
    mobile: 768
  }

  /**
   * Settings in finna to settings in splide
   */
  const settingNameMappings = {
    height: (value) => { return {height: parseInt(value)} },
    slidesToShow: (itemsPerPage) => {
      const breakpoints = {};
      let perPage = 0;
      for (const [key, value] of Object.entries(itemsPerPage)) {
        const bp = breakpointSettingMappings[key] || '';
        switch (bp) {
        case 'perPage':
          perPage = value; 
          break;
        case '':
          break;
        default:
          breakpoints[bp] = {
            perPage: value
          };
          break;
        }
      }
      return {
        breakpoints,
        perPage
      };
    },
    type: (value) => {
      let direction = 'ltr';
      let classes = {
        prev: 'splide__arrow--prev carousel-arrow ',
        next: 'splide__arrow--next carousel-arrow ',
        arrows: 'splide__arrows carousel-arrows '
      };
      switch (value) {
      case 'carousel-vertical':
        classes.prev += 'up';
        classes.next += 'down';
        classes.arrows += 'vertical';
        direction = 'ttb';
        break;
      case 'carousel':
        direction = 'ltr';
        classes.prev += 'left';
        classes.next += 'right';
        classes.arrows += 'horizontal';
        break;
      }
      return {
        direction,
        classes
      };
    },
    slidesToScroll: 'perMove',
    scrollSpeed: 'speed',
    i18n: (translations) => {
      return {
        i18n: {
          prev: VuFind.translate(translations.prev || 'splide_prev_slide'),
          next: VuFind.translate(translations.next || 'splide_next_slide'),
          first: VuFind.translate(translations.first || 'splide_first_slide'),
          last: VuFind.translate(translations.last || 'splide_last_slide'),
          slideX: VuFind.translate(translations.slide || 'splide_navigation'),
          pageX: VuFind.translate(translations.page || 'splide_page'),
          play: VuFind.translate(translations.play || 'splide_autoplay_start'),
          pause: VuFind.translate(translations.pause || 'splide_autoplay_pause'),
          select: VuFind.translate(translations.select || 'splide_select_slide'),
          slideLabel: VuFind.translate(translations.label || 'splide_slide_label'),
        }
      }
    }
  }

  /**
   * Converts settings into compatible Splide settings
   *
   * @param {Object} settings 
   */
  function toSplideSettings(settings) {
    let splidied = {
      direction: 'ltr',
      gap: 10,
      type: 'loop'
    };
    for (const [key, value] of Object.entries(settings)) {
      if (typeof settingNameMappings[key] !== 'undefined') {
        const newKey = settingNameMappings[key];
        if (typeof newKey === 'function') {
          const functionResult = newKey(value);
          splidied = deepMerge(splidied, functionResult);
        } else {
          splidied[newKey] = value;
        }
      }
    }

    return splidied;
  }

  /**
   * Merge sub objects into target from source
   *
   * @param {Object} target To merge key/values to
   * @param {Object} source To merge key/values from
   *
   * @returns {Object} Merged object
   */
  function deepMerge(target, source) {
    if (typeof target === 'object' && typeof source === 'object') {
      for (const key in source) {
        if (typeof source[key] === 'object') {
          if (!target[key]) {
            Object.assign(target, { [key]: {} });
          }
          deepMerge(target[key], source[key]);
        } else {
          Object.assign(target, { [key]: source[key] });
        }
      }
    }
    return target;
  }

  /**
   * Turn given elements into carousels
   *
   * @param {Array|NodeList} elements Elements to turn into a carousel
   * @param {Object}         settings Old Finna settings for carousels
   */
  function create(elements, settings) {
    if (typeof settings.i18n === 'undefined') {
      settings.i18n = {}
    }
    const splideSettings = toSplideSettings(settings);
    for (let i = 0; i < elements.length; i++) {
      new Splide(elements[i], splideSettings).mount();
    }
  }

  return {
    create
  };
})();
