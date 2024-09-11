/* exported getActiveElement, isFocusable, getFocusableElements, trapFocus  */

/**
 * Utility function to get active element in the DOM and shadow DOMs
 *  @param {Document|HTMLElement} [root=document] - The root element or document to search for the active element.
 *  @returns {HTMLElement|null} - The currently active element within the root (or shadow DOM), or `null` if no active element is found.
 */
function getActiveElement(root = document) {
  // Get the active element within the provided root
  const activeEl = root.activeElement;

  // If no active element is found, return null
  if (!activeEl) {
    return null;
  }
  // If the active element has a shadow root, recursively search within the shadow root
  if (activeEl.shadowRoot) {
    return getActiveElement(activeEl.shadowRoot);
  } else {
    // Otherwise, return the active element
    return activeEl;
  }
}

/**
 * Utility function to check if an element is focusable
 * @param {HTMLElement} element - The DOM element to check for focusability.
 * @returns {boolean} - Returns `true` if the element is focusable, `false` otherwise.
 */
function isFocusable(element) {
  if (element.tabIndex >= 0) {
    return true;
  }
  if (element.disabled) {
    return false;
  }
  const focusableElements = ['A', 'BUTTON', 'INPUT', 'SELECT', 'TEXTAREA', 'IFRAME'];
  return focusableElements.includes(element.tagName);
}

/**
 * Utility function to find all focusable elements in the DOM and shadow DOMs
 * @param {HTMLElement|Document} [root=document] - The root element or document to start the search from.
 * @returns {HTMLElement[]} - An array of all focusable elements found within the root and any shadow DOMs.
 */
function getFocusableElements(root = document) {
  const focusableElements = [];

  // Recursively search for focusable elements
  function findFocusable(node) {
    if (isFocusable(node)) {
      focusableElements.push(node);
    }
    // Check for shadow DOM
    if (node.shadowRoot) {
      findFocusable(node.shadowRoot);
    }
    // Traverse child nodes
    node.childNodes.forEach(child => findFocusable(child));
  }
  findFocusable(root);
  return focusableElements;
}

/**
 * Utility function to trap the focus inside the given element
 * @param {HTMLElement} element - The container element to trap focus inside.
 */
function trapFocus(element) {
  const focusableElements = getFocusableElements(element);
  const firstFocusableElement = focusableElements[0];
  const lastFocusableElement = focusableElements[focusableElements.length - 1];
  function handleKeyDown(event) {
    if (event.key === 'Tab') {
      if (!firstFocusableElement) return event.preventDefault();

      const activeElement = getActiveElement();

      // Move focus to the last focusable item in the element
      if (event.shiftKey && activeElement === firstFocusableElement) {
        lastFocusableElement.focus();
        event.preventDefault();
      }
      // Move focus to the first focusable item in the element
      else if (!event.shiftKey && activeElement === lastFocusableElement) {
        firstFocusableElement.focus();
        event.preventDefault();
      }
    }
  }
  element.addEventListener('keydown', handleKeyDown);
}
