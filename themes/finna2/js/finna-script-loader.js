/* global finna, VuFind */

/**
 * Module for a script loader.
 * Exposes functions:
 * - load
 * - loadInOrder
 * @returns {object} Exposed functions
 */
finna.scriptLoader = (() => {

  /**
   * Asynchronous function to load scripts in given order.
   * @param {object}   scripts        Object of scripts to load
   *                                  Key is an unique identifier used to check if
   *                                  script has already been loaded
   *                                  Value is the js file name to load
   * @param {?Function} scriptsLoaded Callback when the scripts are loaded
   */
  async function load(scripts, scriptsLoaded = () => {}) {
    for (let [key, value] of Object.entries(scripts)) {
      key = `scriptloader-js-${key}`;
      // Create a promise for the current script to see if it has been resolved/loaded
      let promise = finna.getPromise(key);
      if (!promise) {
        finna.setPromise(key);
        promise = finna.getPromise(key);
        const scriptElement = document.createElement('script');
        scriptElement.async = 'async';
        scriptElement.src = `${VuFind.path}/themes/finna2/js/${value}?_=${Date.now()}`;
        scriptElement.addEventListener('load', () => {
          finna.resolvePromise(key);
        });
        scriptElement.id = key;
        scriptElement.setAttribute('nonce', VuFind.getCspNonce());
        document.head.appendChild(scriptElement);
      }
      // Wait until the promise has resolved (Script has loaded) until loading the next one.
      await promise;
    }
    scriptsLoaded();
  }

  /**
   * Load given scripts asynchronously. First are the scripts to be loaded before
   * the last scripts can be loaded.
   * @param {object}   first          First scripts to load.
   *                                  Key is an unique identifier used to check if
   *                                  script has already been loaded
   *                                  Value is the js file name to load
   * @param {object}   last           Last scripts to load.
   *                                  Key is an unique identifier used to check if
   *                                  script has already been loaded
   *                                  Value is the js file name to load
   * @param {?Function} scriptsLoaded Callback when the scripts are loaded
   */
  function loadInOrder(first, last, scriptsLoaded) {
    let combined = {...first, ...last};
    load(combined, scriptsLoaded);
  }

  return {
    load,
    loadInOrder
  };
})();
