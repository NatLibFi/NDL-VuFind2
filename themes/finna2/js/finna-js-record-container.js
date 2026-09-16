/*global finna, VuFind*/
/**
 * Module for finna-js-record-container component
 * Exposes functions:
 * - loadContents
 */
finna.jsRecordContainer = (() => {
  /**
   * Load contents to a record specific container i.e similar or record driver related
   * @param {string} selector String selector for element(s) to load contents for
   */
  function loadContents(selector)
  {
    const elements = document.querySelectorAll(selector);
    if (!elements) {
      return;
    }
    if (elements[0].dataset.loaded) {
      return;
    }
    // Mark loaded all elements using the shared data selector:
    document.querySelectorAll(selector).forEach(el => el.dataset.loaded = '1');

    const method = elements[0].dataset.method;
    if (method) {
      const urlParams = {
        id: elements[0].dataset.recordId,
        method: method,
      };
      const dataSource = elements[0].dataset.source;
      if (dataSource) {
        urlParams.source = dataSource;
      }
      fetch(VuFind.path + '/AJAX/JSON?' + new URLSearchParams(urlParams))
        .then(response => response.json())
        .then(result => {
          if (result.data && result.data.html) {
            document.querySelectorAll(selector).forEach(element => {
              VuFind.setInnerHtml(element, VuFind.updateCspNonce(result.data.html));
              element.classList.add('initialized');
            });
          }
        });
    }
  }
  return {
    loadContents
  };
})();
