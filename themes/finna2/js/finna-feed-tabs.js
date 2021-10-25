/*global finna */
finna.feedTabs = (function finnaFeedTab() {
  function FeedTab(container) {
    var _ = this;
    container.classList.add('inited');
    _.tabs = container.querySelectorAll('.feed-tab-anchor');
    _.accordions = container.querySelectorAll('.feed-accordion-anchor');
    _.tabContent = container.querySelector('.tab-content');
    _.setEvents();
    _.firstLoad();

    _.isLoading = false;
  }

  /**
   * Set proper events to listen for
   */
  FeedTab.prototype.setEvents = function setEvents() {
    var _ = this;
    _.tabs.forEach(function addClickListener(element) {
      element.parentNode.addEventListener('click', function onFeedTabClick(e) {
        e.preventDefault();
        _.displayTab(element);
      });
    });
    _.accordions.forEach(function addClickListener(element) {
      element.parentNode.addEventListener('click', function onFeedTabClick(e) {
        e.preventDefault();
        _.displayTab(element);
      });
    });

    window.addEventListener('hashchange', function checkForHashChange() {
      if (_.isLoading) {
        return;
      }
      var hash = window.location.hash;
      if (hash) {
        _.tabs.forEach(function checkIfThis(element) {
          if (element.getAttribute('href') === hash) {
            element.click();
          }
        });
      }
    });
  };

  /**
   * Display the proper feedtab and accordion tab
   * 
   * @param {HTMLElement} element
   */
  FeedTab.prototype.displayTab = function displayTab(element) {
    var _ = this;

    _.isLoading = true;
    var tab = element.dataset.tab;
    var href = element.getAttribute('href');
    if (window.location.hash !== href) {
      window.location.hash = href;
    }

    _.accordions.forEach(function removeActive(el) {
      var accParent = el.parentNode;
      accParent.classList.remove('active');
      accParent.setAttribute('aria-selected', false);
      if (el.dataset.tab === tab) {
        accParent.classList.add('active');
        accParent.setAttribute('aria-selected', true);
        accParent.insertAdjacentElement('afterend', _.tabContent);
      }
    });
    _.tabs.forEach(function removeActive(el) {
      var tabParent = el.parentNode;
      tabParent.classList.remove('active');
      tabParent.setAttribute('aria-selected', false);
      if (el.dataset.tab === tab) {
        tabParent.classList.add('active');
        tabParent.setAttribute('aria-selected', true);
      }
    });
    _.tabContent.innerHTML = '';
    delete _.tabContent.dataset.init;
    _.tabContent.dataset.feed = tab;
    finna.feed.loadFeed(_.tabContent, function onLoad() {
      _.isLoading = false;
    });
  };

  /**
   * Load first tab page when initialization is completed
   */
  FeedTab.prototype.firstLoad = function firstLoad() {
    var _ = this;
    var hash = window.location.hash;

    _.tabs.forEach(function checkFirst(element) {
      var parent = element.parentNode;
      if (!hash && !_.isLoading && parent.classList.contains('active')) {
        parent.click();
      }
      if (hash === element.getAttribute('href')) {
        parent.click();
      }
    });
    if (_.tabs[0] && !_.isLoading) {
      _.tabs[0].parentNode.click();
    }
  };
  
  /**
   * Init feedtabs
   * 
   * @param {String} id 
   */
  function init(id) {
    var containers = document.querySelectorAll('.feed-tabs#' + id + ':not(.inited)');

    // TODO: remove jquery version of the init
    if (window.IntersectionObserver) {
      var observer = new IntersectionObserver(function observe(entries, obs) {
        entries.forEach(function checkEntry(entry) {
          new FeedTab(entry.target);
          obs.unobserve(entry.target);
        }); 
      }, {rootMargin: "0px 0px -200px 0px"});
      containers.forEach(function observeFeedTab(container) {
        observer.observe(container);
      });
    } else {
      $(containers).one('inview', function onInview() {
        new FeedTab(this);
      });
    }
  }

  var my = {
    init: init
  };

  return my;
})();
