class AutoComplete {

    constructor(ed, options) {
      this.editor = ed;

      this.options = $.extend({}, {
          source: [],
          delay: 500,
          queryBy: 'title',
          items: 10
      }, options);

      this.matcher = this.options.matcher || this.matcher;
      this.renderDropdown = this.options.renderDropdown || this.renderDropdown;
      this.render = this.options.render || this.render;
      this.insert = this.options.insert || this.insert;
      this.highlighter = this.options.highlighter || this.highlighter;

      this.query = '';
      this.hasFocus = true;
      // joiner should be an invisible character and functions as glue between delimiter and search text typed by user
      // compare to https://stackoverflow.com/a/28405917 for potential characters
      // e.g. \uFEF, \u2007, \u202F, \u2060, \u200B
      // \uFEFF can not be used any longer as of tinymce version 5.10.9 - 2023-11-14 and 6.7.3 - 2023-11-15
      this.joiner = '\u2060';

      this.renderInput();

      this.bindEvents();
    }

    renderInput() {
        // for some reason the id attribute of the first span gets removed during insert, so we use a data attribute instead
        // don't add any additional characters that would be part of rawHtml.innerText unless it is reflected in the lookup methode
        const rawHtml = '<span data-tiny-complete="1">'
            + `<span id="autocomplete-delimiter">${this.options.delimiter}</span>`
            + `<span data-tiny-complete-searchtext="1"><span class="dummy">${this.joiner}</span></span>`
            + '</span>';
        this.editor.execCommand('mceInsertContent', false, rawHtml);
        this.editor.focus();
        this.editor.selection.select(this.editor.selection.dom.select('span[data-tiny-complete-searchtext="1"] span')[0]);
        this.editor.selection.collapse(0);
    }

    bindEvents() {
        this.editor.on('keyup', this.editorKeyUpProxy = $.proxy(this.rteKeyUp, this));
        this.editor.on('keydown', this.editorKeyDownProxy = $.proxy(this.rteKeyDown, this), true);
        this.editor.on('click', this.editorClickProxy = $.proxy(this.rteClicked, this));

        $('body').on('click', this.bodyClickProxy = $.proxy(this.rteLostFocus, this));

        $(this.editor.getWin()).on('scroll', this.rteScroll = $.proxy(function () { this.cleanUp(true); }, this));
    }

    unbindEvents() {
        this.editor.off('keyup', this.editorKeyUpProxy);
        this.editor.off('keydown', this.editorKeyDownProxy);
        this.editor.off('click', this.editorClickProxy);

        $('body').off('click', this.bodyClickProxy);

        $(this.editor.getWin()).off('scroll', this.rteScroll);
    }

    rteKeyUp(e) {
        switch (e.which || e.keyCode) {
        //DOWN ARROW
        case 40:
        //UP ARROW
        case 38:
        //SHIFT
        case 16:
        //CTRL
        case 17:
        //ALT
        case 18:
            break;

        //BACKSPACE
        case 8:
            if (this.query === '') {
                this.cleanUp(true);
            } else {
                this.lookup();
            }
            break;

        //TAB
        case 9:
        //ENTER
        case 13:
            var item = (this.$dropdown !== undefined) ? this.$dropdown.find('li.active') : [];
            if (item.length) {
                this.select(item.data());
                this.cleanUp(false);
            } else {
                this.cleanUp(true);
            }
            break;

        //ESC
        case 27:
            this.cleanUp(true);
            break;

        default:
            this.lookup();
        }
    }

    rteKeyDown(e) {
        switch (e.which || e.keyCode) {
         //TAB
        case 9:
        //ENTER
        case 13:
        //ESC
        case 27:
            e.preventDefault();
            break;

        //UP ARROW
        case 38:
            e.preventDefault();
            if (this.$dropdown !== undefined) {
                this.highlightPreviousResult();
            }
            break;
        //DOWN ARROW
        case 40:
            e.preventDefault();
            if (this.$dropdown !== undefined) {
                this.highlightNextResult();
            }
            break;
        }

        e.stopPropagation();
    }

    rteClicked(e) {
        var $target = $(e.target);

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
        // the text to be replaced has to match exactly what would be the result of rawHtml.innerText of the renderInput methode
        this.query = this.editor.getBody().querySelector('[data-tiny-complete="1"]').innerText.replace(`${this.options.delimiter}${this.joiner}`, '');

        if (this.$dropdown === undefined) {
            this.show();
        }

        clearTimeout(this.searchTimeout);
        this.searchTimeout = setTimeout($.proxy(function () {
            // Added delimiter parameter as last argument for backwards compatibility.
            var items = $.isFunction(this.options.source) ? this.options.source(this.query, $.proxy(this.process, this), this.options.delimiter) : this.options.source;
            if (items) {
                this.process(items);
            }
        }, this), this.options.delay);
    }

