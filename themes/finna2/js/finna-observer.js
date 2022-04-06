/* global finna */

finna.observer = (() => {
  const supported = {
    IntersectionObserver: ('IntersectionObserver' in window) ||
      ('IntersectionObserverEntry' in window) ||
      ('isIntersecting' in window.IntersectionObserverEntry.prototype) ||
      ('intersectionRatio' in window.IntersectionObserverEntry.prototype)
  };
  const observers = {};

  /**
   * Create an Observer of certain type.
   *
   * @param {string}   identifier     Identifier for the observer.
   * @param {function} onEnterScreen  Function when element enters viewport.
   * @param {function} onNoSupport    Function if the observer is not supported.
   * @param {object}   observerParams Params for the observer.
   */
  function createIntersectionObserver(
    identifier,
    onEnterScreen,
    onNoSupport,
    observerParams = {}
  ) {
    if (!observers[identifier]) {
      if (!supported.IntersectionObserver) {
        observers[identifier] = new IntersectionObserver(
          onEnterScreen,
          observerParams
        );
      } else {
        observers[identifier] = onNoSupport;
      }
    }
  }

  /**
   * Add observable elements to certain observer.
   *
   * @param {string} identifier 
   * @param {NodeList} observable 
   */
  function observe(identifier, observable)
  {
    observable.forEach((element) => {
      if (typeof observers[identifier] === 'function') {
        observers[identifier](element);
      } else {
        observers[identifier].observe(element);
      }
    });
  }

  return {
    createIntersectionObserver,
    observe
  };
})();