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
   * Initialize cancel buttons.
   */
  function initCancelButtons() {
    // Initialize single cancel buttons for each hold
    document.querySelectorAll('.js-confirm-cancel').forEach(el => {
      el.addEventListener('click', (event) => {
        event.preventDefault();
        selectSingleCheckbox(el.dataset.cancelDetails);
        confirmCancelRequest(el, 'cancelSelected');
      });
    });
    // Initialize cancel selected button
    const cancelSelectedButton = document.querySelector('.js-cancel-selected');
    if (cancelSelectedButton) {
      cancelSelectedButton.addEventListener('click', () => {
        var count = document.querySelectorAll('form[name="updateForm"] .checkbox-select-item:checked').length;
        const cancelText = document.querySelector('.js-cancel-selected-text');
        if (cancelText && cancelText.dataset.translation) {
          cancelText.textContent = cancelText.dataset.translation.replace("%%count%%", count);
        }
      });
    }
  }

  return {
    init: () => {},
    initCancelButtons
  };
})();
