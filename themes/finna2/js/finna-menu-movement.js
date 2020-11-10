/* global finna */

/**
 * Supports menu structures like ul > li > a and ul > li > (a + ul) > li > a 
 * 
 * @param {jQuery} element 
 */
function FinnaMovement (element) {
  var _ = this;
  _.menuRootElement = $(element);
  _.menuElements = [];
  _.isHorizontal = _.menuRootElement.hasClass('horizontal');
  _.setChildData();
  _.keys = {
    up: 38,
    down: 40,
    left: 37,
    right: 39
  };
  _.offset = 0;
  _.childOffset = -1;
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
  _.menuRootElement.on('focusout', function setFocusOut(e) {
    if (!$.contains(_.menuRootElement[0], e.relatedTarget)) {
      _.reset();
    }
  });
  _.menuRootElement.on('keydown', function detectKeyPress(e) {
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
  _.menuElements = [];

  function formLeveledChildren(el) {
    var formedObjects = [];
    if (el.find('> li').length) {
      el.children('li').each(function cycleChildren() {
        var obj = {el: $(this), children: [], a: undefined};
        obj.a = obj.el.children('a').first();
        if (!obj.a.length) {
          return true;
        }
        obj.a.attr('tabindex', (i++ === 0) ? '0' : '-1');
        var children;
        if (obj.el.find('ul, div > ul').length) {
          children = formLeveledChildren(obj.el.find('ul, div > ul').first());
        }
        if (typeof children !== 'undefined' && _.isHorizontal) {
          obj.children = children;
        }
        formedObjects.push(obj);
        if (typeof children !== 'undefined' && !_.isHorizontal) {
          formedObjects = formedObjects.concat(children);
        }
      });
    }
    return formedObjects;
  }
  _.menuElements = formLeveledChildren(_.menuRootElement);
};

/**
 * Check the input key given by the user
 */
FinnaMovement.prototype.checkKey = function checkKey(e) {
  var _ = this;
  var code = (e.keyCode ? e.keyCode : e.which);
  switch (code) {
  case _.keys.up:
    if (_.isHorizontal) {
      _.moveSubmenu(-1);
    } else {
      _.moveMainmenu(-1);
    }
    e.preventDefault();
    break;
  case _.keys.right:
    if (_.isHorizontal) {
      _.moveMainmenu(1);
    } else {
      _.moveSubmenu(1);
    }
    e.preventDefault();
    break;
  case _.keys.down:
    if (_.isHorizontal) {
      _.moveSubmenu(1);
    } else {
      _.moveMainmenu(1);
    }
    e.preventDefault();
    break;
  case _.keys.left:
    if (_.isHorizontal) {
      _.moveMainmenu(-1);
    } else {
      _.moveSubmenu(-1);
    }
    e.preventDefault();
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
  if (_.menuElements[_.offset].a.is(':hidden')) {
    _.moveMainmenu(dir);
  } else {
    _.menuElements[_.offset].a.focus();
  }
};

/**
 * Move the cursor in the level 2 menu elements, adjusted by direction
 * 
 * @param {int} dir
 */
FinnaMovement.prototype.moveSubmenu = function moveSubmenu(dir) {
  var _ = this;
  var current = _.menuElements[_.offset];
  current.a.trigger('togglesubmenu');

  if (current.children.length) {
    if (current.a.hasClass('collapsed')) {
      current.a.trigger('togglesubmenu');
    }
    _.childOffset = _.calculateOffset(_.childOffset, current.children, dir);
    current.children[_.childOffset].a.focus();
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
