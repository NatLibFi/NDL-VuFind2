/* global finna, VuFind, jsHelper */

finna.multiSelect = (function multiSelect() {
  var i = 0;
  var regExp = new RegExp(/[a-öA-Ö0-9-_ ]/);

  function MultiSelect(select, id) {
    var _ = this;
    _.id = id;
    _.select = select;
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
    _.ul.addEventListener('mousedown', function preventFocus(e) {
      e.preventDefault();
      e.stopPropagation();
      _.wasClicked = true;
      this.focus();
    });
    _.ul.addEventListener('touchstart', function preventFocus(e) {
      e.stopPropagation();
      _.wasClicked = true;
      this.focus();
    });
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
    jsHelper.addDynamicListener(_.ul, '.option', 'click', function optionClick() {
      _.setActive(this);
      _.setSelected();
    });

    _.ul.addEventListener('focusout', function clearState() {
      _.clearActives();
      _.clearCaches();
    });

    _.ul.addEventListener('keyup', function charMatches(e) {
      e.preventDefault();
      var keyLower = e.key.toLowerCase();
      if (regExp.test(keyLower) === false) {
        return;
      }

      if (_.charCache !== keyLower) {
        _.clearCaches();
      }

      var hasActive = _.active ? _.active.getAttribute('data-formatted').substring(0, 1) === keyLower : false;

      if (_.wordCache.length === 0) {
        _.words.forEach(function appendToUl(word) {
          var char = word.getAttribute('data-formatted').substring(0, 1);
          if (char === keyLower && !word.classList.contains('hidden')) {
            _.wordCache.push(word.value);
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
        var oldId = null;
        var k = 0;
        _.wordCache.forEach(function getNextActive(word){
          if (word.classList.contains('active')) {
            oldId = k + 1;
          }

          if (oldId === k) {
            _.setActive(word);
            _.scrollList(true);
            return false;
          }

          if (oldId === _.wordCache.length) {
            _.setActive(_.wordCache[0]);
            _.scrollList(true);
          }
        });
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
        if (_.active && _.active.classList.contains('option-parent')) {
          console.log("Yup");
        }
        if (e.key === 'ArrowUp') {
          found = jsHelper.getPreviousElementSibling(_.active, 'li:not(.hidden)');
        } else if (e.key === 'ArrowDown') {
          found = jsHelper.getNextElementSibling(_.active, 'li:not(.hidden)');
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

            if (String(child.getAttribute('data-formatted').toLowerCase()).indexOf(curVal) !== -1) {
              child.classList.remove('hidden');
            } else {
              child.classList.add('hidden');
            }
            var hierarchyLine = [];
            if (hierarchyLine.length !== 0) {
              var parent = jsHelper.getPreviousElementSibling(child, '.option-parent');
              if (parent && parent.classList.contains('hidden') && !child.classList.contains('hidden')) {
                parent.classList.remove('hidden');
              }
            }
          }
        }
      }, 100);
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
