/*global finna, Popper */
finna.datepicker = (function datepicker() {
  function initDatepicker() {
    const button = document.querySelector('#button');
    const tooltip = document.querySelector('#tooltip');
    const popperInstance = Popper.createPopper(button, tooltip, {
      modifiers: [
        {
          name: 'offset',
          options: {
            offset: [0, 8],
          },
        },
      ],
    })
    function showDatepicker() {
      tooltip.setAttribute('data-show', '');
      // Tell Popper to update the tooltip position
      popperInstance.update();
    }
    function hideDatepicker() {
      tooltip.removeAttribute('data-show');
    }
    const showEvents = ['mouseenter', 'focus'];
    const hideEvents = ['mouseleave', 'blur'];

  showEvents.forEach((event) => {
    button.addEventListener(event, showDatepicker);
  });

  hideEvents.forEach((event) => {
    button.addEventListener(event, hideDatepicker);
  });
};
  var my = {
    init: function init() {
      initDatepicker();
    },
  };
  return my;
})();