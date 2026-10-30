class AutoComplete {
  editor;
  options = {};
  query = '';
  hasFocus = true;
  // joiner should be an invisible character and functions as glue between delimiter and search text typed by user
  // compare to https://stackoverflow.com/a/28405917 for potential characters
  // e.g. \uFEFF, \u2007, \u202F, \u2060, \u200B
  // \uFEFF can not be used any longer as of tinymce version 5.10.9 - 2023-11-14 and 6.7.3 - 2023-11-15
  joiner = '\u2060';
  searchTimeout;
  $dropdown;

  // fields to hold eventCallbackfunctions
  editorKeyUpProxy;
  editorKeyDownProxy;
  editorClickProxy;
  bodyClickProxy;
  editorScroll;

  constructor(ed, options) {
    this.editor = ed;

    this.options = $.extend({}, {
      source: [],
      delay: 500,
      queryBy: 'title',
      items: 10,
    }, options);

    this.matcher = this.options.matcher || this.matcher;
    this.renderDropdown = this.options.renderDropdown || this.renderDropdown;
    this.render = this.options.render || this.render;
    this.insert = this.options.insert || this.insert;
    this.highlighter = this.options.highlighter || this.highlighter;

    this.renderInput();
    this.bindEvents();
  }

  renderInput() {
    // for some reason the id attribute of the first span gets removed during insert, so we use a data attribute instead
    // don't add any additional characters that would be part of rawHtml.innerText unless it is reflected in the lookup method
    const rawHtml = '<span id="data-tiny-complete">'
        + `<span id="autocomplete-delimiter">${this.options.delimiter}</span>`
        + `<span id="data-tiny-complete-searchtext"><span class="dummy">${this.joiner}</span></span>`
        + '</span>';
    this.editor.execCommand('mceInsertContent', false, rawHtml);
    this.editor.focus();
    this.editor.selection.select(this.editor.selection.dom.select('#data-tiny-complete-searchtext span')[0]);
    this.editor.selection.collapse(0);
  }

  rteScroll() {
    this.cleanUp(true);
  }

  bindEvents() {
    this.editor.on('keyup', this.editorKeyUpProxy = this.rteKeyUp.bind(this));
    this.editor.on('keydown', this.editorKeyDownProxy = this.rteKeyDown.bind(this), true);
    this.editor.on('click', this.editorClickProxy = this.rteClicked.bind(this));

    $('body').on('click', this.bodyClickProxy = this.rteLostFocus.bind(this));

    $(this.editor.getWin()).on('scroll', this.editorScroll = this.rteScroll.bind(this));
  }

  unbindEvents() {
    this.editor.off('keyup', this.editorKeyUpProxy);
    this.editor.off('keydown', this.editorKeyDownProxy);
    this.editor.off('click', this.editorClickProxy);

    $('body').off('click', this.bodyClickProxy);

    $(this.editor.getWin()).off('scroll', this.editorScroll);
  }

  rteKeyUp(e) {
    switch (e.which || e.keyCode) {
    case 40: // DOWN ARROW
    case 38: // UP ARROW
    case 16: // SHIFT
    case 17: // CTRL
    case 18: // ALT
      break;

    case 8:  // BACKSPACE
      if (this.query === '') {
        this.cleanUp(true);
      } else {
        this.lookup();
      }
      break;

    case 9:  // TAB
    case 13: // ENTER
      var item = (this.$dropdown !== undefined) ? this.$dropdown.find('li.active') : [];
      if (item.length) {
        this.select(item.data());
        this.cleanUp(false);
      } else {
        this.cleanUp(true);
      }
      break;

    case 27: // ESC
      this.cleanUp(true);
      break;

    default:
      this.lookup();
    }
  }

  rteKeyDown(e) {
    switch (e.which || e.keyCode) {
    case 9:  // TAB
    case 13: // ENTER
    case 27: // ESC
      e.preventDefault();
      break;

    case 38: // UP ARROW
      e.preventDefault();
      if (this.$dropdown !== undefined) {
        this.highlightPreviousResult();
      }
      break;

    case 40: //DOWN ARROW
      e.preventDefault();
      if (this.$dropdown !== undefined) {
        this.highlightNextResult();
      }
      break;
    }

    e.stopPropagation();
  }

  rteClicked(e) {
    const $target = $(e.target);

    if (this.hasFocus && $target.parent().attr('id') !== 'autocomplete-searchtext') {
      this.cleanUp(true);
    }
  }

  rteLostFocus() {
    if (this.hasFocus) {
      this.cleanUp(true);
    }
  }

  lookup() {
    // the text to be replaced has to match exactly what would be the result of rawHtml.innerText of the renderInput method
    this.query = this.editor.getBody().querySelector('#data-tiny-complete-searchtext').innerText.trim().replace(this.joiner, '');

    if (this.$dropdown === undefined) {
      this.show();
    }

    clearTimeout(this.searchTimeout);
    this.searchTimeout = setTimeout(() => {
      // Added delimiter parameter as last argument for backwards compatibility.
      const items = typeof this.options.source === 'function'
        ? this.options.source(this.query, this.process.bind(this), this.options.delimiter)
        : this.options.source;
      if (items) {
        this.process(items);
      }
    }, this.options.delay);
  }

  matcher(item) {
    const q = this.query.trim()
    const terms = q.toLowerCase().split(/\s+/)
    const matchesText = terms.every(word =>
      item[this.options.queryBy]
        .toLowerCase()
        .includes(word)
    )

    const isPositiveInt = /^[1-9]\d*$/.test(q)
    const n = isPositiveInt ? parseInt(q, 10) : null
    const matchesId = isPositiveInt && (n === item.custom_id || n === item.id)

    return matchesText || matchesId
  }

  sorter(items) {
    const customId = [];
    const beginswith = [];
    const caseSensitive = [];
    const caseInsensitive = [];
    let item;

    while ((item = items.shift()) !== undefined) {
      if (parseInt(this.query, 10) === item.custom_id) {
        customId.push(item);
      } else if (!item[this.options.queryBy].toLowerCase().indexOf(this.query.toLowerCase())) {
        beginswith.push(item);
      } else if (~item[this.options.queryBy].indexOf(this.query)) {
        caseSensitive.push(item);
      } else {
        caseInsensitive.push(item);
      }
    }

    return customId.concat(beginswith, caseSensitive, caseInsensitive);
  }

  highlighter(text) {
    const fragment = document.createDocumentFragment();
    let offset = 0;
    if (this.query.trim() && this.query.length > 0) {
      const re = new RegExp(
        this.query.split(' ')
          // need to sort words by length (desc) so that longer words will be highlighted
          // even if there is a substring of it in the query e.g. elabftw vs lab
          .sort((word1, word2) => word2.length - word1.length)
          // escape regex special chars
          .map(word => word.replace(/([.?*+^$[\]\\(){}|-])/g, '\\$1'))
          // remove empty strings
          .filter(word => word)
          .join('|'),
        'ig',
      );
      let result;
      while ((result = re.exec(text)) !== null) {
        fragment.append(document.createTextNode(text.substring(offset, result.index)));
        const strong = document.createElement('strong');
        strong.textContent = result[0];
        fragment.append(strong);
        offset = re.lastIndex;
      }
    }
    fragment.append(document.createTextNode(text.substring(offset)));
    return fragment;
  }

  show() {
    this.$dropdown = $(this.renderDropdown());
    $('body').append(this.$dropdown);

    this.positionDropdown();
    this.$dropdown.on('click', this.autoCompleteClick.bind(this));
  }

  process(data) {
    if (!this.hasFocus) {
      return;
    }

    const result = [];
    const items = this.sorter(data.filter(item => this.matcher(item))).slice(0, this.options.items);

    for (const item of items) {
      const $element = $(this.render(item));
      $element.find('a').prepend(this.addCustomId(item));
      $element.find('a').prepend(this.addCategory(item));
      // final list entry will be: {category} - {custom id} {title}

      // Keep raw API values in memory instead of serializing them as HTML attributes.
      $element.data(item);
      result.push($element[0]);
    }

    if (result.length) {
      this.$dropdown.empty().append(result).show();
      this.positionDropdown();
    } else {
      this.$dropdown.hide();
    }
  }

  renderDropdown() {
    return '<ul class="rte-autocomplete dropdown-menu"><li class="lds-dual-ring"></li></ul>';
  }

  render(item) {
    const $element = $('<li><a href="#" class="dropdown-item"><span></span></a></li>');
    $element.find('span').append(this.highlighter(item[this.options.queryBy]));
    return $element;
  }

  addCustomId(item) {
    const fragment = document.createDocumentFragment();
    if (item.custom_id) {
      if (item.custom_id === parseInt(this.query, 10)) {
        const strong = document.createElement('strong');
        strong.textContent = item.custom_id;
        fragment.append(strong, ' ');
      } else {
        fragment.append(document.createTextNode(`${item.custom_id} `));
      }
    }
    return fragment;
  }

  addCategory(item) {
    const fragment = document.createDocumentFragment();
    if (item.category_title) {
      const span = document.createElement('span');
      span.style.color = `#${item.category_color}`;
      span.textContent = item.category_title;
      fragment.append(span, ' - ');
    }
    return fragment;
  }

  autoCompleteClick(e) {
    const item = $(e.target).closest('li').data();
    if (!$.isEmptyObject(item)) {
      this.select(item);
      this.cleanUp(false);
    }
    e.stopPropagation();
    e.preventDefault();
  }

  highlightPreviousResult() {
    let currentIndex = this.$dropdown.find('li.active').index();
    const index = (currentIndex === 0) ? this.$dropdown.find('li').length - 1 : --currentIndex;

    this.$dropdown.find('li').removeClass('active').eq(index).addClass('active');
  }

  highlightNextResult() {
    let currentIndex = this.$dropdown.find('li.active').index();
    const index = (currentIndex === this.$dropdown.find('li').length - 1) ? 0 : ++currentIndex;

    this.$dropdown.find('li').removeClass('active').eq(index).addClass('active');
  }

  select(item) {
    this.editor.focus();
    const selection = this.editor.dom.select('#data-tiny-complete')[0];
    this.editor.dom.remove(selection);
    this.editor.insertContent(this.insert(item));
  }

  // Note: not used, overridden in options
  insert(item) {
    const span = document.createElement('span');
    span.textContent = item.category + ' ' + item[this.options.queryBy];
    return span.outerHTML + '&nbsp;';
  }

  cleanUp(rollback) {
    this.unbindEvents();
    this.hasFocus = false;

    if (this.$dropdown !== undefined) {
      this.$dropdown.remove();
      delete this.$dropdown;
    }

    if (rollback) {
      const text = this.query;
      const $selection = $(this.editor.dom.select('#data-tiny-complete'));
      const replacement = this.editor.getDoc().createTextNode(this.options.delimiter + text);
      const focus = $(this.editor.selection.getNode()).offset()?.top === ($selection.offset().top + (($selection.outerHeight() - $selection.height()) / 2));

      this.editor.dom.replace(replacement, $selection[0]);

      if (focus) {
        this.editor.selection.select(replacement);
        this.editor.selection.collapse();
      }
    }
  }

  positionDropdown() {
    const offset = this.editor.inline ? this.offsetInline() : this.offset();
    const viewportMargin = 8;
    const viewportLeft = window.scrollX;
    const viewportRight = viewportLeft + document.documentElement.clientWidth;
    const dropdownWidth = this.$dropdown.outerWidth() ?? 0;
    const left = Math.max(
      viewportLeft + viewportMargin,
      Math.min(offset.left, viewportRight - dropdownWidth - viewportMargin),
    );
    const arrowLeft = Math.max(
      9,
      Math.min(dropdownWidth - 21, offset.left - left + 9),
    );

    this.$dropdown.css({
      top: offset.top,
      left: left,
      '--rte-autocomplete-arrow-left': `${arrowLeft}px`,
    });
  }

  offset() {
    const contentAreaRect = this.editor.getContentAreaContainer().getBoundingClientRect();
    const nodeRect = this.editor.dom.select('#data-tiny-complete')[0].getBoundingClientRect();

    return {
      top: window.scrollY + contentAreaRect.top + nodeRect.bottom + 5,
      left: window.scrollX + contentAreaRect.left + nodeRect.left,
    };
  }

  offsetInline() {
    const nodeRect = this.editor.dom.select('#data-tiny-complete')[0].getBoundingClientRect();

    return {
      top: window.scrollY + nodeRect.bottom + 5,
      left: window.scrollX + nodeRect.left,
    };
  }
}

