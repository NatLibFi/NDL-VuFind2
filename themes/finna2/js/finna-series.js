/*global VuFind, finna*/
finna.series = (function finnaSeries() {
  /**
   * Initialize series tab
   */
  function initSeriesTab() {
    document.querySelectorAll(".list-scrollable").forEach((scrollable, index) => {

      // Identify DOM elements
      const list = scrollable.querySelector(".list");
      const items = list.querySelectorAll(".list-item");
      const links = list.querySelectorAll(".list-link");

      list.querySelectorAll('.list-link img').forEach(el => {
        el.onload = function onCarouselImageLoad() {
          if (this.naturalWidth !== 10 && this.naturalHeight !== 10) {
            el.nextElementSibling.classList.add('hidden');
          }
        };
      });

      // Create next/prev buttons dynamically
      const prevBtn = document.createElement("button");
      prevBtn.className = "arrow-btn hidden";
      prevBtn.setAttribute("aria-label", "Scroll backward");
      prevBtn.innerHTML = "❮";

      const nextBtn = document.createElement("button");
      nextBtn.className = "arrow-btn";
      nextBtn.setAttribute("aria-label", "Scroll forward");
      nextBtn.innerHTML = "❯";

      // Positioning classes
      prevBtn.classList.add("scroll-prev-btn");
      prevBtn.classList.add("scroll-prev-btn-" + index);
      nextBtn.classList.add("scroll-next-btn");
      nextBtn.classList.add("scroll-next-btn-" + index);

      // Insert buttons into DOM
      scrollable.prepend(prevBtn);
      scrollable.append(nextBtn);

      /**
       * Initialize tabindex
       */
      function initTabIndexes() {
        links.forEach(link => link.setAttribute("tabindex", "-1"));

        // Active item from HTML or fallback to the first one
        const activeItem =
            list.querySelector(".list-item.active .list-link") ||
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
            if (entry.target === items[0]) {
              prevBtn.classList.toggle("hidden", entry.isIntersecting);
            }
            if (entry.target === items[items.length - 1]) {
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

      prevBtn.addEventListener("click", () => scrollByDir(-1));
      nextBtn.addEventListener("click", () => scrollByDir(1));

      // Make sure to start at left
      list.scrollTo({ left: 0, behavior: "instant" });
    });

    document.querySelectorAll(".series-header").forEach((el) => {
      el.querySelectorAll(".dropdown-item.dropdown__link").forEach((link) => {
        const container = document.querySelector(".record-tab-series-container");
        link.addEventListener("click", function onSeriesLabelClick(e) {
          e.preventDefault();
          $.ajax({
            url: VuFind.path + '/AJAX/JSON?method=getRecordSeries',
            dataType: 'json',
            data: {
              'id': link.getAttribute('data-id'),
              'source': link.getAttribute('data-source'),
              'seriesKey': link.getAttribute('data-serieskey')
            }
          }).done(function onGetRecordSeriesDone(response) {
            container.innerHTML = VuFind.updateCspNonce(response.data.html);
            initSeriesTab();
            container.querySelector('.dropdown-toggle').focus();
          }).fail(function onGetRecordSeriesFail() {
            container.innerHTML = VuFind.translate('error_occurred');
          });
        });
      });
    });
  }


  /**
   * Initialize
   */
  function init() {
    initSeriesTab();
  }

  var my = {
    init: init
  };

  return my;
})();