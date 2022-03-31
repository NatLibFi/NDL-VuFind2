/*global finna */
finna.feedTabs = (function finnaFeedTab() {
  class TabsBase extends HTMLElement {

    /**
     * Variables observed if changed
     */
    static get observedAttributes() { return ['inview']; }

    get identifier() {
      return this.getAttribute('identifier');
    }

    get title() {
      return this.getAttribute('title');
    }

    get active() {
      return this.getAttribute('active');
    }

    get tabs() {
      return JSON.parse(this.getAttribute('tabs'));
    }

    constructor()
    {
      super();
      this.tabsType = 'abstract';
      this.anchors = [];
      this.tabContent = '';
      this.allowHashChange = false;
      this.isLoading = false;
    }

    createElement()
    {
      this.classList.add('abstract', 'tabs', `${this.tabsType}-tabs`);
      this.id = `${this.tabsType}-tabs-${this.identifier}`;
  
      this.normal = document.createElement('div');
      this.normal.className = 'visible-md visible-lg';
      this.append(this.normal);
  
      this.container = document.createElement('div');
      this.container.className = `${this.tabsType}-container tabs-container`;
      this.normal.append(this.container);
      
      const title = document.createElement('h2');
      title.className = `${this.tabsType}-title title`;
      title.textContent = this.title;
      this.container.append(title);
  
      this.desktop = document.createElement('ul');
      this.desktop.className = `${this.tabsType}-tabs-holder tabs-holder nav nav-tabs`;
      this.desktop.role = 'tablist';
      this.container.append(this.desktop);
  
      this.mobile = document.createElement('ul');
      this.mobile.className = `${this.tabsType}-accordions accordions`;
      this.mobile.role = 'tablist';
      this.append(this.mobile);
  
      const mobileTitle = document.createElement('h2');
      mobileTitle.className = `${this.tabsType}-accordions-title accordions-title`;
      mobileTitle.textContent = this.title;
      this.mobile.append(mobileTitle);
  
      this.content = document.createElement('div');
      this.content.className = `${this.tabsType}-tab-content tab-content`;
      this.append(this.content);

      const contentContainer = document.createElement('div');
      contentContainer.className = `${this.tabsType}-container content-container`;
      this.content.append(contentContainer);

      this.tabElement = document.createElement('li');
      this.tabElement.className = `${this.tabsType}-tab tab`;
      this.tabElement.role = 'tab';
  
      this.anchorElement = document.createElement('li');
      this.anchorElement.className = `${this.tabsType}-accordion accordion`;
  
      const tabAnchor = document.createElement('a');
      tabAnchor.dataset.lightboxIgnore = '';
      tabAnchor.className = `${this.tabsType}-tab-anchor tab-anchor`;
  
      const accordionAnchor = document.createElement('a');
      accordionAnchor.dataset.lightboxIgnore = '';
      accordionAnchor.className = `${this.tabsType}-accordion-anchor accordion-anchor`;
  
      this.tabElement.append(tabAnchor);
      this.anchorElement.append(accordionAnchor);

      this.createTabElements();
      this.setEvents();
      this.initialLoad();
    }

    attributeChangedCallback(name, oldValue, newValue)
    {
      switch (name) {
      case 'inview':
        if (newValue === 'true') {
          this.createElement();
        }
        break;
      }
    }

    setEvents()
    {
      this.addEventListener('tabs-set-active', this.setActive);
      const ref = this;
      window.addEventListener('hashchange', () => {
        var hash = ref.getTabFromLocationHash();
        if (!hash
          || !hash.includes(this.identifier)
          || ref.isLoading
        ) {
          return;
        }
        ref.anchors.forEach((element) => {
          if (element.classList.contains(`${ref.tabsType}-tab`)
            && `${this.identifier}-${element.dataset.tab}` === hash
            && !element.classList.contains('active')
          ) {
            element.click();
            element.querySelector('a').focus();
          }
        });
      });
    }

    /**
     * Function to load initial tab.
     */
    initialLoad()
    {
      const hash = this.getTabFromLocationHash();
      if (!hash || !hash.includes(this.identifier)) {
        console.log(hash);
        this.anchors[0].click();
        return;
      }
      console.log('lets find');
      const anchor = this.anchors.find((element) => {
        return `${this.identifier}-${element.dataset.tab}` === hash;
      });
      if (anchor) {
        anchor.click();
      }
    }

    /**
     * Function to create tab elements.
     */
    createTabElements()
    {
      for (const [key, value] of Object.entries(this.tabs)) {
        const accordionClone = this.anchorElement.cloneNode(true);
        const tabClone = this.tabElement.cloneNode(true);
        tabClone.addEventListener('click', this.onLinkClicked);
        accordionClone.addEventListener('click', this.onLinkClicked);
        this.adjustAccordion(accordionClone, key, value);
        this.adjustTab(tabClone, key, value);
        this.desktop.append(tabClone);
        this.mobile.append(accordionClone);
        this.anchors.push(tabClone, accordionClone);
      }
    }

    /**
     * Assign properties to the tab element.
     */
    adjustTab(el, key, value)
    {
      const name = value.replace(/\W/, '-');
      const tabID = `${this.identifier}-${value}`;
      const isActive = value === this.active;
      el.dataset.tab = name;
      el.setAttribute('aria-selected', isActive ? 'true' : 'false');
      if (isActive) {
        el.classList.add('active');
      }

      const a = el.querySelector('a');
      a.textContent = key;
      a.href = `#${tabID}`;
      a.id = `${tabID}-tab`;
      a.setAttribute('aria-label', key);
    }

    /**
     * Assign properties to the accordion element.
     */
    adjustAccordion(el, key, value)
    {
      const name = value.replace(/\W/, '-');
      const tabID = `${this.identifier}-${value}`;
      const isActive = value === this.active;
      el.dataset.tab = name;
      el.setAttribute('aria-selected', isActive ? 'true' : 'false');
      if (isActive) {
        el.classList.add('active');
      }

      const a = el.querySelector('a');
      a.textContent = key;
      a.href = `#${tabID}`;
      a.id = `${tabID}-accordion`;
      a.setAttribute('aria-label', key);
    }

    /**
     * Set proper tab as active.
     *
     * @param {object} event
     */
    setActive(event)
    {
      const tab = event.detail.caller.dataset.tab || false;
      if (tab) {
        this.anchors.forEach((anchor) => {
          const isTab = tab === anchor.dataset.tab;
          anchor.classList.toggle('active', isTab);
          anchor.setAttribute('aria-selected', isTab);
        });
        this.loadContent(event.detail.caller);
      }
    }
    /**
     * Overwrite this function if needed.
     * 
     * @param {object} event Event object.
     */
    onLinkClicked(event)
    {
      const root = this.closest('finna-abstract-tab');
      if (root) {
        root.dispatchEvent(new CustomEvent('tabs-set-active', {detail: {caller: this}}));
      }
    }

    /**
     * Overwrite this function if needed.
     * 
     * @param {HTMLLIElement} tab List element with data.
     */
    loadContent(tab)
    {
      /** Empty in abstract class */
    }

    /**
     * Return the location hash without hashtag
     * 
     * @return {String} hash without hashtag
     */
    getTabFromLocationHash() {
      var hash = window.location.hash;
      return hash ? hash.substring(1) : '';
    }
  }

  class FeedTabElement extends TabsBase {
    constructor()
    {
      super();
      this.tabsType = 'feed';
      console.log('asd');
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
        root.dispatchEvent(new CustomEvent('tabs-set-active', {detail: {caller: this}}));
      }
    }

    loadContent(tab)
    {
      this.content.innerHTML = '';
      delete this.content.dataset.init;
      this.content.dataset.feed = tab.dataset.tab;
      finna.feed.loadFeed(this.content);
    }
  }

  let lazyTabObserver;
  /**
   * Init feedtabs
   */
  function init() {
    customElements.define('finna-feed-tab', FeedTabElement);
    customElements.define('finna-linkedevent-tab', LinkedEventTabElement);
    customElements.define('finna-feed', Feed);
    const containers = document.querySelectorAll('finna-feed-tab, finna-linkedevent-tab');
    if (!('IntersectionObserver' in window) ||
      !('IntersectionObserverEntry' in window) ||
      !('isIntersecting' in window.IntersectionObserverEntry.prototype) ||
      !('intersectionRatio' in window.IntersectionObserverEntry.prototype)
    ) {
      // Fallback: display images instantly on browsers that don't support the observer properly
      containers.forEach((container) => container.setAttribute('inview', 'true'));
    } else {
      if (!lazyTabObserver) {
        lazyTabObserver = new IntersectionObserver((entries, obs) => {
          entries.forEach((entry) => {
            if (entry.isIntersecting) {
              entry.target.setAttribute('inview', 'true');
              obs.unobserve(entry.target);
            }
          }); 
        });
      }
      containers.forEach((container) => lazyTabObserver.observe(container));
    }
  }

  var my = {
    init: init
  };

  return my;
})();

