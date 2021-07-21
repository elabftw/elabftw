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
import i18next from 'i18next';
import ItemType from './ItemType.class';
import { Ajax } from './Ajax.class';
import { Payload, Method, Model, Action, Target } from './interfaces';
import tinymce from 'tinymce/tinymce';
import { getTinymceBaseConfig } from './tinymce';

$(document).ready(function() {
  if (window.location.pathname !== '/admin.php') {
    return;
  }
  const AjaxC = new Ajax();

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

  // AUTOCOMPLETE user list for team groups
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
          const payload: Payload = {
            method: Method.GET,
            action: Action.Read,
            model: Model.User,
            target: Target.List,
            content: term,
          };
          AjaxC.send(payload).then(json => {
            cache[term] = json.value;
            response(json.value);
          });
        }
      });
    }
  });

  // TEAM GROUPS
  const TeamGroupC = new TeamGroup();

  $('#teamGroupCreateBtn').on('click', function() {
    const content = $('#teamGroupCreate').val() as string;
    TeamGroupC.create(content).then(json => {
      if (json.res) {
        // only reload children
        $('#team_groups_div').load('admin.php #team_groups_div > *');
        $('#teamGroupCreate').val('');
      }
    });
  });
  $('#team_groups_div').on('click', '.teamGroupDelete', function() {
    if (confirm(i18next.t('generic-delete-warning'))) {
      TeamGroupC.destroy($(this).data('id')).then(() => {
        // only reload children
        $('#team_groups_div').load('admin.php #team_groups_div > *');
      });
    }
  });

  $('#team_groups_div').on('keypress blur', '.addUserToGroup', function(e) {
    // Enter is ascii code 13
    if (e.which === 13 || e.type === 'focusout') {
      const user = parseInt($(this).val() as string, 10);
      const group = $(this).data('group');
      TeamGroupC.update(user, group, 'add').then(() => {
        // only reload children
        $('#team_groups_div').load('admin.php #team_groups_div > *');
      });
    }
  });
  $('#team_groups_div').on('click', '.rmUserFromGroup', function() {
    const user = $(this).data('user');
    const group = $(this).data('group');
    TeamGroupC.update(user, group, 'rm').then(() => {
      // only reload children
      $('#team_groups_div').load('admin.php #team_groups_div > *');
    });
  });

  // edit the team group name
  $(document).on('mouseenter', 'h3.teamgroup_name', function() {
    ($(this) as any).editable(function(value) {
      const payload: Payload = {
        method: Method.POST,
        action: Action.Update,
        model: Model.TeamGroup,
        content: value,
        id: $(this).data('id'),
      };

      AjaxC.send(payload);
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
  const StatusC = new Status();

  document.getElementById('statusCreate').addEventListener('click', () => {
    const content = (document.getElementById('statusName') as HTMLInputElement).value;
    const color = (document.getElementById('statusColor') as HTMLInputElement).value;
    const isTimestampable = (document.getElementById('statusTimestamp') as HTMLInputElement).checked;
    if (content.length > 1) {
      StatusC.create(content, color, isTimestampable).then(() => window.location.replace('admin.php?tab=4'));
    }
  });

  document.querySelectorAll('.statusSave').forEach(el => {
    el.addEventListener('click', ev => {
      const statusId = parseInt((ev.target as HTMLElement).dataset.statusid);
      const content = (document.getElementById('statusName_' + statusId) as HTMLInputElement).value;
      const color = (document.getElementById('statusColor_' + statusId) as HTMLInputElement).value;
      const isTimestampable = (document.getElementById('statusTimestamp_' + statusId) as HTMLInputElement).checked;
      const isDefault = (document.getElementById('statusDefault_' + statusId) as HTMLInputElement).checked;
      StatusC.update(statusId, content, color, isTimestampable, isDefault);
    });
  });

  document.querySelectorAll('[data-action="destroy-status"]').forEach(el => {
    el.addEventListener('click', ev => {
      const statusId = parseInt((ev.target as HTMLElement).dataset.statusid);
      if (confirm(i18next.t('generic-delete-warning'))) {
        StatusC.destroy(statusId).then((json) => {
          if (json.res) {
            document.getElementById('status_' + statusId).remove();
          }
        });
      }
    });
  });
  // END STATUS

  // ITEMS TYPES
  const ItemTypeC = new ItemType();

  // CREATE
  $('.itemsTypesEditor').hide();
  $(document).on('click', '#itemsTypesCreate', function() {
    const nameInput = (document.getElementById('itemsTypesName') as HTMLInputElement);
    const name = nameInput.value;
    if (name === '') {
      notif({'res': false, 'msg': 'Name cannot be empty'});
      nameInput.style.borderColor = 'red';
      nameInput.focus();
      return;
    }
    const color = (document.getElementById('itemsTypesColor') as HTMLInputElement).value;
    const checkbox = $('#itemsTypesBookable').is(':checked');
    let bookable = 0;
    if (checkbox) {
      bookable = 1;
    }
    const template = tinymce.get('itemsTypesTemplate').getContent();

    const canread= (document.getElementById('canread_select') as HTMLSelectElement).value;
    const canwrite= (document.getElementById('canwrite_select') as HTMLSelectElement).value;
    // set the editor as non dirty so we can navigate out without a warning to clear
    tinymce.activeEditor.setDirty(false);
    // TODO don't reload the whole page, just what we need
    ItemTypeC.create(name, color, bookable, template, canread, canwrite).then(() => window.location.replace('admin.php?tab=5'));
  });

  // TOGGLE BODY
  $(document).on('click', '.itemsTypesShowEditor', function() {
    ItemTypeC.showEditor($(this).data('id'));
  });

  // UPDATE
  $(document).on('click', '.itemsTypesUpdate', function() {
    const id = $(this).data('id');
    const nameInput = (document.getElementById('itemsTypesName_' + id) as HTMLInputElement);
    const name = nameInput.value;
    if (name === '') {
      notif({'res': false, 'msg': 'Name cannot be empty'});
      nameInput.style.borderColor = 'red';
      nameInput.focus();
      return;
    }
    const color = (document.getElementById('itemsTypesColor_' + id) as HTMLInputElement).value;
    const checkbox = $('#itemsTypesBookable_' + id).is(':checked');
    let bookable = 0;
    if (checkbox) {
      bookable = 1;
    }

    const canread = (document.querySelector(`.itemsTypesSelectCanread[data-id="${id}"`) as HTMLSelectElement).value;
    const canwrite = (document.querySelector(`.itemsTypesSelectCanwrite[data-id="${id}"`) as HTMLSelectElement).value;
    // if tinymce is hidden, it'll fail to trigger
    // so we toggle it quickly to grab the content
    if ($('#itemsTypesTemplate_' + id).is(':hidden')) {
      ItemTypeC.showEditor(id);
    }
    const template = tinymce.get('itemsTypesTemplate_' + id).getContent();
    $('#itemsTypesEditor_' + id).toggle();
    ItemTypeC.update(id, name, color, bookable, template, canread, canwrite);
  });

  // DESTROY
  $(document).on('click', '.itemsTypesDestroy', function() {
    if (confirm(i18next.t('generic-delete-warning'))) {
      const id = $(this).data('id');
      ItemTypeC.destroy(id).then(json => {
        if (json.res) {
          $('#itemstypes_' + id).hide();
          $('#itemstypesOrder_' + id).hide();
        }
      });
    }
  });
  // END ITEMS TYPES

  // randomize the input of the color picker so even if user doesn't change the color it's a different one!
  // from https://www.paulirish.com/2009/random-hex-color-code-snippets/
  const colorInput = '#' + Math.floor(Math.random()*16777215).toString(16);
  $('.randomColor').val(colorInput);
});
