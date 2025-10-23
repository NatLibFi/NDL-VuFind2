/* global VuFind, finna, CookieConsent */
finna.videoPlayer = (() => {
  
  /**
   * Scripts to load in order
   * @member {object} requiredVideoScripts
   */
  const requiredVideoScripts = {
    'videojs': 'vendor/video.min.js',
  };

  /**
   * Scripts that depend on the videojs script
   * @member {object} dependentVideoScripts
   */
  const dependentVideoScripts = {
    'video-popup': 'finna-video-popup.js',
    'videojs-hotkeys': 'vendor/videojs.hotkeys.min.js',
    'videojs-quality': 'vendor/videojs-contrib-quality-levels.js',
    'videojs-airplay': 'vendor/silvermine-videojs-airplay.min.js',
  };

  /**
   * Adds a specific class name to VuFind modal and a listener to listen when
   * the modal is closed to remove the class name.
   * @param {string} className Classname to set for opened modal.
   */
  function overrideModalClass(className)
  {
    const container = document.getElementById('modal');
    if (container) {
      const dialog = container.querySelector('.modal-dialog');
      if (dialog) {
        container.classList.add(className);
        dialog.classList.add('modal-dialog-centered');
        VuFind.listen('lightbox.closed', () => {
          dialog.classList.remove('modal-dialog-centered');
          container.classList.remove(className);
        }, {once: true});
      }
    }
  }

  /**
   * Display warning icons when inline video setting is enabled.
   * @param {HTMLElement} element Element clicked
   */
  function showWarningIcons(element)
  {
    // Open video warnings in inline videos
    document.querySelectorAll('.warnings-wrapper .video-warning').forEach(warning => {
      finna.getPromise('lazyImages').then(() => {
        VuFind.observerManager.observe(
          'LazyImages',
          warning.querySelectorAll('img[data-src]')
        );
      });
      warning.classList.toggle('hidden', element.dataset.index !== warning.dataset.index);
    });
  }

  /**
   * When a video button which uses videojs has been requested.
   * @param {HTMLElement} element Element clicked
   */
  function onVideoOpen(element)
  {
    const videoPlayer = document.createElement('video');
    videoPlayer.className = 'video-js vjs-big-play-centered video-popup';
    videoPlayer.controls = '';

    // Is the video inline video or popup video
    let container;
    if (element.dataset.inline) {
      container = document.getElementById('inline-video');
      container.replaceChildren(videoPlayer);
      showWarningIcons(element);
    } else {
      // Try to close any open finna popups so the video can be shown properly
      $.fn.finnaPopup.closeOpen();
      VuFind.lightbox.render(videoPlayer.outerHTML);
      overrideModalClass('finna-video-modal');
      container = document.getElementById('modal');
    }
    const videoSources = JSON.parse(element.dataset.videoSources);
    finna.videoPopup.initVideoJs(container, videoSources, element.dataset.posterUrl);
  }

  /**
   * When a video which uses iframe has been requested.
   * @param {HTMLElement} element Element clicked
   */
  function onIFrameOpen(element)
  {
    const iFrame = document.createElement('iframe');
    iFrame.className = 'player';
    iFrame.frameborder = 0;
    iFrame.allowFullscreen = 'true';
    iFrame.src = element.dataset.url;
    if (element.dataset.inline) {
      const container = document.getElementById('inline-video');
      container.replaceChildren(iFrame);
      showWarningIcons(element);
    } else {
      // Try to close any open finna popups so the video can be shown properly
      $.fn.finnaPopup.closeOpen();
      VuFind.lightbox.render(iFrame.outerHTML);
      overrideModalClass('finna-iframe-modal');
    }
  }

  /**
   * Display a cookie consent window warning for the user.
   * @param {HTMLElement} element The element which was clicked
   */
  function displayConsentWindow(element)
  {
    const consentModal = document.getElementById('finna-consent-modal-template');
    if (consentModal) {
      // Append the cloned element, as templates return a DocumentFragment instead of node, which does not work
      // if outerHTML is called.
      const cloned = consentModal.content.cloneNode(true);
      const wrapper = document.createElement('div');
      wrapper.className = 'embedded-content-placeholder';
      wrapper.append(cloned);
      // Replace %%consentCategories%% and %%serviceBaseUrl%% with proper values
      const externalLink = wrapper.querySelector('.embedded-content-actions a[href="%%HREF%%"]');
      if (externalLink) {
        externalLink.setAttribute('href', element.dataset.url);
      }
      const description = wrapper.querySelector('.embedded-content-description');
      if (description) {
        const serviceBase = new URL(element.dataset.url);
        description.innerText = description.innerText
          .replace('%%consentCategories%%', element.dataset.consentTitle)
          .replace('%%serviceBaseUrl%%', serviceBase.hostname);
      }
      let consentHolder;
      if (element.dataset.inline) {
        consentHolder = document.getElementById('inline-video');
        consentHolder.replaceChildren(wrapper);
      } else {
        VuFind.lightbox.render(wrapper.outerHTML);
        overrideModalClass('finna-consent-modal');
        consentHolder = document.getElementById('modal');
      }
      const ccPreferences = consentHolder.querySelector('.embedded-content-actions button');
      if (ccPreferences) {
        // Set cookie consent preferences event after the modal has been initialized as the lightbox handles elements
        // as a string, so it loses all the events applied before rendering
        ccPreferences.addEventListener('click', () => {
          VuFind.modal('hide');
          CookieConsent.showPreferences();
        });
      }
    }
  }

  /**
   * Sets the video elements click event.
   * @param {HTMLElement} element Element which displays the video popup
   */
  function setIFrameStateFromConsent(element)
  {
    if (VuFind.cookie.isServiceAllowed(element.dataset.consent)) {
      element.addEventListener('click', () => {
        document.querySelectorAll('.vc-finna-video-button').forEach(b => b.classList.remove('active-video'));
        element.classList.add('active-video');
        onIFrameOpen(element);
      });
      if (element.classList.contains('active-video')) {
        onIFrameOpen(element);
      }
      return;
    } else {
      // We should display a consent information instead of the video
      element.addEventListener('click', () => { displayConsentWindow(element); });
      if (element.dataset.inline && element.classList.contains('active-video')) {
        displayConsentWindow(element);
      }
    }
  }

  /**
   * Provide a selector or HTMLButtonElement to initialize a button for embedded videos.
   * @param {HTMLButtonElement|string} elementOrSelector Element or selector
   */
  function initIFrameButton(elementOrSelector)
  {
    const element = typeof elementOrSelector === 'string'
      ? document.querySelector(elementOrSelector)
      : elementOrSelector;
    if (!element || element.classList.contains('initialized')) {
      return;
    }

    const consentInitialized = VuFind.cookie.getConsentConfig();
    // If consent configuration has not been initialized, wait for it
    if (!consentInitialized) {
      VuFind.listen('cookie-consent-initialized', () => {
        setIFrameStateFromConsent(element);
      });
    } else {
      setIFrameStateFromConsent(element);
    }
  }

  /**
   * Provide a selector or HTMLButtonElement to initialize a button for videos.
   * Handles loading the proper scripts.
   * @param {HTMLButtonElement|string} elementOrSelector Element or selector
   */
  function initVideoButton(elementOrSelector)
  {
    const element = typeof elementOrSelector === 'string'
      ? document.querySelector(elementOrSelector)
      : elementOrSelector;
    if (!element || element.classList.contains('initialized')) {
      return;
    }
    finna.scriptLoader.load(requiredVideoScripts, () => {
      finna.scriptLoader.load(dependentVideoScripts, () => {
        element.addEventListener('click', () => {
          document.querySelectorAll('.vc-finna-video-button').forEach(b => b.classList.remove('active-video'));
          onVideoOpen(element);
        });
        if (element.classList.contains('active-video')) {
          element.click();
        }
      });
    });
  }

  return {
    initVideoButton,
    initIFrameButton
  };
})();
