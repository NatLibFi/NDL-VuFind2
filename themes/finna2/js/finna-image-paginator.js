/* global finna */
finna.imagePaginator = (function imagePaginator() {
  var paginatedImages = [];
  var imageElement = "<a draggable=\"false\" href=\"\" class=\"image-popup image-popup-navi hidden-print\" data-image-index=\"\"><img draggable=\"false\" alt=\"\" data-lazy=\"\"></img></a>";
  var parentElement = "";
  var elementBase = "<div class=\"finna-paginated paginator-mask\"><div class=\"finna-element-track\"></div></div>";
  var infoBar = "<div class=\"paginator-info\"><span class=\"paginator-pager\">555/555</span></div>";
  var pager = "";
  var leftButton = "<button class=\"left-button\" type=\"button\"><</button>";
  var rightButton = "<button class=\"right-button\" type=\"button\">></button>";
  var area = "";
  

  function setPaginatedImages(images) {
    paginatedImages = images;
    init();
  }

  function init() {
    area = $('.recordcovers.paginate');
    var trigger = area.closest('.recordcover-holder').find('.image-popup-trigger'); //Löydetty pääobjekti
    trigger.data('images', paginatedImages);
    area.removeClass('paginate');
    area.addClass('paginated');
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
    loadPage(0, paginatedImages);
  }

  function bindEvents() {
    $(parentElement).on('touchstart touchmove mousedown mousemove', handleMouseDrag);
    $(document).on('mouseup touchend', handleMouseDrag);

    var leftBtn = area.find('.left-button');
    var rightBtn = area.find('.right-button');
    $(document).on('click', '.image-popup', function setCurrentImage(){
      var trigger = getHostElement($(this));
      trigger.attr('currentImageIndex', $(this).data('ind'));
    });
    $(leftBtn).on('click', function toLeft(){
      loadPage(-1, $(this));
    });
    $(rightBtn).on('click', function toRight(){
      loadPage(1, $(this));
    });
  }

  function getHostElement(element) {
    return element.closest('.recordcover-holder').find('.image-popup-trigger');
  }
  
  var canDrag = false;
  var oldPosX = 0;
  var difference = 0;

  function handleMouseDrag(e) {
    var type = e.type;
    
    switch (type) {
    case 'mousedown':
      // Load images here 
      e.preventDefault();
      canDrag = true;
      oldPosX = e.originalEvent.clientX;
      break;
    case 'touchstart':
      // Load images here 
      e.preventDefault();
      canDrag = true;
      oldPosX = e.originalEvent.touches[0].clientX;
      break;
    case 'mousemove':
      // Our finger/mouse is moving, so lets get the direction where to move
      if (canDrag) {
        checkLoadableContent(e.originalEvent.clientX, $(this));
      }
      break;
    case 'touchmove':
      // Our finger/mouse is moving, so lets get the direction where to move
      if (canDrag) {
        e.stopPropagation();
        e.preventDefault();
        
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

  //Pixels to drag for next image to show
  var threshold = 40;
  function checkLoadableContent(newPosX, element) {
    // Our finger/mouse is moving, so lets get the direction where to move
    if (canDrag) {
      // Value is going to be negative, so lets invert the difference between oldpos and newpos
      difference = (oldPosX - newPosX);

      if (difference > threshold) {
        //Remove the first image in the list and load new last if found
        loadOneImage(1, element);
        oldPosX = newPosX;
      } else if (difference < -threshold) {
        //Remove the last image in the list and load new first if found
        oldPosX = newPosX;
        loadOneImage(-1, element);
      }
    }
  }

  var offSet = 0;
  function loadOneImage(direction, element) {
    var searchIndex = 0;
    offSet += direction;
    var currentPaginatedImages = element.closest('.recordcover-holder').find('.image-popup-trigger').data('images');

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
      parentElement.find('a:last').remove();
    } else if (direction > 0) {
      parentElement.find('a:first').remove();
    }

    if (currentPaginatedImages.length > 5 + offSet) {
      //We are allowed to do stuff now, otherwise there is less or equal to five images in array, Lets get the last index image
      if (typeof currentPaginatedImages[searchIndex] !== 'undefined') {
        createImageObject(currentPaginatedImages[searchIndex], (direction === 1));
      }
    }
  }

  //Lets take the images data
  function setPagerInfo(index) {
    var max = paginatedImages.length;
    var text = (index + 1) + "/" + max;
    pager.html(text);
  }

  function loadPage(direction, element) {
    parentElement.empty();
    var oldOffset = offSet;
    var currentPaginatedImages;
    
    //First time here, so lets load images from temp memory
    if (direction === 0) {
      currentPaginatedImages = paginatedImages;
    } else {
      currentPaginatedImages = element.closest('.recordcover-holder').find('.image-popup-trigger').data('images');
    }

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
        createImageObject(currentPaginatedImages[i], true);
      }
    }
  }

  function createImageObject(link, append) {
    var tmpImg = $(imageElement).clone();

    if (typeof link === 'undefined') {
      return;
    }

    if (typeof link.small !== 'undefined') {
      tmpImg.find('img').attr('src', link.small);
    }
    if (typeof link.index !== 'undefined') {
      tmpImg.data('ind', link.index);
    }
    if (typeof link.medium !== 'undefined') {
      tmpImg.attr('href', link.medium);
    }

    if (append === true) {
      parentElement.append(tmpImg);
    } else {
      parentElement.prepend(tmpImg);
    }

    tmpImg.append($('<i class="fa fa-spinner fa-spin"/>')).one('load', function onLoadImage() {
      $(this).empty();
    });

    initPopupTrigger(tmpImg);
  }

  function initPopupTrigger(element) {
    element.click(function initPopupData(e) {
      e.preventDefault();
      var trigger = $(this).closest('.recordcover-holder').find('.image-popup-trigger');
      trigger.data('ind', $(this).data('ind'));
      trigger.data('thumbInd', $(this).data('thumbInd'));
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
    });
  }

  var my = {
    init: init,
    setPaginatedImages: setPaginatedImages
  };

  return my;
})();