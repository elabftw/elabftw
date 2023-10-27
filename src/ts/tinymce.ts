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
import 'tinymce/models/dom';
import 'tinymce/icons/default';
import 'tinymce/themes/silver';
// Note about tinymce css stuff: this page https://www.tiny.cloud/docs/tinymce/6/webpack-es6-npm/ just doesn't work as advertised
// so it's easier to simply copy the css files in web/assets/skins instead.
import 'tinymce/plugins/accordion';
import 'tinymce/plugins/advlist';
import 'tinymce/plugins/anchor';
import 'tinymce/plugins/autolink';
import 'tinymce/plugins/autoresize';
import 'tinymce/plugins/autosave';
import 'tinymce/plugins/charmap';
import 'tinymce/plugins/code';
import 'tinymce/plugins/codesample';
import 'tinymce/plugins/fullscreen';
import 'tinymce/plugins/image';
import 'tinymce/plugins/insertdatetime';
import 'tinymce/plugins/link';
import 'tinymce/plugins/lists';
import 'tinymce/plugins/pagebreak';
import 'tinymce/plugins/save';
import 'tinymce/plugins/searchreplace';
import 'tinymce/plugins/table';
import 'tinymce/plugins/template';
import 'tinymce/plugins/visualblocks';
import 'tinymce/plugins/visualchars';
import '../js/tinymce-langs/ca_ES.js';
import '../js/tinymce-langs/de_DE.js';
import '../js/tinymce-langs/en_GB.js';
import '../js/tinymce-langs/en_US.js';
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
import { EntityType, Target } from './interfaces';
import { getEntity, reloadElement } from './misc';
import { Api } from './Apiv2.class';
import { isSortable } from './TableSorting.class';


