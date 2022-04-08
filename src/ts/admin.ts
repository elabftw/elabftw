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
import { Malle } from '@deltablot/malle';
import TeamGroup from './TeamGroup.class';
import Status from './Status.class';
import i18next from 'i18next';
import ItemType from './ItemType.class';
import { Ajax } from './Ajax.class';
import { Payload, Method, Model, Action, Target } from './interfaces';
import tinymce from 'tinymce/tinymce';
import { getTinymceBaseConfig } from './tinymce';
import Tab from './Tab.class';

document.addEventListener('DOMContentLoaded', () => {
  if (window.location.pathname !== '/admin.php') {
    return;
  }
  const AjaxC = new Ajax();

  const TabMenu = new Tab();
  TabMenu.init(document.querySelector('.tabbed-menu'));

  // activate editor for common template
  tinymce.init(getTinymceBaseConfig('admin'));

  // VALIDATE USERS
  $(document).on('click', '.usersValidate', function() {
    $(this).attr('disabled', 'disabled').text('Please wait…');
    $.post('app/controllers/UsersAjaxController.php', {
      usersValidate: true,
      userid: $(this).data('userid'),
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
        },
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
  const malleableGroupname = new Malle({
    cancel : i18next.t('cancel'),
    cancelClasses: ['button', 'btn', 'btn-danger', 'mt-2'],
    inputClasses: ['form-control'],
    formClasses: ['mb-3'],
    fun: (value, original) => {
      const payload: Payload = {
        method: Method.POST,
        action: Action.Update,
        model: Model.TeamGroup,
        content: value,
        id: parseInt(original.dataset.id, 10),
        notif: true,
      };
      AjaxC.send(payload);
      return value;
    },
    listenOn: '.teamgroup_name.editable',
    tooltip: i18next.t('click-to-edit'),
    submit : i18next.t('save'),
    submitClasses: ['button', 'btn', 'btn-primary', 'mt-2'],
  }).listen();

  // add an observer so new comments will get an event handler too
  new MutationObserver(() => {
    malleableGroupname.listen();
  }).observe(document.getElementById('team_groups_div'), {childList: true});

  // STATUS
  const StatusC = new Status();

  document.querySelector('[data-action="create-status"]').addEventListener('click', () => {
    const content = (document.getElementById('statusName') as HTMLInputElement).value;
    const color = (document.getElementById('statusColor') as HTMLInputElement).value;
    const isTimestampable = (document.getElementById('statusTimestamp') as HTMLInputElement).checked;
    if (content.length > 1) {
      StatusC.create(content, color, isTimestampable).then(() => window.location.replace('admin.php?tab=4'));
    }
  });

  document.querySelectorAll('[data-action="update-status"]').forEach(el => {
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

  // UPDATE
  function itemsTypesUpdate(id: number): void {
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

    const canread = (document.getElementById('itemsTypesCanread') as HTMLSelectElement).value;
    const canwrite = (document.getElementById('itemsTypesCanwrite') as HTMLSelectElement).value;
    const template = tinymce.get('itemsTypesBody').getContent();
    ItemTypeC.update(id, name, color, bookable, template, canread, canwrite);
  }

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

  document.getElementById('container').addEventListener('click', event => {
    const el = (event.target as HTMLElement);
    if (el.matches('[data-action="override-timestamp"]')) {
      document.getElementById('overrideTimestampContent').toggleAttribute('hidden');
      const value = (document.getElementById('overrideTimestamp') as HTMLInputElement).checked;
      const payload: Payload = {
        method: Method.POST,
        action: Action.Update,
        model: Model.Team,
        target: Target.TsOverride,
        content: value ? '1' : '0',
        notif: true,
      };
      AjaxC.send(payload).then(json => {
        notif(json);
      });
    // CREATE ITEMS TYPES
    } else if (el.matches('[data-action="itemstypes-create"]')) {
      const title = prompt(i18next.t('template-title'));
      if (title) {
        // no body on template creation
        ItemTypeC.create(title).then(json => {
          window.location.replace(`admin.php?tab=5&templateid=${json.value}`);
        });
      }
    // UPDATE ITEMS TYPES
    } else if (el.matches('[data-action="itemstypes-update"]')) {
      itemsTypesUpdate(parseInt(el.dataset.id, 10));
    // DESTROY ITEMS TYPES
    } else if (el.matches('[data-action="itemstypes-destroy"]')) {
      ItemTypeC.destroy(parseInt(el.dataset.id, 10)).then(json => {
        notif(json);
        if (json.res) {
          window.location.href = '?tab=5';
        }
      });
    } else if (el.matches('[data-action="export-category"]')) {
      const source = (document.getElementById('categoryExport') as HTMLSelectElement).value;
      const format = (document.getElementById('categoryExportFormat') as HTMLSelectElement).value;
      window.location.href = `make.php?what=${format}&category=${source}`;

    } else if (el.matches('[data-action="export-user"]')) {
      const source = (document.getElementById('userExport') as HTMLSelectElement).value;
      const format = (document.getElementById('userExportFormat') as HTMLSelectElement).value;
      window.location.href = `make.php?what=${format}&user=${source}&type=experiments`;

    } else if (el.matches('[data-action="export-scheduler"]')) {
      const from = (document.getElementById('schedulerDateFrom') as HTMLSelectElement).value;
      const to = (document.getElementById('schedulerDateTo') as HTMLSelectElement).value;
      window.location.href = `make.php?what=schedulerReport&from=${from}&to=${to}`;
    }
  });
});
