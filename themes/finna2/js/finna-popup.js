(function popupModule($) {
  $.fn.finnaPopup = function finnaPopup(params) {
    var _ = $(this);
    if (typeof $.fn.finnaPopup.popups === 'undefined') {
      $.fn.finnaPopup.popups = {};
    }
    var tmp = params.id;
    if (typeof tmp === 'undefined') {
      tmp = 'default';
    }
    if (typeof _.data('popup-' + tmp + '-index') !== 'undefined') {
      return; //Already found in the list, so lets not double init this
    }
    if (typeof $.fn.finnaPopup.popups[tmp] === 'undefined') {
      $.fn.finnaPopup.popups[tmp] = new FinnaPopup($(this), params, params.id);
    } else {
      $.fn.finnaPopup.popups[tmp].addTrigger($(this));
    }
    _.data('popup-id', tmp);
    var events = (typeof params.noClick === 'undefined' || !params.noClick) ? 'click openmodal.finna' : 'openmodal.finna';
    _.off(events).on(events, function showModal(e) {
      e.preventDefault();
      _.on('removeclick.finna', function removeClick() {
        _.off('click');
      });
      // We need to tell which triggers is being used
      $.fn.finnaPopup.popups[tmp].openIndex = _.data('popup-' + tmp + '-index');
      $.fn.finnaPopup.popups[tmp].onPopupOpen(params.onPopupOpen, params.onPopupClose);
    });
    if (typeof params.embed !== 'undefined' && params.embed) {
      if (typeof $.fn.finnaPopup.popups[tmp].content === 'undefined') {
        $.fn.finnaPopup.popups[tmp].triggers[0].trigger('openmodal.finna');
      }
    }
  };
  $.fn.finnaPopup.reIndex = function reIndex() {
    $.each($.fn.finnaPopup.popups, function callReindex(key, obj) {
      obj.reIndex();
    });
  };
  $.fn.finnaPopup.getCurrent = function getCurrent(id) {
    if (typeof $.fn.finnaPopup.popups !== 'undefined') {
      return $.fn.finnaPopup.popups[id].openIndex;
    }
    return undefined;
  };
  $.fn.finnaPopup.closeOpen = function closeOpen() {
    $.each($.fn.finnaPopup.popups, function callReindex(key, obj) {
      if (obj.isOpen) {
        obj.onPopupClose();
      }
    });
  };
})(jQuery);

var previous = '<button class="popup-arrow popup-left-arrow previous-record" type="button"><i class="fa fa-angle-double-left" aria-hidden="true"></i></button>';
var next = '<button class="popup-arrow popup-right-arrow next-record" type="button"><i class="fa fa-angle-double-right" aria-hidden="true"></i></button>';
var closeTemplate = '<button class="finna-popup close-button" title="close_translation">X</button>';
function FinnaPopup(trigger, params, id) {
  var _ = this;
  _.triggers = [];
  _.isOpen = false;
  _.openIndex = 0;
  _.id = id;
  if (typeof params.onPopupInit !== 'undefined') {
    _.onPopupInit = params.onPopupInit;
  }
  _.addTrigger(trigger);
  // Popup modal stuff, backdrop and content etc
  _.cycle = typeof params.cycle !== 'undefined' ? params.cycle : true;
  _.backDrop = undefined;
  _.content = undefined;
  _.classes = typeof params.classes === 'undefined' ? '' : params.classes;
  _.modalBase = typeof params.modal !== 'undefined' ? $(params.modal) : $('<div class="finna-popup default-modal"/>');
  _.nextPopup = undefined;
  _.previousPopup = undefined;
  _.closeButton = undefined;
  _.unveil = typeof params.unveil !== 'undefined' ? params.unveil : false;
  _.embed = typeof params.embed !== 'undefined' ? params.embed : false;
  _.patterns = {
    youtube: {
      index: 'youtube.com',
      id: 'v=',
      src: '//www.youtube.com/embed/%id%?autoplay=1'
    },
    youtube_short: {
      index: 'youtu.be/',
      id: 'youtu.be/',
      src: '//www.youtube.com/embed/%id%?autoplay=1'
    },
    vimeo: {
      index: 'vimeo.com/',
      id: '/',
      src: '//player.vimeo.com/video/%id%?autoplay=1'
    }
  };

  // If given parent element, we create a new element inside that rather than opening a new popup
  _.parent = params.parent;
}

FinnaPopup.prototype.adjustEmbedLink = function adjustEmbedLink(src) {
  var _ = this;
  var embedSrc = src;
  $.each(_.patterns, function findPattern() {
    var p = this;
    if (embedSrc.indexOf(p.index) > -1) {
      if (p.id) {
        if (typeof p.id === 'string') {
          embedSrc = embedSrc.substr(embedSrc.lastIndexOf(p.id) + p.id.length, embedSrc.length);
        } else {
          embedSrc = p.id.call(p, embedSrc);
        }
      }
      embedSrc = p.src.replace('%id%', embedSrc );
      return false;
    }
  });
  return embedSrc;
};

FinnaPopup.prototype.addTrigger = function addTrigger(trigger) {
  var _ = this;
  _.triggers.push(trigger);
  trigger.data('popup-' + _.id + '-index', _.triggers.length - 1);
  _.onPopupInit(trigger);
};

