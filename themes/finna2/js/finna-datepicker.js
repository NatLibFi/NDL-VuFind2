/* global finna, VuFind, trapFocus, Popper */
finna.finnaDatepicker = (function finnaDatepicker() {
  function initDatepicker() {
    const datepickers = document.querySelectorAll('.finna-dateinput');
    datepickers.forEach(datepicker => {
      const input = datepicker.querySelector('input');
      const datepickerToggle = datepicker.querySelector('.datepicker-toggle');
      const datepickerPopover = datepicker.querySelector('.datepicker-popover');
      const datepickerCalendar = datepicker.querySelector('calendar-date');
      let popperInstance = null;

      // Create popper instance
      function createPopperInstance() {
        popperInstance = Popper.createPopper(datepickerToggle, datepickerPopover, {
          placement: "bottom",
          modifiers: [
            {
              name: "offset", //offsets popper from the reference/button
              options: {
                offset: [0, 8]
              }
            }
          ]
        });
      }

      // Destroy popper instance
      function destroyPopperInstance() {
        if (popperInstance) {
          popperInstance.destroy();
          popperInstance = null;
        }
      }

      // Open the datepicker popover
      function datepickerOpen() {
        createPopperInstance();
        datepickerPopover.show();
        datepickerCalendar.focus();
        trapFocus(datepickerPopover);
      }

      // Close the datepicker popover
      function datepickerClose() {
        datepickerPopover.close();
        destroyPopperInstance();
        datepickerToggle.focus();
      }

      // Event listener for the datepicker when a date is selected from the calendar
      datepickerCalendar.addEventListener("change", (event) => {
        const datepickerSelected = VuFind.translate('datepicker_selected');
        const dateString = event.target.value;
        // Convert the date format from YYYY-MM-DD to DD.MM.YYYY
        const convertedDate = dateString.replace(/(\d{4})-(\d{2})-(\d{2})/, "$3.$2.$1");
        input.value = convertedDate;
        const selectedDate = datepickerToggle.querySelector('.datepicker-date-selected');
        selectedDate.replaceChildren(datepickerSelected, ' ', convertedDate);
        datepickerClose();
      });

      // Event listener for the datepicker close button to close the datepicker popover
      const datepickerCloseBtn = datepickerPopover.querySelector('.btn-datepicker-close');
      if (datepickerCloseBtn) {
        datepickerCloseBtn.addEventListener("click", () => {
          datepickerClose();
        });
      }

      // Event listener for keydown events on the calendar
      datepickerPopover.addEventListener("keydown", (event) => {
        if (event.key === "Escape") {
          datepickerClose();
        }
      });

      // Event listener for the toggle button click
      datepickerToggle.addEventListener('click', () => {
        if (datepickerPopover.open === true) {
          datepickerClose();
        } else {
          datepickerOpen();
        }
      });

      // Event listener for down clicks outside the datepicker popover
      document.addEventListener('mousedown', (event) => {
        if (datepickerPopover.open === true && datepickerCalendar) {
          if (datepickerPopover.contains(event.target)) return;
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
