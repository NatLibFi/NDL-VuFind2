/* global finna */
class MultiSelect extends HTMLElement {
  constructor()
  {
    // Always call super first
    super();
    /*{
      'labelId': 'Id for the label: (data-label-id)',
      'placeholder': 'Placeholder for the search: (data-placeholder)',
      'label': 'Label for multiselect: (data-label)',
      'labelText': 'Label text for label: (data-label-text)',
      'entries': 'Entries for multiselect: (data-entries)'
    };*/
    this.entries = JSON.parse(this.dataset.entries);
    delete(this.dataset.entries);
    this.regExp = new RegExp(/[a-öA-Ö0-9-_ ]/);
    this.words = [];
    this.wordCache = [];

    const fieldSet = document.createElement('fieldset');
    this.append(fieldSet);

    const label = document.createElement('label');
    label.setAttribute('id', this.dataset.labelId);
    label.innerHTML = this.dataset.labelText;
    this.label = label;
    fieldSet.append(label);

    const select = document.createElement('select');
    select.style.display = 'none';
    select.setAttribute('name', 'filter[]');
    select.setAttribute('multiple', 'multiple');
    this.select = select;
    fieldSet.append(select);

    const searchForm = document.createElement('input');
    searchForm.classList.add('search');
    searchForm.setAttribute('type', 'text');
    searchForm.setAttribute('placeholder', this.dataset.placeholder);
    searchForm.setAttribute('aria-labelledby', this.label.id);
    this.search = searchForm;
    fieldSet.append(searchForm);

    const ul = document.createElement('ul');
    ul.classList.add('list');
    ul.setAttribute('aria-label', this.dataset.label);
    ul.setAttribute('aria-multiselectable', 'true');
    ul.setAttribute('role', 'listbox');
    ul.setAttribute('aria-activedescendant', '');
    ul.setAttribute('tabindex', '0');
    this.multiSelect = ul;
    fieldSet.append(ul);

    const clearButton = document.createElement('button');
    clearButton.classList.add('clear', 'btn', 'btn-link');
    clearButton.innerHTML = this.dataset.clearText;
    this.clear = clearButton;
    fieldSet.append(clearButton);

    this.createSelect();
    this.setEvents();
  }

  /**
   * Create select and multiselect elements.
   */
  createSelect()
  {
    let index = 0;
    let levelCache = 0;
    let previousElement;
    let currentParent;
    this.entries.forEach((entry) => {
      const innerValue = document.createTextNode(entry.displayText).nodeValue;
      const option = document.createElement('option');
      option.value = document.createTextNode(entry.value).nodeValue;
      option.innerHTML = innerValue;
      const isSelected = entry.selected;
      this.select.append(option);

      const multiOption = document.createElement('li');
      multiOption.classList.add('option');
      multiOption.setAttribute('id', `${this.id}_opt_${index++}`);
      multiOption.reference = option;

      multiOption.innerHTML = innerValue;
      multiOption.dataset.formatted = innerValue;
      if (isSelected) {
        option.setAttribute('selected', 'selected');
        multiOption.classList.add('selected');
      }
      multiOption.setAttribute('aria-selected', option.getAttribute('selected') === 'selected');
      if (entry.level) {
        const level = parseInt(entry.level);
        if (levelCache < level) {
          const group = document.createElement('ul');
          group.classList.add('parent-holder');
          group.setAttribute('role', 'group');

          const previousClone = previousElement.cloneNode();

          previousClone.innerHTML = previousElement.innerHTML;
          previousClone.reference = previousElement.reference;

          previousElement.innerHTML = '';
          previousElement.removeAttribute('aria-selected');
          previousElement.removeAttribute('id');
          previousElement.classList.remove('option');
          previousElement.append(previousClone);

          this.words.pop();
          this.words.push(previousClone);
          previousElement.insertAdjacentElement('beforeend', group);
          currentParent = group;
        } else if (levelCache > level && level !== 0) {
          currentParent = currentParent.closest('ul.parent-holder');
        }
        if (level === 0) {
          this.multiSelect.append(multiOption);
        } else {
          if (levelCache !== level) {
            if (levelCache === 0) {
              previousElement.classList.add('root');
            }
            previousElement.classList.add('option-parent');
            previousElement.setAttribute('aria-expanded', 'true');
          }
          multiOption.classList.add('option-child');
          multiOption.classList.add('child-width-' + level);
          currentParent.insertAdjacentElement('beforeend', multiOption);

        }
        levelCache = level;
      } else {
        this.multiSelect.append(multiOption);
      }
      this.words.push(multiOption);
      previousElement = multiOption;
    });
  }
  
