/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import { saveAs } from 'file-saver/dist/FileSaver.js';
import { addDateOnCursor, notif } from './misc';
import i18next from 'i18next';
import tinymce from 'tinymce/tinymce';
import 'tinymce/icons/default';
import 'tinymce/plugins/advlist';
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

$(document).ready(function() {
  const Templates = {
    controller: 'app/controllers/EntityAjaxController.php',
    create: function(name: string, body = ''): void {
      $.post(this.controller, {
        create: true,
        name: name,
        body: body,
        type: 'experiments_templates'
      }).done(function(json) {
        notif(json);
        if (json.res) {
          window.location.replace('ucp.php?tab=3');
        }
      });
    },
    saveToFile: function(id, name): void {
      // we have the name of the template used for filename
      // and we have the id of the editor to get the content from
      // we don't use activeEditor because it requires a click inside the editing area
      const content = tinymce.get(id).getContent();
      const blob = new Blob([content], {type: 'text/plain;charset=utf-8'});
      saveAs(blob, name + '.elabftw.tpl');
    },
    destroy: function(id): void {
      if (confirm(i18next.t('generic-delete-warning'))) {
        $.post(this.controller, {
          destroy: true,
          id: id,
          type: 'experiments_templates'
        }).done(function(json) {
          notif(json);
          if (json.res) {
            window.location.replace('ucp.php?tab=3');
          }
        });
      }
    }
  };


  // TEMPLATES listeners
  $(document).on('click', '.createNewTemplate', function() {
    const name = prompt('Template title');
    if (name) {
      // no body on template creation
      Templates.create(name);
    }
  });
  // show the handles to reorder when the menu entry is clicked
  $(document).on('click', '#toggleReorder', function() {
    $('.sortableHandle').toggle();
  });
  $(document).on('click', '.saveToFile', function() {
    Templates.saveToFile($(this).data('id'), $(this).data('name'));
  });
  $(document).on('click', '.destroyTemplate', function() {
    Templates.destroy($(this).data('id'));
  });


  $(document).on('click', '#import-from-file', function() {
    $('#import_tpl').toggle();
  });

  // CAN READ/WRITE SELECT PERMISSION
  $(document).on('change', '.permissionSelect', function() {
    const value = $(this).val();
    const rw = $(this).data('rw');
    $.post('app/controllers/EntityAjaxController.php', {
      updatePermissions: true,
      rw: rw,
      id: $('#selectedTemplate').data('id'),
      type: 'experiments_templates',
      value: value,
    }).done(function(json) {
      notif(json);
    });
  });

  // select the already selected permission for templates
  $(document).on('click', '.modalToggle', function() {
    const read = $(this).data('read');
    const write = $(this).data('write');
    $('#canread_select option[value="' + read + '"]').prop('selected',true);
    $('#canwrite_select option[value="' + write + '"]').prop('selected',true);
  });

  // input to upload an elabftw.tpl file
  $('#import_tpl').on('change', function() {
    const title = (document.getElementById('import_tpl') as HTMLInputElement).value.replace('.elabftw.tpl', '').replace('C:\\fakepath\\', '');
    if (!window.FileReader) {
      alert('Please use a modern web browser. Import aborted.');
      return false;
    }
    const reader = new FileReader();
    reader.onload = function(e): void {
      Templates.create(title, e.target.result as string);
      $('#import_tpl').hide();
    };
  });

  // TinyMCE
  tinymce.init({
    mode : 'specific_textareas',
    editor_selector : 'mceditable', // eslint-disable-line @typescript-eslint/camelcase
    skin_url: 'app/css/tinymce', // eslint-disable-line @typescript-eslint/camelcase
    plugins: 'table searchreplace code fullscreen insertdatetime paste charmap lists advlist save image imagetools link pagebreak mention codesample hr',
    pagebreak_separator: '<pagebreak>', // eslint-disable-line @typescript-eslint/camelcase
    toolbar1: 'undo redo | styleselect bold italic underline | alignleft aligncenter alignright alignjustify | superscript subscript | bullist numlist outdent indent | forecolor backcolor | charmap | codesample | link',
    removed_menuitems: 'newdocument, image', // eslint-disable-line @typescript-eslint/camelcase
    charmap_append: [ // eslint-disable-line @typescript-eslint/camelcase
      [0x2640, 'female sign'],
      [0x2642, 'male sign']
    ],
    mentions: {
      // use # for autocompletion
      delimiter: '#',
      // get the source from json with get request
      source: function (query, process): void {
        const url = 'app/controllers/EntityAjaxController.php?mention=1&term=' + query;
        $.getJSON(url, function(data) {
          process(data);
        });
      }
    },
    // keyboard shortcut to insert today's date at cursor in editor
    setup: function(editor: any) {
      editor.addShortcut('ctrl+shift+d', 'add date at cursor', function() { addDateOnCursor(); });
      editor.addShortcut('ctrl+=', 'subscript', function() {
        editor.execCommand('subscript');
      });
      editor.addShortcut('ctrl+shift+=', 'superscript', function() {
        editor.execCommand('superscript');
      });
      editor.on('init', function() {
        editor.getContainer().className += ' rounded';
      });
    },
    language : $('#user-prefs').data('lang')
  });

  // DESTROY API KEY
  $(document).on('click', '.keyDestroy', function() {
    $.post('app/controllers/AjaxController.php', {
      destroyApiKey: true,
      id: $(this).data('id')
    }).done(function(json) {
      notif(json);
      $('#apiTable').load('ucp.php #apiTable');
    });
  });
});
