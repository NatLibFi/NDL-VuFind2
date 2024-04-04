/*global VuFind, finna */

const text = {
  help: "Selaa hakutuloksia nuolinäppäimillä",
  placeholder: "Hae valikosta",
  noResult: "Ei hakutuloksia",
  results: "{x} hakutulosta",
  deleteItem: "Poista {t}",
  delete: "Poista",
  clear: "Tyhjä"
};

const matches = Element.prototype.matches;
let closest = Element.prototype.closest;

if (!closest) {
  closest = function (s) {
    var el = this;

    do {
      if (matches.call(el, s)) return el;
      el = el.parentElement || el.parentNode;
    } while (el !== null && el.nodeType === 1);
    return null;
  };
}

const DEEP_CLONE = true;
const SILENTLY = false;

/**
 * Deep copy of an {@link Iterable} as {@link Array}
 * @template {HTMLElement} T
 * @param {Iterable<T>} array
 * @returns {Array<T>}
 */
function deepCopy (array) {
  return /** @type {Array<T>} */ (Array.from(array).map(option => option.cloneNode(DEEP_CLONE)));
}

function preserveOptgroupAsData (option) {
  if (option.parentNode.tagName === 'OPTGROUP') {
    option.dataset.group = option.parentNode.label
  }
  return option
}

class SelectA11y {
  /**
   * @param {HTMLSelectElement} el - Select HTML element
   * @param {object} [options] - options to control select-a11y behavior
   * @param {object} [options.text] - texts used in the class
   * @param {string} [options.text.help] - helper text used for assistive technologies
   * @param {string} [options.text.placeholder] - search input placeholder
   * @param {string} [options.text.noResult] - text shown when there is no option
   * @param {string} [options.text.results] - text to show the number of results available for assistive technologies
   * @param {string} [options.text.deleteItem] - text used as title for "x" close button for selected option (see options.showSelected below)
   * @param {string} [options.text.delete] - text used for assistive technologies for the "x" close button for selected option (see options.showSelected below)
   * @param {string} [options.text.clear] - text used for assistive technologies for the "x" clear button for clearable single select (see options.clearable below)
   * @param {FillSuggestions} [options.fillSuggestions] - fill suggestions based on search input content
   * @param {boolean} [options.showSelected=true] - show selected options for multiple select
   * @param {boolean} [options.useLabelAsButton=false] - use label as button even for single select.
   * Only work if select value is set to `null` otherwise its value defaults to first option.
   * @param {boolean} [options.clearable=false] - show clear icon for single select.
   * Only work if select value is set. It resets it to `null`.
   */
  constructor(el, options) {

    /** @type {HTMLSelectElement} */
    this.el = el;

    /** @type {HTMLLabelElement | null} */
    this.label = document.querySelector(`label[for=${el.id}]`);

    this.id = el.id;
    this.open = false;
    this.multiple = this.el.multiple;
    this.search = '';

    /** @type {Array<HTMLElement>} */
    this.suggestions = [];

    this.focusIndex = null;

    const passedOptions = Object.assign({}, options);
    const textOptions = Object.assign({}, text, passedOptions.text);
    delete passedOptions.text;
    this._defaultSearch = this._defaultSearch.bind(this);

    this._options = Object.assign({
      text: textOptions,
      showSelected: true,
      fillSuggestions: this._defaultSearch,
      useLabelAsButton: false,
      clearable: false,
    }, passedOptions);

    this._handleFocus = this._handleFocus.bind(this);
    this._handleInput = this._handleInput.bind(this);
    this._handleKeyboard = this._handleKeyboard.bind(this);
    this._handleOpener = this._handleOpener.bind(this);
    this._handleClear = this._handleClear.bind(this);
    this._handleReset = this._handleReset.bind(this);
    this._handleSuggestionClick = this._handleSuggestionClick.bind(this);
    this._positionCursor = this._positionCursor.bind(this);
    this._removeOption = this._removeOption.bind(this);
    this.setText = this.setText.bind(this);
    this._setButtonText = this._setButtonText.bind(this);

    if(!this.multiple) {
      const hasSelectedOption = Array.from(this.el.options).some(option => option.selected);
      if (this._options.useLabelAsButton && !hasSelectedOption) {
        const option = document.createElement('option');
        option.innerText = this.label?.innerText ?? "";
        option.setAttribute('value', '');
        option.setAttribute('selected', 'selected');
        option.setAttribute('disabled', 'disabled');
        option.setAttribute('hidden', 'hidden');
        this.el.options.add(option, 0);
      }
    }

    /**
     * Select original options at initialization of the component.
     * They are never modified and are used to handle reset.
     * @type {Array<HTMLOptionElement>}
     */
    this.originalOptions = deepCopy(Array.from(this.el.options).map(preserveOptgroupAsData));

    /**
     * Select original options at initialization of the component.
     * They are updated based on select / unselect of options but no options are added or removed to it.
     * This is the set of options passed to {@link FillSuggestions} callback.
     * @type {Array<HTMLOptionElement>}
     */
    this.updatedOriginalOptions = deepCopy(this.originalOptions);

    /**
     * Select current options. These can be completely differents options than {@link originalOptions}
     * if the provided promise fetches some from an API.
     * @type {Array<HTMLOptionElement>}
     */
    this.currentOptions = deepCopy(this.originalOptions);

    this._disable();
    this.button = this._createButton();
    this._setButtonText();
    this.clearButton = this._createClearButton();
    this.liveZone = this._createLiveZone();
    this.overlay = this._createOverlay();
    this.wrap = this._wrap();

    if (this.multiple && this._options.showSelected) {
      this.selectedList = this._createSelectedList();
      this._updateSelectedList();

      this.selectedList.addEventListener('click', this._removeOption);
    }

    this.button.addEventListener('click', this._handleOpener);
    this.clearButton.addEventListener('click', this._handleClear);
    this.wrap.addEventListener('keydown', this._handleKeyboard);
    document.addEventListener('blur', this._handleFocus, true);

    if (this.el.form) {
      this.el.form.addEventListener('reset', this._handleReset);
    }
  }

