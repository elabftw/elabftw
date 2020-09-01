/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import { notif, tinyMceInitLight } from './misc';
import $ from 'jquery';
import i18next from 'i18next';
import 'jquery-ui/ui/widgets/autocomplete';
import 'jquery-jeditable/src/jquery.jeditable.js';
import TeamGroup from './TeamGroup.class';
import Status from './Status.class';
import ItemType from './ItemType.class';
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
  if (window.location.pathname !== '/admin.php') {
    return;
  }

  // activate editors in new item type and common template
  tinyMceInitLight();

  // VALIDATE USERS
  $(document).on('click', '.usersValidate', function() {
    $(this).attr('disabled', 'disabled').text('Please wait…');
    $.post('app/controllers/UsersAjaxController.php', {
      usersValidate: true,
      userid: $(this).data('userid')
    }).done(function(json) {
      notif(json);
      if (json.res) {
        window.location.reload();
      }
    });
  });

  // AUTOCOMPLETE
  const cache = {};
  $(document).on('focus', '.addUserToGroup', function() {
    if (!$(this).data('autocomplete')) {
      $(this).autocomplete({
        source: function(request, response): void {
          const term = request.term;
          if (term in cache) {
            response(cache[term]);
            return;
          }
          $.getJSON('app/controllers/AdminAjaxController.php', request, function(data) {
            cache[term] = data;
            response(data);
          });
        }
      });
    }
  });

  // TEAM GROUPS
  const TeamGroupC = new TeamGroup();

  $(document).on('click', '#teamGroupCreateBtn', function() {
    TeamGroupC.create();
  });
  $(document).on('click', '.teamGroupDelete', function() {
    TeamGroupC.destroy($(this).data('id'));
  });

  $(document).on('keypress blur', '.addUserToGroup', function(e) {
    // Enter is ascii code 13
    if (e.which === 13 || e.type === 'focusout') {
      const user = parseInt($(this).val() as string, 10);
      const group = $(this).data('group');
      TeamGroupC.update('add', user, group);
    }
  });
  $(document).on('click', '.rmUserFromGroup', function() {
    const user = $(this).data('user');
    const group = $(this).data('group');
    TeamGroupC.update('rm', user, group);
  });

  // validate on enter
  $('#teamGroupCreate').keypress(function (e) {
    let keynum;
    if (e.which) {
      keynum = e.which;
    }
    if (keynum === 13) { // if the key that was pressed was Enter (ascii code 13)
      TeamGroupC.create();
    }
  });
  // edit the team group name
  $(document).on('mouseenter', 'h3.teamgroup_name', function() {
    ($(this) as any).editable('app/controllers/TeamGroupsController.php', {
      indicator : 'Saving...',
      name : 'teamGroupUpdateName',
      submit : 'Save',
      cancel : 'Cancel',
      cancelcssclass : 'button btn btn-danger',
      submitcssclass : 'button btn btn-primary',
      style : 'display:inline'

    });
  });

  // STATUS
  const StatusC = new Status;

  $(document).on('click', '#statusCreate', function() {
    StatusC.create();
  });

  $(document).on('click', '.statusSave', function() {
    StatusC.update($(this).data('id'));
  });

  $(document).on('click', '.statusDestroy', function() {
    StatusC.destroy($(this).data('id'));
  });
  // END STATUS

  // ITEMS TYPES
  const ItemTypeC = new ItemType();

  $('.itemsTypesEditor').hide();
  $(document).on('click', '#itemsTypesCreate', function() {
    ItemTypeC.create();
  });
  $(document).on('click', '.itemsTypesShowEditor', function() {
    ItemTypeC.showEditor($(this).data('id'));
  });
  $(document).on('click', '.itemsTypesUpdate', function() {
    ItemTypeC.update($(this).data('id'));
  });
  $(document).on('click', '.itemsTypesDestroy', function() {
    ItemTypeC.destroy($(this).data('id'));
  });
  // END ITEMS TYPES

  // COMMON TEMPLATE
  $('#commonTplTemplate').closest('div').find('.button').on('click', function() {
    const template = tinymce.get('commonTplTemplate').getContent();
    $.post('app/controllers/AjaxController.php', {
      commonTplUpdate: template
    }).done(function(json) {
      notif(json);
    });
  });

  // randomize the input of the color picker so even if user doesn't change the color it's a different one!
  // from https://www.paulirish.com/2009/random-hex-color-code-snippets/
  const colorInput = '#' + Math.floor(Math.random()*16777215).toString(16);
  $('.randomColor').val(colorInput);

  // make the tag editable
  $(document).on('mouseenter', '.tag-editable', function() {
    ($(this) as any).editable(function(value) {
      $.post('app/controllers/TagsController.php', {
        update: true,
        newtag: value,
        tagId: $(this).data('tagid'),
      });

      return(value);
    }, {
      tooltip : 'Click to edit',
      indicator : 'Saving...',
      onblur: 'submit',
      style : 'display:inline',
    });
  });
});
