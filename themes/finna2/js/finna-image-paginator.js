/* global finna, VuFind, L */
finna.imagePaginator = (function imagePaginator() {
  var imageElement = "<a draggable=\"false\" href=\"\" class=\"image-popup image-popup-navi hidden-print\"><img draggable=\"false\" alt=\"\" data-lazy=\"\"></img></a>";
  var elementBase = "<div class=\"finna-paginated paginator-mask\"><div class=\"finna-element-track\"></div></div>";
  var infoBar = "<div class=\"paginator-info\"><span class=\"paginator-pager\">555/555</span></div>";
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
    this.createElements();
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
    this.imagePopup.click(function setTriggerEvents(e){
      e.preventDefault();
      parent.setTrigger($(this));
    });
    this.imageHolder.on('mousedown touchstart mousemove touchmove', function checkScroll(e){
      parent.checkSwipe(e);
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

  FinnaPaginator.prototype.checkSwipe = function checkSwipe(e) {
    var type = e.type;
    var currentX = 0;
    if (type !== 'touchend' && type !== 'mouseup') {
      currentX = (type === 'mousedown' || type === 'mousemove') ? e.originalEvent.clientX : e.originalEvent.touches[0].clientX;
    }

    if (type === 'mousedown' || type === 'touchstart') {
      e.preventDefault();
      this.swipeDrag = true;
      this.oldPosX = currentX; 
    } else if (type === 'mousemove' || type === 'touchmove') {
      if (this.swipeDrag) {
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

  FinnaPaginator.prototype.setImagesPerPage = function setImagesPerPage(newAmount) {
    var difference = this.imagesPerPage - newAmount;
    this.offSet += difference;
  }

  FinnaPaginator.prototype.loadPage = function loadPage(direction) {
    this.imageHolder.empty();

    var oldOffset = this.offSet;
    this.offSet += this.imagesPerPage * direction;

    // Lets get first index and last index so we can safely load correct amount of data
    var imagesPerPageAsIndex = this.imagesPerPage - 1;
    var firstImage = 0 + this.offSet;
    var lastImage = imagesPerPageAsIndex + this.offSet;

    if (firstImage < 0) {
      this.offSet = 0;
      firstImage = 0;
      lastImage = imagesPerPageAsIndex;
    }

    if (lastImage > this.images.length - 1) {
      lastImage = this.images.length - 1;
      this.offSet = oldOffset;
    }

    var i = firstImage;
    var k = lastImage;

    for (;i <= k; i++) {
      this.createImagePopup(this.images[i], true);
    }
  }

  FinnaPaginator.prototype.loadOneImage = function loadOneImage(direction) {
    this.offSet += direction;
    // On direction 1, we are going towards end of the array and vice versa
    var searchIndex = -1;
    switch (direction) {
    case -1:
      searchIndex = this.offSet;
      if (searchIndex < 0) {
        this.offSet = 0;
        return;
      }
      this.imageHolder.find('a:last').remove();
      break;
    case 1:
      searchIndex = this.imagesPerPage - 1 + this.offSet;
      if (searchIndex > this.images.length - 1) {
        this.offSet = this.images.length - 1;
        return;
      }
      this.imageHolder.find('a:first').remove();
      break;
    default:
      return;
    }

    if (searchIndex === -1) {
      return;
    }

    //We are allowed to do stuff now, otherwise there is less or equal to five images in array, Lets get the last index image
    if (typeof this.images[searchIndex] !== 'undefined') {
      this.createImagePopup(this.images[searchIndex], (direction === 1));
    }
  }

  FinnaPaginator.prototype.createImagePopup = function createImagePopup(image, append) {
    var tmpImg = $(this.imagePopup).clone(true);

    tmpImg.find('img').attr('src', image.small);
    tmpImg.attr('index', image.index);
    tmpImg.attr('href', image.medium);

    if (append === true) {
      this.imageHolder.append(tmpImg);
    } else {
      this.imageHolder.prepend(tmpImg);
    }

    tmpImg.append($('<i class="fa fa-spinner fa-spin"/>'));
  }

  FinnaPaginator.prototype.setTrigger = function setTrigger(imagePopup) {
    var img = this.trigger.find('img');
    img.css('opacity', 0.5);
    img.one('load', function onLoadImage() {
      img.css('opacity', '');
    });
    img.attr('src', imagePopup.attr('href'));

    var index = imagePopup.attr('index');
    var src = VuFind.path + '/AJAX/JSON?method=getImagePopup&id=' + encodeURIComponent(this.recordId) + '&index=' + index;
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
          console.log("Yarp");
          // We need to initialize a new finnapaginator with different style of element structure, we need an function for that. Can we do it? Yes, we can said bob the builder
          // Actually, we can add a new function to clone this track and extend it to the popup, so we know what is happening
        },
        close: function closePopup() {
          if ($("#video").length){
            //videojs('video').dispose();
          }
        }
      }
    });
  }

  function initPaginator(images, settings) {
    new FinnaPaginator(images, $('.recordcover-holder.paginate'), settings.id, settings.source, 8);
  }

  var my = {
    initPaginator: initPaginator
  };

  return my;
})();