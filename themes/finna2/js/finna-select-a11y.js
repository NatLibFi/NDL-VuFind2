/* global VuFind, SelectA11y  */

/**
 *  Extend Select-a11y (https://sr.ht/~nicolaskempf57/select-a11y/)
 */
class finnaSelectA11y extends SelectA11y.Select {
  constructor(el, options) {
    const finnaSelectA11yText = {
      help: VuFind.translate('select_a11y_help'),
      placeholder: VuFind.translate('select_a11y_placeholder'),
      noResult: VuFind.translate('select_a11y_noresult'),
      results: "{x} " + VuFind.translate('select_a11y_results'),
      delete: VuFind.translate('select_a11y_delete'),
      deleteItem: VuFind.translate('select_a11y_delete') + " {t}",
      clear: VuFind.translate('select_a11y_clear')
    };
    const passedOptions = Object.assign({}, options);
    const mergedOptions = Object.assign({
      text: finnaSelectA11yText,
    }, passedOptions);
    super(el, mergedOptions);
    this.setSelectA11yIcons();
  }
  setSelectA11yIcons() {
    const buttonSelect = this.button;
    const iconButtonContainer = buttonSelect.querySelector('.select-a11y-button__icon');
    if (iconButtonContainer) {
      iconButtonContainer.innerHTML = ' ' + VuFind.icon('filter-expand', 'select-expand') + VuFind.icon('filter-collapse', 'select-collapse');
    }
  }
}
// Add global space.
window.finnaSelectA11y = finnaSelectA11y;
