/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import {
  collectForm,
  getSafeElementById,
  mkSpin,
  mkSpinStop,
  permissionsToJson,
  reloadElements,
  TomSelect,
} from './misc';
import $ from 'jquery';
import { Malle } from '@deltablot/malle';
import i18next from './i18n';
import { getEditor } from './Editor.class';
import { ApiC } from './api';
import { Model, Action, Selected } from './interfaces';
import tinymce from 'tinymce/tinymce';
import { notify } from './notify';
import { on } from './handlers';

function collectSelectable(name: string): number[] {
  const collected = [];
  document.querySelectorAll(`#batchActions input[name=${name}]`).forEach(input => {
    const box = input as HTMLInputElement;
    if (box.checked) {
      collected.push(parseInt((input as HTMLInputElement).value, 10));
    }
  });
  return collected;
}

function collectInt(name: string): number {
  return parseInt((getSafeElementById(name) as HTMLInputElement).value, 10);
}

function collectCan(): string {
  // Warning: copy pasta from common.ts save-permissions action
  // collect existing users listed in ul->li, and store them in a string[] with user:<userid>
  const existingUsers = Array.from(document.getElementById('masscan_list_users').children)
    .map(u => `user:${(u as HTMLElement).dataset.id}`);

  return permissionsToJson(
    parseInt(((document.getElementById('masscan_select_base') as HTMLSelectElement).value), 10),
    Array.from((document.getElementById('masscan_select_teams') as HTMLSelectElement).selectedOptions).map(v=>v.value)
      .concat(Array.from((document.getElementById('masscan_select_teamgroups') as HTMLSelectElement).selectedOptions).map(v=>v.value))
      .concat(existingUsers),
  );
}

function getSelected(): Selected {
  return {
    items_categories: collectSelectable('items_categories'),
    items_status: collectSelectable('items_status'),
    items_tags: collectSelectable('items_tags'),
    experiments_status: collectSelectable('experiments_status'),
    experiments_categories: collectSelectable('experiments_categories'),
    experiments_tags: collectSelectable('experiments_tags'),
    tags: collectSelectable('tags'),
    users_experiments: collectSelectable('users-experiments'),
    users_resources: collectSelectable('users-resources'),
    userid: collectInt('targetUserId'),
    team: collectInt('targetTeamId'),
    can: collectCan(),
  };
}

// RUN ACTION ON SELECTED (BATCH)
on('run-action-selected', (el: HTMLElement) => {
  const btn = el as HTMLButtonElement;
  const selected = getSelected();
  if (!Object.values(selected).some(array => array.length > 0)) {
    notify.error('nothing-selected');
    return;
  }
  const oldHTML = mkSpin(btn);
  selected['action'] = btn.dataset.what;
  // we use a custom notif message, so disable the native one
  ApiC.notifOnSaved = false;
  ApiC.post('batch', selected).then(res => {
    const processed = res.headers.get('location').split('/').pop();
    notify.success('entries-processed', { num: processed });
  }).finally(() => {
    mkSpinStop(btn, oldHTML);
  });
});

on('update-counter-value', (el: HTMLElement) => {
  const counterValue = el.parentElement.parentElement.parentElement.previousElementSibling.querySelector('.counterValue');
  const box = el as HTMLInputElement;
  let count = parseInt(counterValue.textContent, 10);
  if (box.checked) {
    count += 1;
  } else {
    count -= 1;
  }
  counterValue.textContent = String(count);
});

on('create-teamgroup', (_, event: Event) => {
  event.preventDefault();
  const form = document.getElementById('createGroupForm') as HTMLFormElement;
  const params = collectForm(form);
  ApiC.post(`${Model.Team}/current/${Model.TeamGroup}`, params).then(() => {
    reloadElements(['team_groups_div']);
    form.reset();
  });
});

on('adduser-teamgroup', (el: HTMLElement, event: Event) => {
  event.preventDefault();
  const user = parseInt(el.parentNode.parentNode.querySelector('input').value, 10);
  if (isNaN(user)) {
    notify.error('add-user-error');
    return;
  }
  ApiC.patch(
    `${Model.Team}/current/${Model.TeamGroup}/${el.dataset.groupid}`,
    {how: Action.Add, userid: user},
  ).then(() => reloadElements(['team_groups_div']));
});

