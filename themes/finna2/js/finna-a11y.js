/*global finna */
finna.a11y = (function a11y() {
  function initA11y() {

    // On dropdown keydown.
    $(document).on('keydown.bs.dropdown.data-api', function dropdownKeyDown(e) {
      // On dropdown close.
      $(e.target).on('hidden.bs.dropdown', function dropdownClose(ev) {
        var dropdown = $(ev.target);
        var toggle = '[data-toggle="dropdown"]';
        var toggleElement = dropdown.find(toggle)[0];
        // Set a slight delay to address focus shift problems experienced by some screen readers.
        setTimeout(function shiftFocus() {
          // Set focus back to dropdown toggle.
          toggleElement.focus();
        }, 150);
      });
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
