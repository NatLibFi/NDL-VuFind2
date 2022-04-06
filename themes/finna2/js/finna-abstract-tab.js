/* exported AbstractTabs */

class AbstractTabs extends HTMLElement {

  /**
   * Variables observed if changed
   */
  static get observedAttributes() {
    return ['lazyload', 'opentab'];
  }

  get identifier() {
    return this.getAttribute('identifier');
  }

  get title() {
    return this.getAttribute('title');
  }

  get active() {
    return this.getAttribute('active');
  }

  get opentab() {
    return this.getAttribute('opentab');
  }

  set opentab(newValue) {
    return this.setAttribute('opentab', newValue);
  }

  get lazyload() {
    return this.getAttribute('lazyload');
  }

  set lazyload(newValue) {
    return this.setAttribute('lazyload', newValue);
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

    this.container = document.createElement('div');
    this.container.className = `${this.tabsType}-container tabs-container`;
    this.append(this.container);
    
    const title = document.createElement('h2');
    title.className = `${this.tabsType}-title title`;
    title.textContent = this.title;
    this.container.append(title);

    this.desktop = document.createElement('ul');
    this.desktop.className = `${this.tabsType}-tabs-holder tabs-holder`;
    this.desktop.role = 'tablist';
    this.container.append(this.desktop);

    this.mobile = document.createElement('ul');
    this.mobile.className = `${this.tabsType}-accordions accordions`;
    this.mobile.role = 'tablist';
    this.append(this.mobile);

    const mobileTitle = document.createElement('h2');
    mobileTitle.className = `${this.tabsType}-accordions-title accordions-title`;
    mobileTitle.textContent = this.title;
    const titleWrapper = document.createElement('li');
    titleWrapper.append(mobileTitle);
    this.mobile.append(titleWrapper);

    this.contentWrapper = document.createElement('li');
    this.contentWrapper.className = 'content-wrapper';
    this.mobile.append(this.contentWrapper);

    this.content = document.createElement('div');
    this.content.className = `${this.tabsType}-tab-content tab-content`;
    this.contentWrapper.append(this.content);

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

  /**
   * Empty function for use when before creation manipulation is needed.
   */
  beforeCreate() { }

  attributeChangedCallback(name, oldValue, newValue)
  {
    switch (name) {
    case 'lazyload':
      if (newValue === 'inview') {
        this.beforeCreate();
        this.createElement();
        this.removeAttribute('lazyload');
      }
      break;
    case 'opentab':
      if (newValue) {
        this.setActive();
        this.loadContent();
      }
      break;
    }
  }

  setEvents()
  {
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

  onLinkClicked(link)
  {
    if (link) {
      this.opentab = link.parentNode.dataset.tab;
    }
  }

  /**
   * Function to load initial tab.
   */
  initialLoad()
  {
    const hash = this.getTabFromLocationHash();
    const anchor = this.anchors.find((element) => {
      return `${this.identifier}-${element.dataset.tab}` === hash;
    });
    if (anchor) {
      this.opentab = anchor.dataset.tab;
      return;
    }
    if (this.active) {
      this.opentab = this.active;
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
    const name = value.replace(/^\W/, '-');
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
    const parent = this;
    a.addEventListener('click', function asd(e) {
      parent.onLinkClicked(this);
    });
  }

  /**
   * Assign properties to the accordion element.
   */
  adjustAccordion(el, key, value)
  {
    const name = value.replace(/^\W/, '-');
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
    const parent = this;
    a.addEventListener('click', function asd(e) {
      parent.onLinkClicked(this);
    });
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
        if (isTab && anchor.classList.contains('accordion')) {
          anchor.after(this.contentWrapper);
        }
      });
    }
  }

  /**
   * Overwrite this function if needed. When the content is set to be loaded.
   */
  loadContent()
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
