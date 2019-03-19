/*global VuFind, finna, module, videojs, L */
finna.imagePopup = (function finnaImagePopup() {

  function openPopup(trigger) {
    var ind = trigger.data('ind');
    var links = trigger.closest('.recordcover-holder').find('.image-popup');
    var link = links.filter(function filterLink() {
      return $(this).data('ind') === ind
    });
    link.click();
  }

  function initThumbnailNavi() {
    // Assign record indices
    var recordIndex = null;
    if ($('.paginationSimple').length) {
      recordIndex = $('.paginationSimple .index').text();
      $('.image-popup-trigger').each(function handlePopupTrigger() {
        $(this).data('recordInd', recordIndex++);
      });
    }

    //assignIndexes($('.image-popup-trigger'));

    //assignHandleCover($('.recordcovers'));

    //assignPopupTrigger($('.image-popup-trigger'));

    // Roll-over thumbnail images: update medium size record image and indices.
    //assignUpdateImage($('.image-popup-navi'));
  }

  function assignIndexes(element) {
    // Assign image indices
    var index = 0;
    console.log(element);
    element.each(function assignIndex() {
      var assignedImages = $(this).data('images');
      console.log($(this).data('images'));
      $(this).data('ind', index++);
      var recordIdx = $(this).closest('.recordcover-holder').find('.image-popup-trigger').data('recordInd');
      if (recordIdx) {
        $(this).data('recordInd', recordIdx);
      }
    });
  }

  function assignHandleCover(element) {
    // Assign image indices for individual images.
    element.each(function handleCover() {
      var thumbInd = 0;
      $(this).find('.image-popup').each(function handlePopup() {
        $(this).data('thumbInd', thumbInd++);
      });
    });
  }

  function assignPopupTrigger(element) {
    // Open image-popup from medium size record image.
    element.each(function handlePopupTrigger() {
      var links = $(this).closest('.recordcover-holder').find('.recordcovers .image-popup');
      var linkIndex = links.eq(0).data('ind');
      $(this).data('ind', linkIndex);
      $(this).data('thumbInd', 0);
    });
  }

  function assignUpdateImage(element, pagerCallback) {
    element.click(function updateImage(e) {
      var trigger = $(this).closest('.recordcover-holder').find('.image-popup-trigger');
      trigger.data('ind', $(this).data('ind'));
      trigger.data('thumbInd', $(this).data('thumbInd'));
      // Temporarily adjust the image so that the user sees something is happening
      var img = trigger.find('img');
      img.css('opacity', 0.5);
      img.one('load', function onLoadImage() {
        img.css('opacity', '');
        $('.image-dimensions').text('(' + (this.naturalWidth + ' X ' + this.naturalHeight + ')'));
      });
      img.attr('src', $(this).attr('href'));
      var textContainers = $(this).closest('.record-image-container').find('.image-details-container');
      textContainers.addClass('hidden');
      textContainers.filter('[data-img-index="' + $(this).data('imgIndex') + '"]').removeClass('hidden');
      //initRecordImage();
      if (typeof pagerCallback !== 'undefined') {
        pagerCallback($(this).data('ind'));
      }
      e.preventDefault();
    });
  }

  // Copied from finna-mylist.js to avoid dependency
  function getActiveListId() {
    return $('input[name="listID"]').val();
  }

  function initRecordImage() {
    // Collect data for all image-popup triggers on page.
    var urls = $('.image-popup-trigger').data('imagesData').map(function mapPopupTriggers() {
      // result list
      
      /*if (!id.length) {
        // gallery view
        id = $(this).closest('.record-container').find('.hiddenId');
        if (!id.length) {
          // record page
          id = $('.record .hiddenId');
        }
      }
      if (!id.length) {
        // my list
        id = $(this).closest('.myresearch-row').find('.hiddenId');
      }

      if (!id.length) {
        return;
      }
      id = id.val();*/

      var ind = $(this).data('ind');
      console.log(ind);
      var thumbInd = $(this).data('thumbInd');
      var recordInd = $(this).data('recordInd');

      return {
        src: src,
        href: $(this).attr('href'),
        ind: ind,
        recordInd: recordInd
      }
    }).toArray();

    $('.image-popup-trigger').each(function initPopup() {
      var id = $(this).attr('data-record-id');
      var src = VuFind.path + '/AJAX/JSON?method=getImagePopup&id=' + encodeURIComponent(id) + '&index=' + thumbInd;
      if (typeof publicList !== 'undefined') {
        src += '&publicList=1';
      }

      var listId = getActiveListId();
      if (typeof listId !== 'undefined') {
        src += '&listId=' + listId;
      }
      var item = [{
        src: src,

      }];
      $(this).magnificPopup({
        items: urls,
        index: $(this).data('ind'),
        type: 'ajax',
        tLoading: '',
        tClose: VuFind.translate('close'),
        removalDelay: 200,
        ajax: {
          cursor: '',
          settings: {
            dataType: 'json'
          }
        },
        callbacks: {
          parseAjax: function onParseAjax(mfpResponse) {
            mfpResponse.data = mfpResponse.data.data.html;
          },
          ajaxContentAdded: function onAjaxContentAdded() {
            var popup = $('.imagepopup-holder');
            var type = popup.data("type");
            var id = popup.data("id");
            var recordIndex = $.magnificPopup.instance.currItem.data.recordInd;
            VuFind.lightbox.bind('.imagepopup-holder');
            var zoomable = $('#leaflet-map-image').data('large-image-layout') && $('#leaflet-map-image').data('enable-image-popup-zoom');
            if (zoomable) {
              initImageZoom();
            } else {
              $('.image img').one('load', function onLoadImg() {
                $('.imagepopup-holder .image').addClass('loaded');
                initDimensions();
                $(this).attr('alt', $('#popup-image-title').html());
                $(this).attr('aria-labelledby', 'popup-image-title');
                if ($('#popup-image-description').length) {
                  $(this).attr('aria-describedby', 'popup-image-description');
                }
              }).each(function triggerImageLoad() {
                if (this.complete) {
                  $(this).load();
                }
              });
            }

            // Prevent navigation button CSS-transitions on touch-devices
            if (finna.layout.isTouchDevice()) {
              $('.mfp-container .mfp-close, .mfp-container .mfp-arrow-right, .mfp-container .mfp-arrow-left').addClass('touch-device');

              $('.mfp-container').swipe({
                allowPageScroll: 'vertical',
                // Generic swipe handler for all directions
                swipeRight: function onSwipeRight(/*event, phase, direction, distance, duration*/) {
                  $('.mfp-container .mfp-arrow-left').click();
                },
                swipeLeft: function onSwipeLeft(/*event, direction, distance, duration*/) {
                  $('.mfp-container .mfp-arrow-right').click();
                },
                threshold: 75,
                cancelThreshold: 20
              });
            }

            // Record index
            if (recordIndex) {
              var recIndex = $('.imagepopup-holder .image-info .record-index');
              var recordCount = $(".paginationSimple .total").text();
              recIndex.find('.index').html(recordIndex);
              recIndex.find('.total').html(recordCount);
              recIndex.show();
            }

            // Image copyright information
            $('.imagepopup-holder .image-rights .copyright-link a').click(function onClickCopyright() {
              var mode = $(this).data('mode') === '1';

              var moreLink = $('.imagepopup-holder .image-rights .more-link');
              var lessLink = $('.imagepopup-holder .image-rights .less-link');

              moreLink.toggle(!mode);
              lessLink.toggle(mode);

              $('.imagepopup-holder .image-rights .copyright').toggle(mode);

              return false;
            });

            // load feedback modal
            if ($('.imagepopup-holder .feedback-record')[0] || $('.imagepopup-holder .save-record')[0]) {
              $('.imagepopup-holder .feedback-record, .imagepopup-holder .save-record').click(function onClickActionLink(/*e*/) {
                $.magnificPopup.close();
              });
            }

            // Load book description
            var summaryHolder = $('.imagepopup-holder .summary');
            if (type === 'marc') {
              var url = VuFind.path + '/AJAX/JSON?method=getDescription&id=' + id;
              $.getJSON(url)
                .done(function onGetDescriptionDone(response) {
                  var data = response.data.html;
                  if (data.length > 0) {
                    summaryHolder.find('> div p').html(data);
                    finna.layout.initTruncate(summaryHolder);
                    summaryHolder.removeClass('loading');
                  }
                })
                .fail(function onGetDescriptionFail(/*response, textStatus*/) {
                  summaryHolder.removeClass('loading');
                });
            } else {
              finna.layout.initTruncate(summaryHolder);
              summaryHolder.removeClass('loading');
            }

            // Init embedding
            finna.layout.initIframeEmbed(popup);
            initVideoPopup(popup);
          },
          close: function closePopup() {
            if ($("#video").length){
              videojs('video').dispose();
            }
          }
        }
      });
    });
  }

  function initVideoPopup(_container) {
    var container = typeof _container === 'undefined' ? $('body') : _container;

    container.find('a[data-embed-video]').click(function openVideoPopup(e) {
      var videoSources = $(this).data('videoSources');
      var posterUrl = $(this).data('posterUrl');

      var mfp = $.magnificPopup.instance;
      mfp.index = 0;
      mfp.gallery = {enabled: false};
      mfp.items[0] = {
        src: "<div class='video-popup'><video id='video' class='video-js vjs-big-play-centered' controls></video></div>",
        type: 'inline'
      };
      $(".mfp-arrow-right, .mfp-arrow-left").addClass("hidden");
      mfp.updateItemHTML();

      // Use a fairly small buffer for faster quality changes
      videojs.Hls.GOAL_BUFFER_LENGTH = 10;
      videojs.Hls.MAX_GOAL_BUFFER_LENGTH = 20;
      var player = videojs('video');

      player.ready(function onReady() {
        this.hotkeys({
          enableVolumeScroll: false,
          enableModifiersForNumbers: false
        });
      });

      player.src(videoSources);
      player.poster(posterUrl);
      player.load();

      e.preventDefault();
    });
  }

  function resolveRecordImageSize() {
    if ($('.image-popup-navi').length > 1) {
      initThumbnailNavi();
      //initRecordImage();
    } else {
      $('.image-popup-trigger img').one('load', function onLoadImg() {
        if (this.naturalWidth > 10 && this.naturalHeight > 10) {
          initThumbnailNavi();
          //initRecordImage();
        } else {
          $(this).closest('.recordcover-holder').hide();
          $('.access-rights p').first().hide();
          $('.image-rights').hide();
          $('.media-left > .organisation-menu').hide();
          if ( $('.access-rights').has('.more-link') ) {
            $('.access-rights > .more-link').hide();
          }
        }
      });
    }
  }

  function initImageZoom() {
    var img = new Image();
    img.src = $('.imagepopup-holder .original-image-url').attr('href');
    img.onload = function onLoadImg() {
      $('.imagepopup-holder .image').addClass('loaded');
      initDimensions();
      $('.mfp-content').removeClass('full-size');
      $(this).attr('alt', $('#popup-image-title').html());
      $(this).attr('aria-labelledby', 'popup-image-title');
      if ($('#popup-image-description').length) {
        $(this).attr('aria-describedby', 'popup-image-description');
      }
      var map = L.map('leaflet-map-image', {
        minZoom: 1,
        maxZoom: 6,
        center: [0, 0],
        zoomControl: false,
        zoom: 1,
        crs: L.CRS.Simple,
        maxBoundsViscosity: 0.9,
        dragging: true,
      });
      $('.zoom-in').click(function zoomIn() {
        map.setZoom(map.getZoom() + 1)
      });
      $('.zoom-out').click(function zoomOut() {
        map.setZoom(map.getZoom() - 1)
      });
      $('.zoom-reset').click(function zoomReset() {
        map.setZoom(1)
      });
      var h = this.naturalHeight;
      var w = this.naturalWidth;
      if (h === 10 && w === 10) {
        $('#leaflet-map-image').hide();
        return;
      }
      var imageNaturalSizeZoomLevel = 3;
      if (h < 2000 && w < 2000) {
        imageNaturalSizeZoomLevel = 2;
      }
      if (h < 1000 && w < 1000) {
        imageNaturalSizeZoomLevel = 1;
      }
      var southWest = map.unproject([0, h], imageNaturalSizeZoomLevel);
      var northEast = map.unproject([w, 0], imageNaturalSizeZoomLevel);
      var bounds = new L.LatLngBounds(southWest, northEast);
      var overlay = L.imageOverlay(img.src, bounds);
      map.flyToBounds(bounds, {animate: false});
      map.setMaxBounds(bounds);
      overlay.addTo(map);
      map.invalidateSize();
      map.on('zoomend', function adjustPopupSize() {
        if (map.getZoom() > 1) {
          $('.mfp-content').addClass('full-size');
        } else {
          $('.mfp-content').removeClass('full-size');
          map.flyToBounds(bounds, {animate: false});
        }
        map.invalidateSize();
      });
    }
  }

  function initDimensions() {
    if (typeof $('.open-link a').attr('href') !== 'undefined') {
      var img = document.createElement('img')
      img.src = $('.open-link a').attr('href');
      img.onload = function onLoadImg() {
        if (this.width === 10 && this.height === 10) {
          $('.open-link').hide();
        }
        else {
          $('.open-link .image-dimensions').text( '(' + this.width + ' X ' + this.height + ')')
        }
      }
    }
  }

  function initImageCheck() {
    $('.image-popup-trigger img').each(function setupImagePopup() {
      $(this).one('load', function onLoadImage() {
        // Don't hide anything if we have multiple images
        var navi = $(this).closest('.image-popup-navi');
        if (navi && navi.length > 1) {
          return;
        }
        if (this.naturalWidth && this.naturalWidth === 10 && this.naturalHeight === 10) {
          $(this).parent().addClass('no-image');
          $(this).closest('a.image-popup-trigger').unbind('click');
          $('.record.large-image-layout').addClass('no-image-layout').removeClass('large-image-layout');
          $('.large-image-sidebar').addClass('visible-xs visible-sm');
          $('.record-main').addClass('mainbody left');
          var href = $(this).parent().attr('href');
          $(this).parent().attr({'href': href.split('#')[0], 'title': ''});
          $(this).parents('.grid').addClass('no-image');
          $('.rating-stars').addClass('hidden-xs');
          $('.image-rights-default').addClass('hidden');
        }
      }).each(function loadImage() {
        if (this.complete) {
          $(this).load();
        }
      });
    });
  }

  function init() {
    console.log(1);
    if (module !== 'record') {

      //initThumbnailNavi();
      //initRecordImage();
    } else {
      //resolveRecordImageSize();
      //initDimensions();
    }

    //if (location.hash === '#image') {
    //  openPopup($('.image-popup-trigger'));
    //}
    $.extend(true, $.magnificPopup.defaults, {
      tLoading: VuFind.translate('loading') + '...'
    });
    //initImageCheck();
  }

  var my = {
    init: function e() {
      
    },
    //initThumbnailNavi: initThumbnailNavi,
    //assignUpdateImage: assignUpdateImage
  };

  return my;
})();


