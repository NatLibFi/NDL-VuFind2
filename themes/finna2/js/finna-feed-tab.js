/*global VuFind, finna */
finna.feedTab = (function finnaFeedTab() {
  var prevId = null;

  function getTabContainer(tabs, tabId) {
    return tabs.find('.tab-content .tab-pane[data-tab="' + tabId + '"]');
  }

  function loadFeed(tabs, tabId) {
    var feedContainer = getTabContainer(tabs, tabId).find('.feed-container');
    feedContainer.data('init', null);
    finna.feed.loadFeed(feedContainer);
  }

  function deleteFeed(tabs, tabId) {
    var tabContainer = getTabContainer(tabs, tabId);
    tabContainer.removeClass('active');
    tabContainer.find('.feed-container').empty();
  }

  function toggleAccordion(container, accordion) {
    var tabContent = container.find('.tab-content');
    var tabId = accordion.find('a.accordion-title').data('tab');

    if (accordion.hasClass('active') && !accordion.hasClass('initial-active')) {
      container.find('.feed-accordions').find('.accordion.active').removeClass('active');
      container.find('.tab-pane.active').removeClass('active');
      container.find('.nav-tabs li.active').removeClass('active');
      tabContent.insertAfter($('.feed-accordions'));
    } else {
      container.find('.feed-accordions').find('.accordion.active').removeClass('active');
      accordion.addClass('active');
      container.find('.tab-pane.active').removeClass('active');
      container.find('.feed-tab.active').removeClass('active');

      tabContent.insertAfter(accordion);
    }
    accordion.removeClass('initial-active');
    
    tabContent.find('.tab-pane[data-tab="' + tabId + '"]').addClass('active');
    container.find('.feed-tab[data-tab="' + tabId + '"]').addClass('active');
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
        deleteFeed(container, self.prevId);
        getTabContainer(container, self.prevId).removeClass('active');
      }
      toggleAccordion(container, container.find('.feed-accordions .accordion-toggle[data-tab="' + tabId + '"]').closest('.accordion'));
      loadFeed(container, tabId);
      self.prevId = tabId;

      return false;
    });

    // Init accordions (mobile)
    container.find('.feed-accordions .accordion-toggle').click(function accordionClicked(e) {
      var accordion = $(e.target).closest('.accordion');
      var tabId = accordion.find('.accordion-toggle').data('tab');

      if (self.prevId) {
        var tabs = accordion.closest('.feed-tabs');
        deleteFeed(tabs, self.prevId);
        getTabContainer(tabs, self.prevId).removeClass('active');
      }
      toggleAccordion(container, accordion);
      loadFeed(container, tabId);
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
