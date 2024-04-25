/*global finna */
finna.a11y = (function a11y() {
  function initA11y() {

    // On dropdown open
    $(document).on('shown.bs.dropdown', function dropdownOpen(event) {
      var dropdown = $(event.target);

      // Set aria-expanded to true
      dropdown.find('.dropdown-toggle').attr('aria-expanded', true);

      // Set focus on the first link in the dropdown
      setTimeout(function shiftFocus() {
        dropdown.find('.dropdown-menu li:first-child a').trigger("focus");
      }, 50);
    });

    // On dropdown close
    $(document).on('hidden.bs.dropdown', function dropdownClose(event) {
      var dropdown = $(event.target);

      // Set aria-expanded to false
      dropdown.find('.dropdown-toggle').attr('aria-expanded', false);

      // Set focus back to dropdown toggle
      dropdown.find('.dropdown-toggle').trigger("focus");
    });

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
