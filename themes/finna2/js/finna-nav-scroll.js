/*global finna */
finna.finnaNavScroll = (function finnaNavScroll() {
  /**
   * Initialize nav scroll elements
   */
  function initNavScroll() {

    document.querySelectorAll('.nav-scroll').forEach(navScroll => {
      // Create BEFORE element
      const before = document.createElement('span');
      before.classList.add('arrow-deco', 'arrow-deco--before');
      before.textContent = '';

      // Create AFTER element
      const after = document.createElement('span');
      after.classList.add('arrow-deco', 'arrow-deco--after');
      after.textContent = '';

      // Insert into DOM around button
      navScroll.insertAdjacentElement('beforebegin', before);
      navScroll.insertAdjacentElement('afterend', after);
    });
  }
  var my = {
    init: function init() {
      initNavScroll();
    },
  };

  return my;
})();
