/* global finna */

finna.multiSelect = (function multiSelect(){
  var option = '<li role="option" class="option" aria-selected="false"></li>'

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
        el.append(temp);
      });
      _.closest('.solr-adv-filter').append(el);
    });
    setEvents();
  }

  function setEvents() {
    $('.finna-multiselect.done').on('focusin', function setActiveDescendant(e){
      
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
      if (e.key !== 'Enter') {
        return;
      }
      e.preventDefault();
      var _ = $(this);
      setSelectedState(_);
    });
    $('.finna-multiselect.done').on('keydown', function checkButtons(e){
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
        _.scrollTop(0).scrollTop(found.position().top - found.height());
        /*var target = document.getElementById("target");
        target.parentNode.scrollTop = target.offsetTop;*/
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