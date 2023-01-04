/*global VuFind, finna */
finna.authority = (function finnaAuthority() {
  function toggleAuthorityInfoCollapse(mode)
  {
    var authorityRecommend = $('.authority-recommend');
    var tabs = authorityRecommend.find('ul.nav-tabs');
    var authoritybox = authorityRecommend.find('.authoritybox');
    if (typeof mode !== 'undefined') {
      authoritybox.toggleClass('hide', mode);
    } else {
      authoritybox.toggleClass('hide');
    }
    var collapsed = authoritybox.hasClass('hide');
    var recordSummary = authoritybox.find('.recordSummary');

    if (!collapsed && !recordSummary.hasClass('truncate-field')) {
      recordSummary.addClass('truncate-field');
      finna.layout.initTruncate(authoritybox);
    }

    tabs.toggleClass('collapsed', mode);
    authorityRecommend.find('li.toggle').toggleClass('collapsed', mode);
    finna.common.setCookie('collapseAuthorityInfo', collapsed);
  }

  function initAuthorityRecommendTabs()
  {
    $('div.authority-recommend .nav-tabs li').not('.toggle').on('click', function onTabClick() {
      var self = $(this);
      var id = self.data('id');
      var parent = self.closest('.authority-recommend');
      var authoritybox = parent.find('.authoritybox');
      var collapsed = authoritybox.hasClass('hide');

      if (self.hasClass('active') && !collapsed) {
        return;
      }

      parent.find('.nav-tabs li').toggleClass('active', false);
      self.addClass('active');

      var spinner = parent.find('li.spinner');
      spinner.toggleClass('hide', false).show();

      $.getJSON(
        VuFind.path + '/AJAX/JSON',
        {
          method: 'getAuthorityFullInfo',
          id: id,
          searchId: parent.data('search-id')
        }
      )
        .done(function onGetAuthorityInfoDone(response) {
          authoritybox.html(typeof response.data.html !== 'undefined' ? VuFind.updateCspNonce(response.data.html) : '--');
          toggleAuthorityInfoCollapse(false);
          if (!authoritybox.hasClass('hide')) {
            finna.layout.initTruncate(authoritybox);
          }
          spinner.hide();
        })
        .fail(function onGetAuthorityInfoFail() {
          authoritybox.text(VuFind.translate('error_occurred'));
          spinner.hide();
          toggleAuthorityInfoCollapse(false);
        });
    });
    $('div.authority-recommend .nav-tabs li.toggle').on('click', function onToggle() {
      toggleAuthorityInfoCollapse();
    });
  }

  function initAuthorityResultInfo(_holder) {
    var holder = typeof _holder === 'undefined' ? $(document) : _holder;
    VuFind.observerManager.createIntersectionObserver(
      'authorityResults',
      (element) => {
        var $elem = $(element);
        if ($elem.hasClass('loaded')) {
          return;
        }
        var $item = $elem.parents('.record-container');
        if ($item.length === 0) {
          return;
        }
        $elem.addClass('loaded');
        $elem.addClass('loading');
        $elem.removeClass('hidden');
        $elem.append(VuFind.loading());
        var id = $item.find('.hiddenId')[0].value;
        $.getJSON(
          VuFind.path + '/AJAX/JSON',
          {
            method: 'getRecordInfoByAuthority',
            id: id,
            context: 'search'
          }
        )
          .done(function onGetAuthorityRecordCountDone(response) {
            if (response.data.html.length > 0) {
              $elem.html(VuFind.updateCspNonce(response.data.html));
            } else {
              $elem.text('');
            }
            $elem.removeClass('loading');
          })
          .fail(function onGetAuthorityRecordCountFail() {
            $elem.text(VuFind.translate('error_occurred'));
            $elem.removeClass('loading');
          });
      },
      holder.find('.authority-record-info').toArray()
    );
  }

  var my = {
    init: function init() {
    },
    initAuthorityRecommendTabs: initAuthorityRecommendTabs,
    initAuthorityResultInfo: initAuthorityResultInfo
  };

  return my;
})();
