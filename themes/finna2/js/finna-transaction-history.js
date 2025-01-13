/*global finna, VuFind */
finna.transactionHistory = (function transactionHistory() {

  const historyButtonSelector = 'div.js-download-loan-history';

  const toggleButtonSelector = 'button.js-history-toggle';

  const fileFormatButtonSelector = 'button.js-history-file-format';

  /**
   * Calculates the current page for the button limited by the last possible page to be downloaded.
   * @param {HTMLButtonElement} element Button element to be clicked to download history
   * @returns {void}
   */
  function setNextPage(element) {
    let currentPart = +element.dataset.currentPart;
    let lastPart = +element.dataset.lastPart;
    if (currentPart < lastPart) {
      currentPart++;
    }
    element.dataset.currentPart = currentPart;
  }
  
  
  /**
   * Sets the buttons text content to match for the next page to be downloaded if clicked.
   * @param {HTMLButtonElement} element Button element to be clicked to download history
   * @returns {void}
   */
  function syncButtonText(element) {
    const toggleButton = element.querySelector(toggleButtonSelector);
    const textContent = VuFind.translate('loan_history_download_part');
    toggleButton.textContent = `${textContent.replace('%%part%%', element.dataset.currentPart)} `;
    toggleButton.append(VuFind.icon('show-more', {}, true));
  }

  /**
   * Request part of a transaction history to download
   * @param {HTMLElement} element Parent element for transaction history downloading
   * @param {string} formatButton Clicked button containing format specific data
   */
  function getTransactionHistoryPart(element, formatButton)
  {
    const part = element.dataset.currentPart;
    const format = formatButton.dataset.format;
    const searchParams = new URLSearchParams({method: "getTransactionHistory", part, format, type: "file"});
    let filename;
    fetch (`${VuFind.path}/AJAX/FILE?${searchParams}`).then(response => {
      if (!response.ok) {
        throw new Error('');
      }
      const header = response.headers.get('Content-Disposition');
      const parts = header.split(';');
      filename = parts[1].split('=')[1].replaceAll("\"", "");

      return response.blob();
    }).then((blob) => {
      var url = window.URL.createObjectURL(blob);
      var a = document.createElement('a');
      a.href = url;
      a.download = filename;
      document.body.appendChild(a); // we need to append the element to the dom -> otherwise it will not work in firefox
      a.click();
      a.remove();
      setNextPage(element);
      syncButtonText(element);
    }).catch((reason) => {
      console.warn(reason);
    });
  }

  /**
   * Initializes a button to allow for loading loan history in chunks.
   * @param {HTMLButtonElement} element Button element to be clicked to download history
   * @returns {void}
   */
  function initButton(element) {
    fetch (`${VuFind.path}/AJAX/JSON?method=getTransactionHistory&type=status`)
      .then(response => {
        if (!response.ok) {
          throw new Error('');
        }
        return response.json();
      }).then(result => {
        if (!result.data) {
          element.style.display = 'none';
          return;
        }
        element.dataset.currentPart = 1;
        if (result.data && result.data.parts) {
          element.dataset.lastPart = result.data.parts;
          syncButtonText(element);
          const formatButtons = element.querySelectorAll(fileFormatButtonSelector);
          formatButtons.forEach(formatButton => {
            formatButton.addEventListener('click', (e) => {
              e.preventDefault();
              getTransactionHistoryPart(element, formatButton);
            });
          });
        }
      }).catch(error => {
        console.warn(error);
        element.style.display = 'none';
      });
  }

  /**
   * Initialize buttons to handle downloading transaction history
   */
  function init() {
    document.querySelectorAll(historyButtonSelector).forEach(el => { initButton(el); });
  }
  return {
    init: init
  };
})();
