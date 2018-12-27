/*global finna, VuFind*/

finna.multiSelect = (function multiSelect(){
  var inputElement;
  var listItem;
  var selectedItem;

  function initTemplates() {
    inputElement = "<div aria-label=\"testi\" class=\"multiselect-dropdown\">" +
      "<label for=\"\"></label>" +
      "<input aria-expanded=\"false\" autocomplete=\"off\" aria-autocomplete=\"list\" aria-label=\"" + VuFind.translate('add_selection') + "\" class=\"form-control multiselect-input\" type=\"text\">" +
      "<ul class=\"multiselect-dropdown-menu\">" +
      "</ul>" +
      "<div class=\"multiselect-selected\">" +
      "</div>" +
      "</div>";
    listItem = "<li data-target=\"\" tabindex=\"-1\"></li>";
    selectedItem = "<button class=\"multiselect-filter\" data-target=\"\" type=\"button\" title=\"" + VuFind.translate('remove_selection') + "\"></button>";
  }

  function initFields() {
    initTemplates();
    $('.finna-multiselect').each(function createDropdowns(){
      var originalSelect = $(this);
      var root = $(this).parent();
      var tempElement = $(inputElement).clone();
      var input = tempElement.find('.multiselect-input');
      var label = tempElement.find('label');
      var inputLabel = originalSelect.data('label');
      var ul = $(tempElement.find('ul'));

      label.html(inputLabel);
      var completedAria = input.attr('aria-label') + " " + inputLabel;
      tempElement.find('.multiselect-input').attr('aria-label', completedAria);
      root.append(tempElement);
      originalSelect.css('display', 'none');
      originalSelect.prependTo(tempElement);
      initListItems(originalSelect, ul);
    });
    initKeyBindings();
  }

  function initKeyBindings() {
    $('.multiselect-input').on("keydown", function preventSubmit(e){
      var keyCode = e.which;

      if (keyCode === 13) {
        e.preventDefault();
        //Do not submit form on enter press
      }
    });

    $('.multiselect-input').focusin(function showList(){
      openList($(this));
    });

    //Dynamically during runtime created elements needs an external listener
    $(document).on('click', '.multiselect-filter', function removeFilter(){
      removeFromSelected($(this));
    });

    $('.multiselect-dropdown').focusout(function removeFocus(e){
      //If we still have focus inside dropdown, prevent default
      var menu = $(e.currentTarget).find('.multiselect-dropdown-menu');

      if (menu.has(e.relatedTarget).length) {
        e.preventDefault();
      } else {
        closeList($(this));
      }
    });

    $('.multiselect-dropdown-menu li').on('keydown', function listKeys(e){
      var keyCode = e.which;

      switch (keyCode) {
      case 40:
        e.preventDefault();
        nextItem($(this));
        break;
      case 38:
        e.preventDefault();
        previousItem($(this));
        break;
      case 13:
        e.preventDefault();
        addToFilters($(this));
        break;
      }
    });

    $('.multiselect-dropdown-menu li').on('click', function clickItem(){
      addToFilters($(this), true);
    });

    $('.multiselect-input').on("keyup", function keyboardLogic(e){
      var keyCode = e.which;
      switch (keyCode) {
      case 40:
        e.preventDefault();
        jumpToList($(this));
        break;
      case 9:
        break;
      default:
        openList($(this));
        filterOptions($(this));
        break;
      }
    });
  }

  function nextItem(element) {
    var nextAvailable = $(element).nextAll('li:visible');

    if (nextAvailable.length) {
      nextAvailable.first().focus();
    }
  }

  function previousItem(element) {
    var previousAvailable = $(element).prevAll('li:visible');

    if (previousAvailable.length) {
      previousAvailable.first().focus();
    } else {
      element.parent().siblings('.multiselect-input').focus();
    }
    
  }

  //We want to focus only on the first visible item
  function jumpToList(element) {
    var visible = getVisibleObjects(element);

    if (visible.length) {
      visible.first().focus();
    }
  }

  //Find all the visible items in the dropdown
  function getVisibleObjects(element) {
    var menu = element.siblings("ul.multiselect-dropdown-menu").first();
    return menu.children(':visible');
  }

  //Removes the given selected element
  function removeFromSelected(element) {
    var dataTarget = element.attr('data-target');
    var originalSelect = element.parent().siblings('select');
    var parent = element.parent();
    originalSelect.find("option" + "[value='" + dataTarget + "']").removeAttr('selected');
    parent.siblings('.multiselect-dropdown-menu')
      .find("li" + "[data-target='" + dataTarget + "']").removeClass('selected');

    var siblings = element.siblings('button');
    element.remove();

    if (siblings.length) {
      siblings.first().focus();
    } else {
      parent.siblings('.multiselect-input').focus();
    }
  }

  function addToFilters(element) {
    var tempButton = $(selectedItem).clone();
    var selectedArea = element.parent().siblings('.multiselect-selected');
    var originalSelect = element.parent().siblings('select');
    var dataTarget = element.attr('data-target');

    element.addClass('selected');
    tempButton.html(element.html());
    tempButton.attr('data-target', dataTarget);
    originalSelect.find("option[value='" + dataTarget + "']").attr('selected', true);
    selectedArea.append(tempButton);
    element.parent().siblings('.multiselect-input').focus();
  }

  function filterOptions(element) {
    var menu = element.siblings("ul.multiselect-dropdown-menu").first();
    var value = $.trim(element.val().toLowerCase());

    if (value === '') {
      menu.children().each(function resetAll(){
        $(this).show();
      });
    } else {
      menu.children().each(function filterMatches(){
        var lowerCase = $(this).html().toLowerCase();

        if (lowerCase.indexOf(value) < 0) {
          $(this).hide();
        } else if ($(this).is(':hidden')) {
          $(this).show();
        }
      });
    }
  }

  function openList(element) {
    //Find the parent which holds all the dropdown components
    var parent = element.parent();

    if (!parent.hasClass('open')) {
      parent.addClass('open');
      element.attr('aria-expanded', 'true');
      parent.find('.multiselect-dropdown-menu').show();
    }
  }

  function closeList(element) {
    if (element.hasClass('open')) {
      element.removeClass('open');
      var menu = element.find('.multiselect-dropdown-menu');
      element.find('.multiselect-input').attr('aria-expanded', 'false');
      menu.hide();
    }
  }

  function initListItems(select, ul) {
    select.children('option').each(function createList(){
      var listCopy = $(listItem).clone();
      listCopy.html($(this).html());
      listCopy.attr('data-target', $(this).val());
      ul.append(listCopy);
      var attr = $(this).attr('selected');
      if (typeof attr !== "undefined" && attr !== false) {
        addToFilters(listCopy);
      }
    });
  }

  var my = {
    init: initFields
  };

  return my;
})();