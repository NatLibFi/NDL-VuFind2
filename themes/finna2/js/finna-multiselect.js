/*global finna*/

finna.multiSelect = (function multiSelect(){
  var identifierNumber = 0;

  var inputElement = "<div class=\"dropdown\">" +
  "<input class=\"multiselect-input\" type=\"text\" data-toggle=\"dropdown\">" +
  "<ul class=\"dropdown-menu\">" +
  "</ul>" +
  "</div>";
  var listItem = "<li><a href=\"#\"></a></li>";
  var idStart = 'multiselect_root_';

  function initFields() {
    $('.finna-multiselect').each(function multify(){
      var root = $(this).parent();
      var tempElement = $(inputElement).clone();
      root.append(tempElement);
      var ul = tempElement.find('ul');
      $(this).css('display', 'none');
      initListItems($(this), $(ul));
    });
    initListeners();
  }

  function initListeners() {
    $(".multiselect-input").blur(function showDropdown(){
      $(this).dropdown();
    });
  }

  function initListItems(select, ul) {
    select.children('option').each(function createList(){
      var listCopy = $(listItem).clone();
      ul.append(listCopy.find('a').html($(this).html()));
    });
  }

  function initClickLogics() {

  }

  function addSelected(el) {

  }

  function removeSeleced(el) {

  }

  var my = {
    init: function init() {
      initFields()
    }
  };

  return my;
})();