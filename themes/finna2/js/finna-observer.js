/* use finna */

finna.observer = (() => {
  class ObserverController
  {
    /**
     * Constructor for ObserverController.
     */
    constructor()
    {
      this.observers = {};
      this.supported = {
        IntersectionObserver: ('IntersectionObserver' in window) ||
          ('IntersectionObserverEntry' in window) ||
          ('isIntersecting' in window.IntersectionObserverEntry.prototype) ||
          ('intersectionRatio' in window.IntersectionObserverEntry.prototype)
      };
    }

    /**
     * Create an Observer of certain type.
     *
     * @param {string}   identifier     Identifier for the observer.
     * @param {function} onEnterScreen  Function when element enters viewport.
     * @param {function} onNoSupport    Function if the observer is not supported.
     * @param {object}   observerParams Params for the observer.
     */
    createIntersectionObserver(
      identifier,
      onEnterScreen,
      onNoSupport,
      observerParams = {}
    ) {
      if (!this.observers[identifier]) {
        if (!this.supported.IntersectionObserver) {
          this.observers[identifier] = new IntersectionObserver(
            onEnterScreen,
            observerParams
          );
        } else {
          this.observers[identifier] = onNoSupport;
        }
      }
    }

    /**
     * Add observable elements to certain observer.
     *
     * @param {string} identifier 
     * @param {NodeList} observable 
     */
    addObservable(identifier, observable)
    {
      observable.forEach((element) => {
        if (typeof this.observers[identifier] === 'function') {
          this.observers[identifier](element);
        } else {
          this.observers[identifier].observe(element);
        }
      });
    }
  }

  /**
   * Singleton for the ObserverController.
   */
  let instance;

  return {
    get: () => {
      if (!instance) {
        instance = new ObserverController();
      }
      return instance;
    }
  };
})();