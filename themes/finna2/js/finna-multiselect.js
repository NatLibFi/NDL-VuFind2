/*global finna, VuFind*/
finna.multiSelect = (function multiSelect(){
  var listItem = '<li class="checkboxFilter" data-inner="" data-target="" tabindex="-1"><input tabindex="-1" type="checkbox" class="checkbox"></li>';
  var selectedItem = '<button class="multiselect-selection" data-target="" type="button" title=""></button>';

  function initFields() {
    $('.finna-multiselect').each(function createDropdowns(){
      var tempElement = $(this).closest('.multiselect-dropdown');
      var inputLabel = $(this).data('label');
      var completedAria = tempElement.find('.multiselect-input').attr('aria-label') + " " + inputLabel;

      tempElement.find('label').html(inputLabel);
      tempElement.find('.multiselect-input').attr('aria-label', completedAria);
      $(this).css('display', 'none');
      initListItems($(this), $(tempElement.find('ul')));
    });
    initKeyBindings();
  }

  function initKeyBindings() {
    $('.multiselect-input').on("keydown", function preventSubmit(e){
      if (e.which === 13) {
        e.preventDefault();
        //Do not submit form on enter press
      }
    });

    $('.multiselect-dropdown .caret').on('click', function toggleList(){
      $(this).siblings('.multiselect-input').focus();
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
        onListElementClick($(this));
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
      onListElementClick($(this));
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
    case 37:
    case 39:
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

  function onListElementClick(element) {
    if (!element.hasClass('selected')) {
      addToFilters(element);
    } else {
      removeFromListClick(element);
    }
  }

  function nextItem(element) {
    var nextAvailable = $(element).nextAll(':visible');

    if (nextAvailable.length) {
      nextAvailable.first().focus();
    }
  }

  function previousItem(element) {
    var previousAvailable = $(element).prevAll(':visible');

    if (previousAvailable.length) {
      previousAvailable.first().focus();
    } else {
      element.closest('.multiselect-dropdown').find('.multiselect-input').focus();
    }
  }

  function continueWriting(element, eventKey) {
    var inputArea = element.closest('.multiselect-dropdown').find('.multiselect-input');
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
    var children = element.siblings("ul.multiselect-dropdown-menu").first().children('li');

    if (children.length) {
      children.first().focus();
    }
  }

  function removeFromListClick(element) {
    var dataTarget = element.attr('data-target');
    var parent = element.parent();
    var root = element.closest('.multiselect-dropdown');
    var amount = parent.siblings('.multiselect-selected').children('button').length;

    parent.siblings('select').find("option" + "[data-id='" + dataTarget + "']").removeAttr('selected');
    parent.siblings('.multiselect-selected').find("button" + "[data-target='" + dataTarget + "']").remove();
    element.removeClass('selected');
    element.children('.checkbox').prop('checked', false);
    setNoneSelected(root, amount - 1);
  }

  //Removes the given selected element
  function removeFromSelected(element) {
    var dataTarget = element.attr('data-target');
    var originalSelect = element.parent().siblings('select');
    var liElement = element.parent().siblings('.multiselect-dropdown-menu')
      .find("li" + "[data-target='" + dataTarget + "']");

    originalSelect.find("option" + "[data-id='" + dataTarget + "']").removeAttr('selected');
    liElement.removeClass('selected');
    liElement.children('.checkbox').prop('checked', false);
    element.siblings('.removed-selection').focus();
    setNoneSelected(element.closest('.multiselect-dropdown').find('.multiselect-selected'), );
  }

  function setNoneSelected(element, amount) {
    if (amount === 0) {
      element.find('.removed-header').show();
    } else {
      
    }
  }

  function addToFilters(element) {
    if (element.hasClass('selected')) {
      return;
    }

    //var tempButton = $(selectedItem).clone();
    var selectedArea = element.parent().siblings('.multiselect-selected');
    var originalSelect = element.parent().siblings('select');
    var dataTarget = element.attr('data-target');
    var root = element.closest('.multiselect-dropdown');
    var amount = selectedArea.children('button').length;

    element.addClass('selected');
    element.children('.checkbox').prop('checked', true);
    originalSelect.find("option[data-id=" + dataTarget + "]").attr('selected', true);
    setNoneSelected(root, amount + 1);
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
        var innerHtml = $(this).attr('data-inner');

        if (innerHtml.indexOf(value) < 0) {
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
    var i = 0;
    select.children('option').each(function createList(){
      var listCopy = $(listItem).clone();
      listCopy.append($(this).html());
      $(this).attr('data-id', i);
      listCopy.attr('data-target', i++);
      listCopy.attr('data-inner', $(this).html().toLowerCase());
      ul.append(listCopy);
      var attr = $(this).attr('selected');

      if (typeof attr !== "undefined" && attr !== false) {
        listCopy.find('.checkbox').attr('checked', true);
        addToFilters(listCopy);
      }
    });
  }

  var my = {
    init: initFields
  };

  return my;
})();
