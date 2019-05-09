/*global finna, VuFind*/
finna.multiSelect = (function multiSelect(){
  var listItem = '<li role="option" aria-selected="false" data-inner="" data-target=""><input title="checkbox" tabindex="-1" type="checkbox" class="checkbox"></li>';
  var wrapper = '<div class="ms-input-wrapper" role="combobox" aria-expanded="false" aria-owns="" aria-haspopup="listbox">' +
  '<input aria-autocomplete="both" aria-controls="" aria-activedescendant="" aria-label="" class="form-control multiselect-input" type="text">' +
  '<span class="caret"></span>' +
  '</div>';
  var ul = '<ul class="multiselect-dropdown-menu" role="listbox" aria-multiselectable="true" id="">' +
  '</ul>';
  var globalId = 0;
  var backdrop = '<div class="multiselect-backdrop"></div>'

  function MultiSelect(root) {
    var _ = this;
    _.id = globalId++;
    _.root = $(root);
    _.wrapper = null;
    _.select = _.root.find('select');
    _.backdrop = null;
    _.select.hide();
    _.input = null;
    _.ul = null; //_.root.find('.multiselect-dropdown-menu');
    _.liItem = $(listItem).clone();
    _.current = null;
    _.createElements();
    _.setEvents();
    _.createul();
  }

  MultiSelect.prototype.createElements = function createElements() {
    var _ = this;
    var id = _.id + "_msd";
    _.wrapper = $(wrapper).clone();
    _.ul = $(ul).clone();
    _.input = _.wrapper.find('input');
    _.input.attr('aria-controls', id);
    _.wrapper.attr('aria-owns', id);
    _.ul.attr('id', id);

    _.root.append(_.wrapper);
    _.root.append(_.ul);
  }

  MultiSelect.prototype.createul = function createul() {
    var _ = this;
    var i = 0;
    _.select.children('option').each(function createList(){
      var li = _.liItem.clone(true);
      li.append($(this).html());
      li.find(':checkbox').prop('checked', $(this).prop('selected'));
      li.attr({'data-target': i, 'data-inner': $(this).html().toLowerCase(), 'id': _.id + 'msd' + i});
      _.ul.append(li);
      $(this).attr('data-id', i++);
    });
  }

  MultiSelect.prototype.setEvents = function setEvents() {
    var _ = this;
    _.input.on('keydown', function cycleul(e){
      _.checkKeyDown(e);
    });
    _.input.on('keyup', function testkeyup(e){
      _.checkKeyUp(e);
    });
    _.root.on('focusin', function openul() {
      _.setul(true);
    });
    _.input.on('blur', function closeul(e) {
      e.preventDefault();
      console.log(e);
    });
    _.liItem.on('click', function setThis(e){
      e.preventDefault();
      _.onClickLi(e.currentTarget);
    });
  }

  MultiSelect.prototype.onBackdropClick = function onBackdropClick() {
    var _ = this;
    _.setul(false);
  }

  MultiSelect.prototype.setul = function setul(state) {
    var _ = this;
    if (state) {
      if (_.backdrop === null) {
        _.backdrop = $(backdrop).clone();
        _.backdrop.on('click', function handleBackdrop(){
          _.onBackdropClick();
        });
        _.root.append(_.backdrop);
      }
      _.ul.show();
      _.wrapper.attr('aria-expanded', 'true');
    } else {
      if (_.backdrop !== null) {
        _.backdrop.remove();
        _.backdrop = null;
      }
      _.ul.hide();
      _.wrapper.attr('aria-expanded', state);
    }

    _.clearAll();
  }

  MultiSelect.prototype.clearAll = function clearAll() {
    var _ = this;
    _.ul.find('.current').removeClass('current');
    _.current = null;
    _.input.removeAttr('aria-activedescendant');
  }

  MultiSelect.prototype.filter = function filter(string) {
    var _ = this;
    var compare = $.trim(string.toLowerCase());
    if (compare === '') {
      _.ul.children('li').show();
      return;
    }

    _.ul.children().each(function checkMatch() {
      if ($(this).data('inner').indexOf(compare) < 0) {
        $(this).hide();
      } else {
        $(this).show();
      }
    });

    _.clearAll();
  }

  MultiSelect.prototype.checkKeyDown = function checkKeyDown(e) {
    var _ = this;
    var key = e.which;
    switch (key) {
    case 32:
      if (_.current !== null) {
        e.preventDefault();
      }
      break;
    case 40:
      e.preventDefault();
      _.setCurrent(-1);
      break;
    case 38:
      e.preventDefault();
      _.setCurrent(1);
      break;
    case 8: // Bspace
      //_.filter(_.input.val());
      break;
    case 9: // Tabulator
      break;
    default:
      if (isSpecialButton(key)) {
        return;
      }
      //_.filter(_.input.val());
      break;
    }
  }

  MultiSelect.prototype.checkKeyUp = function checkKeyUp(e) {
    var _ = this;
    var key = e.which;
    switch (key) {
    /*case 40:
      e.preventDefault();
      _.setCurrent(-1);
      break;
    case 38:
      e.preventDefault();
      _.setCurrent(1);
      break;*/
    case 32:
      if (_.current !== null) {
        _.toggleOptionState();
      }
      break;
    case 8: // Bspace
      _.filter(_.input.val());
      break;
    case 9: // Tabulator
      break;
    default:
      if (isSpecialButton(key)) {
        return;
      }
      _.filter(_.input.val());
      break;
    }
  }

  MultiSelect.prototype.setCurrent = function setCurrent(dir) {
    var _ = this;
    if (_.current === null && dir === -1) {
      _.current = _.ul.children('li:visible').first();
      _.input.attr('aria-activedescendant', _.current.attr('id'));
      _.current.addClass('current');
      return;
    } else if (_.current === null && dir === 1) {
      return;
    }

    var next = (dir === 1) ? _.current.prevAll('li:visible') : _.current.nextAll('li:visible');
    if (next !== null && next.length) {
      _.current.removeClass('current');
      _.current = next.first();
      _.input.attr('aria-activedescendant', _.current.attr('id'));
      _.current.addClass('current');
    }
  }

  MultiSelect.prototype.onClickLi = function onClickLi(clickTarget) {
    var _ = this;
    _.current = $(clickTarget);
    _.toggleOptionState();
  }

  MultiSelect.prototype.toggleOptionState = function toggleOptionState() {
    var _ = this;
    var cBox = _.current.find(':checkbox');
    var isChecked = cBox.prop('checked');
    cBox.prop('checked', !isChecked);
    _.select.find('[data-id="' + _.current.data('target') + '"]').prop('selected', !isChecked);
    _.current.attr('aria-selected', !isChecked);
  }

  MultiSelect.prototype.setAria = function setAria() {
    var _ = this;
    var label = _.root.find('label')[0].html();
    _.input.attr('aria-label', label);
    var i = 0;
    
  }

  function initFields() {
    $('.multiselect-dropdown').each(function createuls(){
      new MultiSelect(this);
    });
    //initKeyBindings();
  }

  var specialButtons = [8, 9, 16, 17, 18, 20, 27, 32, 33, 34, 35, 36, 37, 38, 39, 40, 45, 46, 91, 112, 113, 114, 115, 116, 117, 118, 119, 120, 121, 122, 123];

  function isSpecialButton(keycode) {
    return jQuery.inArray(+keycode, specialButtons) !== -1;
  }

  var my = {
    init: initFields
  };

  return my;
})();
