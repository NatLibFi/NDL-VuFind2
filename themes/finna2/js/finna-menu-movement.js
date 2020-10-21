
/* global finna */
var verticalKeys = {
  'up': 38,
  'down': 40,
  'left': 37,
  'right': 39
};

var horizontalKeys = {
  'up': 37,
  'down': 39,
  'left': 38,
  'right': 40
};

/**
 * Compatible with 2 levels of menusettings
 * 
 * @param {jQuery} element 
 */
function FinnaMovement (element) {
  var _ = this;
  _.menuRootElement = $(element);
  _.menuElements = []; // Objects with children included
  _.setChildData(); // Put elements in an array so we can navigate them easily -1 / +1
  _.keys = _.menuRootElement.hasClass('horizontal') ? horizontalKeys : verticalKeys;
  _.offset = 0;
  _.childOffset = -1; // Useful for handling child object states
  _.setEvents();
}

/**
 * Set events related to the movement component
 */
FinnaMovement.prototype.setEvents = function setEvents() {
  var _ = this;
  _.menuRootElement.on('reindex.finna', function reIndex() {
    _.setChildData();
  });
  _.menuRootElement.on('focusout.finna', function setFocusOut(e) {
    if (!$.contains(_.menuRootElement[0], e.relatedTarget)) {
      _.reset();
    }
  });
  _.menuRootElement.on('keydown.finna', function detectKeyPress(e) {
    _.checkKey(e);
  });
};

/**
 * Reset the internal pointers of movement handler
 */
FinnaMovement.prototype.reset = function reset() {
  var _ = this;
  _.offset = 0;
  _.childOffset = -1;
};

/** 
 * Find each first level child anchor and their possible ul > li > a as children
 */
FinnaMovement.prototype.setChildData = function setChildData() {
  var _ = this;
  var i = 0;
  var firstLevel = _.menuRootElement.find('> li');
  _.menuElements = [];
  firstLevel.each(function getFirstLevel() {
    var obj = {el: $(this), children: [], a: undefined};
    if (obj.el.children('a').length === 0) {
      return true;
    }
    obj.a = obj.el.children('a').first();
    obj.a.attr('tabindex', (i++ === 0) ? '0' : '-1');

    if (obj.el.find('> ul').length) {
      var secondLevel = obj.el.find('ul').first().find('> li');
      secondLevel.each(function getSecondLevel() {
        var a = $(this).find('> a');
        if (a.length) {
          a.attr('tabindex', '-1');
          obj.children.push(a);
        }
      });
    }
    _.menuElements.push(obj);
  });
};

/**
 * Check the input key given by the user
 */
FinnaMovement.prototype.checkKey = function checkKey(e) {
  var _ = this;
  var code = (e.keyCode ? e.keyCode : e.which);
  switch (code) {
  case _.keys.up:
    _.moveMainmenu(-1);
    break;
  case _.keys.right:
    _.moveSubmenu(1);
    break;
  case _.keys.down:
    _.moveMainmenu(1);
    break;
  case _.keys.left:
    _.moveSubmenu(-1);
    break;
  }
};

/**
 * Move the cursor in the level 1 menu elements, adjusted by direction
 * 
 * @param {int} dir
*/
FinnaMovement.prototype.moveMainmenu = function moveMainmenu(dir) {
  var _ = this;
  _.childOffset = -1;
  _.offset = _.calculateOffset(_.offset, _.menuElements, dir);
  _.menuElements[_.offset].a.focus();
};

/**
 * Move the cursor in the level 2 menu elements, adjusted by direction
 * 
 * @param {int} dir
 */
FinnaMovement.prototype.moveSubmenu = function moveSubmenu(dir) {
  var _ = this;
  var current = _.menuElements[_.offset];
  if (current.a.data('preload') === true) {
    current.a.trigger('togglesubmenu');
    current = _.menuElements[_.offset];
  }
  if (current.children.length) {
    if (current.a.hasClass('collapsed')) {
      current.a.trigger('togglesubmenu');
    }
    _.childOffset = _.calculateOffset(_.childOffset, current.children, dir);
    current.children[_.childOffset].focus();
  }
};

/**
 * Function to calculate desired index, given the old offset, array of elements and dir
 * 
 * @param {int} offset
 * @param {Array} elements
 * @param {int} dir
 */
FinnaMovement.prototype.calculateOffset = function calculateOffset(offset, elements, dir) {
  var tmp = offset;
  if (tmp + dir > elements.length - 1) {
    tmp = 0;
  } else if (tmp + dir < 0) {
    tmp = elements.length - 1;
  } else {
    tmp += dir;
  }
  return tmp;
};

finna.finnaMovement = (function finnaMovement() {
  var my = {
    init: function init() {
      $('.finna-movement').each(function initKeyboardMovement() {
        new FinnaMovement(this);
      });
      
    }
  };

  return my;
})();
