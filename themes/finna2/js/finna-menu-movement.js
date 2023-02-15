/* global finna */

/**
 * Creates an arrow key movement to given menu element, typically an ul.
 * 
 * @param {HTMLUListElement} element 
 */
function FinnaMovement(element) {
  this.menuRootElement = element;
  this.menuElements = [];
  this.setChildData();
  this.setEvents();
}

/**
 * Set events related to the movement component
 */
FinnaMovement.prototype.setEvents = function setEvents() {
  this.mutationObserver = new MutationObserver((mutationList) => {
    for (const mutation of mutationList) {
      const reIndex = mutation.type === 'childList'
        || (mutation.type === 'attributes' && mutation.attributeName === 'aria-expanded');
      if (reIndex) {
        this.setChildData();
        break;
      }
    }
  });
  this.mutationObserver.observe(
    this.menuRootElement,
    {
      attributes: true,
      childList: true,
      subtree: true
    }
  );
  this.menuRootElement.addEventListener('keydown', (e) => {
    this.checkKey(e);
  });
};

/** 
 * Finds all menu elements and their children if the menu is horizontal
 */
FinnaMovement.prototype.setChildData = function setChildData() {
  var i = 0;
  this.menuElements = [];
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

  this.menuRootElement.querySelectorAll(FOCUSABLE_ELEMENTS).forEach((element) => {
    if (null === element.offsetParent) {
      return;
    }
    element.dataset.index = i++;
    this.menuElements.push(element);
  });
};

/**
 * Check the input key given by the user
 */
FinnaMovement.prototype.checkKey = function checkKey(e) {
  if (!this.menuElements.find((el) => e.target === el)) {
    return;
  }
  switch (e.key) {
  case 'ArrowUp':
    this.moveMainmenu(-1);
    e.preventDefault();
    break;
  case 'ArrowLeft':
  case 'ArrowRight':
  case ' ':
    var element = this.getMenuItem(0);
    if (element.tagName !== 'input') {
      $(element).trigger('togglesubmenu');
      e.preventDefault();
    }
    break;
  case 'ArrowDown':
    this.moveMainmenu(1);
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
FinnaMovement.prototype.moveMainmenu = function moveMainMenu(dir) {
  var element = this.getMenuItem(dir);
  if (null === element.offsetParent) {
    this.moveMainmenu(dir);
  } else {
    element.focus();
  }
};

/**
 * Function to fetch wanted element from menuElement with dir.
 * 
 * @param {int} direction
 */
FinnaMovement.prototype.getMenuItem = function getMenuItem(direction) {
  var newIndex = +document.activeElement.dataset.index + direction;
  if (newIndex > this.menuElements.length - 1) {
    return this.menuElements[0];
  } else if (newIndex < 0) {
    return this.menuElements[this.menuElements.length - 1];
  }
  return this.menuElements[newIndex];
};

/**
 * Finna menu movement module.
 */
finna.finnaMovement = (() => {
  var my = {
    init: () => {
      document.querySelectorAll('.finna-movement').forEach((el) => {
        new FinnaMovement(el);
      });
    }
  };

  return my;
})();
