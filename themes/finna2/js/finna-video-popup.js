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

  var my = {
    initVideoJs: initVideoJs
  };

  return my;
})();