  setEvents()
  {
    // Record when the user clicks the list element
    this.multiSelect.addEventListener('mousedown', (e) => {
      e.preventDefault();
      e.stopPropagation();
      this.clicked = true;
      this.multiSelect.focus();
    });

    // Record when the user touches the list element
    this.multiSelect.addEventListener('touchstart', (e) => {
      e.stopPropagation();
      this.clicked = true;
      this.multiSelect.focus();
    });

    // When the user focuses to the list element
    this.multiSelect.addEventListener('focusin', () => {
      if (this.clicked) {
        this.clicked = false;
        return;
      }

      if (this.active === null) {
        this.setActive(this.parentNode.querySelector('.option:not(.hidden)'));
        this.scrollList(true);
      }
    });

    // Add dynamic listener to the list element, to check when the user clicks an option
    this.multiSelect.addEventListener('click', (e) => {
      if (e.target && e.target.classList.contains('option')){
        this.setActive(e.target);
        this.setSelected();
      }
    });

    this.multiSelect.addEventListener('focusout', () => {
      this.clearActives();
      this.clearCaches();
    });

    this.multiSelect.addEventListener('keyup', (e) => {
      e.preventDefault();
      const keyLower = e.key.toLowerCase();
      if (this.regExp.test(keyLower) === false) {
        return;
      }
      if (this.charCache !== keyLower) {
        this.clearCaches();
      }

      const hasActive = this.active ? this.active.dataset.formatted[0].toLowerCase() === keyLower : false;

      if (this.wordCache.length === 0) {
        this.words.forEach((option) => {
          const char = option.dataset.formatted[0].toLowerCase();
          if (char === keyLower && !option.classList.contains('hidden')) {
            this.wordCache.push(option);
          }
        });
      }

      if (this.wordCache.length === 0) {
        return;
      }

      if (hasActive === false) {
        this.clearActives();
        this.setActive(this.wordCache[0]);
        this.scrollList(true);
      } else {
        let lookFor = this.wordCache.indexOf(this.active) + 1;
        if (lookFor > this.wordCache.length - 1) {
          lookFor = 0;
        }
        const current = this.wordCache[lookFor];
        if (current) {
          this.setActive(current);
          this.scrollList(true);
        }
      }
      this.charCache = keyLower;

      if (e.key !== 'Enter' && e.key !== ' ') {
        return;
      }

      this.setSelected();
    });
    this.multiSelect.addEventListener('keydown', (e) => {
      if (!['ArrowUp', 'ArrowDown', 'Enter', ' '].includes(e.key)) {
        return;
      }
      e.preventDefault();
      if (['ArrowUp', 'ArrowDown'].includes(e.key)) {
        if (this.charCache) {
          this.clearCaches();
        }
        if (this.wordCache.length === 0) {
          this.words.forEach((option) => {
            if (!option.classList.contains('hidden')) {
              this.wordCache.push(option);
            }
          });
        }
        let direction = (e.key === 'ArrowUp') ? -1 : 1;
        let lookFor = +this.wordCache.indexOf(this.active) + direction;
        if (lookFor > this.wordCache.length - 1) {
          lookFor = 0;
        }
        if (lookFor < 0) {
          lookFor = this.wordCache.length - 1;
        }

        const current = this.wordCache[lookFor];
        if (current) {
          this.setActive(current);
          this.scrollList(true);
        }
      } else {
        this.setSelected();
      }
    });

    this.clear.addEventListener('click', (e) => {
      e.preventDefault();
      this.words.forEach((option) => {
        option.setAttribute('aria-selected', 'false');
        option.reference.selected = false;
      });
    });

    var searchInterval = false;
    this.search.addEventListener('keyup', () => {
      clearInterval(searchInterval);
      searchInterval = setTimeout(() => {
        if (this.wordCache.length !== 0) {
          this.clearCaches();
        }
        const value = this.search.value.toLowerCase();
        if (value.length === 0) {
          this.words.forEach((option) => {
            option.classList.remove('hidden');
          });
        } else {
          this.words.forEach((option) => {
            const lookFor = option.dataset.formatted.toLowerCase();
            option.classList.toggle('hidden', String(lookFor).indexOf(value) === -1);
          });
        }
      }, 200);
    });
  }

  /**
   * Set HTMLElement as selected.
   *
   * @param {HTMLElement} element 
   */
  setActive(element) {
    this.clearActives();
    this.active = element;
    this.active.classList.add('active');
    this.multiSelect.setAttribute('aria-activedescendant', element.id);
  }

  /**
   * Set active as selected.
   */
  setSelected() {
    const selected = !this.active.reference.selected;
    this.active.reference.selected = selected;
    this.active.setAttribute('aria-selected', selected);
    this.active.classList.toggle('selected', selected);
  }

  /**
   * Clear active selection.
   */
  clearActives() {
    this.multiSelect.setAttribute('aria-activedescendant', '');
    var options = this.multiSelect.parentNode.querySelectorAll('.option.active');
    options.forEach((opt) => {
      opt.classList.remove('active');
    });
    this.active = null;
  }

  /**
   * Scroll the list.
   *
   * @param {bool} clip Need to clip.
   */
  scrollList(clip) {
    const style = window.getComputedStyle(this.active);
    let top = style.getPropertyValue('margin-top');
    top = this.active.offsetTop - parseFloat(top);

    if (clip) {
      this.multiSelect.scrollTop = top;
      return;
    }

    if (top < this.multiSelect.scrollTop) {
      this.multiSelect.scrollTop = top;
    } else if (top >= this.multiSelect.scrollTop) {
      this.multiSelect.scrollTop = top;
    }
  }

  /**
   * Clear the caches.
   */
  clearCaches() {
    var _ = this;
    _.wordCache = [];
    _.charCache = "";
  }
}


finna.multiSelect = (function multiSelect() {
  return {
    init: () => {
      customElements.define('finna-multiselect', MultiSelect);
    }
  };
}());