function init() {
  area = $('.recordcovers.paginate');
  var trigger = area.closest('.recordcover-holder').find('.image-popup-trigger'); //Löydetty pääobjekti
  trigger.data('images', paginatedImages).attr('data-record-id', trigger.closest('.hiddenId').val());
  area.removeClass('paginate').addClass('paginated');
  var elementBaseObject = $(elementBase).clone();
  parentElement = elementBaseObject.find('.finna-element-track');
  var tmpInfoBar = $(infoBar);
  pager = tmpInfoBar.find('.paginator-pager');
  area.empty();
  area.append($(leftButton));
  area.append(elementBaseObject);
  area.append($(rightButton));
  area.append(tmpInfoBar);
  setPagerInfo(0);
  bindEvents();
  loadPage(0, parentElement);
  setParent(area);
}

function bindEvents() {
  $(document).on('touchstart touchmove mousedown mousemove', '.finna-element-track', handleMouseDrag);
  $(document).on('mouseup touchend', handleMouseDrag);

  $(document).on('click', '.image-popup', function setCurrentImage() {
    setParent($(this));
    setMagnificPopup($(this));
  });

  $(document).on('click', '.image-preview', function setLeafletImage(){

  });


  $(document).on('click', '.left-button', function toLeft(){
    setParent($(this));
    loadPage(-1, $(this).siblings('.paginator-mask').find('.finna-element-track'));
  });
  $(document).on('click', '.right-button', function toRight(){
    setParent($(this));
    loadPage(1, $(this).siblings('.paginator-mask').find('.finna-element-track'));
  });
}