  /**
   * Update texts with new texts
   * @param {object} newText
   */
  setText(newText) {
    Object.assign(this._options.text, newText);
  }

  /**
   * Select new value
   * @param {string} value option value
   */
  selectOption(value, dispatchEvent = true) {
    const optionIndex = this.currentOptions.findIndex(option => option.value === value);
    if (optionIndex === -1) {
      return;
    }
    const shouldClose = this.multiple ? false : true;
    this._toggleSelection(optionIndex, shouldClose, dispatchEvent);
  }

  /**
   * Select new value without dispatching the change Event
   * @param {string} value option value
   */
  selectOptionSilently(value) {
    this.selectOption(value, SILENTLY);
  }


  _createButton() {
    const button = document.createElement('button');
    button.setAttribute('type', 'button');
    button.setAttribute('aria-expanded', this.open ? "true" : "false");
    button.className = 'select-a11y-button';
    const text = document.createElement('span');
    text.className = 'select-a11y-button__text';
    text.setAttribute("role", "presentation");

    if (this.label && !this.label.id) {
      this.label.id = `${this.el.id}-label`;
      text.id = `${this.el.id}-text`;
    }
    button.setAttribute('id', this.el.id + '-button');
    button.setAttribute('aria-labelledby', this.label?.id + ' ' + text.id);
    text.innerHTML = "&nbsp;";

    button.appendChild(text);
    button.insertAdjacentHTML('beforeend', '<span class="select-a11y-button__icon" aria-hidden="true"></span>');
    return button;
  }

  _createClearButton() {
    const clear = document.createElement('button');
    clear.setAttribute('type', 'button');
    clear.setAttribute('aria-label', this._options.text.clear);
    clear.className = 'select-a11y-button__clear';
    return clear;
  }

