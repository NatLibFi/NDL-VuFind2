/* global VuFind, finna, EasyMDE */

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

  this.element.on('click.finnaEditable', { editable: this }, function onClickFinnaEditable(event) {
    event.stopPropagation();
    if (event.target.nodeName === 'A') {
      // Do not open the editor when a link within the editable area was clicked.
      return;
    }
    event.data.editable.openEditable();
  });

  this.element.addClass('inited');
}

FinnaMdEditable.prototype.eventOpenEditable = 'finna:openEditable';
FinnaMdEditable.prototype.eventEditableClosed = 'finna:editableClosed';

FinnaMdEditable.prototype.busyClass = 'finna-editable-busy';
FinnaMdEditable.prototype.openClass = 'finna-editable-open';

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
    if (!busy) {
      this.element.removeClass(this.busyClass);
    }
  }
  else if (busy) {
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

  // Hide container and insert textarea for editor.
  this.container.hide();
  var textArea = $('<textarea/>');
  var currentVal = this.container.data('markdown');
  textArea.text(currentVal);
  textArea.insertAfter(this.container);

  var editable = this;

  // Create editor.
  var toolbar = [
    {
      name: 'bold',
      action: EasyMDE.toggleBold,
      className: 'fa fa-bold',
      title: VuFind.translate('editor_format_bold')
    },
    {
      name: 'italic',
      action: EasyMDE.toggleItalic,
      className: 'fa fa-italic',
      title: VuFind.translate('editor_format_italic')
    },
    {
      name: 'heading',
      action: EasyMDE.toggleHeadingSmaller,
      className: 'fa fa-header fa-heading',
      title: VuFind.translate('editor_format_heading')
    },
    '|',
    {
      name: 'quote',
      action: EasyMDE.toggleBlockquote,
      className: 'fa fa-quote-left',
      title: VuFind.translate('editor_format_quote')
    },
    {
      name: 'unordered-list',
      action: EasyMDE.toggleUnorderedList,
      className: 'fa fa-list-ul',
      title: VuFind.translate('editor_format_unordered_list')
    },
    {
      name: 'ordered-list',
      action: EasyMDE.toggleOrderedList,
      className: 'fa fa-list-ol',
      title: VuFind.translate('editor_format_ordered_list')
    },
    '|',
    {
      name: 'link',
      action: EasyMDE.drawLink,
      className: 'fa fa-link',
      title: VuFind.translate('editor_create_link')
    },
    {
      name: 'image',
      action: EasyMDE.drawImage,
      className: 'fa fa-image',
      title: VuFind.translate('editor_insert_image')
    },
    '|',
    {
      name: 'other',
      className: 'fa fa-plus-small',
      title: VuFind.translate('editor_other_commands'),
      children: [
        {
          name: 'panel',
          action: function toolbarPanelAction() {
            editable._insertPanel();
          },
          className: 'fa details-icon',
          title: VuFind.translate('editor_insert_panel')
        },
        {
          name: 'truncate',
          action: function toolbarTruncateAction() {
            editable._insertTruncate();
          },
          className: 'fa fa-pagebreak',
          title: VuFind.translate('editor_insert_truncate')
        }
      ]
    },
    {
      name: 'close',
      action: function toolbarCloseAction() {
        editable.closeEditable();
      },
      className: 'fa fa-times editor-toolbar-close',
      title: VuFind.translate('editor_close_editor')
    }
  ];
  var promptTexts = {
    link: VuFind.translate('editor_prompt_link'),
    image: VuFind.translate('editor_prompt_image')
  };
  var settings = {
    autoDownloadFontAwesome: false,
    autofocus: true,
    element: textArea[0],
    indentWithTabs: false,
    promptTexts: promptTexts,
    promptURLs: true,
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
      var result = editable.editor.options.previewRender(editable.editor.value());
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

FinnaMdEditable.prototype._insertElement = function _insertElement(element, cursorLineOffset, cursorCh) {
  var doc = this.editor.codemirror.getDoc();
  doc.replaceRange(element, doc.getCursor());
  this.editor.codemirror.focus();
  var cursor = doc.getCursor();
  cursor.line = cursor.line + cursorLineOffset;
  cursor.ch = cursorCh;
  doc.setCursor(cursor);
};

FinnaMdEditable.prototype._insertPanel = function _insertPanel() {
  var headingPlaceholder = VuFind.translate('details_summary_placeholder');
  var panelElement = '\n<finna-panel>\n'
    + '  <span slot="heading">' + headingPlaceholder + '</span>\n\n'
    + '  ' + VuFind.translate('details_text_placeholder') + '\n'
    + '</finna-panel>\n';
  this._insertElement(panelElement, -4, 23 + headingPlaceholder.length);
};

FinnaMdEditable.prototype._insertTruncate = function _insertTruncate() {
  var labelPlaceholder = VuFind.translate('details_summary_placeholder');
  var truncateElement = '\n<finna-truncate>\n'
    + '  <span slot="label">' + labelPlaceholder + '</span>\n\n'
    + '  ' + VuFind.translate('details_text_placeholder') + '\n'
    + '</finna-truncate>\n';
  this._insertElement(truncateElement, -4, 21 + labelPlaceholder.length);
};

finna.mdEditable = (function finnaMdEditable() {
  var editables = [];

  var my = {
    editables: editables,
    init: function init() {
      $('.finna-md-editable:not(.inited)').each(function initFinnaMdEditable() {
        editables.push(new FinnaMdEditable($(this)));
      });
    }
  };

  return my;
})();
