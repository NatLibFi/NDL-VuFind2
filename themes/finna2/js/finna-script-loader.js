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
   * If this is the first time calling this function with a certain key,
   * assign all the script loading tasks to the first caller to avoid duplicates.
   * @param {object}   scripts        Object of scripts to load
   *                                  Key is an unique identifier used to check if
   *                                  script has already been loaded
   *                                  Value is the js file name to load
   * @param {?Function} scriptsLoaded Callback when the scripts are loaded
   */
  function load(scripts, scriptsLoaded = () => {}) {
    let promisesToWait = [];
    const onLoadFunc = (e) => finna.resolvePromise(e.currentTarget.id);
    for (let [key, value] of Object.entries(scripts)) {
      key = `scriptloader-js-${key}`;
      let foundPromise = finna.getPromise(key);
      if (!foundPromise) {
        foundPromise = finna.setPromise(key);
        const scriptElement = document.createElement('script');
        scriptElement.src = `${VuFind.path}/themes/finna2/js/${value}?_=${Date.now()}`;
        scriptElement.async = 'async';
        scriptElement.id = key;
        scriptElement.addEventListener('load', onLoadFunc);
        scriptElement.setAttribute('nonce', VuFind.getCspNonce());
        document.head.appendChild(scriptElement);
      }
      promisesToWait.push(foundPromise);
      // Wait until the promise has resolved (Script has loaded) until loading the next one.
    }
    const handlePromise = (cb) => {
      promisesToWait.shift().then(() => {
        if (promisesToWait.length > 0) {
          handlePromise(cb);
        } else {
          cb();
        }
      });
    };
    handlePromise(scriptsLoaded);
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
    let combined = Object.assign({}, first, last);
    load(combined, scriptsLoaded);
  }

  return {
    load,
    loadInOrder
  };
})();
