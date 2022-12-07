/* global VuFind, finna */

class VideoElement extends HTMLElement {

  /**
   * Get the type of the video, iFrame | video
   *
   * @returns {string}
   */
  get type() {
    return (this.getAttribute('type') || '').toLowerCase();
  }

  /**
   * Set the type of the video, iFrame | video
   *
   * @param {string} value iFrame | video
   */
  set type(value) {
    this.setAttribute('type', value);
  }

  /**
   * Get the parent element to which the video player is being embedded into.
   *
   * @returns {string|undefined}
   */
  get embedParent() {
    return this.getAttribute('embed-parent') || undefined;
  }

  /**
   * Set the parent element to which the video player is being embedded into.
   * Omit to display in a new popup.
   *
   * @param {string|undefined} value Parent element id or undefined.
   */
  set embedParent(value) {
    this.setAttribute('embed-parent', value);
  }

  /**
   * Get the source of the video.
   *
   * @returns {string}
   */
  get source() {
    return this.getAttribute('source') || '';
  }

  /**
   * Set the source of the video.
   *
   * @param {string} value The video source
   */
  set source(value) {
    this.setAttribute('source', value);
  }

  /**
   * Get the video sources as an object.
   *
   * @returns {object}
   */
  get videoSources() {
    return this.getAttribute('video-sources') ? JSON.parse(this.getAttribute('video-sources')) : {};
  }

  /**
   * Set the video sources as an object.
   *
   * @param {object} value
   */
  set videoSources(value) {
    this.setAttribute('video-sources', JSON.stringify(value || {}));
  }

  /**
   * Get the poster url to display in viewer.
   *
   * @return {string}
   */
  get posterUrl() {
    return this.getAttribute('poster-url') || '';
  }

  /**
   * Set the poster url to display in viewer.
   *
   * @param {string} value
   */
  set posterUrl(value) {
    this.setAttribute('poster-url', value);
  }

  /**
   * Get the identity for the popup group.
   *
   * @returns {string}
   */
  get popupId() {
    return this.getAttribute('popup-id') || '';
  }

  /**
   * Set the identity for the popup group.
   *
   * @param {string} value
   */
  set popupId(value) {
    this.setAttribute('popup-id', value);
  }

  /**
   * Get consent categories required for the video.
   *
   * @returns {string}
   */
  get consentCategories() {
    return this.getAttribute('consent-categories') || '';
  }

  /**
   * Get consent categories required for the video.
   *
   * @param {string} value
   */
  set consentCategories(value) {
    this.setAttribute('consent-categories', value);
  }

  /**
   * Get if the element has consent.
   * Returns true if the value is 'true' as a string.
   *
   * @returns {boolean}
   */
  get hasConsent() {
    return this.getAttribute('has-consent') === 'true';
  }

  /**
   * Set if the element has consent.
   *
   * @param {string} value
   */
  set hasConsent(value) {
    this.setAttribute('has-consent', value);
  }

  /**
   * Get index.
   *
   * @returns {string}
   */
  get index() {
    return this.getAttribute('index') || '';
  }

  /**
   * Set index.
   *
   * @param {number} value Value
   */
  set index(value) {
    this.setAttribute('index', value);
  }

  /**
   * Get if the video should be activated on load.
   * Returns true if the value is 'true' as a string.
   *
   * @returns {boolean}
   */
  get active() {
    return this.getAttribute('active') === 'true';
  }

  /**
   * Set if the video should be activated on load.
   */
  set active(value) {
    this.setAttribute('active', value);
  }

  /**
   * Constructor
   */
  constructor() {
    super();
    this.videoModal = `<video class="video-js vjs-big-play-centered video-popup" controls></video>`;
    this.iFrameModal = `<div style="height:100%">
    <iframe class="player finna-popup-iframe" frameborder="0" allowfullscreen></iframe>
    </div>`;

    this.translations = {
      close: VuFind.translate('close'),
      next: VuFind.translate('Next Record'),
      previous: VuFind.translate('Previous Record'),
    };
    this.scripts = {
      'js-videojs': 'vendor/video.min.js',
      'js-video-popup': 'finna-video-popup.js'
    };
    this.subScripts = {
      'js-videojs-hotkeys': 'vendor/videojs.hotkeys.min.js',
      'js-videojs-quality': 'vendor/videojs-contrib-quality-levels.js',
      'js-videojs-airplay': 'vendor/silvermine-videojs-airplay.min.js',
    };
  }

  /**
   * When the element is added to the dom
   */
  connectedCallback() {
    // Check if this video is inside a record
    const record = this.closest('div.record');
    const self = this;

    const popupSettings = {
      id: this.popupId,
      modal: this.type === 'iframe' ? this.iFrameModal : this.videoModal,
      cycle: typeof this.embedParent !== 'undefined',
      classes: this.type === 'iframe' ? 'finna-iframe' : 'video-popup',
      parent: this.embedParent,
      translations: this.translations,
      onPopupInit: (t) => {
        if (this.embedParent) {
          t.removeClass('active-video');
        }
      },
      onPopupOpen: function onPopupOpen() {
        if (record) {
          const warnings
            = record.querySelector(`.video-warning[data-index="${self.index}"]`);
          if (this.parent) {
            record.querySelectorAll('.active-video').forEach(v => {
              v.classList.remove('active-video');
            });
            record.querySelectorAll('.video-warning').forEach(v => {
              if (v.dataset.index !== self.index) {
                v.classList.add('hidden');
              } else {
                v.classList.remove('hidden');
                finna.common.observeImages(v.querySelectorAll('img[data-src]'));
              }
            });
            this.currentTrigger().addClass('active-video');
          } else {
            this.content.css('height', '100%');
            if (warnings) {
              const clone = warnings.cloneNode(true);
              clone.classList.remove('hidden');
              this.modalHolder.append(clone);
              finna.common.observeImages(clone.querySelectorAll('img[data-src]'));
              setTimeout(function startFade() {
                $(clone).fadeOut(2000);
              }, 3000);
            }
          }
        }
        if (!self.hasConsent) {
          return;
        }

        switch (self.type) {
        case 'video':
          finna.scriptLoader.loadInOrder(self.scripts, self.subScripts, () => {
            finna.videoPopup.initVideoJs('.video-popup', self.videoSources, self.posterUrl);
          });
          break;
        case 'iframe':
          // If using Chrome + VoiceOver, Chrome crashes if vimeo player video settings button has aria-haspopup=true
          document.querySelectorAll('.vp-prefs .js-prefs').forEach(e => {
            e.setAttribute('aria-haspopup', false);
          });
          this.content.find('iframe').attr('src', this.adjustEmbedLink(self.source));
          break;
        default:
          console.warn(`Unknown video type in video element: ${self.type}`);
          break;
        }
      }
    };

    if (!this.hasConsent) {
      finna.scriptLoader.load(
        {'js-cookie-consent': 'finna-cookie-consent-element.js'},
        () => {
          const consentModal = document.createElement('finna-consent');
          consentModal.consentCategories = this.consentCategories;
          consentModal.serviceUrl = this.source;
    
          popupSettings.modal = consentModal;
          $(this).finnaPopup(popupSettings);
          if (this.active) {
            this.click();
          }
        }
      );
    } else {
      $(this).finnaPopup(popupSettings);
      if (this.active) {
        this.click();
      }
    }
  }

  /**
   * When the element is removed from the dom
   */
  disconnectedCallback() {
    $(this).trigger('removeclick.finna');
  }
}

customElements.define('finna-video', VideoElement);
