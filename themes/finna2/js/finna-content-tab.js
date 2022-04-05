/*global finna */
finna.contentTabs = (() => {
  class ContentTabElement extends AbstractTabs {
    constructor()
    {
      super();
      this.tabsType = 'content';
      this.contents = [];
    }

    /**
     * Get all the divs for showing as content.
     */
    beforeCreate ()
    {
      [...this.children].forEach((div) => {
        const wrapper = document.createElement('li');
        wrapper.append(div);
        this.contents.push(wrapper);
      });
    }

    /**
     * When the content is set to be loaded.
     */
    loadContent()
    {
      this.contents.forEach((content) => {
        if (content.dataset.reference === this.opentab) {
          content.style.display = null;
        } else {
          content.style.display = 'none';
        }
      });
    }

    /**
     * Function to create tab elements.
     */
    createTabElements()
    {
      let i = 0;
      for (const [key, value] of Object.entries(this.tabs)) {
        const accordionClone = this.anchorElement.cloneNode(true);
        const tabClone = this.tabElement.cloneNode(true);
        this.adjustAccordion(accordionClone, key, value);
        this.adjustTab(tabClone, key, value);

        this.desktop.append(tabClone);
        this.mobile.append(accordionClone);
        const currentContent = this.contents[i++];
        currentContent.dataset.reference = value;
        this.mobile.append(currentContent);
        this.anchors.push(tabClone, accordionClone);
      }
      this.contentWrapper.remove();
    }
    /**
     * Set proper tab as active.
     */
    setActive()
    {
      if (this.opentab) {
        this.anchors.forEach((anchor) => {
          const isTab = anchor.dataset.tab === this.opentab;
          anchor.classList.toggle('active', isTab);
          anchor.setAttribute('aria-selected', isTab);
        });
      }
    }
  }
  

  /**
   * Init feedtabs
   */
  function init() {
    customElements.define('finna-content-tab', ContentTabElement);
    const containers = document.querySelectorAll('finna-content-tab');
    const observerController = finna.observer.get();
    observerController.createIntersectionObserver('contenttabs',
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
    observerController.addObservable('contenttabs', containers);
  }
  
  return {
    init
  };
})();
