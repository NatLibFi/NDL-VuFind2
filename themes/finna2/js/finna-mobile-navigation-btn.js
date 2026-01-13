/* global finna, VuFind */

finna.mobileNavigationBtn = (() => {
  
  /**
   * Initialize mobile navigation button.
   * @returns {void}
   */
  function init() {
    const element = document.getElementById('mobile-sidebar-btn');

    const targetSelector = element.dataset.target || '#sidebar';
    const sidebarContent = document.querySelector(targetSelector);
    if (!sidebarContent || !element) {
      return;
    }
    element.addEventListener('click', () => {
      const sidebarClone = sidebarContent.cloneNode(true);
      sidebarClone.id = 'sidebar-lightbox';
      const modalBody = document.querySelector('#modal .modal-body');
      let modalHeader = sidebarClone.querySelector('h1:first-of-type', 'h2:first-of-type');
      if (modalHeader && modalBody.tagName.toLowerCase() !== 'h2') {
        // Change h1 to h2 for accessibility
        const newHeader = VuFind.el('h2', modalHeader.className, {}, modalHeader.childNodes);
        modalHeader.replaceWith(newHeader);
      } else if (!modalHeader){
        // Create a new header from button text if none exists
        modalHeader = VuFind.el('h2', 'mobile-navigation-header', {}, element.innerText);
        sidebarClone.insertAdjacentElement('afterbegin', modalHeader);
      }
      modalHeader.id = 'lightbox-title';
      sidebarClone.querySelectorAll('[data-toggle="finna-toggletip"]').forEach(toggleTip => {
        delete toggleTip.dataset.initialized;
      });
      VuFind.lightbox.render(sidebarClone.outerHTML);
      const renderedSidebar = document.getElementById('sidebar-lightbox');

      const renderedTitle = document.getElementById('lightbox-title');
      if (renderedTitle) {
        modalBody.insertAdjacentElement('afterbegin', renderedTitle);
      }

      finna.layout.initToolTips(renderedSidebar);
      VuFind.emit('finna.mobileNavigation.opened');
    });
  }

  return {
    init
  };
})();