  _createLiveZone() {
    const live = document.createElement('p');
    live.setAttribute('aria-live', 'polite');
    live.classList.add('sr-only');

    return live;
  }

  _createOverlay() {
    const container = document.createElement('div');
    container.classList.add('select-a11y__overlay');

    const suggestions = document.createElement('div');
    suggestions.classList.add('select-a11y-suggestions');
    suggestions.id = `a11y-${this.id}-suggestions`;

    container.innerHTML = `
      <p id="a11y-usage-${this.id}-js" class="sr-only">${this._options.text.help}</p>
      <label for="a11y-${this.id}-js" class="sr-only">${this._options.text.placeholder}</label>
      <input type="search" id="a11y-${this.id}-js" class="select-a11y__input ${this.el.className}" autocomplete="off" autocapitalize="off" spellcheck="false" placeholder="${this._options.text.placeholder}" aria-describedby="a11y-usage-${this.id}-js">
    `;

    container.appendChild(suggestions);

    this.list = suggestions;
    this.list.addEventListener('click', this._handleSuggestionClick);

    /** @type {HTMLInputElement | null} */
    this.input = container.querySelector('input');

    if(this.input) {
      this.input.addEventListener('input', this._handleInput);
      this.input.addEventListener('focus', this._positionCursor, true);
    }

    return container;
  }

  _createSelectedList() {
    const list = document.createElement('ul');
    list.className = 'select-a11y__selected-list';

    return list;
  }

  _disable() {
    this.el.setAttribute('tabindex', '-1');
  }

  /**
   *
   * @typedef Suggestion
   * @property {boolean} hidden - if suggestion is hidden
   * @property {boolean} disabled - if suggestion is disabled
   * @property {boolean} selected - if suggestion is selected
   * @property {string} label - label shown
   * @property {any} value - suggestion value
   * @property {string} [image] - suggestion image
   * @property {string} [alt] - suggestion image alt
   * @property {string} [helper] - suggestion helper
   * @property {string} [description] - suggestion description
   * @property {boolean} [showIcon] - suggestion recommended
   * @property {string} [group] - suggestion group
   * @property {string} [link] - suggestion link
   * @property {string} [linkText] - suggestion linkText
   */

  /**
   *
   * @param {HTMLOptionElement} option
   * @returns {Suggestion} - a suggestion
   */
  _mapToSuggestion(option) {

    return {
      hidden: option.hidden,
      disabled: option.disabled,
      selected: option.hasAttribute('selected'),
      label: option.label,
      value: option.value,
      image: option.dataset.image,
      alt: option.dataset.alt,
      helper: option.dataset.helper,
      description: option.dataset.description,
      showIcon: option.dataset.showIcon,
      group: option.dataset.group,
      link: option.dataset.link,
      linkText: option.dataset.linkText
    };
  }

  /**
   *
   * @param {Suggestion} suggestion
   * @returns {HTMLOptionElement} - an option
   */
  _mapToOption(suggestion) {
    const option = document.createElement('option');
    option.label = suggestion.label;
    option.value = suggestion.value;
    if(suggestion.hidden) {
      option.setAttribute('hidden', 'hidden');
    }
    if(suggestion.disabled) {
      option.setAttribute('disabled', 'disabled');
    }
    if(suggestion.selected) {
      option.setAttribute('selected', 'selected');
    }
    if(suggestion.image) {
      option.dataset.image = suggestion.image;
    }
    if(suggestion.alt) {
      option.dataset.alt = suggestion.alt;
    }
    if(suggestion.description) {
      option.dataset.description = suggestion.description
    }
    if (suggestion.helper) {
      option.dataset.helper = suggestion.helper
    }
    if (suggestion.showIcon) {
      option.dataset.showIcon = suggestion.showIcon
    }
    if (suggestion.group) {
      option.dataset.group = suggestion.group
    }
    if (suggestion.link) {
      option.dataset.link = suggestion.link
    }
    if (suggestion.linkText) {
      option.dataset.linkText = suggestion.linkText
    }
    return option;
  }