    matcher(item) {
        return ~item[this.options.queryBy].toLowerCase().indexOf(this.query.toLowerCase());
    }

    sorter(items) {
        var beginswith = [],
            caseSensitive = [],
            caseInsensitive = [],
            item;

        while ((item = items.shift()) !== undefined) {
            if (!item[this.options.queryBy].toLowerCase().indexOf(this.query.toLowerCase())) {
                beginswith.push(item);
            } else if (~item[this.options.queryBy].indexOf(this.query)) {
                caseSensitive.push(item);
            } else {
                caseInsensitive.push(item);
            }
        }

        return beginswith.concat(caseSensitive, caseInsensitive);
    }

    highlighter(text) {
        if (this.query.length > 0) {
            return text.replace(new RegExp('(' + this.query.replace(/([.?*+^$[\]\\(){}|-])/g, '\\$1') + ')', 'ig'), function (match) {
                return '<strong>' + match + '</strong>';
            });
        }
        return text;
    }

    show() {
        var offset = this.editor.inline ? this.offsetInline() : this.offset();

        this.$dropdown = $(this.renderDropdown())
            .css({
                'top': offset.top,
                'left': offset.left
            });

        $('body').append(this.$dropdown);

        this.$dropdown.on('click', $.proxy(this.autoCompleteClick, this));
    }

    process(data) {
        if (!this.hasFocus) {
            return;
        }

        var _this = this,
            result = [],
            items = $.grep(data, function (item) {
                return _this.matcher(item);
            });

        items = _this.sorter(items);

        items = items.slice(0, this.options.items);

        $.each(items, function (i, item) {
            var $element = $(_this.render(item));

            $element.html($element.html().replace($element.text(), _this.highlighter($element.text())));

            $.each(items[i], function (key, val) {
                $element.attr('data-' + key, val);
            });

            result.push($element[0].outerHTML);
        });

        if (result.length) {
            this.$dropdown.html(result.join('')).show();
        } else {
            this.$dropdown.hide();
        }
    }

    renderDropdown() {
        return '<ul class="rte-autocomplete dropdown-menu"><li class="loading"></li></ul>';
    }

    render(item) {
        let category = '';
        if (item.category_title) {
          category = `${item.category_title} - `;
        }
        return `<li><a href="javascript:;"><span>${category}${item[this.options.queryBy]}</span></a></li>`;
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
        var currentIndex = this.$dropdown.find('li.active').index(),
            index = (currentIndex === 0) ? this.$dropdown.find('li').length - 1 : --currentIndex;

        this.$dropdown.find('li').removeClass('active').eq(index).addClass('active');
    }

    highlightNextResult() {
        var currentIndex = this.$dropdown.find('li.active').index(),
            index = (currentIndex === this.$dropdown.find('li').length - 1) ? 0 : ++currentIndex;

        this.$dropdown.find('li').removeClass('active').eq(index).addClass('active');
    }

    select(item) {
        this.editor.focus();
        var selection = this.editor.dom.select('span[data-tiny-complete="1"]')[0];
        this.editor.dom.remove(selection);
        this.editor.insertContent(this.insert(item));
    }

    // Note: not used, overridden in options
    insert(item) {
        return '<span>' + item.category + ' ' + item[this.options.queryBy] + '</span>&nbsp;';
    }

    cleanUp(rollback) {
        this.unbindEvents();
        this.hasFocus = false;

        if (this.$dropdown !== undefined) {
            this.$dropdown.remove();
            delete this.$dropdown;
        }

        if (rollback) {
            var text = this.query,
                $selection = $(this.editor.dom.select('span[data-tiny-complete="1"]')),
                replacement = $('<p>' + this.options.delimiter + text + '</p>')[0].firstChild,
                focus = $(this.editor.selection.getNode()).offset()?.top === ($selection.offset().top + (($selection.outerHeight() - $selection.height()) / 2));

            this.editor.dom.replace(replacement, $selection[0]);

            if (focus) {
                this.editor.selection.select(replacement);
                this.editor.selection.collapse();
            }
        }
    }

    offset() {
        const contentAreaPosition = $(this.editor.getContentAreaContainer()).offset();
        const nodePosition = $(this.editor.dom.select('span[data-tiny-complete="1"]')).offset();

        return {
            top: contentAreaPosition.top + nodePosition.top + $(this.editor.selection.getNode()).innerHeight() - $(this.editor.getDoc()).scrollTop() + 5,
            left: contentAreaPosition.left + nodePosition.left
        };
    }

    offsetInline() {
        var nodePosition = $(this.editor.dom.select('span[data-tiny-complete="1"]')).offset();

        return {
            top: nodePosition.top + $(this.editor.selection.getNode()).innerHeight() + 5,
            left: nodePosition.left
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

    return (!!$.trim(character).length) ? false : true;
  }

  ed.on('keypress', function (e) {
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
