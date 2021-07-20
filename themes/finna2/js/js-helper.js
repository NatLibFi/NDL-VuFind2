/* exported jsHelper */
var jsHelper = (function jsHelper() {

  /**
   * Create a html element
   * 
   * @param {String} tag 
   * @param {String} className 
   * @param {Object} attributes 
   */
  function createElement(tag, className, attributes) {
    var element = document.createElement(tag);
    element.className = className;
    if (attributes) {
      for (var attr in attributes) {
        if (attributes.hasOwnProperty(attr)) {
          element.setAttribute(attr, attributes[attr]);
        }
      }
    }
    return element;
  }

  /**
   * Find next sibling with syntax
   * 
   * @param {HTMLElement} elem 
   * @param {String} selector 
   */
  function getNextElementSibling(elem, selector) {
    var sibling = elem.nextElementSibling;

    while (sibling) {
      if (sibling.matches(selector)) {
        return sibling;
      }
      sibling = sibling.nextElementSibling;
    }
    return undefined;
  }

  /**
   * Find previous sibling with syntax
   * 
   * @param {HTMLElement} elem 
   * @param {String} selector 
   */
  function getPreviousElementSibling(elem, selector) {
    var sibling = elem.previousElementSibling;

    while (sibling) {
      if (sibling.matches(selector)) {
        return sibling;
      }
      sibling = sibling.previousElementSibling;
    }
    return undefined;
  }

  /**
   * Binds an event to the element and given selector checks if the event can be ran
   * Useful for binding events to dynamically created buttons etc.
   * 
   * @param {HTMLElement} el
   * @param {String} selector
   * @param {String} eventName
   * @param {Array} path 
   */
  function addDynamicListener(el, selector, eventName, handler) {
    el.addEventListener(eventName, function checkEvent(e) {
      // loop parent nodes from the target to the delegation node
      for (var target = e.target; target && target !== this; target = target.parentNode) {
        if (target.matches(selector)) {
          handler.call(target, e);
          break;
        }
      }
    }, false);
  }

  /**
   * Find parent which matches the selector
   * 
   * @param {HTMLElement} el 
   * @param {String} selector 
   */
  function findParent(el, selector) {
    for (var target = el.parentNode; target; target = target.parentNode) {
      if (target.matches(selector)) {
        return target;
      }
    }
    return undefined;
  }

  /**
   * Check if the element is same as selector
   * 
   * @param {object} el 
   * @param {String} selector 
   */
  function is(el, selector) {
    return (el.matches || el.matchesSelector || el.msMatchesSelector || el.mozMatchesSelector || el.webkitMatchesSelector || el.oMatchesSelector).call(el, selector);
  }

  /**
   * Check if the element is same as selector
   * 
   * @param {HTMLElement} el 
   * @param {String} selector 
   */
  function offset(el) {
    var rect = el.getBoundingClientRect();

    return {
      top: rect.top + document.body.scrollTop,
      left: rect.left + document.body.scrollLeft
    };
  }

  /**
   * Return the outer height of element with margin
   * 
   * @param {HTMLElement} el 
   */
  function outerHeightWithMargin(el) {
    var height = el.offsetHeight;
    var style = getComputedStyle(el);
  
    height += parseInt(style.marginTop) + parseInt(style.marginBottom);
    return height;
  }

  /**
   * Return the outer width of element with margin
   * 
   * @param {HTMLElement} el 
   */
  function outerWidthWithMargin(el) {
    var width = el.offsetWidth;
    var style = getComputedStyle(el);

    width += parseInt(style.marginLeft) + parseInt(style.marginRight);
    return width;
  }

  /**
   * Add a listener for when DOMContent has been loaded
   * 
   * @param {Function} el 
   */
  function ready(fn) {
    if (document.readyState !== 'loading'){
      fn();
    } else {
      document.addEventListener('DOMContentLoaded', fn);
    }
  }
  
  /**
   * Trigger a custom event
   * 
   * @param {HTMLElement} el
   * @param {String} eventName 
   * @param {Object} data 
   */
  function triggerCustomEvent(el, eventName, data) {
    var event;
    if (window.CustomEvent && typeof window.CustomEvent === 'function') {
      event = new CustomEvent(eventName, {detail: data});
    } else {
      event = document.createEvent('CustomEvent');
      event.initCustomEvent(eventName, true, true, data);
    }

    el.dispatchEvent(event);
  }

  /**
   * Trigger a native event
   * 
   * @param {HTMLElement} el 
   * @param {String} eventName 
   */
  function triggerNativeEvent(el, eventName) {
    var event = document.createEvent('HTMLEvents');
    event.initEvent(eventName, true, false);
    el.dispatchEvent(event);
  }

  /**
   * Get position of elements as top and left object
   * 
   * @param {HTMLElement} element 
   */
  function getPosition(element) {
    var style = window.getComputedStyle(element);
    var marginTop = style.getPropertyValue('margin-top');
    var marginLeft = style.getPropertyValue('margin-left');

    return {
      top: element.offsetTop - parseFloat(marginTop),
      left: element.offsetLeft - parseFloat(marginLeft)
    };
  }

  var my = {
    is: is,
    offset: offset,
    outerHeightWithMargin: outerHeightWithMargin,
    outerWidthWithMargin: outerWidthWithMargin,
    ready: ready,
    triggerCustomEvent: triggerCustomEvent,
    triggerNativeEvent: triggerNativeEvent,
    createElement: createElement,
    addDynamicListener: addDynamicListener,
    getPosition: getPosition,
    getNextElementSibling: getNextElementSibling,
    getPreviousElementSibling: getPreviousElementSibling,
    findParent: findParent
  };

  return my;
})();