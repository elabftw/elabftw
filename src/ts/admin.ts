/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import { getEntity, notif, notifError, reloadElement, collectForm, permissionsToJson } from './misc';
import $ from 'jquery';
import 'jquery-ui/ui/widgets/autocomplete';
import { Malle } from '@deltablot/malle';
import i18next from 'i18next';
import { MdEditor } from './Editor.class';
import { Api } from './Apiv2.class';
import { Metadata } from './Metadata.class';
import { EntityType, Model, Action } from './interfaces';
import tinymce from 'tinymce/tinymce';
import { getTinymceBaseConfig } from './tinymce';
import Tab from './Tab.class';

document.addEventListener('DOMContentLoaded', () => {
  if (window.location.pathname !== '/admin.php') {
    return;
  }
  const ApiC = new Api();

  const TabMenu = new Tab();
  TabMenu.init(document.querySelector('.tabbed-menu'));

  // activate editor for common template
  tinymce.init(getTinymceBaseConfig('admin'));
  // and for md
  (new MdEditor()).init();

  const entity = getEntity();
  if (entity.id) {
    // add extra fields elements from metadata json
    const MetadataC = new Metadata(entity);
    MetadataC.display('edit');
  }

  $('#teamGroupCreateBtn').on('click', function() {
    const input = (document.getElementById('teamGroupCreate') as HTMLInputElement);
    ApiC.post(`${Model.Team}/${input.dataset.teamid}/${Model.TeamGroup}`, {'name': input.value}).then(() => {
      reloadElement('team_groups_div');
      input.value = '';
    });
  });

  $('#team_groups_div').on('click', '.teamGroupDelete', function() {
    if (confirm(i18next.t('generic-delete-warning'))) {
      ApiC.delete(`${Model.Team}/${$(this).data('teamid')}/${Model.TeamGroup}/${$(this).data('id')}`).then(() => reloadElement('team_groups_div'));
    }
  });

  $('#team_groups_div').on('keypress blur', '.autocompleteUsers', function(e) {
    // Enter is ascii code 13
    if (e.which === 13 || e.type === 'focusout') {
      const user = parseInt($(this).val() as string, 10);
      if (isNaN(user)) {
        notifError(new Error('Use the autocompletion menu to add users.'));
        return;
      }
      const group = $(this).data('group');
      if (e.target.value !== e.target.defaultValue) {
        ApiC.patch(`${Model.Team}/${$(this).data('teamid')}/${Model.TeamGroup}/${group}`, {'how': Action.Add, 'userid': user}).then(() => reloadElement('team_groups_div'));
      }
    }
  });
  $('#team_groups_div').on('click', '.rmUserFromGroup', function() {
    const user = $(this).data('user');
    const group = $(this).data('group');
    ApiC.patch(`${Model.Team}/${$(this).data('teamid')}/${Model.TeamGroup}/${group}`, {'how': Action.Unreference, 'userid': user}).then(() => reloadElement('team_groups_div'));
  });

  // edit the team group name
  const malleableGroupname = new Malle({
    cancel : i18next.t('cancel'),
    cancelClasses: ['button', 'btn', 'btn-danger', 'mt-2'],
    inputClasses: ['form-control'],
    formClasses: ['mb-3'],
    fun: (value, original) => {
      ApiC.patch(`${Model.Team}/${$(this).data('teamid')}/${Model.TeamGroup}/${original.dataset.id}`, {'name': value}).then(() => reloadElement('team_groups_div'));
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

  // UPDATE
  function itemsTypesUpdate(id: number): Promise<Response> {
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

    const body = tinymce.get('itemsTypesBody').getContent();
    const params = {'title': name, 'color': color, 'bookable': bookable, 'body': body};
    return ApiC.patch(`${EntityType.ItemType}/${id}`, params);
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
        ApiC.post(EntityType.ItemType, {'title': title}).then(resp => window.location.href = resp.headers.get('location'));
      }
    // UPDATE ITEMS TYPES
    } else if (el.matches('[data-action="itemstypes-update"]')) {
      itemsTypesUpdate(parseInt(el.dataset.id, 10));
    // DESTROY ITEMS TYPES
    } else if (el.matches('[data-action="itemstypes-destroy"]')) {
      ApiC.delete(`${EntityType.ItemType}/${el.dataset.id}`).then(() => window.location.href = '?tab=5');
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
      window.location.href = `make.php?format=${format}&category=${source}&type=items`;

    // ADD TAG
    } else if (el.matches('[data-action="admin-add-tag"]')) {
      const tagInput = (document.getElementById('adminAddTagInput') as HTMLInputElement);
      ApiC.post(`${Model.TeamTags}`, {'tag': tagInput.value}).then(() => {
        tagInput.value = '';
        reloadElement('tagMgrDiv');
      });
    } else if (el.matches('[data-action="patch-team-admin"]')) {
      const params = collectForm(el.closest('div.form-group'));
      params['force_canread'] = permissionsToJson(parseInt(params['force_canread'], 10), []);
      params['force_canwrite'] = permissionsToJson(parseInt(params['force_canwrite'], 10), []);
      // the tinymce won't get collected
      params['common_template'] = tinymce.get('common_template').getContent();
      params['common_template_md'] = (document.getElementById('common_template_md') as HTMLTextAreaElement).value;
      ApiC.patch(`${Model.Team}/${el.dataset.id}`, params);
    } else if (el.matches('[data-action="export-scheduler"]')) {
      const from = (document.getElementById('schedulerDateFrom') as HTMLSelectElement).value;
      const to = (document.getElementById('schedulerDateTo') as HTMLSelectElement).value;
      window.location.href = `make.php?format=schedulerReport&start=${from}&end=${to}`;
    }
  });
});