function setMagnificPopup(element) {
  var trigger = getHostElement(element);
  var ind = element.data('ind');
  trigger.attr('currentImageIndex', ind);
  var id = trigger.attr('data-record-id');
  var src = VuFind.path + '/AJAX/JSON?method=getImagePopup&id=' + encodeURIComponent(id) + '&index=' + ind;
  var currentElement = {
    src: src,
    index: ind,
    href: element.attr('href')
  };

  trigger.magnificPopup({
    items: currentElement,
    type: 'ajax',
    tLoading: '',
    tClose: "sulje",
    removalDelay: 200,
    ajax: {
      cursor: '',
      settings: {
        dataType: 'json'
      }
    },
    callbacks: {
      parseAjax: function onParseAjax(mfpResponse) {
        mfpResponse.data = mfpResponse.data.data.html;
      },
      ajaxContentAdded: function onAjaxContentAdded() {
        //Lets give popup info that it needs for perfect pagination to work
        var paginatorTrack = $(trigger.closest('.recordcover-holder').find('.recordcovers.paginated').html()).clone(); //Lets create a duplicate from paginator track
        paginatorTrack.find(".paginator-info").remove();
        var popupPaginator = $('.imagepopup-holder').find('.finna-image-pagination');
        popupPaginator.append(paginatorTrack);
        var popupTrack = paginatorTrack.find('.finna-element-track');
        popupTrack.addClass('is-popup-track');
        popupTrack.empty();
        popupPaginator.addClass('recordcovers record paginated');

        map = L.map('leaflet-map-image', {
          minZoom: 1,
          maxZoom: 6,
          center: [0, 0],
          zoomControl: false,
          zoom: 6,
          crs: L.CRS.Simple,
          dragging: true,
          maxBoundsViscosity: 0.9,
        });

        map.on('zoomend', function adjustPopupSize() {
          map.invalidateSize();
        });

        $('.zoom-in').click(function zoomIn() {
          map.setZoom(map.getZoom() + 1)
        });
        $('.zoom-out').click(function zoomOut() {
          map.setZoom(map.getZoom() - 1)
        });
        $('.zoom-reset').click(function zoomReset() {
          map.setZoom(1)
        });

        $('#leaflet-map-image img').one('load', function onLoadLeafletImage(){
          $(this).closest('.image').addClass('loaded');
        }).each(function triggerImageLoad() {
          if (this.complete) {
            $(this).load();
          }
        });
        
        loadPage(0, popupTrack);
      },
      close: function closePopup() {
        if ($("#video").length){
          //videojs('video').dispose();
        }
      }
    }
  });
}

