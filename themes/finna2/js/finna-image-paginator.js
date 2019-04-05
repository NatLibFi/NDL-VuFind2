/* global finna, VuFind, L, videojs */
finna.imagePaginator = (function imagePaginator() {
  var imageElement = "<a draggable=\"false\" href=\"\" class=\"image-popup image-popup-navi hidden-print\"><img draggable=\"false\" alt=\"\" data-lazy=\"\"></img></a>";
  var elementBase = "<div class=\"finna-paginated paginator-mask\"><div class=\"finna-element-track\"></div></div>";
  var infoBar = "<div class=\"paginator-info\"><span class=\"paginator-pager\">0/0</span></div>";
  var leftButton = "<button class=\"left-button\" type=\"button\"><i class=\"fa fa-arrow-left\"></i></button>";
  var rightButton = "<button class=\"right-button\" type=\"button\"><i class=\"fa fa-arrow-right\"></i></button>";
  var mfpPopup = "<div class=\"imagepopup-holder\" data-type=\"\" data-id=\"\">" +
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
  "     <span class=\"total\"></span>" +
  "   </div>" +
  "   </div>" +
  " </div>" +
  "</div>";

  var previousRecordButton = "<button class=\"mfp-arrow mfp-arrow-left previous-record\" type=\"button\"><</button>";
  var nextRecordButton = "<button class=\"mfp-arrow mfp-arrow-right next-record\" type=\"button\">></button>";

  var videoElement = '<div class="video-popup"><video id="video-player" class="video-js vjs-big-play-centered" controls></video></div>';
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

  FinnaPaginator.prototype.getNextPaginator = function getNextPaginator(direction) {
    var searchIndex = this.paginatorIndex + direction;

    var foundPaginator = $('.image-popup-trigger[paginator-index="' + searchIndex + '"');

    if (foundPaginator.length) {
      $.magnificPopup.close();
      console.log(foundPaginator);
      foundPaginator.click();
    } else {
      $.magnificPopup.close();
    }
  }

  function setMasonryState(state) {
    masonryInitialized = state;
  }

  function FinnaPaginator(images, paginatedArea, settings, isMiniPaginator) {
    this.paginatorIndex = paginatorIndex;
    this.isMiniPaginator = isMiniPaginator;
    this.images = images;
    this.root = $(paginatedArea); // Rootobject
    this.root.removeClass('paginate');
    
    this.trigger = this.root.find('.image-popup-trigger');
    this.trigger.attr('paginator-index', paginatorIndex++);
    // Lets get all the data from the settings
    this.recordId = settings.recordId;
    this.source = settings.source;
    this.imagesPerPage = typeof settings.imagesPerPage !== 'undefined' ? settings.imagesPerPage : 6;
    this.rowAmount = this.imagesPerPage;
    this.enableImageZoom = settings.enableImageZoom;
    this.recordType = settings.recordType;

    // Indexes for loading correct images
    this.offSet = 0;

    // References initialized later
    this.imageHolder = '';
    this.nonZoomableHolder = '';
    this.imageDetail = this.root.find('.recordcover-image-detail .image-description');
    this.moreImagesButton = '';
    this.pagerInfo = '';
    this.leftButton = '';
    this.rightButton = '';
    this.imagePopup = '';
    this.leafletHolder = '';
    this.videoHolder = '';
    this.leafletLoader = '';
    this.canvas = '';
    this.previousRecordButton = '';
    this.nextRecordButton = '';

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
    recordCovers.append(this.leftButton, baseObject, this.rightButton, this.pagerInfo);

    if (!this.isMiniPaginator) {
      this.moreImagesButton = this.root.find('.recordcovers-more button');
      this.moreImagesButton.find('.less-more').html(VuFind.translate('show_more'));
      this.moreImagesButton.addClass('less');

      if (this.images.length < 9) {
        this.root.find('.recordcovers-more').hide();
      }
    }

    this.setEvents();

    if (!this.isMiniPaginator) {
      this.loadPage(0);
      var firstDiv = this.imageHolder.children('div');
      this.setTrigger(firstDiv.children('a').first());
    }
  }

  /**
   * Function to properly initialize the buttons and clickevents
   */
  FinnaPaginator.prototype.setEvents = function setEvents() {
    var parent = this;
    if (!this.isMiniPaginator) {
      this.leftButton.click(function loadEarlierImages() {
        parent.loadPage(-1);
      });
      this.rightButton.click(function loadLaterImages() {
        parent.loadPage(1);
      });
      this.moreImagesButton.click(function setImages(){
        parent.onMoreImagesClick();
      });
    }

    this.imagePopup.on('click', function setTriggerEvents(e){
      e.preventDefault();
      parent.setTrigger($(this));
    });

    /*$(window).resize(function checkReload(e){
      parent.checkResize(e);
    });*/
  }

  FinnaPaginator.prototype.onMoreImagesClick = function onMoreImagesClick() {
    if (this.moreImagesButton.hasClass('less')) {
      this.loadPageWithNewAmount(this.imagesPerPage * 3);
      this.moreImagesButton.removeClass('less');
      this.moreImagesButton.find('.less-more').html(VuFind.translate('show_less'));
    } else {
      this.loadPageWithNewAmount(this.rowAmount);
      this.moreImagesButton.addClass('less');
      this.moreImagesButton.find('.less-more').html(VuFind.translate('show_more'));
    }
  }

  FinnaPaginator.prototype.loadPageWithNewAmount = function loadPageWithNewAmount(newImagesPerPage) {
    this.imagesPerPage = newImagesPerPage;
    this.loadPage(0);
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
   * Function to set correct info for page info
   */
  FinnaPaginator.prototype.setPagerInfo = function setPagerInfo(index) {
    this.pagerInfo.find('.paginator-pager').html(+index + 1 + "/" + this.images.length);
  }

  /**
   * Function to create the track which holds smaller images
   */
  FinnaPaginator.prototype.createPopupTrack = function createPopupTrack(popupTrackArea, leafletArea) {
    var recordCovers = this.root.find('.recordcovers').clone(true);
    var parent = this;
    this.imageHolder = recordCovers.find('.finna-element-track').empty();
    this.leftButton = recordCovers.find('.left-button');
    this.rightButton = recordCovers.find('.right-button');
    this.trigger = leafletArea;
    this.pagerInfo = recordCovers.find('.paginator-info');
    if (this.isMiniPaginator) {
      // Lets remove class from the recordcovers so we get better results
      recordCovers.removeClass('mini-paginator');
      this.imagesPerPage = 6;
      this.leftButton.off('click').click(function loadEarlierImages(){
        parent.loadPage(-1);
      });
      this.rightButton.off('click').click(function loadLaterImages(){
        parent.loadPage(1);
      });
    }
    popupTrackArea.append(recordCovers);
    this.createPopupInformation();
    this.setRecordIndex();
    if (this.images.length < 2) {
      popupTrackArea.hide();
    }
    this.loadPage(0, this.openLeafletImageIndex);
  }

  FinnaPaginator.prototype.createPopupInformation = function createPopupInformation() {
    var target = $('#leaflet-map-image').closest('.imagepopup-holder').find('.record-informations');
    target.append(this.pagerInfo);
  }

  FinnaPaginator.prototype.setRecordIndex = function setRecordIndex() {
    if ($('.paginationSimple .index').length) {
      var total = $('.paginationSimple .total').html();
      var current = +$('.paginationSimple .index').html() + this.paginatorIndex;
      this.pagerInfo.closest('.record-informations').find('.total').html(current + "/" + total);
    }
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
        parent.trigger.addClass('no-image');
        if (!parent.isMiniPaginator) {
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
    this.imageDetail.html(imagePopup.attr('data-description'));

    if (this.isMiniPaginator) {
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
  FinnaPaginator.prototype.loadPage = function loadPage(direction, openImageIndex) {
    this.imageHolder.empty();

    if (typeof openImageIndex !== 'undefined') {
      this.offSet = +openImageIndex;
    }

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

    var j = 0;
    var currentDiv = '';

    for (;firstImage <= lastImage; firstImage++) {
      if (j === 0) {
        currentDiv = $('<div/>');
        this.imageHolder.append(currentDiv);
      }
      currentDiv.append(this.createImagePopup(this.images[firstImage]));
      if (j === this.rowAmount - 1 || firstImage === lastImage) {
        j = 0;
      } else {
        j++;
      }
    }
    this.setButtons();
  }

  /**
   * Function to find a single image from array, direction is -1, 0, 1
   */
  FinnaPaginator.prototype.getImageFromArray = function getImageFromArray(direction) {
    this.offSet += direction;
    var imagesLengthAsIndex = this.images.length - 1;
    var searchIndex = this.offSet;
    
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
      if (parent.recordType === 'marc') {
        parent.loadBookDescription();
      } else {
        finna.layout.initTruncate($('.mfp-content'));
        summaryHolder.removeClass('loading');
      }
      VuFind.lightbox.bind('.imagepopup-holder');
      finna.videoPopup.initVideoPopup(true, $('.collapse-content-holder'), parent);
      finna.videoPopup.initIframeEmbed($('.collapse-content-holder'));

      if ($('.imagepopup-holder .feedback-record')[0] || $('.imagepopup-holder .save-record')[0]) {
        $('.imagepopup-holder .feedback-record, .imagepopup-holder .save-record').click(function onClickActionLink(/*e*/) {
          $.magnificPopup.close();
        });
      }
    }).fail( function setImageDataFailure(response) {
      $('.collapse-content-holder').html('<p>Failed to fetch data</p>');
    });
  }

  FinnaPaginator.prototype.loadBookDescription = function loadBookDescription() {
    var url = VuFind.path + '/AJAX/JSON?method=getDescription&id=' + this.recordId;
    var summaryHolder = $('.imagepopup-holder .summary');
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
  }

  FinnaPaginator.prototype.onVideoOpen = function onVideoOpen() {
    this.setCanvasContent('video')
  }

  /**
   * Function to create small images for the paginator track
   */
  FinnaPaginator.prototype.createImagePopup = function createImagePopup(image) {
    var tmpImg = $(this.imagePopup).clone(true);

    tmpImg.find('img').attr('src', image.small);
    tmpImg.attr({'index': image.index, 'href': image.medium, 'data-largest': image.largest, 'data-description': image.description});

    tmpImg.append($('<i class="fa fa-spinner fa-spin"/>'));
    
    return tmpImg;
  }

  FinnaPaginator.prototype.onNonZoomableClick = function onNonZoomableClick(leafletImage) {
    this.setCanvasContent('nonZoomable');

    var img = this.nonZoomableHolder.find('img');
    img.attr('src', leafletImage.attr('data-largest'));
    this.openLeafletImageIndex = leafletImage.attr('index');
    this.setPagerInfo(this.openLeafletImageIndex);
    this.loadImageInformation(this.openLeafletImageIndex);
  }

  FinnaPaginator.prototype.checkRecordButtons = function checkRecordButtons() {
    if (paginatorIndex < 2) {
      $('.previous-record, .next-record').hide();
      return;
    }

    if (this.paginatorIndex < 1) {
      $('.previous-record').hide();
    } else {
      $('.previous-record').show();
    }

    if (this.paginatorIndex === paginatorIndex - 1) {
      $('.next-record').hide();
    } else {
      $('.next-record').show();
    }
  }

  FinnaPaginator.prototype.setCurrentVisuals = function setCurrentVisuals(element) {
    $('a.image-popup-navi').removeClass('current');
    element.addClass('current');
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
    this.setCurrentVisuals(leafletImage);
    this.leafletHolder.eachLayer(function removeLayers(layer) {
      parent.leafletHolder.removeLayer(layer);
    });
    this.leafletLoader.addClass('loading');

    var img = new Image();
    img.src = leafletImage.attr('data-largest');

    img.onload = function onLoadImg() {
      // If popup is closed before loading the image, return without trying to set leaflet
      if (parent.leafletHolder.length === 0) {
        return;
      }
      var h = this.naturalHeight;
      var w = this.naturalWidth;

      var imageNaturalSizeZoomLevel = 5.0;
      var isMobileDevice = $(window).width() < 768;

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

  FinnaPaginator.prototype.onNonZoomableOpen = function onNonZoomableOpen() {
    this.nonZoomableHolder = $(nonZoomableElement).clone();
    this.videoHolder = $(videoElement).clone();
    var parent = this;

    this.canvas = $('.paginator-canvas');
    this.canvas.append(this.videoHolder, this.nonZoomableHolder);
    this.canvas.closest('.mfp-content').addClass('loaded nonzoomable');

    this.imagePopup.off('click').on('click', function onImageClick(e){
      e.preventDefault();
      parent.onNonZoomableClick($(this));
    });
    this.createPopupTrack($('.finna-image-pagination'), $('.non-zoomable'));
    if (this.isMiniPaginator) {
      this.imageHolder.closest('.recordcovers').removeClass('mini-paginator');
    }
    this.imageHolder.find('a[index="' + this.openLeafletImageIndex + '"]').click();

    this.setCanvasContent('nonzoomable');
  }

  FinnaPaginator.prototype.onZoomableOpen = function onZoomableOpen() {
    this.leafletHolder = $(leafletElement).clone();
    this.videoHolder = $(videoElement).clone();
    var parent = this;
    
    this.imagePopup.off('click').on('click', function onImageClick(e){
      e.preventDefault();
      parent.onLeafletImageClick($(this));
    });

    this.canvas = $('.paginator-canvas');
    this.canvas.append(this.leafletHolder, this.videoHolder);
    this.canvas.closest('.mfp-content').addClass('loaded');

    this.leafletLoader = this.leafletHolder.find('.leaflet-image-loading');
    this.createPopupTrack($('.finna-image-pagination'), this.leafletHolder);

    this.leafletHolder = L.map('leaflet-map-image', {
      minZoom: 1,
      maxZoom: 6,
      center: [0, 0],
      zoomControl: false,
      zoom: 1,
      crs: L.CRS.Simple,
      maxBoundsViscosity: 0.9,
    });

    this.setCanvasContent('leaflet');
    if (this.isMiniPaginator) {
      this.imageHolder.closest('.recordcovers').removeClass('mini-paginator');
    }
    this.imageHolder.find('a[index="' + this.openLeafletImageIndex + '"]').click();
  }

  FinnaPaginator.prototype.onNoImageClick = function onNoImageClick() {
    this.setTrigger();
  }

  /**
   * Function to set image popup trigger click event and logic when popup is being opened
   */
  FinnaPaginator.prototype.setTrigger = function setTrigger(imagePopup) {
    this.changeTriggerImage(imagePopup);
    this.setPagerInfo(imagePopup.attr('index'));

    this.openLeafletImageIndex = imagePopup.attr('index');
    this.setCurrentVisuals(imagePopup);
    var parent = this;

    this.trigger.magnificPopup({
      items: {
        src: $(mfpPopup).clone(),
        type: 'inline',
      },
      tClose: VuFind.translate('close'),
      callbacks: {
        open: function onPopupOpen() {
          parent.imagesPerPage = parent.rowAmount;
          var mfpContainer = $('.mfp-container');
          parent.previousRecordButton = $(previousRecordButton).clone();
          parent.nextRecordButton = $(nextRecordButton).clone();

          parent.previousRecordButton.off('click').click(function loadNextPaginator(e){
            e.preventDefault();
            parent.getNextPaginator(-1);
          });
          parent.nextRecordButton.off('click').click(function loadNextPaginator(e){
            e.preventDefault();
            parent.getNextPaginator(1);
          });
          mfpContainer.append(parent.previousRecordButton, parent.nextRecordButton);

          if (parent.enableImageZoom) {
            parent.onZoomableOpen();
          } else {
            parent.onNonZoomableOpen();
          }
          parent.checkRecordButtons();
        },
        close: function onPopupClose() {
          parent.trigger = parent.root.find('.image-popup-trigger');
          parent.imageHolder = parent.root.find('.finna-element-track');
          parent.leftButton = parent.root.find('.left-button');
          parent.rightButton = parent.root.find('.right-button');
          parent.imageHolder.empty();
          parent.imagePopup.off('click').on('click', function setTriggerEvents(e){
            e.preventDefault();
            parent.setTrigger($(this));
          });
          parent.pagerInfo = parent.root.find('.paginator-info');
          parent.leafletHolder = '';
          if (parent.isMiniPaginator) {
            parent.imagesPerPage = 0;
            parent.setSingleImageLoadButtons();
            var image = parent.images[parent.openLeafletImageIndex];
            parent.offSet = +parent.openLeafletImageIndex;
            parent.setListImageTrigger(image);
            parent.setButtons();
          } else {
            if (parent.moreImagesButton.hasClass('less')) {
              parent.imagesPerPage = parent.rowAmount;
            } else {
              parent.imagesPerPage = parent.rowAmount * 3;
            }
            parent.loadPage(0, parent.openLeafletImageIndex);
            parent.imageHolder.find('a[index="' + parent.openLeafletImageIndex + '"]').click();
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
      this.videoHolder.addClass('initialized');
      this.videoHolder.show();
      if (this.nonZoomableHolder !== '') {
        this.nonZoomableHolder.hide();
      }
      break;
    case 'leaflet':
      this.videoHolder.hide();
      $('#leaflet-map-image').show();
      if (this.nonZoomableHolder !== '') {
        this.nonZoomableHolder.hide();
      }
      if (this.videoHolder.hasClass('initialized')) {
        videojs('video-player').stop();
      }
      break;
    case 'nonzoomable':
      $('#leaflet-map-image').hide();
      this.videoHolder.hide();
      if (this.nonZoomableHolder !== '') {
        this.nonZoomableHolder.show();
      }
      if (this.videoHolder.hasClass('initialized')) {
        videojs('video-player').stop();
      }
      break;
    }
  }

  FinnaPaginator.prototype.setMiniPaginator = function setMiniPaginator() {
    var parent = this;
    this.setSingleImageLoadButtons();

    var image = parent.getImageFromArray(0);
    if (image !== null) {
      this.setListImageTrigger(image);
    }
    this.root.find('.recordcovers').addClass('mini-paginator');
    this.root.find('.recordcovers-more').hide();
  }

  FinnaPaginator.prototype.setSingleImageLoadButtons = function setSingleImageLoadButtons() {
    var parent = this;

    this.leftButton.off('click').on('click', function setImage(){
      parent.setMiniPaginatorButton(-1);
    });
    this.rightButton.off('click').on('click', function setImage(){
      parent.setMiniPaginatorButton(1);
    });
    this.setButtons();
  }

  FinnaPaginator.prototype.setMiniPaginatorButton = function setMiniPaginatorButton(direction) {
    var image = this.getImageFromArray(direction);

    if (image !== null && typeof image !== 'undefined') {
      this.setListImageTrigger(image);
    }
    this.setButtons();
  }

  FinnaPaginator.prototype.setListImageTrigger = function setListImageTrigger(image) {
    var tmpImg = $(this.imagePopup).clone(true);

    var img = tmpImg.find('img');
    img.attr('data-src', image.small);
    tmpImg.attr({'index': image.index, 'href': image.medium});
    tmpImg.click();
  }

  function initPaginator(images, settings) {
    var paginator = new FinnaPaginator(images, $('.recordcover-holder.paginate'), settings, false);
    paginator.createElements();
  }

  function initMiniPaginator(images, settings) {
    settings.imagesPerPage = 0;
    var paginator = new FinnaPaginator(images, $('.recordcover-holder.paginate'), settings, true);
    paginator.createElements();
    paginator.setMiniPaginator();
  }

  var my = {
    initPaginator: initPaginator,
    initMiniPaginator: initMiniPaginator,
    setMasonryState: setMasonryState
  };

  return my;
})();