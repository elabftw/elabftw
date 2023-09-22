/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import { notif, notifError, reloadElement, updateCatStat } from './misc';
import $ from 'jquery';
import 'jquery-ui/ui/widgets/autocomplete';
import { Malle } from '@deltablot/malle';
import i18next from 'i18next';
import { MdEditor } from './Editor.class';
import { Api } from './Apiv2.class';
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

  $('#team_groups_div').on('click', '.teamGroupDelete', function() {
    if (confirm(i18next.t('generic-delete-warning'))) {
      ApiC.delete(`${Model.Team}/${$(this).data('teamid')}/${Model.TeamGroup}/${$(this).data('id')}`).then(() => reloadElement('team_groups_div'));
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
    fun: async (value, original) => {
      return ApiC.patch(`${Model.Team}/${original.dataset.teamid}/${Model.TeamGroup}/${original.dataset.id}`, {'name': value})
        .then(resp => resp.json()).then(json => json.name);
    },
    listenOn: '.malleableTeamgroupName',
    returnedValueIsTrustedHtml: true,
    submit : i18next.t('save'),
    submitClasses: ['button', 'btn', 'btn-primary', 'mt-2'],
    tooltip: i18next.t('click-to-edit'),
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
    const body = tinymce.get('itemsTypesBody').getContent();
    const params = {'title': name, 'color': color, 'body': body};
    return ApiC.patch(`${EntityType.ItemType}/${id}`, params);
  }
  // END ITEMS TYPES

  // set a random color to all the "create new" statuslike modals
  // from https://www.paulirish.com/2009/random-hex-color-code-snippets/
  document.querySelectorAll('.randomColor').forEach((input: HTMLInputElement) => {
    input.value = `#${Math.floor(Math.random()*16777215).toString(16)}`;
  });

  // CATEGORY SELECT
  $(document).on('change', '.catstatSelect', function() {
    const url = new URL(window.location.href);
    const queryParams = new URLSearchParams(url.search);
    updateCatStat($(this).data('target'), {type: EntityType.ItemType, id: parseInt(queryParams.get('templateid'), 10)}, String($(this).val()));
  });

  document.getElementById('container').addEventListener('click', event => {
    const el = (event.target as HTMLElement);
    // CREATE ITEMS TYPES
    if (el.matches('[data-action="itemstypes-create"]')) {
      const title = prompt(i18next.t('template-title'));
      if (title) {
        // no body on template creation
        ApiC.post(EntityType.ItemType, {'title': title}).then(resp => window.location.href = resp.headers.get('location') + '#resourcesCategoriesAnchor');
      }
    // UPDATE ITEMS TYPES
    } else if (el.matches('[data-action="itemstypes-update"]')) {
      itemsTypesUpdate(parseInt(el.dataset.id, 10));
    // DESTROY ITEMS TYPES
    } else if (el.matches('[data-action="itemstypes-destroy"]')) {
      if (confirm(i18next.t('generic-delete-warning'))) {
        ApiC.delete(`${EntityType.ItemType}/${el.dataset.id}`).then(() => window.location.href = '?tab=4');
      }
    // CREATE TEAM GROUP
    } else if (el.matches('[data-action="create-teamgroup"]')) {
      const input = (document.getElementById('teamGroupCreate') as HTMLInputElement);
      ApiC.post(`${Model.Team}/${input.dataset.teamid}/${Model.TeamGroup}`, {'name': input.value}).then(() => {
        reloadElement('team_groups_div');
        input.value = '';
      });
    // ADD USER TO TEAM GROUP
    } else if (el.matches('[data-action="adduser-teamgroup"]')) {
      const user = parseInt(el.parentNode.parentNode.querySelector('input').value, 10);
      if (isNaN(user)) {
        notifError(new Error('Use the autocompletion menu to add users.'));
        return;
      }
      ApiC.patch(`${Model.Team}/${el.dataset.teamid}/${Model.TeamGroup}/${el.dataset.groupid}`, {'how': Action.Add, 'userid': user}).then(() => reloadElement('team_groups_div'));
    // CREATE STATUSLIKE
    } else if (el.matches('[data-action="create-statuslike"]')) {
      const holder = el.parentElement.parentElement;
      const color = (holder.querySelector('input[type="color"]') as HTMLInputElement).value;
      const nameInput = (holder.querySelector('input[type="text"]') as HTMLInputElement);
      const name = nameInput.value;
      if (!name) {
        notifError(new Error('Invalid status name'));
        // set the border in red to bring attention
        nameInput.style.borderColor = 'red';
        return;
      }
      ApiC.post(`${Model.Team}/${el.dataset.teamid}/${el.dataset.target}`, {'name': name, 'color': color}).then(() => {
        $(`#create${el.dataset.target}Modal`).modal('hide');
        reloadElement(`${el.dataset.target}Div`);
      });
    // UPDATE STATUSLIKE
    } else if (el.matches('[data-action="update-status"]')) {
      const id = el.dataset.id;
      let target = Model.ExperimentsStatus;
      if (el.dataset.target === 'items') {
        target = Model.ItemsStatus;
      }
      if (el.dataset.target === 'expcat') {
        target = Model.ExperimentsCategories;
      }
      const holder = el.parentElement.parentElement;
      const title = (holder.querySelector('input[type="text"]') as HTMLInputElement).value;
      const color = (holder.querySelector('input[type="color"]') as HTMLInputElement).value;
      const isDefault = (holder.querySelector('input[type="radio"]') as HTMLInputElement).checked;
      const params = {'title': title, 'color': color, 'is_default': Boolean(isDefault)};
      ApiC.patch(`${Model.Team}/${el.dataset.teamid}/${target}/${id}`, params);
    // DESTROY STATUS
    } else if (el.matches('[data-action="destroy-status"]')) {
      if (confirm(i18next.t('generic-delete-warning'))) {
        ApiC.delete(`${Model.Team}/${el.dataset.teamid}/${Model.Status}/${el.dataset.id}`).then(() => reloadElement(`${el.dataset.target}Div`));
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
    } else if (el.matches('[data-action="patch-team-common-template"]')) {
      const params = {};
      params['common_template'] = tinymce.get('common_template').getContent();
      params['common_template_md'] = (document.getElementById('common_template_md') as HTMLTextAreaElement).value;
      ApiC.patch(`${Model.Team}/${el.dataset.id}`, params);
    } else if (el.matches('[data-action="patch-team-common-template-md"]')) {
      const params = {};
      params['common_template_md'] = (document.getElementById('common_template_md') as HTMLTextAreaElement).value;
      ApiC.patch(`${Model.Team}/${el.dataset.id}`, params);
    } else if (el.matches('[data-action="export-scheduler"]')) {
      const from = (document.getElementById('schedulerDateFrom') as HTMLSelectElement).value;
      const to = (document.getElementById('schedulerDateTo') as HTMLSelectElement).value;
      window.location.href = `make.php?format=schedulerReport&start=${from}&end=${to}`;
    }
  });
});
