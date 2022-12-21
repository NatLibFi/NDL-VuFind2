/*global VuFind, finna, videojs */
finna.videoPopup = (function finnaVideoPopup() {

  function initVideoJs(_container, videoSources, posterUrl) {
    var $container = $(_container);
    var $videoElem = $(_container).find('video');

    var options = {
      preload: 'metadata',
      autoplay: false,
      controls: true,
      preloadWebComponents: true,
      nativeControlsForTouch: true,
      techOrder: [ 'html5' ],
      plugins: {
        airPlay: { addButtonToControlBar: true }
      },
      html5: {
        Vhs: {
          overrideNative: !videojs.browser.IS_SAFARI
        }
      }
    };

    // Use a fairly small buffer for faster quality changes
    videojs.Vhs.GOAL_BUFFER_LENGTH = 10;
    videojs.Vhs.MAX_GOAL_BUFFER_LENGTH = 20;
    var player = videojs($videoElem.get(0), options);

    player.ready(function onReady() {
      this.hotkeys({
        enableVolumeScroll: false,
        enableModifiersForNumbers: false
      });
    });

    player.src(videoSources);
    player.poster(posterUrl);
    player.airPlay();
    var selectedBitrate = 'auto';

    player.qualityLevels().on('addqualitylevel', function onAddQualityLevel(event) {
      event.qualityLevel.enabled = selectedBitrate === "auto" || event.qualityLevel.height.toString() === selectedBitrate;
    });

    player.on('loadedmetadata', function onMetadataLoaded() {
      var qualityLevels = player.qualityLevels();
      var addLevel = function addLevel(i, val) {
        var $item = $('<li/>')
          .addClass('vjs-menu-item')
          .attr('tabindex', i)
          .attr('role', 'menuitemcheckbox')
          .attr('aria-live', 'polite')
          .attr('aria-checked', 'false')
          .data('bitrate', String(val).toLowerCase());
        $('<span/>')
          .addClass('vjs-menu-item-text')
          .text(val)
          .appendTo($item);
        $item.appendTo($container.find('.quality-selection'));
        return $item;
      };
      var qLevels = [];
      for (var i = 0; i < qualityLevels.length; i++) {
        var quality = qualityLevels[i];

        if (quality.height !== undefined) {
          qLevels.push(quality.height);

          if (!$container.find('.quality-selection').length) {
            var $qs = $('<div/>').addClass('vjs-menu-button vjs-menu-button-popup vjs-control vjs-button');
            var $button = $('<button/>')
              .addClass('vjs-menu-button vjs-menu-button-popup vjs-button')
              .attr({'type': 'button', 'aria-live': 'polite', 'aria-haspopup': true, 'title': VuFind.translate('Quality')})
              .appendTo($qs);
            $('<span/>')
              .addClass('vjs-icon-cog')
              .attr('aria-hidden', 'true')
              .appendTo($button);
            $('<span/>')
              .addClass('vjs-control-text')
              .text(VuFind.translate('Quality'))
              .appendTo($button);
            var $menu = $('<div/>')
              .addClass('vjs-menu')
              .appendTo($qs);
            $('<ul/>')
              .addClass('quality-selection vjs-menu-content')
              .attr('role', 'menu')
              .appendTo($menu);

            $container.find('.vjs-fullscreen-control').before($qs);
          } else {
            $container.find('.quality-selection').empty();
          }

          qLevels.sort(function compareFunc(a, b) {
            return a - b;
          });

          $.each(qLevels, addLevel);

          addLevel(qLevels.length, 'auto')
            .addClass('vjs-selected')
            .attr('aria-checked', 'true');
        }
      }

      var airPlay = $container.find('.vjs-airplay-button');
      if (airPlay) {
        var airPlayTranslation = VuFind.translate('Open AirPlay menu');
        airPlay.attr('title', airPlayTranslation);
        airPlay.find('.vjs-control-text').html(airPlayTranslation);
      }
    });

    player.load();

    $('body')
      .off('click.videoQuality')
      .on('click.videoQuality', '.quality-selection li', function onClickQuality() {
        if ($container.find($(this)).length === 0) {
          return;
        }
        $container.find('.quality-selection li')
          .removeClass('vjs-selected')
          .prop('aria-checked', 'false');

        $(this)
          .addClass('vjs-selected')
          .attr('aria-checked', 'true');

        selectedBitrate = String($(this).data('bitrate'));
        var levels = player.qualityLevels();
        for (var i = 0; i < levels.length; i++) {
          levels[i].enabled = 'auto' === selectedBitrate || String(levels[i].height) === selectedBitrate;
        }
      });
  }

  function initVideoPopup(_container) {
    var container = typeof _container === 'undefined' ? $('body') : $(_container);
    var inline = container.find('[data-inline], [data-inline-iframe]');
    var parent;
    var embed = inline.length > 0;
    if (inline.length) {
      parent = 'inline-video';
      $('.inline-video-container').insertAfter($('.search-form-container'));
      $('.inline-video-container').removeClass('hidden');
    }

    var translations = {
      close: VuFind.translate('close'),
      next: VuFind.translate('Next Record'),
      previous: VuFind.translate('Previous Record'),
    };
    var scripts = {
      'videojs': 'vendor/video.min.js',
    };
    var subScripts = {
      'videojs-hotkeys': 'vendor/videojs.hotkeys.min.js',
      'videojs-quality': 'vendor/videojs-contrib-quality-levels.js',
      'videojs-airplay': 'vendor/silvermine-videojs-airplay.min.js',
    };
    container.find('[data-embed-video]').each(function initVideo() {
      var _ = $(this);
      var videoSources = _.data('videoSources');
      var posterUrl = _.data('posterUrl');
      _.finnaPopup({
        id: 'recordvideo',
        modal: '<video class="video-js vjs-big-play-centered video-popup" controls></video>',
        classes: 'video-popup',
        parent: parent,
        embed: embed,
        cycle: inline.length === 0,
        translations: translations,
        onPopupInit: function onPopupInit(t) {
          if (this.parent) {
            t.removeClass('active-video');
          }
        },
        onPopupOpen: function onPopupOpen() {
          var index = _.data('index');
          var warnings = $('.video-warning[data-index="' + index + '"]');
          if (this.parent) {
            $('.active-video').removeClass('active-video');
            this.currentTrigger().addClass('active-video');
            $('.video-warning').addClass('hidden');
            if (warnings[0]) {
              VuFind.observerManager.observe(
                'LazyImages',
                warnings[0].querySelectorAll('img[data-src]')
              );
              warnings.removeClass('hidden');
            }
          } else {
            this.content.css('height', '100%');
            if (warnings[0]) {
              var clone = warnings.clone();
              clone.appendTo(this.modalHolder);
              clone.removeClass('hidden');
              VuFind.observerManager.observe(
                'LazyImages',
                clone[0].querySelectorAll('img[data-src]')
              );
              setTimeout(function startFade() {
                clone.fadeOut(2000);
              }, 3000);
            }
          }

          finna.scriptLoader.loadInOrder(scripts, subScripts, function onScriptsLoaded() {
            if (document.querySelector('.video-popup video')) {
              finna.videoPopup.initVideoJs('.video-popup', videoSources, posterUrl);
            }
          });
        }
      });
    });
  }

  function initIframeEmbed(_container) {
    var container = typeof _container === 'undefined' ? $('body') : _container;
    var inline = container.find('[data-inline], [data-inline-iframe]');
    var parent;
    var embed = inline.length > 0;

    if (inline.length) {
      parent = 'inline-video';
      $('.inline-video-container').insertAfter($('.search-form-container'));
      $('.inline-video-container').removeClass('hidden');
    }
    var translations = {
      close: VuFind.translate('close'),
      next: VuFind.translate('Next Record'),
      previous: VuFind.translate('Previous Record'),
    };

    container.find('[data-embed-iframe]').each(function setIframes() {
      var _ = $(this);
      var source = _.is('a') ? _.attr('href') : _.data('link');
      _.finnaPopup({
        id: 'recordiframe',
        cycle: inline.length === 0,
        classes: 'finna-iframe',
        modal: '<div style="height:100%">' +
          '<iframe class="player finna-popup-iframe" frameborder="0" allowfullscreen></iframe>' +
          '</div>',
        parent: parent,
        embed: embed,
        translations: translations,
        onPopupInit: function onPopupInit(t) {
          if (this.parent) {
            t.removeClass('active-video');
          }
        },
        onPopupOpen: function onPopupOpen() {
          var index = _.data('index');
          var warnings = $('.video-warning[data-index="' + index + '"]');
          if (this.parent) {
            $('.active-video').removeClass('active-video');
            this.currentTrigger().addClass('active-video');
            $('.video-warning').addClass('hidden');
            if (warnings[0]) {
              warnings.removeClass('hidden');
              VuFind.observerManager.observe(
                'LazyImages',
                warnings[0].querySelectorAll('img[data-src]')
              );
            }
          } else {
            this.content.css('height', '100%');
            if (warnings[0]) {
              var clone = warnings.clone();
              clone.removeClass('hidden');
              clone.appendTo(this.modalHolder);
              VuFind.observerManager.observe(
                'LazyImages',
                clone[0].querySelectorAll('img[data-src]')
              );
              setTimeout(function startFade() {
                clone.fadeOut(2000);
              }, 3000);
            }
          }
          // If using Chrome + VoiceOver, Chrome crashes if vimeo player video settings button has aria-haspopup=true
          $('.vp-prefs .js-prefs').attr('aria-haspopup', false);
          var player = this.content.find('iframe');
          player.attr('src', this.adjustEmbedLink(source));
        }
      });
    });
  }

  var my = {
    initVideoPopup: initVideoPopup,
    initVideoJs: initVideoJs,
    initIframeEmbed: initIframeEmbed,
  };

  return my;
})();