on('rmuser-teamgroup', (el: HTMLElement) => {
  ApiC.patch(`${Model.Team}/current/${Model.TeamGroup}/${el.dataset.groupid}`, {how: Action.Unreference, userid: el.dataset.userid})
    .then(() => el.parentElement.remove());
});

on('destroy-teamgroup', (el: HTMLElement) => {
  if (confirm(i18next.t('generic-delete-warning'))) {
    ApiC.delete(`${Model.Team}/current/${Model.TeamGroup}/${el.dataset.id}`)
      .then(() => el.parentElement.remove());
  }
});

on('export-category', () => {
  const source = (document.getElementById('categoryExport') as HTMLSelectElement).value;
  const format = (document.getElementById('categoryExportFormat') as HTMLSelectElement).value;
  window.location.href = `make.php?format=${encodeURIComponent(format)}&category=${encodeURIComponent(source)}&type=items`;
});

on('export-user', () => {
  const source = (document.getElementById('userExport') as HTMLSelectElement).value;
  const format = (document.getElementById('userExportFormat') as HTMLSelectElement).value;
  window.location.href = `make.php?format=${encodeURIComponent(format)}&owner=${encodeURIComponent(source)}&type=experiments`;
});

on('admin-add-tag', () => {
  const tagInput = (document.getElementById('adminAddTagInput') as HTMLInputElement);
  if (!tagInput.value) {
    return;
  }
  ApiC.post(`${Model.Team}/current/${Model.Tag}`, {tag: tagInput.value}).then(() => {
    tagInput.value = '';
    reloadElements(['tagMgrDiv']);
  });
});

if (window.location.pathname === '/admin.php') {
  on('patch-newcomer_banner', () => {
    const params = {};
    params['newcomer_banner'] = tinymce.get('newcomer_banner').getContent();
    ApiC.patch(`${Model.Team}/current`, params);
  });

  on('patch-onboarding-email', () => {
    const key = 'onboarding_email_body';
    ApiC.patch(`${Model.Team}/current`, {
      [key]: tinymce.get(key).getContent(),
    });
  });

  on('open-onboarding-email-modal', () => {
    // reload the modal in case the users of the team have changed
    reloadElements(['sendOnboardingEmailModal'])
      .then(() => $('#sendOnboardingEmailModal').modal('toggle'))
      .then(() => new TomSelect('#sendOnboardingEmailToUsers', {
        plugins: ['dropdown_input', 'no_active_items', 'remove_button'],
      }));
  });

  on('send-onboarding-emails', () => {
    ApiC.notifOnSaved = false;
    ApiC.patch(`${Model.Team}/current`, {
      'action': Action.SendOnboardingEmails,
      'userids': Array.from((document.getElementById('sendOnboardingEmailToUsers') as HTMLSelectElement).selectedOptions)
        .map(option => parseInt(option.value, 10)),
    }).then(response => {
      if (response.ok) {
        notify.success('onboarding-email-sent');
      }
    });
  });

  getEditor().init('admin');

  // edit the team group name
  const malleableGroupname = new Malle({
    cancel : i18next.t('cancel'),
    cancelClasses: ['button', 'btn', 'btn-danger', 'mt-2'],
    inputClasses: ['form-control'],
    formClasses: ['mb-3'],
    fun: async (value, original) => {
      return ApiC.patch(`${Model.Team}/current/${Model.TeamGroup}/${original.dataset.id}`, {name: value})
        .then(resp => resp.json()).then(json => json.name);
    },
    listenOn: '.malleableTeamgroupName',
    returnedValueIsTrustedHtml: false,
    submit : i18next.t('save'),
    submitClasses: ['button', 'btn', 'btn-primary', 'mt-2'],
    tooltip: i18next.t('click-to-edit'),
  }).listen();

  // add an observer so new team groups will get an event handler
  new MutationObserver(() => {
    malleableGroupname.listen();
  }).observe(document.getElementById('team_groups_div'), {childList: true});
}
