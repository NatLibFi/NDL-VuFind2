/* global finna, VuFind, trapFocus, Popper */
finna.finnaDatepicker = (function finnaDatepicker() {
  function initDatepicker() {
    document.querySelectorAll('.finna-datepicker').forEach(datepicker => {
      const input = datepicker.querySelector('input');
      const datepickerToggle = datepicker.querySelector('.datepicker-toggle');
      const datepickerDialog = datepicker.querySelector('.datepicker-dialog');
      const datepickerCalendar = datepicker.querySelector('calendar-date');
      const datepickerCloseBtn = datepickerDialog.querySelector('.btn-datepicker-close');
      let popperInstance = null;

      // Create popper instance
      const createPopperInstance = () => {
        popperInstance = Popper.createPopper(datepickerToggle, datepickerDialog, {
          placement: "bottom",
          modifiers: [{ name: "offset", options: { offset: [0, 8] } }]
        });
      };

      // Destroy popper instance
      const destroyPopperInstance = () => {
        if (popperInstance) {
          popperInstance.destroy();
          popperInstance = null;
        }
      };

      // Open the datepicker dialog
      const datepickerOpen = () => {
        createPopperInstance();
        datepickerDialog.setAttribute('data-show', '');
        popperInstance.update();
        datepickerCalendar.focus({ preventScroll: true, focusVisible: true });
        trapFocus(datepickerDialog);
      };

      // Close the datepicker dialog
      const datepickerClose = () => {
        datepickerDialog.removeAttribute('data-show');
        destroyPopperInstance();
        datepickerToggle.focus({ preventScroll: true });
      };

      // Event listener to date picker for handling date selection
      datepickerCalendar.addEventListener("change", event => {
        const datepickerSelected = VuFind.translate('datepicker_selected');
        const [year, month, day] = event.target.value.split('-');
        // Convert the date format from YYYY-MM-DD to D.M.YYYY
        const convertedDate = `${+day}.${+month}.${year}`;
        input.value = convertedDate;
        datepickerToggle.querySelector('.datepicker-date-selected')
          .replaceChildren(datepickerSelected, ' ', convertedDate);
        datepickerClose();
      });

      // Event listener for keydown events on the datepicker
      datepickerDialog.addEventListener("keydown", (event) => {
        if (event.key === "Escape") {
          datepickerClose();
        }
      });

      // Add event listener to close button, if it exists
      if (datepickerCloseBtn) {
        datepickerCloseBtn.addEventListener("click", datepickerClose);
      }

      // Event listener to close datepicker on "Escape" key press
      datepickerDialog.addEventListener("keydown", event => {
        if (event.key === "Escape") {
          datepickerClose();
        }
      });

      // Event listener for the toggle button click
      datepickerToggle.addEventListener('click', () => {
        if (datepickerDialog.open === true) {
          datepickerClose();
        } else {
          datepickerOpen();
        }
      });

      // Event listener for down clicks outside the datepicker
      document.addEventListener('mousedown', event => {
        if (datepickerDialog.hasAttribute('data-show') && !datepickerDialog.contains(event.target)) {
          datepickerClose();
        }
      });
    });
  }
  var my = {
    init: function init() {
      initDatepicker();
    },
  };
  return my;
})();
