/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import { notif } from './misc';
import $ from 'jquery';
import 'jquery-ui/ui/widgets/autocomplete';
import 'jquery-jeditable/src/jquery.jeditable.js';
import TeamGroup from './TeamGroup.class';
import Status from './Status.class';
import ItemType from './ItemType.class';
import tinymce from 'tinymce/tinymce';
import { getTinymceBaseConfig } from './tinymce';

$(document).ready(function() {
  if (window.location.pathname !== '/admin.php') {
    return;
  }

  // activate editor for common template
  tinymce.init(getTinymceBaseConfig('admin'));

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
          request.what = 'user';
          request.action = 'getList';
          request.params = {
            name: term,
          };
          $.getJSON('app/controllers/Ajax.php', request, function(data) {
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
    ($(this) as any).editable(function(value) {
      $.post('app/controllers/Ajax.php', {
        action: 'update',
        what: 'teamgroup',
        params: {
          name: value,
          id: $(this).data('id'),
        },
      });
      return(value);
    }, {
      indicator : 'Saving...',
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
    $.post('app/controllers/Ajax.php', {
      action: 'updateCommon',
      what: 'template',
      type: 'experiments_templates',
      params: {
        template: template,
      },
    }).done(function(json) {
      notif(json);
    });
  });

  // randomize the input of the color picker so even if user doesn't change the color it's a different one!
  // from https://www.paulirish.com/2009/random-hex-color-code-snippets/
  const colorInput = '#' + Math.floor(Math.random()*16777215).toString(16);
  $('.randomColor').val(colorInput);
});
