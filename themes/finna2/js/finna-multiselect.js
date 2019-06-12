/* global finna */

finna.multiSelect = (function multiSelect(){
  var option = '<li class="option" role="option" aria-selected="false"></li>';
  var hierarchy = '<span aria-hidden="true"></span>';

  function init() {
    var i = 0;
    $('.finna-multiselect.init').each(function createMultiselect(){
      var _ = $(this);
      var el = $(this).siblings('ul').first();
      var msId = i++;
      var k = 0;
      _.hide();
      _.children('option').each(function createElements(){
        var c = $(this);
        c.attr('data-id', k);
        var temp = $(option).clone();
        temp.attr('data-target', k);
        temp.attr('id', msId + '_opt_' + k++);
        temp.attr('aria-selected', c.prop('selected'));
        temp.html(c.html());
        if (c.hasClass('option-parent')) {
          temp.addClass('option-parent');
        }
        if (c.hasClass('option-child')) {
          var hierarchyClone = $(hierarchy).clone();
          hierarchyClone.attr('class', c.attr('class'));
          hierarchyClone.addClass('hierarchy-line');
          temp.prepend(hierarchyClone);
        }
        el.append(temp);
      });
    });
    setEvents();
  }

  function setEvents() {
    $('.finna-multiselect.done').on('focusin', function setActiveDescendant(e){
      var _ = $(this);
      var current = _.find('.active');
      if (current.length === 0) {
        if (_.attr('aria-activedescendant') === '') {
          var first = _.find('.option').first();
          _.children('.option').removeClass('active');
          first.addClass('active');
          _.attr('aria-activedescendant', first.attr('id'));
        }
        return;
      }
    });
    $('.finna-multiselect.done .option').on('click', function setActiveState(){
      var _ = $(this);
      var ul = _.closest('.finna-multiselect.done');
      var current = ul.find('.active');
      if (current.length) {
        current.removeClass('active');
      }
      _.addClass('active');
      ul.attr('aria-activedescendant', _.attr('id'));
      setSelectedState(ul);
    });
    $('.finna-multiselect.done').on('focusout', function clearActive(){
      var _ = $(this);
      _.attr('aria-activedescendant', '');
      _.children('.option').removeClass('active');
    });
    $('.finna-multiselect.done').on('keyup', function checkKeyUp(e){
      e.preventDefault();
      var _ = $(this);
      var inp = e.key;
      if (/[a-öA-Ö0-9-_ ]/.test(inp)) {
        var firstFound = null;
        var current = null;
        var active = _.find('.active').first();
        var activeFound = active.length !== 0;
        var activeStart = '';
        console.log(inp);
        if (activeFound) {
          activeStart = active.html().substring(0, 1);
        }
        _.children('.option').each(function checkForSuitable() {
          var cur = $(this);
          var matches = cur.html().substring(0, 1) === inp;
          if (matches) {
            if (firstFound === null) {
              if (activeStart !== cur.html().substring(0, 1)) {
                // We assume that this is the first one so lets pick this
                cur.addClass('active');
                active.removeClass('active');
                _.attr('aria-activedescendant', cur.attr('id'));
                return false;
              }
              firstFound = cur;
              // Is this the first one found
              console.log("Yerp");
            }

            if (activeFound && cur.hasClass('active') && current === null) {
              current = $(this);
              console.log("Pa");
              return true;
              // Is this current
            }

            if (current !== null) {
              // We assume that this is the first one so lets pick this
              cur.addClass('active');
              current.removeClass('active');
              _.attr('aria-activedescendant', cur.attr('id'));
            }
          } else if (current !== null) {
            if (current === firstFound) {
              return true;
            }
            firstFound.addClass('active');
            current.removeClass('active');
            _.attr('aria-activedescendant', cur.attr('id'));
            return false;
          }
        });
      }
        
      if (e.key !== 'Enter' && e.key !== ' ') {
        return;
      }

      setSelectedState(_);
    });
    $('.finna-multiselect.done').on('keydown', function checkButtons(e){
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
      }
      if (e.key !== 'ArrowUp' && e.key !== 'ArrowDown') {
        return;
      }
      e.preventDefault();
      var _ = $(this);
      var current = _.find('.active');
      if (current.length === 0) {
        if (_.attr('aria-activedescendant') === '') {
          var first = _.find('.option').first();
          _.children('.option').removeClass('active');
          first.addClass('active');
          _.attr('aria-activedescendant', first.attr('id'));
        }
        return;
      }
      var found = null;
      if (e.key === 'ArrowUp') {
        found = current.prev('.option');
      } else if (e.key === 'ArrowDown') {
        found = current.next('.option');
      }
      if (found.length) {
        current.removeClass('active');
        found.addClass('active');
        _.attr('aria-activedescendant', found.attr('id'));
        _.scrollTop(0).scrollTop(found.position().top - (found.height() + 4) * 3);
      }
    });
  }

  function setSelectedState(ul) {
    var current = ul.find('.active').first();
    var original = ul.siblings('select').first().find('option[data-id=' + current.data('target') + ']');
    var isSelected = original.prop('selected');
    original.prop('selected', !isSelected);
    current.attr('aria-selected', !isSelected);
  }

  var my = {
    init: init
  };

  return my;
}());