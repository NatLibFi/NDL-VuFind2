/*global VuFind, finna*/
finna.menu = (function finnaMenu() {

  function initStatusObserver() {
    if (!window.MutationObserver) {
      // No browser support
      return;
    }

    // Callback function to execute when mutations are observed
    var callback = function observerCallback(mutationsList/*, observer*/) {
      $.each(mutationsList, function checkMutation() {
        if (this.type === 'childList' && this.addedNodes) {
          $(this.addedNodes).each(function checkNode() {
            if ($(this).hasClass('warn') || $(this).hasClass('overdue') || $(this).hasClass('fa-bell')) {
              $('.loans-menu-status')
                .attr("data-toggle", "tooltip")
                .attr("data-placement", "bottom")
                .attr("title", VuFind.translate("account_has_alerts"))
                .tooltip()
                .html('<i class="fa fa-exclamation-triangle" aria-hidden="true"></i>')
                .removeClass('hidden');
              return false;
            }
          });
        }
      });
    };

    var observer = new MutationObserver(callback);
    $('.checkedout-status').each(function setupCheckedout() {
      observer.observe(this, { childList: true, subtree: true });
    });
    $('.holds-status').each(function setupHolds() {
      observer.observe(this, { childList: true, subtree: true });
    });
  }

  function initAccountChecks() {
    VuFind.account.register("profile", {
      selector: ".profile-status",
      ajaxMethod: "getAccountNotifications",
      render: function render($element, status, ICON_LEVELS) {
        if (!status.notifications) {
          $element.addClass("hidden");
          return ICON_LEVELS.NONE;
        }
        $element.html('<i class="fa fa-exclamation-triangle" title="' + VuFind.translate('account_has_alerts') + '" aria-hidden="true"></i>');
        return ICON_LEVELS.DANGER;
      }
    });
  }

  function initMenuLists() {
    $('#open-loans, #open-list').on('togglesubmenu.finna', function onToggleSubmenu() {
      toggleSubmenu($(this), $(this).attr('id') === 'open-list');
    });

    $('#open-loans > .caret, #open-list > .caret').on('click', function clickLink(e) {
      e.preventDefault();
      $(this).parent().trigger('togglesubmenu');
    });

    if ($('.mylist-bar').length === 0) {
      $('#open-list').one('beforetoggle.finna', function loadList() {
        var link = $(this);
        link.data('preload', false);
        var parent = link.parent();
        $.ajax({
          type: 'GET',
          dataType: 'json',
          async: false,
          url: VuFind.path + '/AJAX/JSON?method=getMyLists',
          data: {'active': null}
        }).done(function onGetMyListsDone(data) {
          parent.append(data.data);
          link.closest('.finna-movement').trigger('reindex');
        });
      }).data('preload', true);
    } else {
      $('#open-list').removeClass('collapsed').siblings('ul').first().addClass('in');
    }
  }

  function toggleSubmenu($a, lockList) {
    $a.trigger('beforetoggle');
    $a.toggleClass('collapsed');
    $a.siblings('ul').first().toggleClass('in', !$a.hasClass('collapsed'));

    if (lockList) {
      $('.nav-tabs-personal').toggleClass('move-list');
      if (!$('.nav-tabs-personal').hasClass('move-list')) {
        window.scroll(0, 0);
      }
    }
  }

  function init() {
    initMenuLists();
    initStatusObserver();
    initAccountChecks();
  }

  var my = {
    init: init
  };

  return my;
})();