tinymce.PluginManager.add('mention', ed => {

  // getParam() is deprecated, need to use ed.options.get but need to register first
  // https://www.tiny.cloud/docs/tinymce/6/apis/tinymce.editoroptions/
  let autoComplete;
  const autoCompleteData = ed.getParam('mentions');

  // If the delimiter is undefined set default value to ['@'].
  autoCompleteData.delimiter = (autoCompleteData.delimiter !== undefined) ? autoCompleteData.delimiter : ['@'];

  const prevCharIsSpace = () => {
    const start = ed.selection.getRng(true).startOffset;
    const text = ed.selection.getRng(true).startContainer.data || '';
    const character = text.substr(start - 1, 1);

    return character.trim().length ? false : true;
  };

  ed.on('keypress', e => {
    if (autoCompleteData.delimiter.includes(e.key) && prevCharIsSpace()) {
      if (autoComplete === undefined || (autoComplete.hasFocus !== undefined && !autoComplete.hasFocus)) {
        e.preventDefault();
        // Clone options object and set the used delimiter.
        autoComplete = new AutoComplete(ed, $.extend({}, autoCompleteData, { delimiter: autoCompleteData.delimiter[0] }));
      }
    }
  });

  return {
    name: 'mention',
    url: 'https://doc.elabftw.net',
  };
});