const ApiC = new Api();
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
  }).then(() => {
    // remove dirty state of editor
    tinymce.activeEditor.setDirty(false);
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
  let plugins = 'accordion advlist anchor autolink autoresize table searchreplace code fullscreen insertdatetime charmap lists save image link pagebreak codesample template mention visualblocks visualchars';
  if (page !== 'admin') {
    plugins += ' autosave';
  }
  const entity = getEntity();

  return {
    selector: '.mceditable',
    browser_spellcheck: true,
    // make it load the skin.min.css and content.min.css from there
    skin_url: '/assets',
    // remove the "Upgrade" button
    promotion: false,
    autoresize_bottom_margin: 50,
    // autoresize plugin will disallow manually resizing, but setting resize to true will make the scrollbar disappear
    //resize: true,
    plugins: plugins,
    pagebreak_separator: '<div class="page-break"></div>',
    toolbar1: 'undo redo | styleselect fontsizeselect bold italic underline strikethrough | alignleft aligncenter alignright alignjustify | superscript subscript | bullist numlist outdent indent | forecolor backcolor | charmap adddate | codesample | link | sort-table | save',
    removed_menuitems: 'newdocument, image, anchor',
    image_caption: true,
    images_reuse_filename: false, // if set to true the src url gets a date appended
    images_upload_credentials: true,
    contextmenu: false,
    paste_data_images: Boolean(page === 'edit'),
    // use the preprocessing function on paste event to fix the bgcolor attribute from libreoffice into proper background-color style
    paste_preprocess: function(plugin, args) {
      args.content = args.content.replaceAll('bgcolor="', 'style="background-color:');
    },
    codesample_languages: [
      {text: 'Bash', value: 'bash'},
      {text: 'C', value: 'c'},
      {text: 'C++', value: 'cpp'},
      {text: 'CSS', value: 'css'},
      {text: 'Fortran', value: 'fortran'},
      {text: 'Go', value: 'go'},
      {text: 'Java', value: 'java'},
      {text: 'JavaScript', value: 'javascript'},
      {text: 'Json', value: 'json'},
      {text: 'Julia', value: 'julia'},
      {text: 'Latex', value: 'latex'},
      {text: 'Lua', value: 'lua'},
      {text: 'Makefile', value: 'makefile'},
      {text: 'Matlab', value: 'matlab'},
      {text: 'Perl', value: 'perl'},
      {text: 'Python', value: 'python'},
      {text: 'R', value: 'r'},
      {text: 'Ruby', value: 'ruby'},
      {text: 'SQL', value: 'sql'},
    ],
    codesample_global_prismjs: true,
    language: document.getElementById('user-prefs').dataset.lang,
    charmap_append: [
      [0x2640, 'female sign'],
      [0x2642, 'male sign'],
      [0x25A1, 'white square'],
      [0x2702, 'black scissors'],
      [0x21BB, 'clockwise open circle arrow'],
    ],
    height: '500',
    mentions: {
      // use # for autocompletion
      delimiter: ['#'],
      // get the source from json with get request
      source: function(query: string, process: (data) => void): void {
        // grab experiments and items
        const expjson = ApiC.getJson(`${EntityType.Experiment}?limit=100&q=${query}`);
        const itemjson = ApiC.getJson(`${EntityType.Item}?limit=100&q=${query}`);
        // and merge them into one
        Promise.all([expjson, itemjson]).then(values => {
          process(values[0].concat(values[1]));
        });
      },
      insert: function(selected): string {
        if (selected.type === 'items') {
          ApiC.post(`${entity.type}/${entity.id}/items_links/${selected.id}`).then(() => reloadElement('linksDiv'));
        }
        if (selected.type === 'experiments' && (entity.type === EntityType.Experiment || entity.type === EntityType.Item)) {
          ApiC.post(`${entity.type}/${entity.id}/experiments_links/${selected.id}`).then(() => reloadElement('linksExpDiv'));
        }
        return `<span><a href='${selected.page}.php?mode=view&id=${selected.id}'>${selected.type === 'experiments' ? 'Experiment' : selected.mainattr_title} - ${selected.title}</a></span>`;
      },
    },
    mobile: {
      plugins: [ 'save', 'lists', 'link', 'autolink' ],
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

      // add Font Awesome icon sort-amount-down-alt-solid for table sorting
      editor.ui.registry.addIcon('sort-amount-down-alt', '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="-50 -50 662 562"><!-- Font Awesome Pro 5.15.4 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) --><path d="M240 96h64a16 16 0 0 0 16-16V48a16 16 0 0 0-16-16h-64a16 16 0 0 0-16 16v32a16 16 0 0 0 16 16zm0 128h128a16 16 0 0 0 16-16v-32a16 16 0 0 0-16-16H240a16 16 0 0 0-16 16v32a16 16 0 0 0 16 16zm256 192H240a16 16 0 0 0-16 16v32a16 16 0 0 0 16 16h256a16 16 0 0 0 16-16v-32a16 16 0 0 0-16-16zm-256-64h192a16 16 0 0 0 16-16v-32a16 16 0 0 0-16-16H240a16 16 0 0 0-16 16v32a16 16 0 0 0 16 16zm-64 0h-48V48a16 16 0 0 0-16-16H80a16 16 0 0 0-16 16v304H16c-14.19 0-21.37 17.24-11.29 27.31l80 96a16 16 0 0 0 22.62 0l80-96C197.35 369.26 190.22 352 176 352z"/></svg>');
      // add toggle button for table sorting
      editor.ui.registry.addToggleButton('sort-table', {
        icon: 'sort-amount-down-alt',
        tooltip: 'sortable table',
        onAction: api => {
          const table = editor.selection.getNode().closest('table');
          if (table) {
            if (api.isActive()) {
              // unset sortable
              delete table.dataset.tableSort;
              api.setActive(false);
            } else {
              // show alert if table is not sortable
              if (!isSortable(table, true)) {
                editor.focus();
                return;
              }
              // set sortable
              table.dataset.tableSort = 'true';
              // here the top row could be reformatted automatically td -> th
              api.setActive(true);
            }
            editor.undoManager.add();
          }
          editor.focus();
        },
        onSetup: api => {
          // button is enabled only if table is selected
          // button is active (highlighted) only if table is set sortable
          api.setEnabled(false);

          const callback = event => {
            const table = event.element.closest('table');
            if (!table) {
              api.setEnabled(false);
              api.setActive(false);
              return;
            }

            // table is selected, enable button
            api.setEnabled(true);
            if (table.dataset.tableSort === 'true') {
              // table is set sortable, highlight button
              api.setActive(true);
              return;
            }
            api.setActive(false);
          };

          editor.on('NodeChange', callback);

          return () => {
            editor.off('NodeChange', callback);
          };
        },
      });
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
    toolbar_sticky: true,
  };
}
