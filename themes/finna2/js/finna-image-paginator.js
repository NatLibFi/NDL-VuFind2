/* global finna, VuFind, L, videojs */
finna.imagePaginator = (function imagePaginator() {
  var imageElement = '<a draggable="false" href="" class="image-popup image-popup-navi hidden-print"></a>';
  var previousRecordButton = '<button class="popup-arrow popup-left-arrow previous-record" type="button"><i class="fa fa-chevron-left" aria-hidden="true"></i></button>';
  var nextRecordButton = '<button class="popup-arrow popup-right-arrow next-record" type="button"><i class="fa fa-chevron-right" aria-hidden="true"></i></button>';
  var paginatorIndex = 0;
  var timeOut = null;
  var loadPageTimeOut = null;

  var defaults = {
    recordId: 0,
    iconlabelClass: 'default-icon',
    maxRows: 3,
    imagesPerPage: 8,
    imagesOnMobile: 6,
    imagesOnPopup: 10,
    imagesOnNormal: 8,
    imagesPerRow: 8,
    enableImageZoom: false,
    recordType: 'default-type'
  };

  FinnaPaginator.prototype.getNextPaginator = function getNextPaginator(direction) {
    var searchIndex = this.paginatorIndex + direction;
    var foundPaginator = $('.image-popup-trigger[paginator-index="' + searchIndex + '"');

    if (foundPaginator.length) {
      $.magnificPopup.close();
      if (foundPaginator.hasClass('no-image')) {
        foundPaginator.siblings('.hidden-trigger').click();
      } else {
        foundPaginator.click();
      }
    } else {
      $.magnificPopup.close();
    }
  }

  function FinnaPaginator(images, paginatedArea, settings, isList) {
    var _ = this;

    _.paginatorIndex = paginatorIndex;
    _.isSet = true;
    _.isList = isList;
    _.images = images;
    _.root = $(paginatedArea);
    _.root.removeClass('paginate');

    _.trigger = _.root.find('.image-popup-trigger');
    _.trigger.attr('paginator-index', paginatorIndex++);

    _.settings = $.extend({}, defaults, settings);
    _.setMaxImages(_.settings.imagesOnNormal);
    // Index for loading correct images
    _.offSet = 0;

    // Needed references
    _.imageHolder = null;
    _.nonZoomableHolder = null;
    _.imageDetail = _.root.find('.recordcover-image-detail .image-description');
    _.moreBtn = null;
    _.lessBtn = null;
    _.pagerInfo = null;
    _.leftBtn = null;
    _.rightBtn = null;
    _.imagePopup = null;
    _.leafletHolder = null;
    _.leafletLoader = null;

    _.openImageIndex = 0;
  }

  /**
   * Function to create proper elements for the paginator
   */
  FinnaPaginator.prototype.init = function init() {
    var _ = this;

    _.setReferences(_.root.find('.recordcovers'));
    _.imagePopup = $(imageElement).clone();

    if (!_.isList) {
      _.moreBtn = _.root.find('.show-more-images');
      _.lessBtn = _.root.find('.show-less-images');
      toggleButtons(_.moreBtn, _.lessBtn);
    }

    _.setEvents();

    if (!_.isList) {
      _.loadPage(0);
      _.setTrigger(_.imageHolder.find('a:first'));
    }
  }

  FinnaPaginator.prototype.setReferences = function setReferences(covers, isPopup) {
    var _ = this;
    covers.addClass('paginated');

    _.imageHolder = covers.find('.finna-element-track');
    _.leftBtn = covers.find('.left-button');
    _.rightBtn = covers.find('.right-button');
    if (typeof isPopup === 'undefined' || isPopup === false) {
      _.pagerInfo = covers.find('.paginator-info');
    } else {
      _.pagerInfo = $('.mfp-container').find('.paginator-info');
    }
    _.imageHolder.empty();

    if (_.images.length < 2) {
      covers.hide();
      _.pagerInfo.hide();
    }

    if (_.images.length < _.settings.imagesPerRow) {
      $('.recordcovers-more').hide();
    }
  }

  FinnaPaginator.prototype.setEvents = function setEvents() {
    var _ = this;

    if (!_.isList) {
      _.leftBtn.click(function loadImages() {
        _.loadPage(-1);
      });
      _.rightBtn.click(function loadImages() {
        _.loadPage(1);
      });
      _.moreBtn.click(function setImages(){
        toggleButtons(_.lessBtn, _.moreBtn);
        _.loadPage(0, null, _.settings.imagesPerRow * _.settings.maxRows);
      });
      _.lessBtn.click(function setImages(){
        toggleButtons(_.moreBtn, _.lessBtn);
        _.loadPage(0, null, _.settings.imagesPerRow);
      });

      _.trigger.on('wheel', function scrollImage(e) {
        var delta = Math.sign(+e.originalEvent.deltaY);
        e.preventDefault();
        _.onScrollEvent(delta);
      });

      _.trigger.find('img').swipe({
        swipeLeft: function swipeLeft(/*event, phase, direction, distance, duration*/) {
          event.preventDefault();
          _.onScrollEvent(1);
        },
        swipeRight: function swipeRight(/*event, phase, direction, distance, duration*/) {
          event.preventDefault();
          _.onScrollEvent(-1);
        },
        threshold: 75,
        cancelThreshold: 20,
        excludedElements: '.noSwipe a'
      });
    }

    _.imageHolder.on('wheel', function scrollPages(e){
      e.preventDefault();
      var delta = Math.sign(e.originalEvent.deltaY);
      _.onScrollPopups(delta);
    });

    _.imagePopup.on('click', function setTriggerEvents(e){
      e.preventDefault();
      _.setTrigger($(this));
    });

    _.imageHolder.swipe({
      swipeLeft: function swipeLeft(/*event, phase, direction, distance, duration*/) {
        event.preventDefault();
        _.onScrollPopups(1);
      },
      swipeRight: function swipeRight(/*event, phase, direction, distance, duration*/) {
        event.preventDefault();
        _.onScrollPopups(-1);
      },
      threshold: 75,
      cancelThreshold: 20,
      excludedElements: '.noSwipe a'
    });
  }

  function toggleButtons(show, hide) {
    show.show();
    hide.hide();
  }

  /**
   * Function to set left and right button to correct states
   */
  FinnaPaginator.prototype.setButtons = function setButtons() {
    var _ = this;

    if (_.images.length <= _.settings.imagesPerPage || _.offSet === _.images.length - 1) {
      _.rightBtn.attr('disabled', true);
    } else {
      _.rightBtn.removeAttr('disabled');
    }

    if (_.images.length <= _.settings.imagesPerPage || _.offSet < 1) {
      _.leftBtn.attr('disabled', true);
    } else {
      _.leftBtn.removeAttr('disabled');
    }
  }

  FinnaPaginator.prototype.onScrollEvent = function onScrollEvent(delta) {
    var _ = this;
    var nextImage = +_.openImageIndex + delta;
    if (nextImage > _.images.length - 1 || nextImage < 0) {
      return;
    }

    // Try to find next available image, if not found then load next page and try again
    var foundImage = _.imageHolder.find('a[index="' + nextImage + '"]');
    if (foundImage.length) {
      foundImage.click();
    } else {
      _.loadPage(0, nextImage);
      _.imageHolder.find('a[index="' + nextImage + '"]').click();
    }
  }

  FinnaPaginator.prototype.onScrollPopups = function onScrollPopups(delta) {
    var _ = this;
    if (_.images.length < _.settings.imagesPerPage) {
      return;
    }
    if (loadPageTimeOut === null) {
      loadPageTimeOut = setTimeout(function loadPageWheel(){
        loadPageTimeOut = null;
      }, 500);
      _.loadPage(delta);
    }
  }

  /**
   * Function to set correct info for page info
   */
  FinnaPaginator.prototype.setPagerInfo = function setPagerInfo() {
    var _ = this;
    _.pagerInfo.find('.image-index').html(+_.openImageIndex + 1 + " / " + _.images.length);
  }

  /**
   * Function to create the track which holds smaller images
   */
  FinnaPaginator.prototype.createPopupTrack = function createPopupTrack(popupTrackArea, isPopup) {
    var _ = this;
    var covers = _.root.find('.recordcovers').clone(true);
    _.setReferences(covers, isPopup);

    if (_.isList) {
      covers.removeClass('mini-paginator');
      _.leftBtn.off('click').click(function loadImages(){
        _.loadPage(-1);
      });
      _.rightBtn.off('click').click(function loadImages(){
        _.loadPage(1);
      });
    }
    popupTrackArea.append(covers);
    if (_.images.length < 2) {
      popupTrackArea.hide();
    }
    _.loadPage(0, _.openImageIndex);
  }

  /**
   * Sets the current record index inside list view to the modal
   */
  FinnaPaginator.prototype.setRecordIndex = function setRecordIndex() {
    var _ = this;
    if ($('.paginationSimple .index').length) {
      var total = $('.paginationSimple .total').html();
      var current = +$('.paginationSimple .index').html() + _.paginatorIndex;
      _.pagerInfo.siblings('.record-index').find('.total').html(current + " / " + total);
    }
  }

  /**
   * Function to load image popup trigger image, checks also if the image exists or do we have to hide something
   * When the image does not exist, we remove the trigger event and let the user navigate directly to record
   */
  FinnaPaginator.prototype.changeTriggerImage = function changeTriggerImage(imagePopup) {
    var _ = this;
    var img = _.trigger.find('img');
    img.attr('data-src', imagePopup.attr('href')).css('opacity', 0.5);
    img.attr('alt', imagePopup.find('img').attr('alt'));

    img.one('load', function onLoadImage() {
      img.css('opacity', '');
      if (this.naturalWidth && this.naturalWidth === 10 && this.naturalHeight === 10) {
        _.trigger.addClass('no-image');
        if (_.isList) {
          if (_.images.length < 2) {
            _.settings.enableImageZoom = false;
          }
          _.trigger.off('click');
          var oldTrigger = _.trigger;
          _.trigger = _.trigger.siblings('.hidden-trigger');
          _.setTrigger(imagePopup);
          _.trigger = oldTrigger;
          $(this).parents('.grid').addClass('no-image');
        }
        if (!_.isList && _.images.length <= 1) {
          _.root.closest('.media-left').addClass('hidden-xs').find('.organisation-menu').hide();
          _.root.css('display', 'none');
          _.root.siblings('.image-details-container').hide();
          $('.record.large-image-layout').addClass('no-image-layout').removeClass('large-image-layout');
          $('.large-image-sidebar').addClass('visible-xs visible-sm');
          $('.record-main').addClass('mainbody left');
        }
      } else if (_.trigger.hasClass('no-image')) {
        _.trigger.removeClass('no-image');
      }
    });
    if (!_.isList) {
      $('.image-details-container').addClass('hidden');
      $('.image-details-container[data-img-index="' + imagePopup.attr('index') + '"]').removeClass('hidden');
    }

    _.imageDetail.html(imagePopup.data('description'));

    if (_.isList) {
      img.unveil(100, function tryMasonry(){
        $(this).load(function rearrange(){
          if (finna.layout.getMasonryState()) {
            $('.result-view-grid .masonry-wrapper').masonry('layout');
          }
        });
      });
    } else {
      img.unveil();
    }
  }

  /**
   * Function to clear track of images and load new amount of images
   */
  FinnaPaginator.prototype.loadPage = function loadPage(direction, openImageIndex, imagesPerPage) {
    var _ = this;
    _.imageHolder.empty();

    if (typeof imagesPerPage !== 'undefined') {
      _.settings.imagesPerPage = imagesPerPage
    }

    if (typeof openImageIndex !== 'undefined' && openImageIndex !== null) {
      _.offSet = +openImageIndex;
    }

    _.offSet += _.settings.imagesPerPage * direction;
    if (_.offSet < 0) {
      _.offSet = 0;
    }

    var max = _.settings.imagesPerPage - 1;
    var lastImage = max + _.offSet;

    if (lastImage > _.images.length - 1) {
      lastImage = _.images.length - 1;
      _.offSet = lastImage;
    }

    var firstImage = lastImage - max;

    if (firstImage < 1) {
      _.offSet = 0;
      firstImage = 0;
    }
    var column = 1;
    var cur = '';
    for (var currentImage = firstImage; currentImage <= lastImage; currentImage++) {
      if (column === 1) {
        cur = $('<div/>');
        _.imageHolder.append(cur);
      }
      cur.append(_.createImagePopup(_.images[currentImage]));
      column = (column === _.settings.imagesPerRow) ? 1 : column + 1;
    }
    _.setCurrentVisuals();
    _.setButtons();
  }

  /**
   * Function to find a single image from array, direction is -1, 0, 1
   */
  FinnaPaginator.prototype.getImageFromArray = function getImageFromArray(direction) {
    var _ = this;
    var max = _.images.length - 1;
    _.offSet += direction;
    
    if (_.offSet < 0) {
      _.offSet = 0;
    } else if (_.offSet > max) {
      _.offSet = max;
    }

    return _.images[_.offSet];
  }

  /**
   * Function to load information for specifix index
   */
  FinnaPaginator.prototype.loadImageInformation = function loadImageInformation() {
    var _ = this;
    var src = VuFind.path + '/AJAX/JSON?method=getImageInformation&id=' + encodeURIComponent(_.settings.recordId) + '&index=' + _.openImageIndex;

    if (typeof publicList !== 'undefined') {
      src += '&publicList=1';
    }
    var listId = $('input[name="listID"]').val();

    if (typeof listId !== 'undefined') {
      src += '&listId=' + listId;
    }
    $('.collapse-content-holder').html('<div class="large-spinner"><i class="fa fa-spinner fa-spin"/></div>');
    $.ajax({
      url: src,
      dataType: 'html'
    }).done( function setImageData(response) {
      $('.collapse-content-holder').html(JSON.parse(response).data.html);
      if (_.settings.recordType === 'marc') {
        _.loadBookDescription();
      } else {
        finna.layout.initTruncate($('.mfp-content'));
        $('.imagepopup-holder .summary').removeClass('loading');
      }
      VuFind.lightbox.bind('.imagepopup-holder');
      $('.collapse-content-holder').find('[data-embed-video]').click(function onClickVideoLink(){
        var videoSources = $(this).data('videoSources');
        var scripts = $(this).data('scripts');
        var posterUrl = $(this).data('posterUrl');
        finna.layout.loadScripts(scripts, function onScriptsLoaded() {
          finna.videoPopup.initVideoJs('.video-popup', videoSources, posterUrl);
        });
        setCanvasContent('video');
      })
      finna.videoPopup.initIframeEmbed($('.collapse-content-holder'));

      if ($('.imagepopup-holder .feedback-record')[0] || $('.imagepopup-holder .save-record')[0]) {
        $('.imagepopup-holder .feedback-record, .imagepopup-holder .save-record').click(function onClickActionLink(/*e*/) {
          $.magnificPopup.close();
        });
      }
      _.setRecordIndex();
    }).fail( function setImageDataFailure() {
      $('.collapse-content-holder').html('');
      _.setRecordIndex();
    });
  }

  /**
   * Function to load extra information for marc type records
   */
  FinnaPaginator.prototype.loadBookDescription = function loadBookDescription() {
    var _ = this;
    var url = VuFind.path + '/AJAX/JSON?method=getDescription&id=' + _.settings.recordId;
    var summaryHolder = $('.imagepopup-holder .summary');
    $.getJSON(url)
      .done(function onGetDescriptionDone(response) {
        var data = response.data.html;
        if (data.length > 0) {
          summaryHolder.find('> div p').html(data);
          finna.layout.initTruncate(summaryHolder);
        }
        summaryHolder.removeClass('loading');
      })
      .fail(function onGetDescriptionFail(/*response, textStatus*/) {
        summaryHolder.removeClass('loading');
      });
  }

  FinnaPaginator.prototype.onVideoOpen = function onVideoOpen() {
    setCanvasContent('video')
  }

  /**
   * Function to create small images for the paginator track
   */
  FinnaPaginator.prototype.createImagePopup = function createImagePopup(image) {
    var _ = this;
    var holder = $(_.imagePopup).clone(true);
    var img = new Image();
    holder.append(img, $('<i class="fa fa-spinner fa-spin"/>'));
    img.src = image.small;
    img.alt = image.alt;
    img.title = image.description;
    img.onload = function onLoad() {
      $(this).siblings('i').remove();
    }
    holder.attr({'index': image.index, 'data-largest': image.largest, 'data-description': image.description});
    if (!_.isList && _.settings.enableImageZoom) {
      holder.attr('href', image.largest);
    } else {
      holder.attr('href', image.medium);
    }
    return holder;
  }

  FinnaPaginator.prototype.onNonZoomableClick = function onNonZoomableClick(image) {
    var _ = this;
    var img = new Image();
    img.src = image.data('largest');
    _.nonZoomableHolder.find('img').css('opacity', '0.5');
    _.nonZoomableHolder.find('.iconlabel').hide();
    img.onload = function onLoad() {
      if (this.naturalWidth && this.naturalWidth === 10 && this.naturalHeight === 10) {
        _.nonZoomableHolder.addClass('no-image').find('.iconlabel').show();
      } else if (_.nonZoomableHolder.hasClass('no-image')) {
        _.nonZoomableHolder.removeClass('no-image').find('.iconlabel').hide();
      }
      _.nonZoomableHolder.find('img').replaceWith($(this));
    }
    _.nonZoomableHolder.find('.iconlabel').addClass(_.settings.iconlabelClass);
    _.openImageIndex = image.attr('index');
  
    setCanvasContent('nonZoomable');
    _.setCurrentVisuals();
    _.setPagerInfo();
    _.loadImageInformation();
  }

  FinnaPaginator.prototype.checkRecordButtons = function checkRecordButtons() {
    var _ = this;
    if (paginatorIndex < 2) {
      $('.previous-record, .next-record').hide();
      return;
    }
    if (_.paginatorIndex < 1) {
      $('.previous-record').hide();
    } else {
      $('.previous-record').show();
    }
    if (_.paginatorIndex === paginatorIndex - 1) {
      $('.next-record').hide();
    } else {
      $('.next-record').show();
    }
  }

  FinnaPaginator.prototype.setCurrentVisuals = function setCurrentVisuals() {
    var _ = this;
    $('a.image-popup-navi').removeClass('current');
    $('a[index="' + _.openImageIndex + '"]').addClass('current');
  }

  FinnaPaginator.prototype.setMaxImages = function setMaxImages(amount, isPopup) {
    var _ = this;
    var width = $(window).width();
    var images = amount;
    if ((typeof isPopup === 'undefined' || isPopup === false) && _.isList) {
      images = 0;
    } else if (width < 500) {
      images = _.settings.imagesOnMobile;
    } else if (width < 768) {
      images = _.settings.imagesPerRow = amount;
    } else if (width < 991) {
      images = _.settings.imagesPerRow = _.settings.imagesOnMobile;
    } else {
      images = _.settings.imagesPerRow = amount;
    }
    _.settings.imagesPerRow = images;
    _.settings.imagesPerPage = _.settings.imagesPerRow;
  }

  /**
   * Function to handle when leaflet small image has been clicked
   */
  FinnaPaginator.prototype.onLeafletImageClick = function onLeafletImageClick(image) {
    var _ = this;

    if (_.openImageIndex !== image.attr('index')) {
      _.loadImageInformation(_.openImageIndex);
    }
    _.openImageIndex = image.attr('index');
    setCanvasContent('leaflet');
    _.setZoomButtons();
    _.setPagerInfo();
    _.setCurrentVisuals();

    _.leafletHolder.eachLayer(function removeLayers(layer) {
      _.leafletHolder.removeLayer(layer);
    });
    _.leafletHolder.setMaxBounds(null);

    var img = new Image();
    img.src = image.data('largest');
    timeOut = setTimeout(function onLoadStart() {
      _.leafletLoader.addClass('loading');
    }, 100);

    img.onload = function onLoadImg() {
      if (timeOut !== null) {
        clearTimeout(timeOut);
        timeOut = null;
      }
      if (_.leafletHolder.length === 0) {
        return;
      }
      var h = this.naturalHeight;
      var w = this.naturalWidth;

      var zoomLevel = 5.0;

      var width = $('#leaflet-map-image').width();
      var height = $('#leaflet-map-image').height();

      if (w > width) {
        zoomLevel = +w / +width;
      }
      if (h > height) {
        zoomLevel += +h / +height;
        zoomLevel /= 2;
      }
      var maxZoom = _.leafletHolder.getMaxZoom();
      var minZoom = _.leafletHolder.getMinZoom();

      if (zoomLevel > maxZoom) {
        zoomLevel = maxZoom;
      } else if (zoomLevel < minZoom) {
        zoomLevel = 2;
      }

      var sw = _.leafletHolder.unproject([0, h], zoomLevel);
      var ne = _.leafletHolder.unproject([w, 0], zoomLevel);

      var bounds = new L.LatLngBounds(sw, ne);
      _.leafletHolder.flyToBounds(bounds, {animate: false});
      L.imageOverlay(img.src, bounds).addTo(_.leafletHolder);
      _.leafletHolder.invalidateSize(false);
      _.leafletLoader.removeClass('loading');
      _.leafletHolder.setMaxBounds(bounds);
    }
  }

  FinnaPaginator.prototype.onNonZoomableOpen = function onNonZoomableOpen() {
    var _ = this;
    _.imagePopup.off('click').on('click', function onImageClick(e){
      e.preventDefault();
      _.onNonZoomableClick($(this));
    });
    setCanvasContent('nonzoomable');
  }

  FinnaPaginator.prototype.onZoomableOpen = function onZoomableOpen() {
    var _ = this;
    
    _.imagePopup.off('click').on('click', function onImageClick(e){
      e.preventDefault();
      _.onLeafletImageClick($(this));
    });
    _.leafletLoader = _.leafletHolder.find('.leaflet-image-loading');

    _.leafletHolder = L.map('leaflet-map-image', {
      minZoom: 1,
      maxZoom: 6,
      center: [0, 0],
      zoomControl: false,
      zoom: 1,
      crs: L.CRS.Simple,
      maxBoundsViscosity: 0,
      bounceAtZoomLimits: false
    });

    if (_.isList) {
      _.imageHolder.closest('.recordcovers').removeClass('mini-paginator');
    }
    setCanvasContent('leaflet');
  }

  /**
   * Function to set image popup trigger click event and logic when popup is being opened
   */
  FinnaPaginator.prototype.setTrigger = function setTrigger(imagePopup) {
    var _ = this;
    _.changeTriggerImage(imagePopup);
    _.openImageIndex = imagePopup.attr('index');
    _.setPagerInfo();
    _.setCurrentVisuals();
    var modal = $('#imagepopup-modal').find('.imagepopup-holder').clone();

    _.trigger.magnificPopup({
      items: {
        src: modal,
        type: 'inline',
      },
      tClose: VuFind.translate('close'),
      callbacks: {
        open: function onPopupOpen() {
          var mfpContainer = $(this)[0].container;
          mfpContainer.find('.leaflet-map-image').attr('id', 'leaflet-map-image');
          mfpContainer.find('.popup-nonzoom').attr('id', 'popup-nonzoom');
          mfpContainer.find('.popup-video').attr('id', 'popup-video');
          mfpContainer.find('video').attr('id', 'video-player');

          _.setMaxImages(_.settings.imagesOnPopup, true);
          if (!_.isList) {
            toggleButtons(_.moreBtn, _.lessBtn);
          }
          
          var previousRecord = $(previousRecordButton).clone();
          var nextRecord = $(nextRecordButton).clone();
          
          mfpContainer.find('.mfp-content').addClass('loaded');
          mfpContainer.append(previousRecord, nextRecord);

          previousRecord.off('click').click(function loadNextPaginator(e){
            e.preventDefault();
            _.getNextPaginator(-1);
          });
          nextRecord.off('click').click(function loadNextPaginator(e){
            e.preventDefault();
            _.getNextPaginator(1);
          });
          
          _.leafletHolder = $('#leaflet-map-image');
          _.nonZoomableHolder = $('#popup-nonzoom');
          _.videoHolder = $('#popup-video');

          if (_.settings.enableImageZoom) {
            _.onZoomableOpen();
          } else {
            mfpContainer.find('.mfp-content').addClass('nonzoomable');
            _.onNonZoomableOpen();
          }
          _.createPopupTrack(mfpContainer.find('.finna-image-pagination'), true);
          var foundImage = _.imageHolder.find('a[index="' + _.openImageIndex + '"]');
          _.openImageIndex = null;
          foundImage.click();
          _.checkRecordButtons();
        },
        close: function onPopupClose() {
          var covers = _.root.find('.recordcovers');
          _.setReferences(covers);
          _.imagePopup.off('click').on('click', function setTriggerEvents(e){
            e.preventDefault();
            _.setTrigger($(this));
          });
          _.leafletHolder = '';
          _.setMaxImages(_.settings.imagesOnNormal);
          if (_.isList) {
            _.offSet = +_.openImageIndex;
            covers.addClass('mini-paginator');
            _.onListButton(0);
          } else {
            _.loadPage(0, _.openImageIndex);
            _.imageHolder.find('a[index="' + _.openImageIndex + '"]').click();
          }
        }
      }
    });
  }

  /**
   * Function to initialize zoom button logics inside popup
   */
  FinnaPaginator.prototype.setZoomButtons = function setZoomButtons() {
    var _ = this;
    $('.zoom-in').off('click').click(function zoomIn(e) {
      e.stopPropagation();
      _.leafletHolder.setZoom(_.leafletHolder.getZoom() + 1)
    });
    $('.zoom-out').off('click').click(function zoomOut(e) {
      e.stopPropagation();
      _.leafletHolder.setZoom(_.leafletHolder.getZoom() - 1)
    });
    $('.zoom-reset').off('click').click(function zoomReset(e) {
      e.stopPropagation();
      _.leafletHolder.setZoom(1)
    });
  }

  FinnaPaginator.prototype.setMiniPaginator = function setMiniPaginator() {
    var _ = this;
    var image = _.getImageFromArray(0);
    _.setListEvents();

    if (image !== null) {
      _.setListTrigger(image);
    }
    _.root.find('.recordcovers').addClass('mini-paginator');
    _.root.find('.recordcovers-more').hide();
  }

  FinnaPaginator.prototype.setListEvents = function setListEvents() {
    var _ = this;
    _.leftBtn.off('click').on('click', function setImage(){
      _.onListButton(-1);
    });
    _.rightBtn.off('click').on('click', function setImage(){
      _.onListButton(1);
    });
    _.setButtons();
  }

  FinnaPaginator.prototype.onListButton = function onListButton(direction) {
    var _ = this;
    var image = _.getImageFromArray(direction);
    _.setListTrigger(image);
    _.setButtons();
  }

  FinnaPaginator.prototype.setListTrigger = function setListTrigger(image) {
    var _ = this;
    var tmpImg = $(_.imagePopup).clone(true);
    tmpImg.find('img').data('src', image.small);
    tmpImg.attr({'index': image.index, 'href': image.medium});
    tmpImg.click();
  }

  function initPaginator(images, settings) {
    var paginator = new FinnaPaginator(images, $('.recordcover-holder.paginate'), settings, false);
    paginator.init();
  }

  function initMiniPaginator(images, settings) {
    settings.imagesOnNormal = 0;
    var paginator = new FinnaPaginator(images, $('.recordcover-holder.paginate'), settings, true);
    paginator.init();
    paginator.setMiniPaginator();
  }


  /**
   * Function to set the contents of canvas to a new element like leaflet or video
   */
  function setCanvasContent(type) {
    switch (type) {
    case 'video':
      $('#leaflet-map-image, #popup-nonzoom').hide();
      $('#popup-video').addClass('initialized').show();
      break;
    case 'leaflet':
      $('#popup-video, #popup-nonzoom').hide();
      $('#leaflet-map-image').show();
      if ($('#popup-video').hasClass('initialized')) {
        videojs('video-player').pause();
        $('#popup-video').removeClass('initialized');
      }
      break;
    case 'nonzoomable':
      $('#leaflet-map-image, #popup-video').hide();
      $('#popup-nonzoom').show();
      if ($('#popup-video').hasClass('initialized')) {
        videojs('video-player').pause();
        $('#popup-video').removeClass('initialized');
      }
      break;
    }
  }

  var my = {
    initPaginator: initPaginator,
    initMiniPaginator: initMiniPaginator,
    setCanvasContent: setCanvasContent
  };

  return my;
})();
