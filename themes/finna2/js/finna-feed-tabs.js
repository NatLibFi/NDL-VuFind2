/*global finna */
finna.feedTabs = (() => {
  class FeedTabElement extends TabsBase {
    constructor()
    {
      super();
      this.tabsType = 'feed';
      console.log(this.tabsType);
    }

    /**
     * Overwrite this function if needed. When the tab link is clicked.
     * 
     * @param {object} event Event object.
     */
    onLinkClicked(event)
    {
      const root = this.closest(`finna-${this.tabsType}-tab`);
      console.log(`finna-${this.tabsType}-tab`);
      if (root) {
        root.opentab = this.dataset.tab;
      }
    }

    loadContent()
    {
      this.content.innerHTML = '';
      delete this.content.dataset.init;
      this.content.dataset.feed = this.opentab;
      const self = this;
      this.isLoading = true;
      finna.feed.loadFeed(this.content, () =>  {
        self.isLoading = false;
      });
    }
  }

  /**
   * Init feedtabs
   */
  function init() {
    customElements.define('finna-feed-tab', FeedTabElement);
    const containers = document.querySelectorAll('finna-feed-tab');
    const observerController = finna.observer.get();
    observerController.createIntersectionObserver('feedtabs',
      (entries, obs) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            entry.target.lazyload = 'inview';
            obs.unobserve(entry.target);
          }
        }); 
      },
      (container) => {
        container.lazyload = 'inview';
      }
    );
    observerController.addObservable('feedtabs', containers);
  }
  
  return {
    init
  };
})();