function setParent(element) {
  var tmpParent = getHostElement(element);

  if (tmpParent.length !== 0) {
    if (typeof currentHostElement === 'undefined' || tmpParent.attr('data-record-id') !== currentHostElement.attr('data-record-id')) {
      currentHostElement = tmpParent;
      paginatedImages = tmpParent.data('images');
    }
  }
}

function getHostElement(element) {
  return element.closest('.recordcover-holder').find('.image-popup-trigger');
}

var canDrag = false;
var _oldPosX = 0;

function handleMouseDrag(e) {
  var type = e.type;

  switch (type) {
  case 'mousedown':
    // Load images here 
    e.preventDefault();
    setParent($(e.target));
    canDrag = true;
    _oldPosX = e.originalEvent.clientX;
    break;
  case 'touchstart':
    // Load images here 
    e.preventDefault();
    setParent($(e.target));
    canDrag = true;
    _oldPosX = e.originalEvent.touches[0].clientX;
    break;
  case 'mousemove':
    // Our finger/mouse is moving, so lets get the direction where to move
    if (canDrag) {
      setParent($(e.target));
      checkLoadableContent(e.originalEvent.clientX, $(this));
    }
    break;
  case 'touchmove':
    // Our finger/mouse is moving, so lets get the direction where to move
    if (canDrag) {
      e.stopPropagation();
      e.preventDefault();
      setParent($(e.target));
      checkLoadableContent(e.originalEvent.touches[0].clientX, $(this));
    }
    break;
  case 'mouseup':
  case 'touchend':
    //Unload images
    canDrag = false;
    break;
  }
}

