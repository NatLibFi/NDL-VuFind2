/* videojs, VuFind */
finna.audioPlayer = (() => {
  let audioPlayers = [];

  const scripts = {
    'videojs': 'vendor/video.min.js',
  };

  const subScripts = {
    'videojs-hotkeys': 'vendor/videojs.hotkeys.min.js',
    'videojs-quality': 'vendor/videojs-contrib-quality-levels.js',
    'videojs-airplay': 'vendor/silvermine-videojs-airplay.min.js',
  };

  function initPlayer(audio) {
    const audioPlayer = videojs(
      audio.attr('id'),
      { controlBar: { volumePanel: false, muteToggle: false } },
      function onVideoJsInited() {}
    );
    audioPlayers.push(audioPlayer);
    VuFind.listen('lightbox.closing', disposePlayers);
  }

  /**
   * Initialize audio buttons
   */
  function initPlayers() {
    document.querySelectorAll('.audio-accordion .audio-item-wrapper').forEach(element => {
      element.classList.add('done');
      element.querySelector('.play').addEventListener('click', () => {
        element.querySelector('.audio-player-wrapper').classList.remove('hide');
        const audioPlayer = element.querySelector('audio');
      }, {once: true})
    });
    $('.audio-accordion .audio-item-wrapper').each(function initAudioPlayer() {
      var self = $(this);
      self.addClass('done');
      var play = self.find('.play');
      var source = self.find('source');
      play.one('click', function onPlay() {
        console.log('test');
        finna.scriptLoader.load(
          scripts,
          () => {
            finna.scriptLoader.load(
              subScripts,
              function onVideoJsLoaded() {
                self.find('.audio-player-wrapper').removeClass('hide');
                var audio = self.find('audio');
                audio.removeClass('hide').addClass('video-js');
                source.attr('src', source.data('src'));
                initPlayer(audio);
                play.remove();
                self.find('.vjs-play-control').focus();
              }
            );
          }
        );
      });
      play.on('keydown', function onKeyDown(e) {
        if (e.which === 13 || e.which === 32) {
          e.preventDefault();
          play.trigger('click');
        }
      });
    });
    $('finna-video').on('keydown', function onKeyDown(e) {
      if (e.which === 13 || e.which === 32) {
        e.preventDefault();
        $(this).trigger('click');
      }
    });
  }

  function disposePlayers() {
    console.log('disposing');
    audioPlayers.forEach((audio) => {audio.dispose()});
    audioPlayers = [];
  }

  return {
    initPlayers,
    initPlayer,
    disposePlayers
  };
})();
