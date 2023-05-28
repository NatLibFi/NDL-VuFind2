/* global VuFind */

$(function onFinnaBazaarBrowseBarReady() {
  $('#finna-bazaar-add-resource').on("submit", function onAddResourceFormSubmit(event) {
    event.preventDefault();
    const form = $(this);
    $.getJSON(VuFind.path + '/AJAX/JSON?method=bazaarDestroySession')
      .always(function afterAjaxCall() {
        form.off('submit');
        form.submit();
      });
  });
});