var _threshold = 40;

function checkLoadableContent(newPosX, element) {
  if (canDrag) {
    var difference = (_oldPosX - newPosX);

    if (difference > _threshold) {
      loadOneImage(1, element.closest('.finna-element-track'));
      _oldPosX = newPosX;
    } else if (difference < -_threshold) {
      _oldPosX = newPosX;
      loadOneImage(-1, element.closest('.finna-element-track'));
    }
  }
}

var offSet = 0;
/*function loadOneImage(direction, trackElement) {
  var searchIndex = 0;
  var currentPaginatedImages = paginatedImages;

  if (typeof currentPaginatedImages === 'undefined' || currentPaginatedImages.length === 0) {
    return;
  }
  offSet += direction;
  // On direction 1, we are going towards end of the array and vice versa
  if (direction > 0) {
    searchIndex = 4 //Lets say we are looking at the 5 first five images so these are our indexes
  } else {
    searchIndex = 0; //this is our first images we are looking for
  }

  searchIndex += offSet;
  if (searchIndex < 0) {
    offSet = 0;
    return;
  } else if (searchIndex > currentPaginatedImages.length - 1) {
    offSet = currentPaginatedImages.length - 5;
    return;
  }

  if (direction < 0) {
    trackElement.find('a:last').remove();
  } else if (direction > 0) {
    trackElement.find('a:first').remove();
  }

  if (currentPaginatedImages.length > 5 + offSet) {
    //We are allowed to do stuff now, otherwise there is less or equal to five images in array, Lets get the last index image
    if (typeof currentPaginatedImages[searchIndex] !== 'undefined') {
      createImageObject(currentPaginatedImages[searchIndex], (direction === 1), trackElement);
    }
  }
}*/

