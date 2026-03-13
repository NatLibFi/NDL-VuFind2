/*global finna, VuFind*/
finna.scrollableNav = (function finnaScrollableNav() {
  /**
   * Initialize scrollable nav
   */
  function init() {
    document.querySelectorAll('.nav-scrollable').forEach((scrollable) => {
      const list = scrollable.querySelector(".nav");
      const items = [...list.querySelectorAll(".nav-item")];
      const links = [...list.querySelectorAll(".nav-link")];

      // Create arrow buttons
      const btn = (dir, label, symbol) => {
        const b = document.createElement("button");
        b.className = `arrow-btn scroll-${dir}-btn`;
        b.innerHTML = symbol;
        b.setAttribute("aria-label", label);
        scrollable.append(b);
        return b;
      };

      const prevBtn = btn("prev", "Scroll backward", VuFind.icon('tabs-prev'));
      const nextBtn = btn("next", "Scroll forward", VuFind.icon('tabs-next'));

      prevBtn.classList.add("hidden"); // initially hidden

      // Add scroll to buttons
      const scrollByDir = (dir) =>
        list.scrollBy({ left: (list.clientWidth * 0.5) * dir, behavior: "smooth" });

      prevBtn.onclick = () => scrollByDir(-1);
      nextBtn.onclick = () => scrollByDir(1);


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

      list.addEventListener("keydown", (e) => {
        const idx = Array.from(links).indexOf(document.activeElement);
        if (e.key === 'ArrowRight') navigate(links[idx + 1]);
        if (e.key === 'ArrowLeft') navigate(links[idx - 1]);
      });

      // Show and hide arrows
      const observer = new IntersectionObserver(entries => {
        entries.forEach(entry => {
          if (entry.target === items[0])
            prevBtn.classList.toggle("hidden", entry.isIntersecting);

          if (entry.target === items[items.length - 1])
            nextBtn.classList.toggle("hidden", entry.isIntersecting);
        });
      }, { root: list, threshold: 0.9 });

      observer.observe(items[0]);
      observer.observe(items[items.length - 1]);

      // Scroll to active item on load
      const activeOnLoad = list.querySelector(".nav-item .nav-link.active") || links[0];

      links.forEach(l => l.tabIndex = -1);
      activeOnLoad.tabIndex = 0;

      activeOnLoad.scrollIntoView({
        behavior: "instant",
        inline: "center",
        block: "nearest"
      });
    });
  }

  return {
    init
  };
})();
