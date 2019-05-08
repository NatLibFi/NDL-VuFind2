/* global finna */
finna.multiSelect = (function multiSelect (){
  var li = '<li><label><input type="checkbox"></label></li>';

  function initElements() {
    var cbIndex = 0;
    $('.multi-select').each(function createListboxes() {
      var _ = $(this);
      var ul = _.siblings('ul');
      _.hide();
      _.attr('multiple', 'multiple');
      var chiIndex = 0;

      _.children().each(function adjust() {
        var cl = $(li).clone(true);
        var html = $(this).html();
        var id = cbIndex + "_cb_" + chiIndex;
        cl.attr({'aria-label': html});
        cl.find('input').attr('id', id);
        cl.find('label').attr({'data-target': $(this).attr('value'), 'for': id}).append(html);
        ul.append(cl);
      });
      _.closest('div').append(ul);
      cbIndex++;
    });
  }

  function setEvents() {
    $('.multiselect-list').on('focusin', function setStart() {
      var _ = $(this);
      var first = _.children('li').first();
      first.addClass('current');
      _.attr('aria-activedescendant', first.attr('id'));
    });

    $('.multiselect-list').on('focusout', function removeCurrent() {
      $(this).children().removeClass('current');
    });

    $('.multiselect-list').blur(function whenHappens() {
      console.log("Elmao");
    });

    $('.multiselect-list').on('keydown', function doStuff(e){
      var _ = $(this);
      var cur = _.find('.current');
      switch (e.which) {
      case 38: // Up
        e.preventDefault();
        var prev = cur.prev('li');
        if (prev.length !== 0) {
          prev.addClass('current');
          cur.removeClass('current');
          _.attr('aria-activedescendant', prev.attr('id'));
        }
        break;
      case 40: // Down
        e.preventDefault();
        var next = cur.next('li');
        if (next.length !== 0) {
          next.addClass('current');
          cur.removeClass('current');
          _.attr('aria-activedescendant', next.attr('id'));
        }
        break;
      case 32: // Space
      case 13: // Enter
        e.preventDefault();
        var checked = cur.find(':checkbox').prop('checked');
        cur.find(':checkbox').prop('checked', !checked);
        break;
      case 9:
        
        break;
      }
    });
  }

  var my = {
    init: function init() {
      setEvents();
      initElements();
    }
  };

  return my;
}());