//Lets take the images data
function setPagerInfo(index) {
  var max = paginatedImages.length;
  var text = (index + 1) + "/" + max;
  pager.html(text);
}

/*function loadPage(direction, trackElement) {
  trackElement.empty();
  var oldOffset = offSet;
  var currentPaginatedImages = paginatedImages;

  if (typeof currentPaginatedImages === 'undefined' || currentPaginatedImages.length === 0) {
    return;
  }

  //We need to add 4 every time we load a new page
  if (direction < 0) {
    offSet -= 4;
  } else if (direction > 0) {
    offSet += 4;
  }

  var firstImage = 0 + offSet;
  var lastImage = 4 + offSet;

  if (firstImage < 0) {
    offSet = 0;
    firstImage = 0;
    lastImage = 4;
  }

  if (lastImage > currentPaginatedImages.length - 1) {
    lastImage = currentPaginatedImages.length - 1;
    offSet = oldOffset;
  }

  var i = firstImage;
  var k = lastImage;

  if (currentPaginatedImages.length > 0) {
    for (;i <= k; i++) {
      createImageObject(currentPaginatedImages[i], true, trackElement);
    }
  }
}*/

function createImageObject(link, append, trackElement) {
  if (typeof link === 'undefined') {
    return;
  }

  var tmpImg = $(imageElement).clone();

  if (trackElement.hasClass('is-popup-track')) {
    tmpImg.addClass('popup-leaflet');
  }

  tmpImg.find('img').attr('src', link.small);
  tmpImg.data('ind', link.index);
  tmpImg.attr('href', link.medium);

  if (append === true) {
    trackElement.append(tmpImg);
  } else {
    trackElement.prepend(tmpImg);
  }

  tmpImg.append($('<i class="fa fa-spinner fa-spin"/>'));

  initPopupTrigger(tmpImg);
}

