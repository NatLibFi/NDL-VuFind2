/* global finna */

finna.multiSelect = (function multiSelect(){
  var option = '<li class="option" aria-selected="false"></li>'
  var area = '<ul tabindex="0" class="finna-multiselect done" style="max-height: 200px; background-color: white; overflow-y: scroll;" aria-activedescendant=""></ul>'
  function init() {
    var i = 0;
    $('.finna-multiselect.init').each(function createMultiselect(){
      var _ = $(this);
      var el = $(area).clone();
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
      var current = _.find('.active').first();
      var original = _.siblings('select').first().find('option[data-id=' + current.data('target') + ']');
      var isSelected = original.prop('selected');
      original.prop('selected', !isSelected);
      current.attr('aria-selected', !isSelected);
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
      }
    });
  }

  var my = {
    init: init
  };

  return my;
}());