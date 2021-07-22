/* global finna, VuFind, jsHelper */

finna.multiSelect = (function multiSelect() {
  var i = 0;
  var regExp = new RegExp(/[a-öA-Ö0-9-_ ]/);

  function MultiSelect(select, id) {
    var _ = this;
    _.id = id;
    _.select = select;
    _.select.classList.remove('init');
    _.createElements();
    _.select.style.display = 'none';
    _.deleteButton = _.select.parentNode.querySelector('button.clear');
    _.words = [];
    _.wordCache = [];
    _.charCache = "";
    _.wasClicked = false;
    _.active = null;
    _.createList();
  }

  MultiSelect.prototype.createElements = function createElements() {
    var _ = this;
    _.ul = jsHelper.createElement('ul', 'finna-multiselect done', {
      'aria-label': _.select.getAttribute('data-label'),
      'aria-multiselectable': 'true',
      'role': 'listbox',
      'aria-activedescendant': '',
      'tabindex': '0'
    });
    _.searchField = jsHelper.createElement('input', 'finna-multiselect search form-control', {
      'type': 'text',
      'placeholder': _.select.getAttribute('data-placeholder'),
      'aria-labelledby': _.select.getAttribute('aria-labelledby')
    });
    _.deleteButton = jsHelper.createElement('button', 'finna-multiselect clear btn btn-link');
    _.deleteButton.innerHTML = VuFind.translate('clearCaption');
    _.select.insertAdjacentElement('afterend', _.searchField);
    _.searchField.insertAdjacentElement('afterend', _.ul);
    _.ul.insertAdjacentElement('afterend', _.deleteButton);
  };

  MultiSelect.prototype.createList = function createList() {
    var _ = this;
    var k = 0;
    var currentParent;
    var previousElement;
    var beforeLevel;
    for (var ind = 0; ind < _.select.children.length; ind++) {
      var cur = _.select.children[ind];
      cur.setAttribute('data-id', k);
      var formattedHtml = cur.innerHTML;

      var optionClone = jsHelper.createElement('li', 'option', {
        'data-target': k,
        'id': _.id + '_opt_' + k++,
        'aria-selected': cur.selected,
        'data-formatted': formattedHtml,
        'role': 'option'
      });

      var value = document.createElement('div');
      value.innerHTML = formattedHtml;
      value.classList.add('value');
      optionClone.insertAdjacentElement('afterbegin', value);

      var level = cur.getAttribute('data-level');
      if (level) {
        level = parseInt(level);
        if (beforeLevel < level) {
          var ulGroup = document.createElement('ul');
          ulGroup.classList.add('parent-holder');
          ulGroup.setAttribute('role', 'group');
          previousElement.insertAdjacentElement('beforeend', ulGroup);
          currentParent = ulGroup;
        } else if (beforeLevel > level && level !== 0) {
          currentParent = jsHelper.findParent(currentParent, 'ul.parent-holder');
        }
        if (level === 0) {
          optionClone.classList.add('option-parent');
          optionClone.setAttribute('aria-expanded', 'true');       
          _.ul.append(optionClone);
        } else {
          optionClone.classList.add('option-child');
          optionClone.classList.add('child-width-' + level);
          currentParent.insertAdjacentElement('beforeend', optionClone);
        }
        beforeLevel = level;
      } else {
        _.ul.append(optionClone);
      }
      _.words.push(optionClone);
      previousElement = optionClone;
    }

    _.setEvents();
  };

  MultiSelect.prototype.setEvents = function setEvents() {
    var _ = this;
    // Record when the user clicks the list element
    _.ul.addEventListener('mousedown', function preventFocus(e) {
      e.preventDefault();
      e.stopPropagation();
      _.wasClicked = true;
      this.focus();
    });

    // Record when the user touches the list element
    _.ul.addEventListener('touchstart', function preventFocus(e) {
      e.stopPropagation();
      _.wasClicked = true;
      this.focus();
    });

    // When the user focuses to the list element
    _.ul.addEventListener('focusin', function setFirstActive() {
      if (_.wasClicked) {
        _.wasClicked = false;
        return;
      }

      if (_.active === null) {
        _.setActive(this.parentNode.querySelector('.option:not(.hidden)'));
        _.scrollList(true);
      }
    });

    // Add dynamic listener to the list element, to check when the user clicks an option
    jsHelper.addDynamicListener(_.ul, '.option', 'click', function optionClick() {
      _.setActive(this);
      _.setSelected();
    });

    // Event when the user focuses out of the element
    _.ul.addEventListener('focusout', function clearState() {
      _.clearActives();
      _.clearCaches();
    });
    
    // Check for keypresses in the list element
    _.ul.addEventListener('keyup', function charMatches(e) {
      e.preventDefault();
      var keyLower = e.key.toLowerCase();
      if (regExp.test(keyLower) === false) {
        return;
      }
      if (_.charCache !== keyLower) {
        _.clearCaches();
      }

      var hasActive = _.active ? _.active.getAttribute('data-formatted')[0].toLowerCase() === keyLower : false;

      if (_.wordCache.length === 0) {
        _.words.forEach(function appendToUl(option) {
          var char = option.getAttribute('data-formatted')[0];
          char = char.toLowerCase();
          if (char === keyLower && !option.classList.contains('hidden')) {
            _.wordCache.push(option);
          }
        });
      }

      if (_.wordCache.length === 0) {
        return;
      }

      if (hasActive === false) {
        _.clearActives();
        _.setActive(_.wordCache[0]);
        _.scrollList(true);
      } else {
        var lookFor = 0;
        for (var k = 0; k < _.wordCache.length && k > -1; k++) {
          var current = _.wordCache[k];
          if (current.classList.contains('active')) {
            lookFor = jsHelper.keepIndexInBounds(k + 1, 0, _.wordCache.length - 1, true);
            if (_.wordCache[lookFor]) {
              _.setActive(_.wordCache[lookFor]);
              _.scrollList(true);
              break;
            }
          }
        }
      }
      _.charCache = keyLower;

      if (e.key !== 'Enter' && e.key !== ' ') {
        return;
      }

      _.setSelected();
    });
    _.ul.addEventListener('keydown', function scrollArea(e) {
      if (e.key !== 'ArrowUp' && e.key !== 'ArrowDown' && e.key !== 'Enter' && e.key !== ' ') {
        return;
      }
      e.preventDefault();

      if (e.key === 'ArrowUp' || e.key === 'ArrowDown') {
        var found = null;
        var index = +_.active.getAttribute('data-target');
        var direction = 1;
        if (e.key === 'ArrowUp') {
          direction = -1;
        }
        index = jsHelper.keepIndexInBounds(index + direction, 0, _.words.length, true);
        for (; index < _.words.length && index > -1; index += direction) {
          var current = _.words[index];
          if (+current.getAttribute('data-target') === index && !current.classList.contains('hidden')) {
            found = current;
            break;
          }
        }
  
        if (found) {
          _.setActive(found);
          _.scrollList(false);
        }
      } else {
        _.setSelected();
      }
    });
    _.deleteButton.addEventListener('click', function clearSelections(e) {
      e.preventDefault();
      _.words.forEach(function clearAll(option) {
        option.setAttribute('aria-selected', 'false');
      });

      var selected = _.select.parentNode.querySelectorAll('option:checked');
      selected.forEach(function clearAll(selection) {
        selection.selected = false;
      });
    });
    var searchInterval = false;
    _.searchField.addEventListener('keyup', function filterOptions() {
      clearInterval(searchInterval);
      searchInterval = setTimeout(function doSearch() {
        if (_.wordCache.length !== 0) {
          _.clearCaches();
        }
        var curVal = _.searchField.value.toLowerCase();
        if (curVal.length === 0) {
          for (var o = 0; o < _.words.length; o++) {
            _.words[o].classList.remove('hidden');
          }
        } else {
          for (var k = 0; k < _.words.length; k++) {
            var child = _.words[k];
            var lookFor = child.getAttribute('data-formatted').toLowerCase();
            if (String(lookFor).indexOf(curVal) !== -1) {
              child.classList.remove('hidden');
              // If a child node has a parent node, then we need to keep them also displayed
              if (!child.classList.contains('option-parent')) {
                var parents = jsHelper.getParentsUntil(child, 'li.option-parent');
                parents.forEach(function showParent(parent) {
                  parent.classList.remove('hidden');
                });
              } 
            } else {
              child.classList.add('hidden');
            }
          }
        }
      }, 200);
    });
  };

  MultiSelect.prototype.scrollList = function scrollList(clipTo) {
    var _ = this;
    var top = jsHelper.getPosition(_.active).top;
    if (typeof clipTo !== 'undefined' && clipTo === true) {
      _.ul.scrollTop = top;
      return;
    }

    if (top < _.ul.scrollTop) {
      _.ul.scrollTop = top;
    } else if (top >= _.ul.scrollTop) {
      _.ul.scrollTop = top;
    }
  };

  MultiSelect.prototype.clearCaches = function clearCaches() {
    var _ = this;
    _.wordCache = [];
    _.charCache = "";
  };

  MultiSelect.prototype.clearActives = function clearActives() {
    var _ = this;
    _.ul.setAttribute('aria-activedescendant', '');
    var options = _.ul.parentNode.querySelectorAll('.option.active');
    options.forEach(function removeActive(opt) {
      opt.classList.remove('active');
    }, options);
    _.active = null;
  };

  MultiSelect.prototype.setActive = function setActive(element) {
    var _ = this;
    _.clearActives();
    _.active = element;
    _.active.classList.add('active');
    _.ul.setAttribute('aria-activedescendant', _.active.getAttribute('id'));
  };

  MultiSelect.prototype.setSelected = function setSelected() {
    var _ = this;
    var original = _.select.parentNode.querySelector('option[data-id="' + _.active.getAttribute('data-target') + '"]');
    var isSelected = original.selected;
    original.selected = !isSelected;
    _.active.setAttribute('aria-selected', !isSelected);
  };

  function init() {
    var elems = document.querySelectorAll('.finna-multiselect.init');

    for (var u = 0; u < elems.length; u++) {
      new MultiSelect(elems[u], i++);
    }
  }

  var my = {
    init: init
  };

  return my;
}());
