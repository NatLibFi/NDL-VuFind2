/*global finna, VuFind*/
finna.multiSelect = (function multiSelect(){
  var inputElement;
  var listItem;
  var selectedItem;

  function initTemplates() {
    inputElement = "<div class=\"multiselect-dropdown\">" +
      "<label for=\"\"></label>" +
      "<input aria-expanded=\"false\" autocomplete=\"off\" aria-autocomplete=\"list\" aria-label=\"" + VuFind.translate('add_selection') + "\" class=\"form-control multiselect-input\" type=\"text\">" +
      "<ul class=\"multiselect-dropdown-menu\">" +
      "</ul>" +
      "<div class=\"multiselect-selected\">" +
      "<span class=\"removed-selection sr-only\" aria-label=\"" + VuFind.translate('selection_removed') + "\" tabindex=\"-1\"></span>" +
      "</div>" +
      "</div>";
    listItem = "<li data-target=\"\" tabindex=\"-1\"></li>";
    selectedItem = "<button class=\"multiselect-selection\" data-target=\"\" type=\"button\" title=\"" + VuFind.translate('remove_selection') + "\"></button>";
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
      var completedAria = input.attr('aria-label') + " " + inputLabel;

      label.html(inputLabel);
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
    $(document).on('click', '.multiselect-selection', function removeFilter(){
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
      case 8:
        e.preventDefault();
        continueWriting($(this), e.key);
        break;
      default:
        if (isSpecialButton(keyCode)) {
          return;
        }

        e.preventDefault();
        continueWriting($(this), e.key);
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
      case 8:
        filterOptions($(this));
        break;
      case 9:
        break;
      default:
        if (isSpecialButton(keyCode)) {
          return;
        }
        openList($(this));
        filterOptions($(this));
        break;
      }
    });
  }

  function isSpecialButton(keycode) {
    switch (keycode) {
    case 8:
    case 9:
    case 16:
    case 17:
    case 18:
    case 20:
    case 27:
    case 32:
    case 33:
    case 34:
    case 35:
    case 36:
    case 45:
    case 46:
    case 91:
    case 112:
    case 113:
    case 114:
    case 115:
    case 116:
    case 117:
    case 118:
    case 119:
    case 120:
    case 121:
    case 122:
    case 123:
      return true;
    default:
      return false;
    }
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

  function continueWriting(element, eventKey) {
    var inputArea = element.parent().siblings('.multiselect-input');
    var value = inputArea.val();

    if (eventKey === 'Backspace' && value.length > 0) {
      value = value.substring(0, value.length - 1)
    } else if (eventKey !== 'Backspace') {
      value = value + eventKey;
    }

    inputArea.focus();
    inputArea.val(value);
  }

  //We want to focus only on the first visible item
  function jumpToList(element) {
    var menu = element.siblings("ul.multiselect-dropdown-menu").first();
    var visible = menu.children(':visible');

    if (visible.length) {
      visible.first().focus();
    }
  }

  //Removes the given selected element
  function removeFromSelected(element) {
    var dataTarget = element.attr('data-target');
    var originalSelect = element.parent().siblings('select');
    var parent = element.parent();
    originalSelect.find("option" + "[value='" + dataTarget + "']").removeAttr('selected');
    parent.siblings('.multiselect-dropdown-menu')
      .find("li" + "[data-target='" + dataTarget + "']").removeClass('selected');

    element.siblings('.removed-selection').focus();
    element.remove();
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
      element.find('.multiselect-input').attr('aria-expanded', 'false');
      element.find('.multiselect-dropdown-menu').hide();
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