/*global finna, AbstractTabs */
finna.feedTabs = (() => {
  class FeedTabsElement extends AbstractTabs {
    constructor()
    {
      super();
      this.tabsType = 'feed';
    }

    loadContent()
    {
      this.content.innerHTML = '';
      delete this.content.dataset.init;
      this.content.dataset.feed = this.opentab;
      const self = this;
      this.isLoading = true;
      finna.feed.loadFeed(this.content, () => {
        self.isLoading = false;
      });
    }
  }

  /**
   * Init feedtabs
   */
  function init() {
    customElements.define('finna-feed-tabs', FeedTabsElement);
    const containers = document.querySelectorAll('finna-feed-tabs');
    finna.observer.createIntersectionObserver('feedtabs',
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
    finna.observer.observe('feedtabs', containers);
  }
  
  return {
    init
  };
})();
