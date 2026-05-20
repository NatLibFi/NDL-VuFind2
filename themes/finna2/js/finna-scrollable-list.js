/*global finna*/
finna.scrollableList = (function finnaScrollableList() {
  /**
   * Initialize scrollable list
   * @param {Element} [_container] The container element to search for links (default = document.body).
   */
  function initScrollableList(_container) {
    const container = _container || document.body;
    container.querySelectorAll(".list-scrollable").forEach((scrollable) => {
      if ('initialized' in scrollable.dataset) {
        return;
      }
      scrollable.dataset.initialized = true;

      // Identify DOM elements
      const list = scrollable.querySelector(".list-scrollable__list");
      if (!list) {
        console.warn('.list not found');
        return;
      }
      const items = list.querySelectorAll(".js-list-item");
      const links = list.querySelectorAll(".js-list-link");

      list.querySelectorAll('.list-scrollable__image').forEach(el => {
        el.onload = function onCarouselImageLoad() {
          if (this.naturalWidth === 10 && this.naturalHeight === 10) {
            el.classList.add('hidden');
            el.nextElementSibling.classList.remove('hidden');
          }
        };
      });

      // Find prev/next buttons
      const prevBtn = scrollable.querySelector('.js-scroll-prev');
      const nextBtn = scrollable.querySelector('.js-scroll-next');

      /**
       * Initialize tabindex
       */
      function initTabIndexes() {
        links.forEach(link => link.setAttribute("tabindex", "-1"));

        // Active item from HTML or fallback to the first one
        const activeItem =
            list.querySelector(".list-scrollable__list-item.active .list-scrollable__item-container") ||
            links[0];
        activeItem.setAttribute("tabindex", "0");
      }
      initTabIndexes();

      /**
       * Ensure only active element is tabbable
       * @param {object} activeLink active link
       */
      function updateTabIndexes(activeLink) {
        links.forEach(link => link.setAttribute("tabindex", "-1"));
        if (activeLink) activeLink.setAttribute("tabindex", "0");
      }
      // Initialize tabindex (first active or first item)
      const initialActive = list.querySelector(".list-item.active .list-link") || links[0];
      updateTabIndexes(initialActive);

      /**
       * Activate an item
       * @param {object} link link
       */
      function activate(link) {
        items.forEach(i => i.classList.remove("active"));
        link.parentElement.classList.add("active");
        updateTabIndexes(link);

        link.scrollIntoView({ behavior: "smooth", inline: "center" });
        link.focus({ preventScroll: true });
      }

      // Intersection Observer for prev/next buttons
      const observer = new IntersectionObserver(
        (entries) => {
          entries.forEach((entry) => {
            if (prevBtn && entry.target === items[0]) {
              prevBtn.classList.toggle("hidden", entry.isIntersecting);
            }
            if (nextBtn && entry.target === items[items.length - 1]) {
              nextBtn.classList.toggle("hidden", entry.isIntersecting);
            }
          });
        },
        { root: list, threshold: 0.9 }
      );

      observer.observe(items[0]);
      observer.observe(items[items.length - 1]);

      // Keyboard navigation
      list.addEventListener("keydown", (e) => {
        const idx = Array.from(links).indexOf(document.activeElement);
        if (e.key === "ArrowRight" && links[idx + 1]) {
          activate(links[idx + 1]);
        }
        if (e.key === "ArrowLeft" && links[idx - 1]) {
          activate(links[idx - 1]);
        }
      });

      /**
       * Button scrolling
       * @param {*} dir direction
       */
      function scrollByDir(dir) {
        list.scrollBy({
          left: (list.clientWidth * 0.5) * dir,
          behavior: "smooth",
        });
      }

      if (prevBtn) {
        prevBtn.addEventListener("click", () => scrollByDir(-1));
      }
      if (nextBtn) {
        nextBtn.addEventListener("click", () => scrollByDir(1));
      }

      // Make sure to start at left
      list.scrollTo({ left: 0, behavior: "instant" });
    });
  }

  /**
   * Initialize
   */
  function init() {
    initScrollableList();
  }

  var my = {
    init,
    initScrollableList
  };

  return my;
})();
