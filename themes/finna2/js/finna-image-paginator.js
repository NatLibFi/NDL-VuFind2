/* global finna, VuFind, L */
finna.imagePaginator = (function imagePaginator() {
  var imageElement = "<a draggable=\"false\" href=\"\" class=\"image-popup image-popup-navi hidden-print\"><img draggable=\"false\" alt=\"\" data-lazy=\"\"></img></a>";
  var elementBase = "<div class=\"finna-paginated paginator-mask\"><div class=\"finna-element-track\"></div></div>";
  var infoBar = "<div class=\"paginator-info\"><span class=\"paginator-pager\">0/0</span></div>";
  var leftButton = "<button class=\"left-button\" type=\"button\"><</button>";
  var rightButton = "<button class=\"right-button\" type=\"button\">></button>";
  var mfpPopup = "<div class=\"imagepopup-holder\" data-type=\"\" data-id=\"\">" +
  "<button class=\"mfp-arrow mfp-arrow-left previous-record\" type=\"button\"><</button>" +
  "<button class=\"mfp-arrow mfp-arrow-right next-record\" type=\"button\">></button>" +
  "<div class=\"imagepopup-container\">" +
    "<div class=\"paginator-canvas\"></div>" +
    "<div class=\"finna-image-pagination\">" +
    "</div>" +
    "<div style=\"clear: both;\"></div>" +
  "</div>" +
  "<div class=\"image-information-holder\">" +
  " <div class=\"collapse-content-holder\">" +
  " </div>" +
  " <div class=\"record-informations\">" +
  "   <div class=\"record-index\">" +
  "     <span class=\"current\"></span>" +
  "     <span class=\"total\"></span>" +
  "   </div>" +
  "   </div>" +
  " </div>" +
  "</div>";

  var videoElement = '<div class="video-popup"><video id="video-player" class="video-js vjs-big-play-centered" controls></video></div>';
  var iFrameElement = '<div class="mfp-iframe-scaler">'
  + '<div class="mfp-close"></div>'
  + '<iframe class="mfp-iframe" frameborder="0" allowfullscreen></iframe>' +
  + '</div>';
  var leafletElement = "<div id=\"leaflet-map-image\" class=\"image-wrapper\">" +
  "<img />" +
  "<div class=\"imagepopup-zoom-container\">" +
    "<div class=\"zoom-in zoom-button\"><i class=\"fa fa-zoom-in\" aria-hidden=\"true\"></i></div>" +
    "<div class=\"zoom-out zoom-button\"><i class=\"fa fa-zoom-out\" aria-hidden=\"true\"></i></div>" +
    "<div class=\"zoom-reset zoom-button\"><i class=\"fa fa-zoom-100\" aria-hidden=\"true\"></i></div>" +
  "</div>" +
  "<div class=\"leaflet-image-loading\"></div>" +
"</div>";
  var nonZoomableElement = '<div class="non-zoomable">' +
    '<img alt="Kansikuva"></img>' +
  '</div>'

  var masonryInitialized = false;
  var paginatorIndex = 0;
  var recordsFound = 0;

  FinnaPaginator.prototype.getNextPaginator = function getNextPaginator(direction) {
    var searchIndex = this.paginatorIndex + direction;

    var foundPaginator = $('.image-popup-trigger[paginator-index="' + searchIndex + '"');

    if (foundPaginator.length) {
      $.magnificPopup.close()
      foundPaginator.click();
    }
  }

  function setMasonryState(state) {
    masonryInitialized = state;
  }

  function FinnaPaginator(images, paginatedArea, settings) {
    this.paginatorIndex = paginatorIndex;
    this.images = images;
    this.root = $(paginatedArea); // Rootobject
    this.root.removeClass('paginate');
    
    this.trigger = this.root.find('.image-popup-trigger');
    this.trigger.attr('paginator-index', paginatorIndex++);
    // Lets get all the data from the settings
    this.recordId = settings.recordId;
    this.source = settings.source;
    this.imagesPerPage = typeof settings.imagesPerPage !== 'undefined' ? settings.imagesPerPage : 8;
    this.allowZoomContent = settings.allowZoomContent;
    this.recordType = settings.recordType;

    // Thershold for how long the swipe needs to be for new image to load
    this.swipeThreshold = 80;
    this.swipeDrag = false;
    this.oldPosX = 0;

    // Indexes for loading correct images
    this.offSet = 0;

    // When the window goes smaller than breakpoint, adjust the amount of images in paginator
    this.breakPoints = {
      10000: this.imagesPerPage,
      991: this.imagesPerPage - 2,
      768: this.imagesPerPage,
      500: this.imagesPerPage - 2
    };
    this.currentBreakPoint = 10000;
    // Objects which are initialized later
    this.imageHolder = '';
    this.pagerInfo = '';
    this.leftButton = '';
    this.rightButton = '';
    this.imagePopup = '';
    this.leafletHolder = '';
    this.videoHolder = '';
    this.leafletLoader = '';
    this.canvas = '';

    this.openLeafletImageIndex = 0;
  }

  /**
   * Function to create proper elements for the paginator
   */
  FinnaPaginator.prototype.createElements = function createElements() {
    var recordCovers = this.root.find('.recordcovers');
    
    if (this.images.length < 2) {
      recordCovers.hide();
    }

    recordCovers.addClass('paginated');
    var baseObject = $(elementBase).clone();
    this.imageHolder = baseObject.find('.finna-element-track');
    recordCovers.empty();
    this.leftButton = $(leftButton).clone();
    this.rightButton = $(rightButton).clone();
    this.pagerInfo = $(infoBar).clone();
    this.imagePopup = $(imageElement).clone();
    recordCovers.append(this.leftButton);
    recordCovers.append(baseObject);
    recordCovers.append(this.rightButton);
    recordCovers.append(this.pagerInfo);

    this.setEvents();
    this.checkResize();
    // When the page has loaded, lets put initialization for popup for 1 image
    this.setTrigger(this.imageHolder.children('a').first());
  }

  /**
   * Function to properly initialize the buttons and clickevents
   */
  FinnaPaginator.prototype.setEvents = function setEvents() {
    var parent = this;
    this.leftButton.click(function loadEarlierImages() {
      parent.loadPage(-1);
    });
    this.rightButton.click(function loadLaterImages() {
      parent.loadPage(1);
    });
    this.imagePopup.on('click', function setTriggerEvents(e){
      e.preventDefault();
      parent.setTrigger($(this));
    });
    /*$(window).resize(function checkReload(e){
      parent.checkResize(e);
    });*/
  }

  /**
   * Function to set left and right button to correct states
   */
  FinnaPaginator.prototype.setButtons = function setButtons() {
    if (this.images.length <= this.imagesPerPage || this.offSet === this.images.length - 1) {
      this.rightButton.attr('disabled', true);
    } else {
      this.rightButton.removeAttr('disabled');
    }

    if (this.images.length <= this.imagesPerPage || this.offSet < 1) {
      this.leftButton.attr('disabled', true);
    } else {
      this.leftButton.removeAttr('disabled');
    }
  }

  /**
   * Function to check if the screen has been resized
   */
  FinnaPaginator.prototype.checkResize = function checkResize(e) {
    if (this.imagesPerPage === 0) {
      return;
    }

    var width = $(window).width();
    var limits = Object.keys(this.breakPoints);

    for (var i = 0; i < limits.length; i++) {
      var curLimit = limits[i];

      if (width < curLimit && this.currentBreakPoint === curLimit) {
        break;
      }

      if (width < curLimit && this.currentBreakPoint !== curLimit) {
        this.currentBreakPoint = curLimit;
        // Now we need to set offset to match our new page loading
        this.imagesPerPage = this.breakPoints[curLimit];
        this.loadPage(0);
        // No need to loop as it is the lowest we found
        break;
      }
    }
  }

  /**
   * Function to set correct info for page info
   */
  FinnaPaginator.prototype.setPagerInfo = function setPagerInfo(index) {
    this.pagerInfo.find('.paginator-pager').html(+index + 1 + "/" + this.images.length);
  }

  /**
   * Function to set the amount of images we can show on the track
   */
  FinnaPaginator.prototype.setImagesPerPage = function setImagesPerPage(newAmount) {
    var difference = this.imagesPerPage - newAmount;
    this.offSet += difference;
    // When the window goes smaller than breakpoint, adjust the amount of images in paginator
    this.breakPoints = {
      10000: newAmount,
      991: newAmount - 2,
      768: newAmount,
      500: newAmount - 2
    };
  }

  /**
   * Function to create the track which holds smaller images
   */
  FinnaPaginator.prototype.createPopupTrack = function createPopupTrack(popupTrackArea, leafletArea) {
    var recordCovers = this.root.find('.recordcovers').clone(true);
    var parent = this;
    var track = recordCovers.find('.finna-element-track').empty();
    this.leftButton = recordCovers.find('.left-button');
    this.rightButton = recordCovers.find('.right-button');
    this.imageHolder = track;
    this.trigger = leafletArea;
    this.pagerInfo = recordCovers.find('.paginator-info');
    if (Object.getPrototypeOf(this) === FinnaMiniPaginator.prototype) {
      // Lets remove class from the recordcovers so we get better results
      recordCovers.removeClass('mini-paginator');
      this.imagesPerPage = 8;
      this.leftButton.off('click').click(function loadEarlierImages(){
        parent.loadPage(-1);
      });
      this.rightButton.off('click').click(function loadLaterImages(){
        parent.loadPage(1);
      });
    }
    popupTrackArea.append(recordCovers);
    this.createPopupInformation();
    if (this.images.length < 2) {
      popupTrackArea.hide();
    }
    this.loadPage(0);
  }

  FinnaPaginator.prototype.createPopupInformation = function createPopupInformation() {
    var target = $('#leaflet-map-image').closest('.imagepopup-holder').find('.record-informations');
    var newPager = this.pagerInfo.clone();
    this.pagerInfo.hide();
    this.pagerInfo = newPager;
    target.append(this.pagerInfo);
    var total = $('.paginationSimple .total').html();
    target.find('.total').html(total);
  }

  /**
   * Function to load image popup trigger image, checks also if the image exists or do we have to hide something
   * When the image does not exist, we remove the trigger event and let the user navigate directly to record
   */
  FinnaPaginator.prototype.changeTriggerImage = function changeTriggerImage(imagePopup) {
    var img = this.trigger.find('img');
    var parent = this;

    img.css('opacity', 0.5);
    img.one('load', function onLoadImage() {
      img.css('opacity', '');
      if (this.naturalWidth && this.naturalWidth === 10 && this.naturalHeight === 10) {
        parent.trigger.off('click');
        parent.trigger.addClass('no-image');
        if (Object.getPrototypeOf(parent) !== FinnaMiniPaginator.prototype) {
          var mediaHolder = parent.root.closest('.media-left, .media-right');
          mediaHolder.addClass('hidden-xs');
          parent.root.css('display', 'none');
          parent.root.siblings('.image-details-container').css('display', 'none');
          mediaHolder.find('.organisation-menu').hide();
          $('.record.large-image-layout').addClass('no-image-layout').removeClass('large-image-layout');
          $('.large-image-sidebar').addClass('visible-xs visible-sm');
          $('.record-main').addClass('mainbody left');
        } else {
          $(this).parents('.grid').addClass('no-image');
        }
      }
      parent.setImageInformation(imagePopup.attr('index'));
    });

    img.attr('data-src', imagePopup.attr('href'));

    if (Object.getPrototypeOf(parent) === FinnaMiniPaginator.prototype) {
      img.unveil(0, function tryMasonry(){
        $(this).load(function tryToResize(){
          if (masonryInitialized) {
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
  FinnaPaginator.prototype.loadPage = function loadPage(direction) {
    this.imageHolder.empty();
  
    this.offSet += this.imagesPerPage * direction;
    if (this.offSet < 0) {
      this.offSet = 0;
    }

    var imagesPerPageAsIndex = this.imagesPerPage - 1;
    var lastImage = imagesPerPageAsIndex + this.offSet;

    if (lastImage > this.images.length - 1) {
      lastImage = this.images.length - 1;
      this.offSet = lastImage;
    }

    var firstImage = lastImage - imagesPerPageAsIndex;

    if (firstImage < 1) {
      this.offSet = 0;
      firstImage = 0;
    }

    for (;firstImage <= lastImage; firstImage++) {
      this.createImagePopup(this.images[firstImage], true);
    }
    this.setButtons();
  }

  /**
   * Function to find a single image from array, direction is -1, 0, 1
   */
  FinnaPaginator.prototype.getImageFromArray = function getImageFromArray(direction) {
    this.offSet += direction;
    var imagesLengthAsIndex = this.images.length - 1;

    // On direction 1, we are going towards end of the array and vice versa
    var searchIndex = 0;

    switch (direction) {
    case -1:
      searchIndex = this.offSet;
      break;
    case 1:
      var startPosition = this.imagesPerPage !== 0 ? this.imagesPerPage - 1 : 0;
      searchIndex = startPosition + this.offSet;
      break;
    default:
      searchIndex = 0;
      this.offSet = 0;
      break;
    }
    
    if (searchIndex < 0) {
      this.offSet = 0;
      searchIndex = this.offSet;
    } else if (searchIndex > imagesLengthAsIndex) {
      this.offSet = imagesLengthAsIndex;
      searchIndex = this.offSet;
    }
    

    if (typeof this.images[searchIndex] === 'undefined') {
      return null;
    } else {
      return this.images[searchIndex];
    }
  }

  /**
   * Function to load information for specifix index
   */
  FinnaPaginator.prototype.loadImageInformation = function loadImageInformation(index) {
    var src = VuFind.path + '/AJAX/JSON?method=getImageInformation&id=' + encodeURIComponent(this.recordId) + '&index=' + index;
    var parent = this;

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
      var object = JSON.parse(response);
      $('.collapse-content-holder').html(object.data.html);
      var summaryHolder = $('.imagepopup-holder .summary');
      finna.layout.initTruncate($('.mfp-content'));
      summaryHolder.removeClass('loading');
      finna.videoPopup.initVideoPopup(true, $('.collapse-content-holder'), parent);
    }).fail( function setImageDataFailure(response) {
      $('.collapse-content-holder').html('<p>Failed to fetch data</p>');
    });
  }

  FinnaPaginator.prototype.onVideoOpen = function onVideoOpen() {
    this.setCanvasContent('video')
  }

  FinnaPaginator.prototype.onIFrameOpen = function onIFrameOpen() {
    this.setCanvasContent('iframe');
  }

  /**
   * Function to create small images for the paginator track
   */
  FinnaPaginator.prototype.createImagePopup = function createImagePopup(image, append) {
    var tmpImg = $(this.imagePopup).clone(true);

    var img = tmpImg.find('img');

    img.attr('src', image.small);
    tmpImg.attr({'index': image.index, 'href': image.medium, 'data-largest': image.largest});

    tmpImg.append($('<i class="fa fa-spinner fa-spin"/>'));
    tmpImg.on('load', function clearLoadingCircle(){
      tmpImg.remove('i');
    });

    if (append === true) {
      this.imageHolder.append(tmpImg);
    } else {
      this.imageHolder.prepend(tmpImg);
    }
  }

  /**
   * Function to handle when leaflet small image has been clicked
   */
  FinnaPaginator.prototype.onLeafletImageClick = function onLeafletImageClick(leafletImage) {
    var parent = this;
    this.setCanvasContent('leaflet');
    this.setZoomButtons();

    this.openLeafletImageIndex = leafletImage.attr('index');
    this.setPagerInfo(this.openLeafletImageIndex);
    this.leafletHolder.eachLayer(function removeLayers(layer) {
      parent.leafletHolder.removeLayer(layer);
    });
    this.leafletLoader.addClass('loading');
    var img = new Image();
    img.src = leafletImage.attr('data-largest');
    // We need to fetch some data from here
    img.onload = function onLoadImg() {
      var h = this.naturalHeight;
      var w = this.naturalWidth;

      var imageNaturalSizeZoomLevel = 5.0;
      var isMobileDevice = $(window).width() < 768;
      //Mobile devices require bigger zoom value, as they are larger to view

      if (h < 5000 && w < 5000) {
        imageNaturalSizeZoomLevel = isMobileDevice ? 5 : 3.5;
      }
      if (h < 3500 && w < 3500) {
        imageNaturalSizeZoomLevel = isMobileDevice ? 3.75 : 2.5;
      }
      if (h < 2000 && w < 2000) {
        imageNaturalSizeZoomLevel = isMobileDevice ? 2.6 : 1.5;
      }
      if (h < 1000 && w < 1000) {
        imageNaturalSizeZoomLevel = isMobileDevice ? 2.4 : 0.5;
      }

      var southWest = parent.leafletHolder.unproject([0, h], imageNaturalSizeZoomLevel);
      var northEast = parent.leafletHolder.unproject([w, 0], imageNaturalSizeZoomLevel);
      var bounds = new L.LatLngBounds(southWest, northEast);
      L.imageOverlay(img.src, bounds).addTo(parent.leafletHolder);
      parent.leafletHolder.setMaxBounds(bounds);
      parent.leafletLoader.removeClass('loading');
      parent.leafletHolder.invalidateSize(bounds, {animate: false});

    }
    this.loadImageInformation(this.openLeafletImageIndex);
  }

  /**
   * Function to set image popup trigger click event and logic when popup is being opened
   */
  FinnaPaginator.prototype.setTrigger = function setTrigger(imagePopup) {
    this.changeTriggerImage(imagePopup);
    this.setPagerInfo(imagePopup.attr('index'));

    var index = imagePopup.attr('index');

    var parent = this;
    this.trigger.magnificPopup({
      items: {
        src: $(mfpPopup).clone(),
        type: 'inline',
      },
      tClose: "sulje",
      callbacks: {
        open: function onPopupOpen() {
          parent.imagePopup.off('click').on('click', function onImageClick(e){
            e.preventDefault();
            parent.onLeafletImageClick($(this));
          });
          parent.canvas = $('.paginator-canvas');
          var leafletClone = $(leafletElement).clone();
          var videoClone = $(videoElement).clone();
          var iFrameClone = $(iFrameElement).clone();
          parent.canvas.append(leafletClone);
          parent.canvas.append(videoClone);
          parent.canvas.append(iFrameClone);
          var leafletArea = $('#leaflet-map-image');
          leafletArea.closest('.mfp-content').addClass('loaded');
          var popupArea = leafletArea.closest('.imagepopup-holder');
          popupArea.addClass(parent.recordType);

          $('.previous-record').off('click').click(function getPreviousRecord(){
            parent.getNextPaginator(-1);
          });
          $('.next-record').off('click').click(function getNextRecord(){
            parent.getNextPaginator(1);
          });

          parent.leafletLoader = leafletArea.find('.leaflet-image-loading');
          parent.createPopupTrack($('.finna-image-pagination'), leafletArea);

          parent.leafletHolder = L.map('leaflet-map-image', {
            minZoom: 1,
            maxZoom: 6,
            center: [0, 0],
            zoomControl: false,
            zoom: 1,
            crs: L.CRS.Simple,
            maxBoundsViscosity: 0.9,
          });

          parent.videoHolder = videoClone;
          parent.iFrameHolder = iFrameClone;
          parent.setCanvasContent('leaflet');
          if (Object.getPrototypeOf(parent) === FinnaMiniPaginator.prototype) {
            parent.imageHolder.closest('.recordcovers').removeClass('mini-paginator');
          }
          parent.imageHolder.find('a[index="' + index + '"]').click();
        },
        close: function onPopupClose() {
          parent.trigger = parent.root.find('.image-popup-trigger');
          parent.imageHolder = parent.root.find('.finna-element-track');
          parent.leftButton = parent.root.find('.left-button');
          parent.rightButton = parent.root.find('.right-button');
          parent.imageHolder.empty();
          parent.breakPoints[10000] = parent.imagesPerPage;
          parent.imagePopup.off('click');
          parent.imagePopup.on('click', function setTriggerEvents(e){
            e.preventDefault();
            parent.setTrigger($(this));
          });
          parent.pagerInfo = parent.root.find('.paginator-info');
          parent.leafletHolder = '';
          if (Object.getPrototypeOf(parent) === FinnaMiniPaginator.prototype) {
            parent.imagesPerPage = 0;
            parent.setSingleImageLoadButtons();
            var image = parent.images[parent.openLeafletImageIndex];
            parent.offSet = +parent.openLeafletImageIndex;
            parent.setListImageTrigger(image);
            parent.setButtons();
          } else {
            parent.loadPage(0);
            parent.imageHolder.find('a[index="' + index + '"]').click();
          }
          if ($("#video").length){
            //videojs('video').dispose();
          }
        }
      }
    });
  }

  /**
   * Function to initialize zoom button logics inside popup
   */
  FinnaPaginator.prototype.setZoomButtons = function setZoomButtons() {
    var parent = this;
    $('.zoom-in').click(function zoomIn(e) {
      e.stopPropagation();
      parent.leafletHolder.setZoom(parent.leafletHolder.getZoom() + 1)
    });
    $('.zoom-out').click(function zoomOut(e) {
      e.stopPropagation();
      parent.leafletHolder.setZoom(parent.leafletHolder.getZoom() - 1)
    });
    $('.zoom-reset').click(function zoomReset(e) {
      e.stopPropagation();
      parent.leafletHolder.setZoom(1)
    });
  }

  /**
   * Function to show detailed information about image in record view
   */
  FinnaPaginator.prototype.setImageInformation = function setImageInformation(index) {
    $('.image-details-container').addClass('hidden');
    $('.image-details-container[data-img-index="' + index + '"]').removeClass('hidden');
  }

  /**
   * Function to set the contents of canvas to a new element like leaflet or video
   */
  FinnaPaginator.prototype.setCanvasContent = function setCanvasContent(type) {
    
    switch (type) {
    case 'video':
      $('#leaflet-map-image').hide();
      this.videoHolder.show();
      this.iFrameHolder.hide();
      break;
    case 'leaflet':
      this.videoHolder.hide();
      $('#leaflet-map-image').show();
      this.iFrameHolder.hide();
      break;
    case 'iframe':
      $('#leaflet-map-image').hide();
      this.videoHolder.hide();
      this.iFrameHolder.show();
      break;
    }
  }

  function FinnaMiniPaginator(images, paginatedArea, settings) {
    FinnaPaginator.call(this, images, paginatedArea, settings);
  }

  FinnaMiniPaginator.prototype = Object.create(FinnaPaginator.prototype);

  FinnaMiniPaginator.prototype.setMiniPaginator = function setMiniPaginator() {
    var parent = this;
    this.setSingleImageLoadButtons();

    var image = parent.getImageFromArray(0);
    if (image !== null) {
      this.setListImageTrigger(image);
    }
    this.root.find('.recordcovers').addClass('mini-paginator');
  }

  FinnaMiniPaginator.prototype.setSingleImageLoadButtons = function setSingleImageLoadButtons() {
    var parent = this;

    this.leftButton.off('click').on('click', function setImage(){
      parent.setMiniPaginatorButton(-1);
    });
    this.rightButton.off('click').on('click', function setImage(){
      parent.setMiniPaginatorButton(1);
    });
    this.setButtons();
  }

  FinnaMiniPaginator.prototype.setMiniPaginatorButton = function setMiniPaginatorButton(direction) {
    var image = this.getImageFromArray(direction);

    if (image !== null && typeof image !== 'undefined') {
      this.setListImageTrigger(image);
    }
    this.setButtons();
  }

  FinnaMiniPaginator.prototype.setListImageTrigger = function setListImageTrigger(image) {
    var tmpImg = $(this.imagePopup).clone(true);

    var img = tmpImg.find('img');
    img.attr('data-src', image.small);
    tmpImg.attr('index', image.index);
    tmpImg.attr('href', image.largest);
    tmpImg.click();
  }

  FinnaMiniPaginator.prototype.constructor = FinnaMiniPaginator;

  function initPaginator(images, settings) {
    var paginator = new FinnaPaginator(images, $('.recordcover-holder.paginate'), settings);
    paginator.createElements();
  }

  function initMiniPaginator(images, settings) {
    settings.imagesPerPage = 0;
    var paginator = new FinnaMiniPaginator(images, $('.recordcover-holder.paginate'), settings);
    paginator.createElements();
    paginator.setMiniPaginator();
  }

  function initFeedbackButton() {
    if ($('.imagepopup-holder .feedback-record')[0] || $('.imagepopup-holder .save-record')[0]) {
      $('.imagepopup-holder .feedback-record, .imagepopup-holder .save-record').click(function onClickActionLink(/*e*/) {
        $.magnificPopup.close();
      });
    }
  }

  function initVideoPopup(_container) {
    var container = typeof _container === 'undefined' ? $('body') : _container;

    container.find('a[data-embed-video]').click(function openVideoPopup(e) {
      var videoSources = $(this).data('videoSources');
      var posterUrl = $(this).data('posterUrl');
      var scripts = $(this).data('scripts');

      $('.mfp-arrow-right, .mfp-arrow-left').addClass('hidden');
      $('#leaflet-map-image').remove();


      finna.layout.loadScripts(scripts, function onScriptsLoaded() {
        finna.layout.initVideoJs('.video-popup', videoSources, posterUrl);
      });

      e.preventDefault();
    });
  }

  var my = {
    initPaginator: initPaginator,
    initMiniPaginator: initMiniPaginator,
    setMasonryState: setMasonryState
  };

  return my;
})();