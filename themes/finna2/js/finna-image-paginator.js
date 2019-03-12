/* global finna, VuFind, L */
finna.imagePaginator = (function imagePaginator() {
  var imageElement = "<a draggable=\"false\" href=\"\" class=\"image-popup image-popup-navi hidden-print\"><img draggable=\"false\" alt=\"\" data-lazy=\"\"></img></a>";
  var elementBase = "<div class=\"finna-paginated paginator-mask\"><div class=\"finna-element-track\"></div></div>";
  var infoBar = "<div class=\"paginator-info\"><span class=\"paginator-pager\">0/0</span></div>";
  var leftButton = "<button class=\"left-button\" type=\"button\"><</button>";
  var rightButton = "<button class=\"right-button\" type=\"button\">></button>";
  var paginatedObjects = [];

  function FinnaPaginator(images, paginatedArea, recordId, source, imagesPerPage) {
    if (images.length === 0) {
      // Lets init a dummyimage
    }
    this.images = images;
    this.root = $(paginatedArea); // Rootobject
    this.root.removeClass('paginate');
    this.recordId = recordId;
    this.source = source;
    this.imagesPerPage = imagesPerPage;
    this.trigger = this.root.find('.image-popup-trigger');
    
    // Thershold for how long the swipe needs to be for new image to load
    this.swipeThreshold = 40;
    this.swipeDrag = false;
    this.oldPosX = 0;

    // Indexes for loading correct images
    this.offSet = 0;

    // When the window goes smaller than breakpoint, adjust the amount of images in paginator
    this.breakPoints = {
      10000: imagesPerPage,
      991: imagesPerPage - 2,
      768: imagesPerPage,
      500: imagesPerPage - 2
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
    $(window).resize(function checkReload(e){
      parent.checkResize(e);
    });
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

  FinnaPaginator.prototype.setImageDetails = function setImageDetails(key) {

  }

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
  }

  FinnaPaginator.prototype.createPopupTrack = function createPopupTrack(popupTrackArea, leafletArea) {
    var recordCovers = this.root.find('.recordcovers').clone(true);
    var track = recordCovers.find('.finna-element-track').empty();
    this.leftButton = recordCovers.find('.left-button');
    this.rightButton = recordCovers.find('.right-button');
    this.imageHolder = track;
    this.trigger = leafletArea;
    this.pagerInfo = recordCovers.find('.paginator-info');
    popupTrackArea.append(recordCovers);
    this.loadPage(0);
  }

  FinnaPaginator.prototype.changeTriggerImage = function changeTriggerImage(imagePopup) {
    var img = this.trigger.find('img');
    img.css('opacity', 0.5);
    img.one('load', function onLoadImage() {
      img.css('opacity', '');
    });
    img.attr('src', imagePopup.attr('href'));
  }

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
        return;
      }
      break;
    case 1:
      var startPosition = this.imagesPerPage !== 0 ? this.imagesPerPage - 1 : 0;
      searchIndex = startPosition + this.offSet;
      if (searchIndex > this.images.length - 1) {
        this.offSet = oldOffset;
        return;
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

    if (typeof this.images[searchIndex] === 'undefined') {
      console.log(this.images[searchIndex]);
      return null;
    } else {
      return this.images[searchIndex];
    }
  }

  FinnaPaginator.prototype.loadOneImage = function loadOneImage(direction) {
    var image;
    var elementToRemove = direction === 1 ? 'a:first' : 'a:last';
    image = this.getImageFromArray(direction);

    if (typeof image !== 'undefined') {
      this.imageHolder.find(elementToRemove).remove();
      this.createImagePopup(image, (direction === 1));
    } else {
      console.log("Image was not found!");
    }
    this.setButtons();
  }

  FinnaPaginator.prototype.createImagePopup = function createImagePopup(image, append) {
    var tmpImg = $(this.imagePopup).clone(true);

    tmpImg.find('img').attr('src', image.small);
    tmpImg.attr('index', image.index);
    tmpImg.attr('href', image.medium);
    tmpImg.attr('large-href', image.large);

    if (append === true) {
      this.imageHolder.append(tmpImg);
    } else {
      this.imageHolder.prepend(tmpImg);
    }

    tmpImg.append($('<i class="fa fa-spinner fa-spin"/>'));
  }

  FinnaPaginator.prototype.onLeafletImageClick = function onLeafletImageClick(leafletImage) {
    var parent = this;
    this.setPagerInfo(leafletImage.attr('index'));
    this.leafletHolder.eachLayer(function removeLayers(layer) {
      parent.leafletHolder.removeLayer(layer);
    });
    var img = new Image();
    img.src = leafletImage.attr('href');
    img.onload = function onLoadImg() {
      var h = this.naturalHeight;
      var w = this.naturalWidth;

      var imageNaturalSizeZoomLevel = 4;
      if (h < 2000 && w < 2000) {
        imageNaturalSizeZoomLevel = 3;
      }
      if (h < 1000 && w < 1000) {
        imageNaturalSizeZoomLevel = 2;
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
  }

  FinnaPaginator.prototype.setTrigger = function setTrigger(imagePopup) {
    this.changeTriggerImage(imagePopup);
    this.setPagerInfo(imagePopup.attr('index'));

    var index = imagePopup.attr('index');
    var src = VuFind.path + '/AJAX/JSON?method=getImagePopup&id=' + encodeURIComponent(this.recordId) + '&index=' + index;
    var parent = this;
    var currentElement = {
      src: src,
      index: index,
      href: imagePopup.attr('href')
    };
    this.trigger.magnificPopup({
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
          parent.imagePopup.off('click').on('click', function onImageClick(e){
            e.preventDefault();
            parent.onLeafletImageClick($(this));
          });
          var leafletArea = $('#leaflet-map-image');
          parent.createPopupTrack($('.finna-image-pagination'), leafletArea);
          // Lets init leaflet
          parent.leafletHolder = L.map('leaflet-map-image', {
            minZoom: 1,
            maxZoom: 6,
            center: [0, 0],
            zoomControl: false,
            zoom: 1,
            crs: L.CRS.Simple,
            maxBoundsViscosity: 0.9,
          });
          parent.imageHolder.find('a[index="' + index + '"]').click();
        },
        close: function closePopup() {
          parent.trigger = parent.root.find('.image-popup-trigger');
          parent.imageHolder = parent.root.find('.finna-element-track');
          parent.leftButton = parent.root.find('.left-button');
          parent.rightButton = parent.root.find('.right-button');
          parent.imageHolder.empty();
          parent.imagePopup.off('click');
          parent.imagePopup.on('click', function setTriggerEvents(e){
            e.preventDefault();
            parent.setTrigger($(this));
          });
          parent.pagerInfo = parent.root.find('.paginator-info');
          parent.leafletHolder = '';
          parent.loadPage(0);
          if ($("#video").length){
            //videojs('video').dispose();
          }
        }
      }
    });
  }

  function FinnaMiniPaginator(images, paginatedArea, recordId, source, imagesPerPage) {
    FinnaPaginator.call(this, images, paginatedArea, recordId, source, 0);
  }

  FinnaMiniPaginator.prototype = Object.create(FinnaPaginator.prototype);

  FinnaMiniPaginator.prototype.setMiniPaginator = function setMiniPaginator() {
    var parent = this;

    this.leftButton.off('click').on('click', function setImage(){
      var image = parent.getImageFromArray(-1);
      if (image !== null) {
        parent.setListImageTrigger(image);
      }
      parent.setButtons();
    });
    this.rightButton.off('click').on('click', function setImage(){
      var image = parent.getImageFromArray(1);
      if (image !== null) {
        parent.setListImageTrigger(image);
      }
      parent.setButtons();
    });
    var image = parent.getImageFromArray(0);
    if (image !== null) {
      this.setListImageTrigger(image);
    }
  }

  FinnaMiniPaginator.prototype.setListImageTrigger = function setListImageTrigger(image) {
    var tmpImg = $(this.imagePopup).clone(true);

    tmpImg.find('img').attr('src', image.small);
    tmpImg.attr('index', image.index);
    tmpImg.attr('href', image.medium);
    tmpImg.attr('large-href', image.large);

    tmpImg.click();
  }

  FinnaMiniPaginator.prototype.constructor = FinnaMiniPaginator;

  function initPaginator(images, settings) {
    var paginator = new FinnaPaginator(images, $('.recordcover-holder.paginate'), settings.id, settings.source, 8);
    paginator.createElements();
  }

  function initMiniPaginator(images, settings) {
    var paginator = new FinnaMiniPaginator(images, $('.recordcover-holder.paginate'), settings.id, settings.source, 0);
    paginator.createElements();
    paginator.setMiniPaginator();
  }

  var my = {
    initPaginator: initPaginator,
    initMiniPaginator: initMiniPaginator
  };

  return my;
})();