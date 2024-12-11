/*global VuFind*/
VuFind.register('finnaBootstrap3CompatibilityLayer', function finnaBootstrap3CompatibilityLayer() {
  function initDropdownStyles() {
    document.querySelectorAll('ul.dropdown-menu > li').forEach((el) => {
      el.classList.add('dropdown-item');
    });
    document.querySelectorAll('.dropdown-menu-right').forEach((el) => {
      el.classList.add('dropdown-menu-end');
    });
  }

  function init() {
    initDropdownStyles();
  }

  return {
    init: init
  };
});
