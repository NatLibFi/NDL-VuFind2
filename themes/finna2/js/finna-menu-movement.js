/* global finna */

/**
 * Creates an arrow key movement to given menu element, typically an ul.
 * 
 * @param {jQuery} element 
 */
function FinnaMovement (element) {
  var _ = this;
  _.menuRootElement = $(element);
  _.menuElements = [];
  _.setChildData();
  _.indexCache = -1;
  _.setEvents();
}

/**
 * Set events related to the movement component
 */
FinnaMovement.prototype.setEvents = function setEvents() {
  var _ = this;
  _.mutationObserver = new MutationObserver((mutationList) => {
    for (const mutation of mutationList) {
      const reIndex = mutation.type === 'childList'
        || (mutation.type === 'attributes' && mutation.attributeName === 'aria-expanded');
      if (reIndex) {
        _.setChildData();
        _.setFocusTo();
        break;
      }
    }
  });
  _.mutationObserver.observe(_.menuRootElement[0], {attributes: true, childList: true, subtree: true})
  _.menuRootElement.on('keydown', function detectKeyPress(e) {
    _.checkKey(e);
  });
};

/**
 * Function to refocus to cached element
 */
FinnaMovement.prototype.setFocusTo = function setFocusTo() {
  var _ = this;
  if (_.indexCache !== -1) {
    var element = _.getMenuItem(0, _.indexCache);
    element.focus();
  }
};

/** 
 * Finds all menu elements and their children if the menu is horizontal
 */
FinnaMovement.prototype.setChildData = function setChildData() {
  var _ = this;
  var i = 0;
  _.menuElements = [];

  var FOCUSABLE_ELEMENTS = [
    'a[href]',
    'area[href]',
    'input[type=radio]:checked',
    'input:not([disabled]):not([type="hidden"]):not([aria-hidden]):not([type=radio])',
    'select:not([disabled]):not([aria-hidden])',
    'textarea:not([disabled]):not([aria-hidden])',
    'button:not([disabled]):not([aria-hidden]):not(.tooltip-myaccount)',
    'iframe',
    'object',
    'embed',
    '[contenteditable]',
    '[tabindex]:not([tabindex^="-"])'
  ];

  var nodes = _.menuRootElement[0].querySelectorAll(FOCUSABLE_ELEMENTS);
  var children = [].slice.apply(nodes);
  var formedObjects = [];
  children.forEach(function createElement(element) {
    if (null === element.offsetParent) {
      return;
    }
    var input = $(element);
    input.data('index', i++);
    formedObjects.push(input);
  });
  _.menuElements = formedObjects;
};

/**
 * Check the input key given by the user
 */
FinnaMovement.prototype.checkKey = function checkKey(e) {
  var _ = this;
  if (!_.menuElements.find((el) => e.target === el[0])) {
    return;
  }
  switch (e.key) {
  case 'ArrowUp':
    _.moveMainmenu(-1);
    e.preventDefault();
    break;
  case 'ArrowLeft':
  case 'ArrowRight':
  case 'Space':
    var element = _.getMenuItem(0);
    if (!element.is('input')) {
      element.trigger('togglesubmenu');
      e.preventDefault();
    }
    break;
  case 'ArrowDown':
    _.moveMainmenu(1);
    e.preventDefault();
    break;
  }
};

/**
 * Move the cursor in the level 1 menu elements, adjusted by direction
 * 
 * @param {int} dir
 *
 */
FinnaMovement.prototype.moveMainmenu = function moveMainmenu(dir) {
  var _ = this;
  var element = _.getMenuItem(dir);
  if (element.is(':hidden')) {
    _.moveMainmenu(dir);
  } else {
    element.focus();
  }
};

/**
 * Function to fetch wanted element from menuElement with dir. Optionally you can use cacheIndex to 
 * get certain element
 * 
 * @param {int} direction
 * @param {int} cacheIndex
 */
FinnaMovement.prototype.getMenuItem = function getMenuItem(direction, cacheIndex) {
  var _ = this;
  var currentIndex = cacheIndex || $(':focus').data('index');
  var newIndex = +currentIndex + direction;

  if (newIndex > _.menuElements.length - 1) {
    newIndex = 0;
  } else if (newIndex < 0) {
    newIndex = _.menuElements.length - 1;
  }
  _.indexCache = newIndex;
  return _.menuElements[newIndex];
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
