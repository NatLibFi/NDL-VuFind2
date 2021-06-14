/* global VuFind, finna, EasyMDE */

FinnaMdEditable.prototype.eventOpenEditable = 'finna:openEditable';
FinnaMdEditable.prototype.eventEditableClosed = 'finna:editableClosed';

FinnaMdEditable.prototype.busyClass = 'finna-editable-busy';
FinnaMdEditable.prototype.openClass = 'finna-editable-open';

/**
 * Finna Markdown editable.
 *
 * @param {jQuery} element
 * @constructor
 */
function FinnaMdEditable(element) {
  this.element = element;
  this.container = this.element.find('.finna-editable-container');
  this.preview = true === this.container.data('preview');
  this.emptyHtml = this.container.data('empty-html');
  this.editor = null;

  this.element.on('click.finnaEditable', { instance: this }, function onClickFinnaEditable(event) {
    event.stopPropagation();
    if (event.target.nodeName === 'A') {
      // Do not open the editor when a link within the editable area was clicked.
      return;
    }
    event.data.instance.openEditable();
  });
}

/**
 * Returns the open state of the editable.
 *
 * @returns {boolean}
 */
FinnaMdEditable.prototype.isOpen = function isOpen() {
  return this.element.hasClass(this.openClass);
};

/**
 * Returns the busy state of the editable.
 *
 * @returns {boolean}
 */
FinnaMdEditable.prototype.isBusy = function isBusy() {
  return this.element.hasClass(this.busyClass);
};

/**
 * Conditionally sets the busy state of the editable.
 *
 * An opened editable can not be set busy.
 *
 * @param {boolean} busy Busy state to set.
 *
 * @returns {FinnaMdEditable}
 */
FinnaMdEditable.prototype.setBusy = function setBusy(busy) {
  if (this.isOpen()) {
    return this;
  }
  if (this.isBusy()) {
    if (false === busy) {
      this.element.removeClass(this.busyClass);
    }
  }
  else if (true === busy) {
    this.element.addClass(this.busyClass);
  }
  return this;
};

/**
 * Conditionally opens the editable.
 *
 * A busy editable can not be opened.
 *
 * @returns {FinnaMdEditable}
 */
FinnaMdEditable.prototype.openEditable = function openEditable() {
  if (this.isOpen() || this.isBusy()) {
    return this;
  }
  var editableEvent = $.Event(this.eventOpenEditable, { editable: this });
  $(document).trigger(editableEvent);
  if (editableEvent.isDefaultPrevented()) {
    return this;
  }
  this.element.addClass(this.openClass);

  var instance = this;

  // Hide container and insert textarea for editor.
  this.container.hide();
  var textArea = $('<textarea/>');
  var currentVal = this.container.data('markdown');
  textArea.text(currentVal);
  textArea.insertAfter(this.container);

  // Create editor.
  var toolbar = [
    'bold', 'italic',
    'heading', '|',
    'quote', 'unordered-list',
    'ordered-list', '|',
    'link', 'image',
    '|',
    {
      name: "other",
      className: "fa fa-plus-small",
      title: "Other",
      children: [
        {
          name: 'panel',
          action: function toolbarPanelAction() {
            instance.insertPanel();
          },
          className: 'fa details-icon',
          title: 'Insert panel element'
        },
        {
          name: 'truncate',
          action: function toolbarTruncateAction() {
            instance.insertTruncate();
          },
          className: 'fa fa-pagebreak',
          title: 'Insert truncate element'
        }
      ]
    },
    {
      name: 'close',
      action: function toolbarCloseAction() {
        instance.closeEditable();
      },
      className: 'fa fa-times editor-toolbar-close',
      title: 'Close'
    }
  ];
  var settings = {
    autoDownloadFontAwesome: false,
    autofocus: true,
    element: textArea[0],
    indentWithTabs: false,
    toolbar: toolbar,
    spellChecker: false,
    status: false
  };
  this.editor = new EasyMDE(settings);

  this.element.find('.CodeMirror-code').focus();

  // Prevent clicks within the editor area from bubbling up.
  this.element.find('.EasyMDEContainer').unbind('click').click(function onClickEditor() {
    return false;
  });

  // Preview
  if (this.preview) {
    var html = this.editor.options.previewRender(this.editor.value());
    $('.markdown-preview').remove();
    var preview = $('<div/>').addClass('markdown-preview')
      .html($('<div/>').addClass('data').html(html));
    $('<div/>').addClass('preview').text(VuFind.translate('preview').toUpperCase()).prependTo(preview);
    preview.appendTo(this.element);

    this.editor.codemirror.on('change', function onChangeEditor() {
      var result = instance.editor.options.previewRender(instance.editor.value());
      preview.find('.data').html(result);
    });
  }

  return this;
};