  /**
   * @callback FillSuggestions
   * @param {string} search - searched term
   * @param {Array<HTMLOptionElement>} options - original select options
   * @returns {Promise<Array<Suggestion>>} suggestions
   */

  /**
   *
   * @type {FillSuggestions}
   */
  _defaultSearch(search, options) {
    const newOptions = options.filter(option => {
      const text = option.label || option.value;
      return text.toLocaleLowerCase().indexOf(search) !== -1;
    }).map(this._mapToSuggestion);
    return Promise.resolve(newOptions);
  }

  /**
   *
   * @returns {Promise<Array<Suggestion>>}
   */
  async _fillSuggestions() {
    const search = this.search.toLowerCase();

    // loop over the
    const suggestions = await this._options.fillSuggestions(search, this.updatedOriginalOptions);
    this.currentOptions = suggestions.map(this._mapToOption);
    this.el.replaceChildren(...this._fillSelect(this.currentOptions));

    const suggestionElements = suggestions
      .map((suggestion, index) => {
        const suggestionElement = document.createElement('div');
        suggestionElement.setAttribute('role', 'option');
        suggestionElement.setAttribute('tabindex', '0');
        suggestionElement.setAttribute('data-index', index.toString());
        if(suggestion.hidden) {
          suggestionElement.setAttribute('data-hidden', "hidden");
        }
        if(suggestion.disabled) {
          suggestionElement.setAttribute('data-disabled', "disabled");
        }
        suggestionElement.classList.add('select-a11y-suggestion');

        const firstColumn = document.createElement('div');
        firstColumn.classList.add('select-a11y-column');
        suggestionElement.appendChild(firstColumn);

        const labelElement = document.createElement('div');
        labelElement.classList.add('select-a11y-suggestion__label');
        labelElement.innerText = suggestion.label || suggestion.value;
        firstColumn.appendChild(labelElement);

        // check if the option is selected
        if (suggestion.selected) {
          suggestionElement.setAttribute('aria-selected', 'true');
        }
        if (suggestion.description) {
          const descriptionElement = document.createElement('div');
          descriptionElement.classList.add('select-a11y-suggestion__description');

          if (suggestion.showIcon) {
            descriptionElement.setAttribute('data-show-icon', 'true');
          }

          descriptionElement.innerText = suggestion.description;
          firstColumn.appendChild(descriptionElement);
        }
        if (suggestion.helper) {
          const secondColumn = document.createElement('div');
          secondColumn.classList.add('column--right');
          suggestionElement.appendChild(secondColumn);

          const helperElement = document.createElement('code');
          helperElement.classList.add('select-a11y-suggestion__helper');
          helperElement.innerText = suggestion.helper;
          secondColumn.appendChild(helperElement);
        } else if (suggestion.link) {
          const secondColumn = document.createElement('div');
          secondColumn.classList.add('column--right');
          suggestionElement.appendChild(secondColumn);

          const linkElement = document.createElement('a');
          linkElement.target = "_blank";
          linkElement.innerText = suggestion.linkText;
          linkElement.href = suggestion.link;
          secondColumn.appendChild(linkElement);
        }
        if (suggestion.image) {
          const image = document.createElement('img');
          image.setAttribute('src', suggestion.image);
          image.setAttribute('alt', suggestion.alt ? suggestion.alt : '');
          image.classList.add('select-a11y-suggestion__image');
          suggestionElement.prepend(image);
        }
        return { suggestionElement, group: suggestion.group };
      })
      .filter((suggestion) => !suggestion.suggestionElement.dataset.disabled && !suggestion.suggestionElement.dataset.hidden);

    const noGroupedSuggestions = [];
    const groupedSuggestions = {};

    suggestionElements.forEach(({ suggestionElement, group }) => {
      if (group) {
        if (!groupedSuggestions[group]) {
          const groupDiv = document.createElement('div');
          groupDiv.setAttribute('role', 'group');
          groupedSuggestions[group] = groupDiv;
          const presentation = document.createElement('div')
          presentation.setAttribute('role', 'presentation')
          presentation.innerHTML = group;
          groupDiv.appendChild(presentation);
        }
        groupedSuggestions[group].appendChild(suggestionElement);
      } else {
        noGroupedSuggestions.push(suggestionElement);
      }
    });

    this.suggestions = suggestionElements.map(({ suggestionElement }) => suggestionElement);

    if(this.list) {
      if (!this.suggestions.length) {
        this.list.innerHTML = `<p class="select-a11y__no-suggestion">${this._options.text.noResult}</p>`;
      } else {
        const listBox = document.createElement('div');
        listBox.setAttribute('role', 'listbox');

        if (this.multiple) {
          listBox.setAttribute('aria-multiselectable', 'true');
        }

        Object.values(noGroupedSuggestions).forEach((item) => {
          listBox.appendChild(item);
        });
        Object.values(groupedSuggestions).forEach((group) => {
          listBox.appendChild(group);
        });
        this.list.innerHTML = '';
        this.list.appendChild(listBox);
      }
    }
    this._setLiveZone();
    return suggestions;
  }

