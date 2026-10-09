/* global finna, confirmCancelRequest */
finna.holds = (function finnaHolds() {

  /**
   * Select single checkbox and deselect other checkboxes.
   * @param {string} value Value of checkbox to be selected.
   */
  function selectSingleCheckbox(value) {
    document.querySelectorAll('form[name="updateForm"] .checkbox-select-item').forEach(checkbox => checkbox.checked = checkbox.value === value);
  }

  /**
   * Initialize single cancel buttons for each hold.
   */
  function initCancelButtons() {
    document.querySelectorAll('.js-confirm-cancel').forEach(el => {
      el.addEventListener('click', (event) => {
        event.preventDefault();
        selectSingleCheckbox(el.dataset.cancelDetails);
        confirmCancelRequest(el, 'cancelSelected');
      });
    });
  }

  return {
    init: () => {},
    initCancelButtons
  };
})();
