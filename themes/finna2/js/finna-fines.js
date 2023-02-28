/*global finna */
finna.fines = (function finnaFines() {

  const CHECKBOX_SELECTOR = 'form#online_payment_form .checkbox-select-item';

  var paySelectedDefaultText;

  function getWhole(currency)
  {
    return Math.trunc(currency / 100);
  }

  function getFraction(currency)
  {
    var fraction = String(currency % 100);
    while (fraction.length < 2) {
      fraction += '0';
    }
    return fraction;
  }

  function formatAmount(currency, template)
  {
    return template.replace('11', getWhole(currency)).replace('22', getFraction(currency));
  }

  function init()
  {
    paySelectedDefaultText = document.querySelector('#pay_selected').value;
    const checkCheckboxes = function () {
      // Count the balance for selected fees:
      var selectedAmount = 0;
      document.querySelectorAll(CHECKBOX_SELECTOR + ':checked').forEach((cb) => {
        selectedAmount += parseInt(cb.dataset.amount, 10);
      });

      // If something is selected, include any transaction fee:
      var transactionFee = 0;
      if (selectedAmount) {
        const transactionField = document.querySelector('#online_payment_transaction_fee .amount');
        if (transactionField) {
          transactionFee = parseInt(transactionField.dataset.raw, 10);
        }
      }

      const minimumAmount = parseInt(document.querySelector('#online_payment_minimum_payment .amount').dataset.raw, 10);
      const button = document.querySelector('#pay_selected');
      const minimumContainer = document.querySelector('#online_payment_minimum_payment');
      if (selectedAmount + transactionFee >= minimumAmount) {
        button.removeAttribute('disabled');
        button.value = formatAmount(selectedAmount + transactionFee, button.dataset.template);
        minimumContainer.classList.add('invisible');
      } else {
        button.setAttribute('disabled', 'disabled');
        button.value = paySelectedDefaultText;
        if (selectedAmount) {
          minimumContainer.classList.remove('invisible');
        } else {
          minimumContainer.classList.add('invisible');
        }
      }

      // Update summary for remaining after payment:
      const remainingAmount = parseInt(document.querySelector('#online_payment_total_due .amount').dataset.raw, 10) - selectedAmount;
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