  _fillSelect(options) {
    const newOptions = [];

    options.forEach((option) => {
      const group = option.dataset.group;
      if (group) {
        const existingOptgroup = newOptions.find((opt) => opt.tagName === 'OPTGROUP' && opt.label === group);
        if (!existingOptgroup) {
          const optgroup = document.createElement('optgroup');
          optgroup.label = group;
          newOptions.push(optgroup)
        }
        newOptions[newOptions.length - 1].appendChild(option);
      } else {
        newOptions.push(option)
      }
    });
    return newOptions;
  }

  _handleOpener(event) {
    this._toggleOverlay();
  }

  _handleFocus() {
    if (!this.open) {
      return;
    }

    clearTimeout(this._focusTimeout);

    this._focusTimeout = setTimeout(() => {
      if (!this.overlay.contains(document.activeElement) && this.button !== document.activeElement) {
        this._toggleOverlay(false, document.activeElement === document.body);
      }
      else if (document.activeElement === this.input) {
        // reset the focus index
        this.focusIndex = null;
      }
      else {
        const optionIndex = this.suggestions.indexOf(/** @type HTMLElement */ (document.activeElement));

        if (optionIndex !== -1) {
          this.focusIndex = optionIndex;
        }
      }
    }, 10);
  }

  _handleClear() {
    this.el.value = "";
    this._handleReset();
  }

  _handleReset() {
    clearTimeout(this._resetTimeout);

    this._resetTimeout = setTimeout(async () => {
      this.search = '';
      this.updatedOriginalOptions = deepCopy(this.originalOptions);
      this.currentOptions = deepCopy(this.originalOptions);
      await this._fillSuggestions();
      this.el.dispatchEvent(new Event('change'));
      this._setButtonText();
      if (this.multiple && this._options.showSelected) {
        this._updateSelectedList();
      }
    }, 10);
  }

  _handleSuggestionClick(event) {
    const option = closest.call(event.target, '[role="option"]');

    if (!option) {
      return;
    }

    const optionIndex = parseInt(option.getAttribute('data-index'), 10);
    const shouldClose = this.multiple && event.metaKey ? false : true;

    this._toggleSelection(optionIndex, shouldClose);
  }

  _handleInput() {
    // prevent event firing on focus and blur
    if (this.search === this.input?.value) {
      return;
    }

    this.search = this.input?.value ?? "";
    this._fillSuggestions();
  }

