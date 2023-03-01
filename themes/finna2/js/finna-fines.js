/*global finna */
finna.fines = (function finnaFines() {

  const CHECKBOX_SELECTOR = 'form#online_payment_form .checkbox-select-item';

  var paySelectedDefaultText;

  /**
   * Get the whole part from currency in cents
   *
   * @param {int} currency
   *
   * @return {int}
   */
  function getWhole(currency)
  {
    return Math.trunc(currency / 100);
  }

  /**
   * Get the fraction part from currency in cents padded to two characters
   *
   * @param {int} currency
   * @param {string} template
   *
   * @return {string}
   */
  function getFraction(currency)
  {
    var fraction = String(currency % 100);
    while (fraction.length < 2) {
      fraction += '0';
    }
    return fraction;
  }

  /**
   * Format currency according to a template where 11 is whole and 22 is fraction
   *
   * @param {int} currency
   * @param {string} template
   *
   * @return {string}
   */
  function formatAmount(currency, template)
  {
    return template.replace('11', getWhole(currency)).replace('22', getFraction(currency));
  }

  /**
   * Set the aria-live attribute for an element
   *
   * @param {Element} element
   * @param {string} politeness
   *
   * @return {void}
   */
  function ariaLive(element, politeness)
  {
    if (politeness) {
      element.setAttribute('aria-polite', politeness);
    } else {
      element.removeAttribute('aria-polite');
    }
  }

  /**
   * Initialize payment
   *
   * @return {void}
   */
  function init()
  {
    const payButton = document.querySelector('#pay_selected');
    if (null === payButton) {
      return;
    }
    paySelectedDefaultText = payButton.value;
    const checkCheckboxes = function () {
      // Count the balance for selected fees:
      var selectedAmount = 0;
      document.querySelectorAll(CHECKBOX_SELECTOR + ':checked').forEach((cb) => {
        selectedAmount += parseInt(cb.dataset.amount, 10);
      });

      // If something is selected, include any transaction fee:
      var transactionFee = 0;
      if (selectedAmount) {
        const transactionField = document.querySelector('#online_payment_transaction_fee');
        if (transactionField) {
          transactionFee = parseInt(transactionField.dataset.raw, 10);
        }
      }

      const minimumContainer = document.querySelector('#online_payment_minimum_payment');
      const minimumAmount = parseInt(minimumContainer.dataset.raw, 10);
      if (selectedAmount + transactionFee >= minimumAmount) {
        payButton.removeAttribute('disabled');
        payButton.value = formatAmount(selectedAmount + transactionFee, payButton.dataset.template);
        minimumContainer.classList.add('hidden');
        ariaLive(minimumContainer, '');
      } else {
        payButton.setAttribute('disabled', 'disabled');
        payButton.value = paySelectedDefaultText;
        if (selectedAmount) {
          minimumContainer.classList.remove('hidden');
          ariaLive(minimumContainer, 'polite');
        } else {
          minimumContainer.classList.add('hidden');
          ariaLive(minimumContainer, '');
        }
      }

      // Update SR info:
      const srInfo = document.querySelector('#online_payment_selected_sr');
      srInfo.textContent = formatAmount(selectedAmount + transactionFee, srInfo.dataset.template);
      ariaLive(srInfo, 'polite');

      // Update summary for remaining after payment:
      const remainingAmount = parseInt(document.querySelector('#online_payment_total_due').dataset.raw, 10) - selectedAmount;
      const remainingField = document.querySelector('#online_payment_remaining_after .amount');
      remainingField.textContent = formatAmount(remainingAmount, remainingField.dataset.template);
    };

    document.querySelectorAll(CHECKBOX_SELECTOR).forEach((checkbox) => {
      checkbox.addEventListener('change', checkCheckboxes);
    });
  }

  var my = {
    init: init
  };

  return my;
})();
