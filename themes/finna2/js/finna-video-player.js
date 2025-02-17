/* global VuFind, finna */
finna.videoPlayer = (() => {
  
  /**
   * These scripts must be loaded first for additional scripts requested to work properly
   * @member {object} requiredVideoScripts
   */
  const requiredVideoScripts = {
    'videojs': 'vendor/video.min.js',
    'video-popup': 'finna-video-popup.js'
  };

  /**
   * Additional scripts required to load when requesting video with videojs
   * @member {object} additionalVideoScripts
   */
  const additionalVideoScripts = {
    'videojs-hotkeys': 'vendor/videojs.hotkeys.min.js',
    'videojs-quality': 'vendor/videojs-contrib-quality-levels.js',
    'videojs-airplay': 'vendor/silvermine-videojs-airplay.min.js',
  };

  /**
   * Is the module already initialized?
   * @member {boolean}
   */
  let initialized = false;

  /**
   * Adds a specific class name to VuFind modal and a listener to listen when
   * the modal is closed to remove the class name.
   * @param {string} className Classname to set for opened modal.
   */
  function overrideModalClass(className)
  {
    const container = document.getElementById('modal');
    if (container) {
      container.classList.add(className);
      VuFind.listen('lightbox.closed', () => {
        container.classList.remove(className);
      }, {once: true});
    }
  }

  /**
   * Display warning icons when using inline video setting set to true.
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
      warning.classList.toggle('hidden', element.dataset.vcIndex !== warning.dataset.index);
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
    if (element.dataset.vcInline) {
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
    iFrame.src = element.dataset.vcUrl;
    if (element.dataset.vcInline) {
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
   * Provide a selector or HTMLButtonElement to initialize a button for videos.
   * @param {HTMLButtonElement|string} elementOrSelector Element or selector
   */
  function initVideoButton(elementOrSelector)
  {
    const element = typeof elementOrSelector === 'string'
      ? document.querySelector(elementOrSelector)
      : elementOrSelector;
    if (!element) {
      return;
    }
    if (element.classList.contains('done')) {
      return;
    }
    element.classList.add('done');
    finna.getPromise('videoScripts').then(() => {
      element.addEventListener('click', () => {
        document.querySelectorAll('.vc-finna-video').forEach(b => b.classList.remove('active-video'));
        if (element.dataset.vcEmbed) {
          onIFrameOpen(element);
        } else {
          onVideoOpen(element);
        }
        element.classList.add('active-video');
      });
      if (element.classList.contains('active-video')) {
        element.click();
      }
    });
  }

  /**
   * Initialize the module
   */
  function init()
  {
    if (initialized) {
      return;
    }
    initialized = true;
    finna.scriptLoader.loadInOrder(requiredVideoScripts, additionalVideoScripts, () => {
      finna.resolvePromise('videoScripts');
    });
  }

  return {
    initVideoButton,
    init
  };
})();