  _handleKeyboard(event) {
    const option = closest.call(event.target, '[role="option"]');
    const input = closest.call(event.target, 'input');

    if (event.keyCode === 27) {
      this._toggleOverlay();
      return;
    }

    if (input && event.keyCode === 13) {
      event.preventDefault();
      return;
    }

    if (event.keyCode === 40) {
      event.preventDefault();
      this._moveIndex(1);
      return
    }

    if (!option) {
      return;
    }

    if (event.keyCode === 39) {
      event.preventDefault();
      this._moveIndex(1);
      return
    }

    if (event.keyCode === 37 || event.keyCode === 38) {
      event.preventDefault();
      this._moveIndex(-1);
      return;
    }

    if ((!this.multiple && event.keyCode === 13) || event.keyCode === 32) {
      event.preventDefault();
      this._toggleSelection(parseInt(option.getAttribute('data-index'), 10), this.multiple ? false : true);
    }

    if (this.multiple && event.keyCode === 13) {
      this._toggleOverlay();
    }
  }

  _moveIndex(step) {
    if (this.focusIndex === null) {
      this.focusIndex = 0;
    }
    else {
      const nextIndex = this.focusIndex + step;
      const selectionItems = this.suggestions.length - 1;

      if (nextIndex > selectionItems) {
        this.focusIndex = 0;
      }
      else if (nextIndex < 0) {
        this.focusIndex = selectionItems;
      }
      else {
        this.focusIndex = nextIndex;
      }
    }

    this.suggestions[this.focusIndex].focus();
  }

  _positionCursor() {
    setTimeout(() => {
      if(this.input) {
        const endOfInput = this.input.value.length ?? 0;
      this.input.setSelectionRange(endOfInput, endOfInput);
      }
    });
  }

  _removeOption(event) {
    const button = closest.call(event.target, 'button');

    if (!button) {
      return;
    }

    const currentButtons = this.selectedList?.querySelectorAll('button');
    const buttonPreviousIndex = Array.prototype.indexOf.call(currentButtons, button) - 1;
    const optionIndex = parseInt(button.getAttribute('data-index'), 10);

    // disable the option
    this._toggleSelection(optionIndex);

    // manage the focus if there's still the selected list
    if (this.selectedList?.parentElement) {
      const buttons = this.selectedList.querySelectorAll('button');

      // look for the bouton before the one clicked
      if (buttons[buttonPreviousIndex]) {
        buttons[buttonPreviousIndex].focus();
      }
      // fallback to the first button in the list if there's none
      else {
        buttons[0].focus();
      }
    } else {
      this.button.focus();
    }
  }

  _setButtonText() {
    const selectedOption = this.el.item(this.el.selectedIndex);
    /** @type {HTMLElement} */
    const child = this.button.firstElementChild;

    if (selectedOption && selectedOption.value) {
      this.button.classList.remove('select-a11y-button--no-selected-option');
    } else {
      this.button.classList.add('select-a11y-button--no-selected-option');
    }

    if (this.multiple) {
      if(this._options.useLabelAsButton) {
        child.innerText = this.label?.innerText || "";
      } else {
        child.innerHTML = "&nbsp;";
      }
    } else {
      if (selectedOption) {
        child.innerText = selectedOption.label || selectedOption.value;
      }
    }
  }

  _setLiveZone() {
    const suggestions = this.suggestions.length;
    let text = '';

    if (this.open) {
      if (!suggestions) {
        text = this._options.text.noResult;
      }
      else {
        text = this._options.text.results.replace('{x}', suggestions);
      }
    }

    this.liveZone.innerText = text;
  }

  _toggleOverlay(state, focusBack) {
    this.open = state !== undefined ? state : !this.open;
    this.button.setAttribute('aria-expanded', this.open ? "true" : "false");

    if (this.open) {
      this._fillSuggestions();
      this.button.insertAdjacentElement('afterend', this.overlay);
      this.input?.focus();
    }
    else if (this.wrap.contains(this.overlay)) {
      this.wrap.removeChild(this.overlay);

      // reset the focus index
      this.focusIndex = null;

      // reset search values for default search
      if (this.input && this._options.fillSuggestions === this._defaultSearch) {
        this.input.value = '';
      }
      this.search = '';


      // reset aria-live
      this._setLiveZone();
      if (state === undefined || focusBack) {
        // fix bug that will trigger a click on the button when focusing directly
        setTimeout(() => {
          this.button.focus();
        });
      }
    }
  }

