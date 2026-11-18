$(function onFinnaTabsNavReady() {
  $('.finna-tabs-nav').each(function doFinnaTabsNavLayout() {
    var activeUl = $(this).find(
      '.nav-tabs > li.active-trail'
    );
    var activeTab = $("#sub-" + activeUl.attr('id'));
    if (activeTab) {
      activeTab.css('display', 'flex');
    }
  });
});
