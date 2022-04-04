/*global finna */
finna.feedTabs = (() => {
  class FeedTabElement extends TabsBase {
    constructor()
    {
      super();
      this.tabsType = 'feed';
    }
  
    /**
     * Overwrite this function to do personal stuff.
     * 
     * @param {object} event Event object.
     */
    onLinkClicked(event)
    {
      const root = this.closest('finna-feed-tab');
      if (root) {
        root.opentab = this.dataset.tab;
      }
    }
  
    loadContent()
    {
      this.content.innerHTML = '';
      delete this.content.dataset.init;
      this.content.dataset.feed = this.opentab;
      finna.feed.loadFeed(this.content);
    }
  }
  
  let lazyTabObserver;
  /**
   * Init feedtabs
   */
  function init() {
    customElements.define('finna-content-tab', FeedTabElement);
    const containers = document.querySelectorAll('finna-content-tab');
    finna.observer.get().create()
    if (!('IntersectionObserver' in window) ||
      !('IntersectionObserverEntry' in window) ||
      !('isIntersecting' in window.IntersectionObserverEntry.prototype) ||
      !('intersectionRatio' in window.IntersectionObserverEntry.prototype)
    ) {
      // Fallback: display images instantly on browsers that don't support the observer properly
      containers.forEach((container) => container.lazyload = 'inview');
    } else {
      if (!lazyTabObserver) {
        lazyTabObserver = new IntersectionObserver((entries, obs) => {
          entries.forEach((entry) => {
            if (entry.isIntersecting) {
              entry.target.lazyload = 'inview';
              obs.unobserve(entry.target);
            }
          }); 
        });
      }
      containers.forEach((container) => lazyTabObserver.observe(container));
    }
  }
  
  return {
    init
  };
})();