  _toggleSelection(optionIndex, close = true, dispatch = true) {
    const toggledOption = this.el.item(optionIndex);
    if (this.multiple) {
      if(toggledOption?.hasAttribute('selected')) {
        toggledOption.removeAttribute('selected');
      } else {
        toggledOption?.setAttribute('selected', 'selected');
      }
    }
    else {
      toggledOption?.setAttribute('selected', 'selected');
      this.el.selectedIndex = optionIndex;
    }
    this.updatedOriginalOptions = this.updatedOriginalOptions.map(option => {
      if(option.value === toggledOption?.value) {
        if(toggledOption.hasAttribute('selected')) {
          option.setAttribute('selected', 'selected');
        } else {
          option.removeAttribute('selected');
        }
      }
      if(!this.multiple && option.value !== toggledOption?.value) {
        option.removeAttribute('selected');
      }
      return option;
    });
    this.currentOptions = this.currentOptions.map(option => {
      if(option.value === toggledOption?.value) {
        if(toggledOption.hasAttribute('selected')) {
          option.setAttribute('selected', 'selected');
        } else {
          option.removeAttribute('selected');
        }
      }
      if(!this.multiple && option.value !== toggledOption?.value) {
        option.removeAttribute('selected');
      }
      return option;
    });
    this.suggestions = this.suggestions.map((suggestion) => {
      const index = parseInt(suggestion.getAttribute('data-index') ?? "", 10);
      const option = this.el.item(index);
      if (option && option.selected) {
        suggestion.setAttribute('aria-selected', 'true');
      }
      else {
        suggestion.removeAttribute('aria-selected');
      }
      return suggestion;
    });
    if(dispatch) {
      this.el.dispatchEvent(new Event('change'));
    }
    this._setButtonText();
    if (this.multiple && this._options.showSelected) {
      this._updateSelectedList();
    }

    if (close && this.open) {
      this._toggleOverlay();
    }
  }

  _updateSelectedList() {
    const items = this.currentOptions.map((option, index) => {
      if(!option.selected || option.hidden) {
        return;
      }
      const text = option.label || option.value;

      return `
        <li class="select-a11y__selected-item">
          <span>${text}</span>
          <button class="select-a11y-delete" title="${this._options.text.deleteItem.replace('{t}', text)}" type="button" data-index="${index}">
            <span class="sr-only">${this._options.text.delete}</span>
            <span class="select-a11y-delete__icon" aria-hidden="true"></span>
          </button>
        </li>`;
    }).filter(Boolean);

    if(this.selectedList) {
      this.selectedList.innerHTML = items.join('');

      if (items.length) {
        if (!this.selectedList?.parentElement) {
          this.wrap.appendChild(this.selectedList);
        }
      } else if (this.selectedList.parentElement) {
        this.wrap.removeChild(this.selectedList);
      }
    }
  }

  _wrap() {
    const wrapper = document.createElement('div');
    wrapper.classList.add('select-a11y');
    this.el.parentElement?.appendChild(wrapper);

    const tagHidden = document.createElement('div');
    tagHidden.classList.add('select-a11y__hidden');
    tagHidden.setAttribute('aria-hidden', 'true');

    if (this._options.useLabelAsButton) {
      tagHidden.appendChild(this.label);
    }
    tagHidden.appendChild(this.el);

    wrapper.appendChild(tagHidden);
    wrapper.appendChild(this.liveZone);
    wrapper.appendChild(this.button);
    if (this._options.clearable) {
      wrapper.appendChild(this.clearButton);
    }

    return wrapper;
  }
}
