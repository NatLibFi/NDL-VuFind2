/* global VuFind */

$(document).ready(function onFinnaBazaarBrowseBarReady() {
  $('#finna-bazaar-add-resource').submit(function onAddResourceFormSubmit(event) {
    event.preventDefault();
    const form = $(this);
    $.getJSON(VuFind.path + '/AJAX/JSON?method=bazaarDestroySession')
      .always(function afterAjaxCall() {
        form.off('submit');
        form.submit();
      });
  });
});
