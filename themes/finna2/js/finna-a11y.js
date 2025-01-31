/*global finna */
finna.a11y = (function a11y() {
  /**
   * Initialize event listeners for bootstrap accessibility
   */
  function initA11y() {
    // Restore focus back to trigger element after lightbox is closed.
    $(document).on('show.bs.modal', function triggerFocusShift() {
      let triggerElement = document.activeElement;
      $(document).one('hidden.bs.modal', function restoreFocus() {
        if (triggerElement) {
          triggerElement.focus();
        }
      });
    });
  }
  var my = {
    init: function init() {
      initA11y();
    },
  };

  return my;
})();