function initPopupTrigger(element) {
  element.click(function initPopupData(e) {
    e.preventDefault();
    if ($(this).hasClass('popup-leaflet')) {
      setZoomedImage($(this));
    } else {
      var trigger = $(this).closest('.recordcover-holder').find('.image-popup-trigger');
      trigger.data('ind', $(this).data('ind'));
      // Temporarily adjust the image so that the user sees something is happening
      var img = trigger.find('img');
      img.css('opacity', 0.5);
      img.one('load', function onLoadImage() {
        img.css('opacity', '');
      });
      img.attr('src', $(this).attr('href'));
      var textContainers = $(this).closest('.record-image-container').find('.image-details-container');
      textContainers.addClass('hidden');
      textContainers.filter('[data-img-index="' + $(this).data('imgIndex') + '"]').removeClass('hidden');
      setPagerInfo($(this).data('ind'));
    }
  });
}

var oldLeafletImage;

function setZoomedImage(element) {
  var img = new Image();
  img.src = element.attr('href');
  console.log("Up");
  img.onload = function onLoadImg() {
    $(this).attr('alt', $('#popup-image-title').html());
    var h = this.naturalHeight;
    var w = this.naturalWidth;

    if (h === 10 && w === 10) {
      $('#leaflet-map-image').hide();
      return;
    }
    var imageNaturalSizeZoomLevel = 3;
    if (h < 2000 && w < 2000) {
      imageNaturalSizeZoomLevel = 2;
    }
    if (h < 1000 && w < 1000) {
      imageNaturalSizeZoomLevel = 1;
    }
    var southWest = map.unproject([0, h], imageNaturalSizeZoomLevel);
    var northEast = map.unproject([w, 0], imageNaturalSizeZoomLevel);
    var bounds = new L.LatLngBounds(southWest, northEast);
    var overlay = L.imageOverlay(img.src, bounds);
    if (typeof oldLeafletImage !== 'undefined') {
      map.removeLayer(oldLeafletImage);
    }
    /*map.flyToBounds(bounds, {animate: false});
    map.setMaxBounds(bounds);*/
    map.flyToBounds(bounds, {animate: false});
    map.setMaxBounds(bounds);
    overlay.addTo(map);
    oldLeafletImage = overlay;
    map.fitBounds(bounds, {padding: [50, 50]});
    map.invalidateSize();
  }
}
