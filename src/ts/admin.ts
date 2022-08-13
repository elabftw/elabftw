/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import { notif, reloadElement, collectForm } from './misc';
import $ from 'jquery';
import 'jquery-ui/ui/widgets/autocomplete';
import { Malle } from '@deltablot/malle';
import TeamGroup from './TeamGroup.class';
import i18next from 'i18next';
import ItemsTypes from './ItemsTypes.class';
import { Ajax } from './Ajax.class';
import { Api } from './Apiv2.class';
import { Payload, Method, Model, Action, Target } from './interfaces';
import tinymce from 'tinymce/tinymce';
import { getTinymceBaseConfig } from './tinymce';
import Tab from './Tab.class';

document.addEventListener('DOMContentLoaded', () => {
  if (window.location.pathname !== '/admin.php') {
    return;
  }
  const AjaxC = new Ajax();
  const ApiC = new Api();

  const TabMenu = new Tab();
  TabMenu.init(document.querySelector('.tabbed-menu'));

  // activate editor for common template
  tinymce.init(getTinymceBaseConfig('admin'));

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
        reloadElement('team_groups_div');
        (document.getElementById('teamGroupCreate') as HTMLInputElement).value = '';
      }
    });
  });
  $('#team_groups_div').on('click', '.teamGroupDelete', function() {
    if (confirm(i18next.t('generic-delete-warning'))) {
      TeamGroupC.destroy($(this).data('id')).then(() => {
        reloadElement('team_groups_div');
      });
    }
  });

  $('#team_groups_div').on('keypress blur', '.addUserToGroup', function(e) {
    // Enter is ascii code 13
    if (e.which === 13 || e.type === 'focusout') {
      const user = parseInt($(this).val() as string, 10);
      const group = $(this).data('group');
      if (e.target.value !== e.target.defaultValue) {
        TeamGroupC.update(user, group, 'add').then(() => {
          reloadElement('team_groups_div');
        });
      }
    }
  });
  $('#team_groups_div').on('click', '.rmUserFromGroup', function() {
    const user = $(this).data('user');
    const group = $(this).data('group');
    TeamGroupC.update(user, group, 'rm').then(() => {
      reloadElement('team_groups_div');
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

  // ITEMS TYPES
  const ItemTypeC = new ItemsTypes();

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
    ItemTypeC.updateAll(id, name, color, bookable, template, canread, canwrite);
  }
  // END ITEMS TYPES

  // randomize the input of the color picker so even if user doesn't change the color it's a different one!
  // from https://www.paulirish.com/2009/random-hex-color-code-snippets/
  const colorInput = '#' + Math.floor(Math.random()*16777215).toString(16);
  $('.randomColor').val(colorInput);

  document.getElementById('container').addEventListener('click', event => {
    const el = (event.target as HTMLElement);
    // CREATE ITEMS TYPES
    if (el.matches('[data-action="itemstypes-create"]')) {
      const title = prompt(i18next.t('template-title'));
      if (title) {
        // no body on template creation
        ItemTypeC.create(title).then(resp => window.location.href = resp.headers.get('location'));
      }
    // UPDATE ITEMS TYPES
    } else if (el.matches('[data-action="itemstypes-update"]')) {
      itemsTypesUpdate(parseInt(el.dataset.id, 10));
    // DESTROY ITEMS TYPES
    } else if (el.matches('[data-action="itemstypes-destroy"]')) {
      ItemTypeC.destroy(parseInt(el.dataset.id, 10)).then(() => window.location.href = '?tab=5');
    // CREATE STATUS
    } else if (el.matches('[data-action="create-status"]')) {
      const content = (document.getElementById('statusName') as HTMLInputElement).value;
      const color = (document.getElementById('statusColor') as HTMLInputElement).value;
      return ApiC.post(`${Model.Team}/${el.dataset.teamid}/${Model.Status}`, {'name': content, 'color': color}).then(() => reloadElement('statusBox'));
    // UPDATE STATUS
    } else if (el.matches('[data-action="update-status"]')) {
      const id = el.dataset.id;
      const title = (document.getElementById('statusName_' + id) as HTMLInputElement).value;
      const color = (document.getElementById('statusColor_' + id) as HTMLInputElement).value;
      const isDefault = (document.getElementById('statusDefault_' + id) as HTMLInputElement).checked;
      const params = {'title': title, 'color': color, 'is_default': Boolean(isDefault)};
      return ApiC.patch(`${Model.Team}/${el.dataset.teamid}/${Model.Status}/${id}`, params);
    // DESTROY STATUS
    } else if (el.matches('[data-action="destroy-status"]')) {
      if (confirm(i18next.t('generic-delete-warning'))) {
        return ApiC.delete(`${Model.Team}/${el.dataset.teamid}/${Model.Status}/${el.dataset.id}`).then(() => reloadElement('statusBox'));
      }
    // EXPORT CATEGORY
    } else if (el.matches('[data-action="export-category"]')) {
      const source = (document.getElementById('categoryExport') as HTMLSelectElement).value;
      const format = (document.getElementById('categoryExportFormat') as HTMLSelectElement).value;
      window.location.href = `make.php?what=${format}&category=${source}&type=items`;

    } else if (el.matches('[data-action="patch-team-admin"]')) {
      const params = collectForm(el.closest('div.form-group'));
      // the tinymce won't get collected
      params['common_template'] = tinymce.get('common_template').getContent();
      ApiC.patch(`${Model.Team}/${el.dataset.id}`, params);
    } else if (el.matches('[data-action="export-scheduler"]')) {
      const from = (document.getElementById('schedulerDateFrom') as HTMLSelectElement).value;
      const to = (document.getElementById('schedulerDateTo') as HTMLSelectElement).value;
      window.location.href = `make.php?format=schedulerReport&start=${from}&end=${to}`;
    }
  });
});