/**
 * Closes the editable.
 *
 * A busy editable can not be opened.
 *
 * @returns {FinnaMdEditable}
 */
FinnaMdEditable.prototype.closeEditable = function closeEditable() {
  if (null !== this.editor) {
    var markdown = this.editor.value();
    var resultHtml = this.editor.options.previewRender(markdown);

    this.editor.toTextArea();
    this.editor = null;
    this.element.removeClass(this.openClass).find('textarea').remove();

    this.container.show();
    this.container.data('markdown', markdown);

    if (markdown.length === 0) {
      resultHtml = this.emptyHtml;
    }

    this.container.html(resultHtml);

    if (this.preview) {
      this.element.find('.markdown-preview').remove();
    }
  }

  var editableEvent = $.Event(this.eventEditableClosed, { editable: this });
  $(document).trigger(editableEvent);

  return this;
};

FinnaMdEditable.prototype.getEditorCursorPos = function getEditorCursorPos() {
  var doc = this.editor.codemirror.getDoc();
  var cursorPos = doc.getCursor();
  return {
    line: cursorPos.line,
    ch: cursorPos.ch
  };
};

FinnaMdEditable.prototype.insertElement = function insertElement(element) {
  var doc = this.editor.codemirror.getDoc();
  doc.replaceRange(element, this.getEditorCursorPos());
  this.editor.codemirror.focus();
};

FinnaMdEditable.prototype.insertPanel = function insertPanel() {
  var headingPlaceholder = VuFind.translate('details_summary_placeholder');
  var panelElement = '\n<finna-panel>\n'
    + '  <span slot="heading">' + headingPlaceholder + '</span>\n\n'
    + '  ' + VuFind.translate('details_text_placeholder') + '\n'
    + '</finna-panel>\n';
  this.insertElement(panelElement);
  var doc = this.editor.codemirror.getDoc();
  var cursorPos = this.getEditorCursorPos();
  doc.setCursor({line: cursorPos.line - 4, ch: 23 + headingPlaceholder.length});
};

FinnaMdEditable.prototype.insertTruncate = function insertTruncate() {
  var labelPlaceholder = VuFind.translate('details_summary_placeholder');
  var truncateElement = '\n<finna-truncate>\n'
    + '  <span slot="label">' + labelPlaceholder + '</span>\n\n'
    + '  ' + VuFind.translate('details_text_placeholder') + '\n'
    + '</finna-truncate>\n';
  this.insertElement(truncateElement);
  var doc = this.editor.codemirror.getDoc();
  var cursorPos = this.getEditorCursorPos();
  doc.setCursor({line: cursorPos.line - 4, ch: 21 + labelPlaceholder.length});
};

finna.mdEditable = (function finnaMdEditable() {
  var editables = [];

  var my = {
    editables: editables,
    init: function init() {
      $('.finna-md-editable').each(function initFinnaMdEditable() {
        editables.push(new FinnaMdEditable($(this)));
      });
    }
  };

  return my;
})();
