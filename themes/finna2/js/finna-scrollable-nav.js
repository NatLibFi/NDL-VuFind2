/*global finna*/
finna.scrollableNav = (function finnaScrollableNav() {
  /**
   * Initialize scrollable nav
   */
  function init() {
    document.querySelectorAll('.nav-scrollable').forEach((scrollable) => {
      const list = scrollable.querySelector('.nav');
      const items = [...list.querySelectorAll('.nav-item')];
      const links = [...list.querySelectorAll('.nav-link')];

      const prevBtn = scrollable.querySelector('.js-scroll-prev');
      const nextBtn = scrollable.querySelector('.js-scroll-next');

      // Add scroll to buttons
      const scrollByDir = (dir) =>
        list.scrollBy({ left: (list.clientWidth * 0.5) * dir, behavior: 'smooth' });

      if (prevBtn) {
        prevBtn.onclick = () => scrollByDir(-1);
      }
      if (nextBtn) {
        nextBtn.onclick = () => scrollByDir(1);
      }

      /**
       * Navigation Logic
       * @param {HTMLElement} el Element
       */
      function navigate(el) {
        if (!el) return;
        links.forEach(l => {
          l.classList.remove('active');
          l.setAttribute('tabindex', '-1');
        });
        el.classList.add('active');
        el.setAttribute('tabindex', '0');
        el.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
        el.focus({ preventScroll: true });
      }

      // Add keyboard navigation
      links.forEach(link => link.addEventListener('click', (e) => { e.preventDefault(); navigate(link); }));

      list.addEventListener('keydown', (e) => {
        const idx = Array.from(links).indexOf(document.activeElement);
        if (e.key === 'ArrowRight') {
          navigate(links[idx + 1]);
        }
        if (e.key === 'ArrowLeft') {
          navigate(links[idx - 1]);
        }
      });

      // Show and hide arrows
      const observer = new IntersectionObserver(entries => {
        entries.forEach(entry => {
          if (prevBtn && entry.target === items[0]) {
            prevBtn.classList.toggle('hidden', entry.isIntersecting);
          }

          if (nextBtn && entry.target === items[items.length - 1]) {
            nextBtn.classList.toggle('hidden', entry.isIntersecting);
          }
        });
      }, { root: list, threshold: 0.9 });

      observer.observe(items[0]);
      observer.observe(items[items.length - 1]);

      // Scroll to active item on load
      const activeOnLoad = list.querySelector('.nav-item .nav-link.active') || links[0];

      links.forEach(l => l.tabIndex = -1);
      activeOnLoad.tabIndex = 0;

      activeOnLoad.scrollIntoView({
        behavior: 'instant',
        inline: 'center',
        block: 'nearest'
      });
    });
  }

  return {
    init
  };
})();
