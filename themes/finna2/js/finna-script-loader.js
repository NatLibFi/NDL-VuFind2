/* global finna, VuFind */

/**
 * Module for a script loader.
 * Exposes functions:
 *  - init
 *  - load
 *
 * @returns {object} Exposed functions
 */
finna.scriptLoader = (() => {

  /**
   * Cache all scripts which are already loaded here
   *
   * @var {array}
   */
  const scriptCache = [];

  /**
   * Load given scripts asynchronously.
   *
   * @param {object}   scripts       Object of scripts to load
   *                                 Key is an unique identifier used to check if
   *                                 script has already been loaded
   *                                 Value is the js file name to load
   * @param {?function} scriptsLoaded Callback when the scripts are loaded
   */
  function load(scripts, scriptsLoaded) {
    let keyCount = Object.keys(scripts).length;
    let onScriptLoad;
    if (keyCount) {
      onScriptLoad = () => {
        if (--keyCount === 0) {
          scriptsLoaded();
        }
      };
    }
    for (const [key, value] of Object.entries(scripts)) {
      if (scriptCache.includes(key)) {
        keyCount--;
        continue;
      }
      scriptCache.push(key);
      const scriptElement = document.createElement('script');
      scriptElement.async = 'async';
      scriptElement.src = `${VuFind.path}/themes/finna2/js/${value}`;
      if (typeof onScriptLoad === 'function' && typeof scriptsLoaded === 'function') {
        scriptElement.addEventListener('load', onScriptLoad);
      }
      scriptElement.id = key;
      scriptElement.setAttribute('nonce', VuFind.getCspNonce());
      document.head.appendChild(scriptElement);
    }
    if (keyCount === 0 && typeof scriptsLoaded === 'function') {
      scriptsLoaded();
    }
  }

  /**
   * Load given scripts asynchronously. First are the scripts to be loaded before
   * the last scripts can be loaded.
   *
   * @param {object}   first          First scripts to load.
   *                                  Key is an unique identifier used to check if
   *                                  script has already been loaded
   *                                  Value is the js file name to load
   * @param {object}   last           Last scripts to load.
   *                                  Key is an unique identifier used to check if
   *                                  script has already been loaded
   *                                  Value is the js file name to load
   * @param {?function} scriptsLoaded Callback when the scripts are loaded
   */
  function loadInOrder(first, last, scriptsLoaded) {
    load(first, () => {
      load(last, scriptsLoaded);
    });
  }
  

  return {
    load,
    loadInOrder
  };
})();
