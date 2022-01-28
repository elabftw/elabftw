/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import tinymce from 'tinymce/tinymce';
import { Editor } from 'tinymce/tinymce';
import { DateTime } from 'luxon';
import 'tinymce/icons/default';
import 'tinymce/plugins/advlist';
import 'tinymce/plugins/anchor';
import 'tinymce/plugins/autosave';
import 'tinymce/plugins/charmap';
import 'tinymce/plugins/code';
import 'tinymce/plugins/codesample';
import 'tinymce/plugins/fullscreen';
import 'tinymce/plugins/hr';
import 'tinymce/plugins/image';
import 'tinymce/plugins/imagetools';
import 'tinymce/plugins/insertdatetime';
import 'tinymce/plugins/link';
import 'tinymce/plugins/lists';
import 'tinymce/plugins/pagebreak';
import 'tinymce/plugins/paste';
import 'tinymce/plugins/save';
import 'tinymce/plugins/searchreplace';
import 'tinymce/plugins/table';
import 'tinymce/plugins/template';
import 'tinymce/themes/silver';
import 'tinymce/themes/mobile';
import '../js/tinymce-langs/ca_ES.js';
import '../js/tinymce-langs/de_DE.js';
import '../js/tinymce-langs/en_GB.js';
import '../js/tinymce-langs/es_ES.js';
import '../js/tinymce-langs/fr_FR.js';
import '../js/tinymce-langs/id_ID.js';
import '../js/tinymce-langs/it_IT.js';
import '../js/tinymce-langs/ja_JP.js';
import '../js/tinymce-langs/ko_KR.js';
import '../js/tinymce-langs/nl_BE.js';
import '../js/tinymce-langs/pl_PL.js';
import '../js/tinymce-langs/pt_BR.js';
import '../js/tinymce-langs/pt_PT.js';
import '../js/tinymce-langs/ru_RU.js';
import '../js/tinymce-langs/sk_SK.js';
import '../js/tinymce-langs/sl_SI.js';
import '../js/tinymce-langs/zh_CN.js';
import '../js/tinymce-plugins/mention/plugin.js';
import EntityClass from './Entity.class';
import Link from './Link.class';
import { Target } from './interfaces';
import { getEntity } from './misc';

// AUTOSAVE
const doneTypingInterval = 7000;  // time in ms between end of typing and save

// called when you click the save button of tinymce
export function quickSave(): void {
  const entity = getEntity();
  const EntityC = new EntityClass(entity.type);
  EntityC.update(entity.id, Target.Body, tinymce.activeEditor.getContent()).catch(() => {
    // detect if the session timedout (Session expired error is thrown)
    // store the modifications in local storage to prevent any data loss
    localStorage.setItem('body', tinymce.activeEditor.getContent());
    localStorage.setItem('id', String(entity.id));
    localStorage.setItem('type', entity.type);
    localStorage.setItem('date', new Date().toLocaleString());
    // reload the page so user gets redirected to the login page
    location.reload();
    return;
  });
}

function getNow(): DateTime {
  const locale = document.getElementById('user-prefs').dataset.jslang;
  return DateTime.now().setLocale(locale);
}

function getDatetime(): string {
  const useIso = document.getElementById('user-prefs').dataset.isodate;
  if (useIso === '1') {
    const fullDatetime = getNow().toISO({ includeOffset: false });
    // now we remove the milliseconds from that string
    // 2021-04-23T18:57:28.633  ->  2021-04-23T18:57:28
    return fullDatetime.slice(0, -4);
  }
  return getNow().toLocaleString(DateTime.DATETIME_MED_WITH_WEEKDAY);
}

// ctrl-shift-D will add the date in the tinymce editor
function addDatetimeOnCursor(): void {
  tinymce.activeEditor.execCommand('mceInsertContent', false, `${getDatetime()} `);
}

function isOverCharLimit(): boolean {
  const body = tinymce.get(0).getBody();
  const text = tinymce.trim(body.innerText || body.textContent);
  return text.length > 1000000;
}

// user finished typing, save work
function doneTyping(): void {
  if (isOverCharLimit()) {
    alert('Too many characters!!! Cannot save properly!!!');
    return;
  }
  quickSave();
}

