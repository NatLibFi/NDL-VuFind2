/* global VuFind, finna */

class VideoElement extends HTMLElement {

  /**
   * Type of the video, iFrame | video
   */
  get type() {
    return (this.getAttribute('type') || '').toLowerCase();
  }

  /**
   * Parent element to which the element is being embedded into
   */
  get embedParent() {
    return this.getAttribute('embed-parent') || undefined;
  }

  /**
   * Source of the video
   */
  get source() {
    return this.getAttribute('source') || '';
  }

  /**
   * Video sources as a json object
   */
  get videoSources() {
    return this.getAttribute('video-sources') ? JSON.parse(this.getAttribute('video-sources')) : {};
  }

  /**
   * Poster url to display in viewer
   */
  get posterUrl() {
    return this.getAttribute('poster-url') || '';
  }

  /**
   * Identity for the popup group
   */
  get popupId() {
    return this.getAttribute('popup-id') || '';
  }

  /**
   * Consent categories required for the video
   */
  get consentCategories() {
    return this.getAttribute('consent-categories') || '';
  }

  /**
   * Get if the element has consent
   */
  get hasConsent() {
    return this.getAttribute('has-consent') === 'true';
  }

  /**
   * Get the set index
   */
  get index() {
    return this.getAttribute('index');
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
    this.consentModal = '';

    this.translations = {
      close: VuFind.translate('close'),
      next: VuFind.translate('Next Record'),
      previous: VuFind.translate('Previous Record'),
    };
    this.scripts = {
      'js-videojs': 'vendor/video.min.js',
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
    const self = this;
    if (!this.hasConsent) {
      finna.scriptLoader.load(
        {'js-cookie-consent': 'finna-cookie-consent-element.js'},
        () => {
          this.consentModal = document.createElement('finna-consent');
          this.consentModal.consentCategories = this.consentCategories;
          this.consentModal.serviceUrl = this.source;
    
          this.iFrameModal = this.consentModal;
          this.videoModal = this.consentModal;
        }
      );
    }

    // Finnapopup needs jquery atm to be initialized.
    $(this).finnaPopup(
      {
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
          if (!self.hasConsent) {
            return;
          }
          const record = self.closest('div.record');
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
      }
    );
  }
}

customElements.define('finna-video', VideoElement);
