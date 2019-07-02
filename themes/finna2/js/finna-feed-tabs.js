/*global VuFind, finna */
finna.feedTabs = (function finnaFeedTab() {
  var prevId = null;

  function getTabContainer(tabs, tabId) {
    return tabs.find('.tab-content');
  }

  function loadFeed(tabs, tabId) {
    var tabContainer = getTabContainer(tabs, tabId);
    var feedContainer = tabContainer.find('.feed-container');
    feedContainer.data('init', null);
    feedContainer.data('feed', tabId);
    finna.feed.loadFeed(feedContainer);
  }

  function toggleAccordion(container, accordion) {
    var tabContent = container.find('.tab-content').detach();
    var tabId = accordion.find('a.accordion-title').data('tab');
    var loadContent = false;
    var accordions = container.find('.feed-accordions');
    if (!accordion.hasClass('active') || accordion.hasClass('initial-active')) {
      accordions.find('.accordion.active').removeClass('active');
      accordions.toggleClass('all-closed', false);
      accordion.addClass('active');
      container.find('.feed-tab.active').removeClass('active');
      container.find('.feed-tab[data-tab="' + tabId + '"]').addClass('active');
      loadContent = true;
    }
    tabContent.insertAfter(accordion);
    accordion.removeClass('initial-active');

    return loadContent;
  }


  function init(id) {
    var container = $('.feed-tabs[data-id="' + id + '"]');
    if (container.hasClass('inited')) {
      return;
    }
    container.addClass('inited');
    container.tab('show');
    var self = this;

    // Init feed tabs
    container.find('li.nav-item a').click(function feedTabClick() {
      var tabId = $(this).data('tab');
      var tabContainer = getTabContainer(container, tabId);
      var li = $(this).closest('li');
      if (li.hasClass('active') && !li.hasClass('initial-active')) {
        return false;
      }
      li.removeClass('initial-active');

      if (self.prevId) {
        getTabContainer(container, self.prevId).removeClass('active');
      }
      var accordion = container.find('.feed-accordions .accordion-toggle[data-tab="' + tabId + '"]').closest('.accordion');
      if (toggleAccordion(container, accordion)) {
        loadFeed(container, tabId);
      }
      self.prevId = tabId;

      return false;
    });

    // Init accordions (mobile)
    container.find('.feed-accordions .accordion-toggle').click(function accordionClicked(e) {
      var accordion = $(e.target).closest('.accordion');
      var tabId = accordion.find('.accordion-toggle').data('tab');

      if (self.prevId) {
        var tabs = accordion.closest('.feed-tabs');
        getTabContainer(tabs, self.prevId).removeClass('active');
      }
      if (toggleAccordion(container, accordion)) {
        loadFeed(container, tabId);
      }
      self.prevId = accordion.find('a.accordion-title').data('tab');
      return false;
    });

    container.find('.feed-accordions .accordion.active .accordion-toggle').click();
  }

  var my = {
    init: init
  };

  return my;
})();
