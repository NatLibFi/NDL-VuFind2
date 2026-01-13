/* global finna */
finna.bulkActionButtons = (function finnaBulkActionButtons() {
  return {
    init: () => {
      const element = document.querySelector('.finna-bulk-action-buttons');
      if (!element) {
        return;
      }
      const functionElements = element.querySelectorAll('.mylist-functions .js-bulk-action');

      const mutationObserver = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
          if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
            const noneChecked = mutation.target.classList.contains('hidden');
            functionElements.forEach((el) => {
              el.toggleAttribute('disabled', noneChecked);
              el.toggleAttribute('aria-disabled', noneChecked);
            });
          }
        });
      });
      const clearSelectionButton = element.querySelector('.clear-selection');
      mutationObserver.observe(clearSelectionButton, { attributes: true });

      const noneChecked = clearSelectionButton.classList.contains('hidden');
      functionElements.forEach((el) => {
        el.toggleAttribute('disabled', noneChecked);
        el.toggleAttribute('aria-disabled', noneChecked);
      });
    }
  };
})();