FinnaPopup.prototype.customOpen = function customOpen(){};

FinnaPopup.prototype.customClose = function customClose(){};

FinnaPopup.prototype.reIndex = function reIndex() {
  var _ = this;
  _.triggers = [];
  $(':data(popup-id)').each(function toList() {
    if ($(this).data('popup-id') === _.id) {
      _.addTrigger($(this));
    }
  });
};

FinnaPopup.prototype.currentTrigger = function currentTrigger() {
  var _ = this;
  return $(_.triggers[_.openIndex]);
};

FinnaPopup.prototype.getTrigger = function getTrigger(direction) {
  var _ = this;
  if (typeof _.triggers[_.openIndex + direction] !== 'undefined') {
    _.customClose();
    _.triggers[_.openIndex + direction].trigger('openmodal');
  }
  _.checkButtons();
};

FinnaPopup.prototype.checkButtons = function checkButtons() {
  var _ = this;
  if (typeof _.previousPopup === 'undefined' && typeof _.nextPopup === 'undefined') {
    return;
  }

  _.previousPopup.toggle(_.openIndex > 0 && _.triggers.length > 1);
  _.nextPopup.toggle(_.openIndex < _.triggers.length - 1 && _.triggers.length > 1);
};

// Function where we are going to check if we are opening the correct popup
FinnaPopup.prototype.show = function show() {
  var _ = this;
  var hasParent = typeof _.parent !== 'undefined';
  if (!_.embed) {
    $(document).on('focusin.finna', function setFocusTrap(e) {
      _.focusTrap(e);
    });
    _.toggleScroll(false);
  }

  if (typeof _.backDrop === 'undefined' && !hasParent) {
    _.backDrop = $('<div class="finna-popup backdrop"></div>');
    _.backDrop.off('click').on('click', function test(e) {
      _.onPopupClose();
    });
    $(document.body).prepend(_.backDrop);
  }
  if (typeof _.modalHolder !== 'undefined') {
    _.modalHolder.remove();
  }
  if (typeof _.content === 'undefined' && !hasParent) {
    _.content = $('<div class="finna-popup content" tabindex="-1"></div>');
    _.backDrop.append(_.content);
  } else if (hasParent) {
    _.content = $('#' + _.parent);
    _.content.addClass('finna-popup');
    if (_.content.children().length > 0) {
      _.content.empty();
    }
  }
  _.modalHolder = $('<div class="finna-popup ' + _.classes + ' modal-holder"/>');
  _.content.prepend(_.modalHolder);
  if (typeof _.parent === 'undefined' && typeof _.closeButton === 'undefined') {
    _.closeButton = $(closeTemplate).clone();
    _.closeButton.on('click', function callClose(e) {
      e.preventDefault();
      e.stopPropagation();
      _.onPopupClose();
    });
    _.content.append(_.closeButton);
  }
  _.modalHolder.on('click', function preventClickThrough(e) {
    e.stopPropagation();
    e.preventDefault();
  });
  if (typeof _.previousPopup === 'undefined' && _.cycle) {
    var pClone = $(previous).clone();
    pClone.off('click').on('click', function nextPopup(e) {
      e.preventDefault();
      e.stopPropagation();
      _.getTrigger(-1);
    });
    _.previousPopup = pClone;
    _.content.append(_.previousPopup);
  }

  if (typeof _.nextPopup === 'undefined' && _.cycle) {
    var nClone = $(next).clone();
    nClone.off('click').on('click', function nextPopup(e) {
      e.preventDefault();
      e.stopPropagation();
      _.getTrigger(+1);
    });
    _.nextPopup = nClone;
    _.content.append(_.nextPopup);
  }
  _.isOpen = true;
  _.checkButtons();
};

FinnaPopup.prototype.onPopupInit = function onPopupInit(trigger) { };

FinnaPopup.prototype.onPopupOpen = function onPopupOpen(open, close) {
  var _ = this;
  _.show();

  if (typeof open !== 'undefined') {
    _.customOpen = open;
  }
  if (typeof close !== 'undefined') {
    _.customClose = close;
  }
  var modalClone = _.modalBase.clone();
  _.modalHolder.append(modalClone);
  _.customOpen();
};

FinnaPopup.prototype.toggleScroll = function toggleScroll(value) {
  $(document.body).css('overflow', value ? 'auto' : 'hidden');
};

FinnaPopup.prototype.onPopupClose = function onPopupClose(change) {
  var _ = this;

  if (typeof _.backDrop !== 'undefined') {
    _.backDrop.remove();
    _.backDrop = undefined;
  }

  _.modalHolder = undefined;
  _.content = undefined;
  _.nextPopup = undefined;
  _.previousPopup = undefined;
  _.closeButton = undefined;
  _.customClose();
  _.customOpen = undefined;
  _.customClose = undefined;
  _.isOpen = false;
  if (!_.embed) {
    _.toggleScroll(true);
    $(document).off('focusin.finna');
  }
};

FinnaPopup.prototype.focusTrap = function focusTrap(e) {
  var _ = this;
  if (!$.contains(_.content[0], e.target)) {
    _.content.find(':focusable').eq(0).focus();
  }
};