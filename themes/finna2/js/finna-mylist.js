/*global finna, Sortable */
finna.myList = (function finnaMyList() {

  /**
   * Initialize favorite ordering functionality
   */
  function initFavoriteOrderingFunctionality() {
    var el = document.getElementById('sortable');
    var sortable = Sortable.create(el);
    $('#sort_form').on('submit', function onSubmitSortForm(/*event*/) {
      var list = [];
      var children = sortable.el.children;
      if (children.length > 0) {
        for (var i = 0; i < children.length; i++) {
          list.push(children[i].id);
        }
      }
      this.querySelector('input[name="orderedList"]').value = JSON.stringify(list);
      return true;
    });
  }

  var my = {
    initFavoriteOrderingFunctionality: initFavoriteOrderingFunctionality,
  };

  return my;
})();