// options for tinymce to pass to tinymce.init()
export function getTinymceBaseConfig(page: string): object {
  let plugins = 'anchor table searchreplace code fullscreen insertdatetime paste charmap lists advlist save image imagetools link pagebreak mention codesample hr template';
  if (page !== 'admin') {
    plugins += ' autosave';
  }
  const entity = getEntity();

  return {
    selector: '.mceditable',
    browser_spellcheck: true,
    skin_url: 'app/css/tinymce',
    plugins: plugins,
    pagebreak_separator: '<div class="page-break"></div>',
    toolbar1: 'undo redo | styleselect fontsizeselect bold italic underline | alignleft aligncenter alignright alignjustify | superscript subscript | bullist numlist outdent indent | forecolor backcolor | charmap adddate | codesample | link | save',
    removed_menuitems: 'newdocument, image',
    image_caption: true,
    images_reuse_filename: true,
    contextmenu: false,
    paste_data_images: Boolean(page === 'edit'),
    content_style: '.mce-content-body {font-size:10pt;}',
    codesample_languages: [
      {text: 'Bash', value: 'bash'},
      {text: 'C', value: 'c'},
      {text: 'C++', value: 'cpp'},
      {text: 'CSS', value: 'css'},
      {text: 'Fortran', value: 'fortran'},
      {text: 'Go', value: 'go'},
      {text: 'Java', value: 'java'},
      {text: 'JavaScript', value: 'javascript'},
      {text: 'Julia', value: 'julia'},
      {text: 'Latex', value: 'latex'},
      {text: 'Lua', value: 'lua'},
      {text: 'Makefile', value: 'makefile'},
      {text: 'Matlab', value: 'matlab'},
      {text: 'Perl', value: 'perl'},
      {text: 'Python', value: 'python'},
      {text: 'R', value: 'r'},
      {text: 'Ruby', value: 'ruby'},
    ],
    language: $('#user-prefs').data('lang'),
    charmap_append: [
      [0x2640, 'female sign'],
      [0x2642, 'male sign'],
    ],
    height: '500',
    mentions: {
      // use # for autocompletion
      delimiter: '#',
      // get the source from json with get request
      source: function(query: string, process: (data) => void): void {
        const url = 'app/controllers/EntityAjaxController.php';
        $.getJSON(url, {
          mention: 1,
          term: query,
          type: entity.type,
        }).done(function(data) {
          process(data);
        });
      },
      insert: function(data): string {
        if (data.type === 'items') {
          const LinkC = new Link(entity);
          LinkC.create(parseInt(data.id)).then((json) => {
            if (json.res === true) {
              // only reload children of links_div_id
              $('#links_div_' + entity.id).load(window.location.href + ' #links_div_' + entity.id + ' > *');
            }
          });
        }
        return `<span><a href='${data.page}.php?mode=view&id=${data.id}'>${data.name}</a></span>`;
      },
    },
    mobile: {
      theme: 'mobile',
      plugins: [ 'save', 'lists', 'link' ],
      toolbar: [ 'undo', 'redo', 'bold', 'italic', 'underline', 'bullist', 'numlist', 'link' ],
    },
    // keyboard shortcut to insert today's date at cursor in editor
    setup: (editor: Editor): void => {
      // holds the timer setTimeout function
      let typingTimer;
      // make the edges round
      editor.on('init', () => editor.getContainer().className += ' rounded');
      // add date+time button
      editor.ui.registry.addButton('adddate', {
        icon: 'insert-time',
        tooltip: 'Insert timestamp',
        onAction: function() {
          editor.insertContent(`${getDatetime()} `);
        },
      });
      // some shortcuts
      editor.addShortcut('ctrl+shift+d', 'add date/time at cursor', addDatetimeOnCursor);
      editor.addShortcut('ctrl+=', 'subscript', () => editor.execCommand('subscript'));
      editor.addShortcut('ctrl+shift+=', 'superscript', () => editor.execCommand('superscript'));

      // on edit page there is an autosave triggered
      if (page === 'edit' || page === 'ucp') {
        editor.on('keydown', () => clearTimeout(typingTimer));
        editor.on('keyup', () => {
          clearTimeout(typingTimer);
          typingTimer = setTimeout(doneTyping, doneTypingInterval);
        });
      }
    },
    style_formats_merge: true,
    style_formats: [
      {
        title: 'Image Left',
        selector: 'img',
        styles: {
          'float': 'left',
          'margin': '0 10px 0 10px',
        },
      }, {
        title: 'Image Right',
        selector: 'img',
        styles: {
          'float': 'right',
          'margin': '0 0 10px 10px',
        },
      },
    ],
  };
}
