/* global finna, VuFind, L */
finna.imagePaginator = (function imagePaginator() {
  var imageElement = "<a draggable=\"false\" href=\"\" class=\"image-popup image-popup-navi hidden-print\"><img draggable=\"false\" alt=\"\" data-lazy=\"\"></img></a>";
  var elementBase = "<div class=\"finna-paginated paginator-mask\"><div class=\"finna-element-track\"></div></div>";
  var infoBar = "<div class=\"paginator-info\"><span class=\"paginator-pager\">0/0</span></div>";
  var leftButton = "<button class=\"left-button\" type=\"button\"><</button>";
  var rightButton = "<button class=\"right-button\" type=\"button\">></button>";
  var mfpPopup = "<div class=\"imagepopup-holder\" data-type=\"\" data-id=\"\">" +
  "<div class=\"imagepopup-container\">" +
    "<div id=\"leaflet-map-image\" class=\"image-wrapper\">" +
      "<img />" +
      "<div class=\"imagepopup-zoom-container\">" +
        "<div class=\"zoom-in zoom-button\"><i class=\"fa fa-zoom-in\" aria-hidden=\"true\"></i></div>" +
        "<div class=\"zoom-out zoom-button\"><i class=\"fa fa-zoom-out\" aria-hidden=\"true\"></i></div>" +
        "<div class=\"zoom-reset zoom-button\"><i class=\"fa fa-zoom-100\" aria-hidden=\"true\"></i></div>" +
      "</div>" +
    "</div>" +
    "<div class=\"collapse-content-holder\">" +
    "</div>" +
    "<div class=\"finna-image-pagination\">" +
    "</div>" +
    "<div style=\"clear: both;\"></div>" +
  "</div>" +
  "</div>";
  var paginatedObjects = [];

  function FinnaPaginator(images, paginatedArea, settings) {
    if (images.length === 0) {
      // Lets init a dummyimage
    }
    this.images = images;
    this.root = $(paginatedArea); // Rootobject
    this.root.removeClass('paginate');
    
    
    this.trigger = this.root.find('.image-popup-trigger');
    // Lets get all the data from the settings
    this.recordId = settings.recordId;
    this.source = settings.source;
    this.imagesPerPage = typeof settings.imagesPerPage !== 'undefined' ? settings.imagesPerPage : 8;
    this.recordType = settings.recordType;

    // Thershold for how long the swipe needs to be for new image to load
    this.swipeThreshold = 40;
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
  }

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

  FinnaPaginator.prototype.setEvents = function setEvents() {
    var parent = this;
    this.leftButton.click(function loadEarlierImages() {
      parent.loadPage(-1);
    });
    this.rightButton.click(function loadLaterImages() {
      parent.loadPage(1);
    });
    this.imageHolder.on('mousedown mousemove touchmove', function checkScroll(e){
      parent.checkSwipe(e);
    });
    this.imagePopup.on('click', function setTriggerEvents(e){
      e.preventDefault();
      parent.setTrigger($(this));
    });
    $(document).on('mouseup touchend', function setDragToFalse(){
      if (parent.swipeDrag) {
        parent.swipeDrag = false;
      }
    });
    /*$(window).resize(function checkReload(e){
      parent.checkResize(e);
    });*/
  }

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

  FinnaPaginator.prototype.checkResize = function checkResize(e) {
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
   * Needs to be checked for better structure
   */
  FinnaPaginator.prototype.checkSwipe = function checkSwipe(e) {
    var type = e.type;
    var currentX = 0;
    if (type !== 'touchend' && type !== 'mouseup') {
      currentX = (type === 'mousedown' || type === 'mousemove') ? e.originalEvent.clientX : e.originalEvent.touches[0].clientX;
    }

    if (type === 'mousedown') {
      e.preventDefault();
      this.swipeDrag = true;
      this.oldPosX = currentX; 
    } else if (type === 'mousemove' || type === 'touchmove') {
      if (this.swipeDrag === false && type === 'touchmove') {
        this.swipeDrag = true;
        this.oldPosX = currentX;
      } else if (this.swipeDrag) {
        e.preventDefault();
        e.stopPropagation();
        var difference = (this.oldPosX - currentX);

        if (difference > this.swipeThreshold) {
          this.loadOneImage(1);
        } else if (difference < -this.swipeThreshold) {
          this.loadOneImage(-1);
        } else {
          return;
        }
        this.oldPosX = currentX;
      }
    }
  }

  FinnaPaginator.prototype.setPagerInfo = function setPagerInfo(index) {
    this.pagerInfo.find('.paginator-pager').html(+index + 1 + "/" + this.images.length);
  }

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
    this.loadPage(0);
  }

  FinnaPaginator.prototype.changeTriggerImage = function changeTriggerImage(imagePopup) {
    var img = this.trigger.find('img');
    var parent = this;

    img.css('opacity', 0.5);
    img.one('load', function onLoadImage() {
      img.css('opacity', '');
      if (this.naturalWidth && this.naturalWidth === 10 && this.naturalHeight === 10) {
        parent.trigger.off('click');
        parent.trigger.addClass('no-image');
      }
      parent.setImageInformation(imagePopup.attr('index'));
    });

    img.attr('data-src', imagePopup.attr('href'));
    img.unveil();
  }

  /**
   * Needs to be checked for better structure
   */
  FinnaPaginator.prototype.loadPage = function loadPage(direction) {
    this.imageHolder.empty();
  
    this.offSet += this.imagesPerPage * direction;
    if (this.offSet < 0) {
      this.offSet = 0;
    }
    // Lets get first index and last index so we can safely load correct amount of data
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
   * Needs to be checked for better structure
   */
  FinnaPaginator.prototype.getImageFromArray = function getImageFromArray(direction) {
    var oldOffset = this.offSet;
    this.offSet += direction;

    // On direction 1, we are going towards end of the array and vice versa
    var searchIndex = 0;
    switch (direction) {
    case -1:
      searchIndex = this.offSet;
      if (searchIndex < 0) {
        this.offSet = oldOffset;
        return null;
      }
      break;
    case 1:
      var startPosition = this.imagesPerPage !== 0 ? this.imagesPerPage - 1 : 0;
      searchIndex = startPosition + this.offSet;
      if (searchIndex > this.images.length - 1) {
        this.offSet = oldOffset;
        return null;
      }
      break;
    default:
      searchIndex = 0;
      this.offSet = 0;
      break;
    }

    if (searchIndex === -1) {
      this.offSet = 0;
      return null;
    }
    console.log(searchIndex + " was " + this.offSet);
    if (typeof this.images[searchIndex] === 'undefined') {
      return null;
    } else {
      return this.images[searchIndex];
    }
  }

  FinnaPaginator.prototype.loadOneImage = function loadOneImage(direction) {
    var image;
    var elementToRemove = direction === 1 ? 'a:first' : 'a:last';
    image = this.getImageFromArray(direction);

    if (typeof image !== 'undefined' && image !== null) {
      this.imageHolder.find(elementToRemove).remove();
      this.createImagePopup(image, (direction === 1));
    }

    this.setButtons();
  }

  // Need to fetch new part of information, some of the functions will work without adding them to the prototype
  // As it increases the size of the created object
  FinnaPaginator.prototype.loadImageInformation = function loadImageInformation(index) {
    var src = VuFind.path + '/AJAX/JSON?method=getImageInformation&id=' + encodeURIComponent(this.recordId) + '&index=' + index;
    if (typeof publicList !== 'undefined') {
      src += '&publicList=1';
    }
    var listId = $('input[name="listID"]').val();

    if (typeof listId !== 'undefined') {
      src += '&listId=' + listId;
    }
    $('.collapse-content-holder').html('<div><i class="fa fa-spinner fa-spin"/></div>');
    $.ajax({
      url: src,
      dataType: 'html'
    }).done( function setImageData(response) {
      var object = JSON.parse(response);
      $('.collapse-content-holder').html(object.data.html);
    }).fail( function setImageDataFailure(response) {
      $('.collapse-content-holder').html('<p>Failed to fetch data</p>');
    });
  }

  FinnaPaginator.prototype.createImagePopup = function createImagePopup(image, append) {
    var tmpImg = $(this.imagePopup).clone(true);

    var img = tmpImg.find('img');
    img.attr('src', image.small);
    tmpImg.attr('index', image.index);
    tmpImg.attr('href', image.largest);

    if (append === true) {
      this.imageHolder.append(tmpImg);
    } else {
      this.imageHolder.prepend(tmpImg);
    }

    tmpImg.append($('<i class="fa fa-spinner fa-spin"/>'));
  }

  FinnaPaginator.prototype.onLeafletImageClick = function onLeafletImageClick(leafletImage) {
    var parent = this;
    var imageIndex = leafletImage.attr('index');
    this.setPagerInfo(imageIndex);
    this.leafletHolder.eachLayer(function removeLayers(layer) {
      parent.leafletHolder.removeLayer(layer);
    });
    this.leafletHolder.setZoom(0);
    var img = new Image();
    img.src = leafletImage.attr('href');
    // We need to fetch some data from here
    img.onload = function onLoadImg() {
      var h = this.naturalHeight;
      var w = this.naturalWidth;

      var imageNaturalSizeZoomLevel = 3;
      if (h < 2000 && w < 2000) {
        imageNaturalSizeZoomLevel = 2;
      }
      if (h < 1000 && w < 1000) {
        imageNaturalSizeZoomLevel = 1;
      }

      var southWest = parent.leafletHolder.unproject([0, h], imageNaturalSizeZoomLevel);
      var northEast = parent.leafletHolder.unproject([w, 0], imageNaturalSizeZoomLevel);
      var bounds = new L.LatLngBounds(southWest, northEast);

      L.imageOverlay(img.src, bounds).addTo(parent.leafletHolder, {animate: false});
      parent.leafletHolder.flyToBounds(bounds, {animate: false});
      parent.leafletHolder.setMaxBounds(bounds, {animate: false});
      parent.leafletHolder.on('zoomend', function adjustPopupSize() {
        parent.leafletHolder.invalidateSize(bounds, {animate: false});
      });
    }
    this.loadImageInformation(imageIndex);
  }

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
          console.log("doins");
          parent.imagePopup.off('click').on('click', function onImageClick(e){
            e.preventDefault();
            parent.onLeafletImageClick($(this));
          });

          var leafletArea = $('#leaflet-map-image');
          leafletArea.closest('.mfp-content').addClass('loaded');
          var popupArea = leafletArea.closest('.imagepopup-holder');
          popupArea.addClass(parent.recordType);
          popupArea.attr('data-type', parent.recordType);
          popupArea.attr('data-id', parent.recordId);
          
          parent.breakPoints[10000] = 15;
          parent.createPopupTrack($('.finna-image-pagination'), leafletArea);

          if ($(window).width() > 768) {
            $('#popup-content-collapse').addClass('in');
          }
          console.log(parent.offSet);
          parent.leafletHolder = L.map('leaflet-map-image', {
            minZoom: 1,
            maxZoom: 6,
            center: [0, 0],
            zoomControl: false,
            zoom: 1,
            crs: L.CRS.Simple,
            maxBoundsViscosity: 0.9,
          });
          if (Object.getPrototypeOf(parent) === FinnaMiniPaginator.prototype) {
            // Lets remove class from the recordcovers so we get better results
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
            parent.leftButton.off('click').on('click', function setImage(){
              parent.setMiniPaginatorButton(-1);
            });
            parent.rightButton.off('click').on('click', function setImage(){
              parent.setMiniPaginatorButton(1);
            });
            var image = parent.getImageFromArray(0);
            parent.setListImageTrigger(image);
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

  FinnaPaginator.prototype.setImageInformation = function setImageInformation(index) {
    $('.image-details-container').addClass('hidden');
    $('.image-details-container[data-img-index="' + index + '"]').removeClass('hidden');
  }

  function FinnaMiniPaginator(images, paginatedArea, settings) {
    FinnaPaginator.call(this, images, paginatedArea, settings);
  }

  FinnaMiniPaginator.prototype = Object.create(FinnaPaginator.prototype);

  FinnaMiniPaginator.prototype.setMiniPaginator = function setMiniPaginator() {
    var parent = this;

    this.leftButton.off('click').on('click', function setImage(){
      parent.setMiniPaginatorButton(-1);
    });
    this.rightButton.off('click').on('click', function setImage(){
      parent.setMiniPaginatorButton(1);
    });
    var image = parent.getImageFromArray(0);
    if (image !== null) {
      this.setListImageTrigger(image);
    }
    this.root.find('.recordcovers').addClass('mini-paginator');
  }

  FinnaMiniPaginator.prototype.setPageLoadButtons = function setPageLoadButtons(isPopup) {

  }

  FinnaMiniPaginator.prototype.setMiniPaginatorButton = function setMiniPaginatorButton(direction) {
    var image = this.getImageFromArray(direction);
    if (image !== null) {
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
    
    img.unveil();
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

  var my = {
    initPaginator: initPaginator,
    initMiniPaginator: initMiniPaginator
  };

  return my;
})();