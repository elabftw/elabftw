/**
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import { notif } from './misc';
import $ from 'jquery';
import 'jquery-jeditable/src/jquery.jeditable.js';
import 'jquery-ui/ui/widgets/autocomplete';
import tinymce from 'tinymce/tinymce';
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

/* eslint-disable */
function tinyMceInitLight() {
  tinymce.init({
    mode: 'specific_textareas',
    editor_selector: 'mceditable',
    skin_url: 'app/css/tinymce',
    browser_spellcheck: true,
    content_css: 'app/css/tinymce.css',
    plugins: 'table searchreplace code fullscreen insertdatetime paste charmap lists advlist save image imagetools link pagebreak',
    pagebreak_separator: '<pagebreak>',
    toolbar1: 'undo redo | styleselect bold italic underline | alignleft aligncenter alignright alignjustify | superscript subscript | bullist numlist outdent indent | forecolor backcolor | charmap | codesample | link',
    removed_menuitems: 'newdocument, image',
    image_caption: true,
    language : $('#info').data('lang')
  });
/* eslint-enable */
}

$(document).ready(function() {
  const confirmText = $('#info').data('confirm');

  // activate editors in new item type and common template
  tinyMceInitLight();

  // TEAMGROUPS
  const TeamGroups = {
    controller: 'app/controllers/TeamGroupsController.php',
    create: function(): void {
      const name = $('#teamGroupCreate').val() as string;
      if (name.length > 0) {
        $.post(this.controller, {
          teamGroupCreate: name
        }).done(function(json) {
          notif(json);
          if (json.res) {
            $('#team_groups_div').load('admin.php #team_groups_div');
            $('#teamGroupCreate').val('');
          }
        });
      }
    },
    update: function(action, user, group): void {
      $.post(this.controller, {
        teamGroupUpdate: true,
        action: action,
        teamGroupUser: user,
        teamGroupGroup: group
      }).done(function() {
        $('#team_groups_div').load('admin.php #team_groups_div');
      });
    },
    destroy: function(id): void {
      if (confirm(confirmText)) {
        $.post(this.controller, {
          teamGroupDestroy: true,
          teamGroupGroup: id
        }).done(function() {
          $('#team_groups_div').load('admin.php #team_groups_div');
        });
      }
    }
  };

  // TEAM GROUP

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

  $(document).on('click', '#teamGroupCreateBtn', function() {
    TeamGroups.create();
  });
  $(document).on('click', '.teamGroupDelete', function() {
    TeamGroups.destroy($(this).data('id'));
  });

  $(document).on('keypress blur', '.addUserToGroup', function(e) {
    // Enter is ascii code 13
    if (e.which === 13 || e.type === 'focusout') {
      const user = parseInt($(this).val() as string, 10);
      const group = $(this).data('group');
      TeamGroups.update('add', user, group);
    }
  });
  $(document).on('click', '.rmUserFromGroup', function() {
    const user = $(this).data('user');
    const group = $(this).data('group');
    TeamGroups.update('rm', user, group);
  });

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

  // STATUS
  const Status = {
    controller: 'app/controllers/StatusController.php',
    create: function(): void {
      const name = $('#statusName').val();
      if (name === '') {
        notif({'res': false, 'msg': 'Name cannot be empty'});
        $('#statusName').css('border-color', 'red');
        return;
      }
      const color = $('#statusColor').val();
      const isTimestampable = +$('#statusTimestamp').is(':checked');

      $.post(this.controller, {
        statusCreate: true,
        name: name,
        color: color,
        isTimestampable: isTimestampable
      }).done(function(json) {
        notif(json);
        if (json.res) {
          window.location.replace('admin.php?tab=4');
        }
      });
    },
    update: function(id): void {
      const name = $('#statusName_' + id).val();
      const color = $('#statusColor_' + id).val();
      const isTimestampable = +$('#statusTimestamp_'+ id).is(':checked');
      const isDefault = $('#statusDefault_' + id).is(':checked');

      $.post(this.controller, {
        statusUpdate: true,
        id: id,
        name: name,
        color: color,
        isTimestampable: isTimestampable,
        isDefault: isDefault ? 1 : 0,
      }).done(function(json) {
        notif(json);
      });
    },
    destroy: function(id): void {
      $.post(this.controller, {
        statusDestroy: true,
        id: id
      }).done(function(json) {
        notif(json);
        if (json.res) {
          $('#status_' + id).hide();
        }
      });
    }
  };
  $(document).on('click', '#statusCreate', function() {
    Status.create();
  });

  $(document).on('click', '.statusSave', function() {
    Status.update($(this).data('id'));
  });

  $(document).on('click', '.statusDestroy', function() {
    Status.destroy($(this).data('id'));
  });

  // ITEMSTYPES
  const ItemsTypes = {
    controller: 'app/controllers/ItemsTypesAjaxController.php',
    create: function(): void {
      const name = $('#itemsTypesName').val();
      if (name === '') {
        notif({'res': false, 'msg': 'Name cannot be empty'});
        $('#itemsTypesName').css('border-color', 'red');
        return;
      }
      const color = $('#itemsTypesColor').val();
      const checkbox = $('#itemsTypesBookable').is(':checked');
      let bookable = 0;
      if (checkbox) {
        bookable = 1;
      }
      const template = tinymce.get('itemsTypesTemplate').getContent();
      $.post(this.controller, {
        itemsTypesCreate: true,
        name: name,
        color: color,
        bookable: bookable,
        template: template
      }).done(function(json) {
        notif(json);
        if (json.res) {
          window.location.replace('admin.php?tab=5');
        }
      });
    },
    showEditor: function(id): void {
      $('#itemsTypesTemplate_' + id).addClass('mceditable');
      tinyMceInitLight();
      $('#itemsTypesEditor_' + id).toggle();
    },
    update: function(id): void {
      const name = $('#itemsTypesName_' + id).val();
      const color = $('#itemsTypesColor_' + id).val();
      const checkbox = $('#itemsTypesBookable_' + id).is(':checked');
      let bookable = 0;
      if (checkbox) {
        bookable = 1;
      }
      // if tinymce is hidden, it'll fail to trigger
      // so we toggle it quickly to grab the content
      if ($('#itemsTypesTemplate_' + id).is(':hidden')) {
        this.showEditor(id);
      }
      const template = tinymce.get('itemsTypesTemplate_' + id).getContent();
      $('#itemsTypesEditor_' + id).toggle();

      $.post(this.controller, {
        itemsTypesUpdate: true,
        id: id,
        name: name,
        color: color,
        bookable: bookable,
        template: template
      }).done(function(json) {
        notif(json);
      });
    },
    destroy: function(id): void {
      $.post(this.controller, {
        itemsTypesDestroy: true,
        id: id
      }).done(function(json) {
        notif(json);
        if (json.res) {
          $('#itemstypes_' + id).hide();
          $('#itemstypesOrder_' + id).hide();
        }
      });
    }
  };

  $('.itemsTypesEditor').hide();
  $(document).on('click', '#itemsTypesCreate', function() {
    ItemsTypes.create();
  });
  $(document).on('click', '.itemsTypesShowEditor', function() {
    ItemsTypes.showEditor($(this).data('id'));
  });
  $(document).on('click', '.itemsTypesUpdate', function() {
    ItemsTypes.update($(this).data('id'));
  });
  $(document).on('click', '.itemsTypesDestroy', function() {
    ItemsTypes.destroy($(this).data('id'));
  });

  // COMMON TEMPLATE
  $('#commonTplTemplate').closest('div').find('.button').on('click', function() {
    const template = tinymce.get('commonTplTemplate').getContent();
    $.post('app/controllers/AjaxController.php', {
      commonTplUpdate: template
    }).done(function(json) {
      notif(json);
    });
  });

  // validate on enter
  $('#teamGroupCreate').keypress(function (e) {
    let keynum;
    if (e.which) {
      keynum = e.which;
    }
    if (keynum === 13) { // if the key that was pressed was Enter (ascii code 13)
      TeamGroups.create();
    }
  });
  // edit the team group name
  ($('h3.teamgroup_name') as any).editable('app/controllers/TeamGroupsController.php', {
    indicator : 'Saving...',
    name : 'teamGroupUpdateName',
    submit : 'Save',
    cancel : 'Cancel',
    cancelcssclass : 'button btn btn-danger',
    submitcssclass : 'button btn btn-primary',
    style : 'display:inline'

  });

  // randomize the input of the color picker so even if user doesn't change the color it's a different one!
  // from https://www.paulirish.com/2009/random-hex-color-code-snippets/
  const colorInput = '#' + Math.floor(Math.random()*16777215).toString(16);
  $('.randomColor').val(colorInput);

  ($('.tag-editable') as any).editable(function(value) {
    $.post('app/controllers/TagsController.php', {
      update: true,
      newtag: value,
      tag: $(this).data('tag')
    });

    return(value);
  }, {
    tooltip : 'Click to edit',
    indicator : 'Saving...',
    onblur: 'submit',
    style : 'display:inline'
  });
});
