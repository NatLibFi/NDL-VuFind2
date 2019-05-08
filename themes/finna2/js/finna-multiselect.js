/*global finna, VuFind*/
finna.multiSelect = (function multiSelect(){
  var listItem = '<li role="option" aria-selected="false" data-inner="" data-target=""><input aria-label="checkbox" tabindex="-1" type="checkbox" class="checkbox"></li>';
  var globalId = 0;

  function MultiSelect(root) {
    var _ = this;
    _.id = globalId++;
    _.root = $(root);
    _.wrapper = _.root.find('.ms-input-wrapper');
    _.select = _.root.find('select');
    _.select.hide();
    _.input = _.root.find('.multiselect-input');
    _.dropdown = _.root.find('.multiselect-dropdown-menu');
    _.liItem = $(listItem).clone();
    _.current = null;
    _.setEvents();
    _.createDropdown();
  }

  MultiSelect.prototype.createDropdown = function createDropdown() {
    var _ = this;
    var i = 0;
    _.select.children('option').each(function createList(){
      var li = _.liItem.clone(true);
      li.append($(this).html());
      li.find(':checkbox').prop('checked', $(this).prop('selected'));
      li.attr({'data-target': i, 'data-inner': $(this).html().toLowerCase(), 'id': _.id + 'msd' + i});
      _.dropdown.append(li);
      $(this).attr('data-id', i++);
    });
  }

  MultiSelect.prototype.setEvents = function setEvents() {
    var _ = this;
    _.input.on('keydown', function cycleDropdown(e){
      _.checkKeyDown(e);
    });
    _.input.on('keyup', function testkeyup(e){
      _.checkKeyUp(e);
    });
    _.root.on('focusin', function openDropdown() {
      _.setDropdown(true);
    });
    _.input.on('blur', function closeDropdown(e) {
      e.preventDefault();
      console.log(e);
      if (e.relatedTarget !== _.dropdown) {
        _.setDropdown(false);
      } else {
        console.log("Wut");
      }
    });
    _.liItem.on('click', function setThis(e){
      e.preventDefault();
      _.onClickLi(e.currentTarget);
    });
  }

  MultiSelect.prototype.setDropdown = function setDropdown(state) {
    var _ = this;
    if (state) {
      _.dropdown.show();
      _.wrapper.attr('aria-expanded', 'true');
    } else {
      _.dropdown.hide();
      _.wrapper.attr('aria-expanded', state);
    }

    _.clearAll();
  }

  MultiSelect.prototype.clearAll = function clearAll() {
    var _ = this;
    _.dropdown.find('.current').removeClass('current');
    _.current = null;
    _.input.removeAttr('aria-activedescendant');
  }

  MultiSelect.prototype.filter = function filter(string) {
    var _ = this;
    var compare = $.trim(string.toLowerCase());
    if (compare === '') {
      _.dropdown.children('li').show();
      return;
    }

    _.dropdown.children().each(function checkMatch() {
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
      _.current = _.dropdown.children('li:visible').first();
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
    $('.multiselect-dropdown').each(function createDropdowns(){
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
