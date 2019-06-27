/*global VuFind, finna */
finna.feedTab = (function finnaFeedTab() {
  var tabContainer = null;
  var id = null;
  var prevId = null;

  function getTabContainer(tabs, tabId) {
    return tabs.find('.tab-content .tab-pane[data-tab="' + tabId + '"]');
  }
    
  function init(id) {
    console.log("init: " + id);
    this.id = id;  
    this.tabContainer = $('.feed-tabs[data-id="' + id + '"]');
    this.tabContainer.tab('show');

    var self = this;

    this.tabContainer.find('li.nav-item a').click(function feedTabClick() {
      var tabId = $(this).data('tab');
      var tabs = $(this).closest('.feed-tabs');

      console.log("prev: " + self.prevId);
      
      var tabContainer = getTabContainer(tabs, tabId); //tabs.find('.tab-content .tab-pane[data-tab="' + tabId + '"]');
      if (tabContainer.hasClass('inited')) {
        return;
      }

      if (self.prevId) {
        var prevTabContainer = getTabContainer(tabs, self.prevId);
        prevTabContainer.removeClass('inited');
        prevTabContainer.find('.feed-container').empty();
      }
      
      tabContainer.addClass('inited');
      var feedContainer = tabContainer.find('.feed-container');
      feedContainer.data('init', null);
      finna.feed.loadFeed(feedContainer);
      self.prevId = tabId;
    });

    this.tabContainer.find('li.nav-item.active a').click();

  }

  var my = {
    init: init
  };

  return my;
})();
