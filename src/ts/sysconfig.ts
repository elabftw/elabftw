/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import { clearForm, collectForm, reloadElements } from './misc';
import { Action, Model } from './interfaces';
import i18next from './i18n';
import tinymce from 'tinymce/tinymce';
import { getEditor } from './Editor.class';
import { notify } from './notify';
import { ApiC } from './api';
import $ from 'jquery';
import { SemverCompare } from './SemverCompare.class';
import { on } from './handlers';

function updateTsFieldsVisibility(select: HTMLSelectElement) {
  const noAccountTsa = ['dfn', 'digicert', 'sectigo', 'globalsign'];
  if (noAccountTsa.includes(select.value)) {
    // mask all
    document.getElementById('ts_loginpass').toggleAttribute('hidden', true);
    document.getElementById('ts_urldiv').toggleAttribute('hidden', true);
  } else if (select.value === 'universign' || select.value === 'dgn') {
    // only make loginpass visible
    document.getElementById('ts_loginpass').removeAttribute('hidden');
    document.getElementById('ts_urldiv').toggleAttribute('hidden', true);
  } else if (select.value === 'custom') {
    // show all
    document.getElementById('ts_loginpass').removeAttribute('hidden');
    document.getElementById('ts_urldiv').removeAttribute('hidden');
  }
}

// GET the latest version information
function checkForUpdate() {
  const updateUrl = 'https://get.elabftw.net/updates.json';
  const currentVersionDiv = document.getElementById('currentVersion');
  const latestVersionDiv = document.getElementById('latestVersion');
  const currentVersion = currentVersionDiv.innerText;
  // Note: this doesn't work on Chrome
  // see: https://bugs.chromium.org/p/chromium/issues/detail?id=571722
  // normal user-agent will be sent
  const headers = new Headers({
    'User-Agent': 'Elabftw/' + currentVersion,
  });

  fetch(updateUrl, {
    headers: headers,
  }).then(response => {
    if (!response.ok) {
      throw new Error('Error fetching latest version!');
    }
    return response.json();
  }).then(data => {
    latestVersionDiv.append(data.version);
    const SemverCompareC = new SemverCompare(currentVersion, data.version);
    if (SemverCompareC.isOld()) {
      currentVersionDiv.style.color = 'red';
      const warningDiv = document.createElement('div');
      warningDiv.classList.add('alert', 'alert-warning');
      const chevron = document.createElement('i');
      chevron.classList.add('fas', 'fa-chevron-right', 'color-warning', 'fa-fw');
      warningDiv.appendChild(chevron);
      const text = document.createElement('span');
      text.classList.add('ml-1');
      text.innerText = `${data.date} - A new version is available!`;
      warningDiv.appendChild(text);
      const updateLink = document.createElement('a');
      updateLink.href = 'https://doc.elabftw.net/how-to-update.html';
      updateLink.classList.add('button', 'btn', 'btn-primary', 'text-white', 'ml-2');
      updateLink.innerText = 'Update elabftw';
      const changelogLink = document.createElement('a');
      changelogLink.href = 'https://doc.elabftw.net/changelog.html';
      changelogLink.classList.add('button', 'btn', 'btn-primary', 'text-white', 'ml-2');
      changelogLink.innerText = 'Read changelog';
      warningDiv.appendChild(updateLink);
      warningDiv.appendChild(changelogLink);
      document.getElementById('versionNotifZone').appendChild(warningDiv);
    } else {
      // show a little green check if we have latest version
      const successIcon = document.createElement('i');
      successIcon.style.color = 'green';
      successIcon.classList.add('fas', 'fa-check', 'fa-lg', 'ml-1');
      latestVersionDiv.appendChild(successIcon);
    }
  }).catch(error => latestVersionDiv.append(error));
}

if (window.location.pathname === '/sysconfig.php') {

  checkForUpdate();

  // TEST EMAIL
  on('send-test-email', async (el: HTMLElement, event: Event) => {
    event.preventDefault();
    const form = document.getElementById('testEmailForm') as HTMLFormElement;
    const params = collectForm(form);
    const button = el as HTMLButtonElement;
    button.disabled = true;
    const buttonText = button.innerText;
    button.innerText = i18next.t('please-wait');
    ApiC.post('instance', params).then(() => {
      button.innerText = buttonText;
    }).catch(() => {
      button.innerText = i18next.t('error');
      // TODO don't hardcode colors
      button.style.backgroundColor = '#e6614c';
    }).finally(() => button.disabled = false);
  });

  // MASS MAIL
  on('send-mass-email', async (el: HTMLElement, event: Event) => {
    event.preventDefault();
    const form = document.getElementById('massEmailForm') as HTMLFormElement;
    const params = collectForm(form);
    const button = (el as HTMLButtonElement);
    button.disabled = true;
    const buttonText = button.innerText;
    button.innerText = i18next.t('please-wait');
    ApiC.post('instance', params).then(() => {
      button.innerText = buttonText;
      form.reset();
    }).catch(() => {
      button.innerText = i18next.t('error');
      // TODO don't hardcode colors
      button.style.backgroundColor = '#e6614c';
    }).finally(() => button.disabled = false);
  });

  // Timestamp provider select
  if (document.getElementById('ts_authority')) {
    const select = (document.getElementById('ts_authority') as HTMLSelectElement);
    // trigger the function when the value is changed
    select.addEventListener('change', () => {
      updateTsFieldsVisibility(select);
    });
    // and also on page load
    updateTsFieldsVisibility(select);
  }

  on('post2instance', (el: HTMLElement) => {
    ApiC.post('instance', {action: el.dataset.target as Action}).then(() => reloadElements(['bruteforceDiv']));
  });

  on('create-team', (_, event: Event) => {
    event.preventDefault();
    const form = document.getElementById('createTeamForm') as HTMLFormElement;
    const params = collectForm(form);
    const content = String(params['name'] ?? '').trim();
    ApiC.post(Model.Team, {name: content}).then(() => {
      $('#createTeamModal').modal('hide');
      form.reset();
      reloadElements(['teamsDiv', 'create-user-team']);
    });
  });

  on('patch-team-sysadmin', (el: HTMLElement) => {
    const id = el.dataset.id;
    const params = {
      'name': (document.getElementById('teamName_' + id) as HTMLInputElement).value,
      'orgid': (document.getElementById('teamOrgid_' + id) as HTMLInputElement).value,
      'visible': (document.getElementById('teamVisible_' + id) as HTMLSelectElement).value,
    };
    ApiC.patch(`${Model.Team}/${id}`, params);
  });

  on('archive-team', (el: HTMLElement) => {
    ApiC.patch(`${Model.Team}/${el.dataset.id}`, {'action': Action.Archive});

  });

  on('destroy-team', (el: HTMLElement) => {
    ApiC.delete(`${Model.Team}/${el.dataset.id}`).then(() => el.parentElement.parentElement.remove());
  });

  on('patch-announcement', (el: HTMLElement) => {
    const input = (document.getElementById(el.dataset.inputid) as HTMLInputElement);
    if (el.dataset.operation === 'clear') {
      input.value = '';
    }
    const params = {};
    params[input.name] = input.value;
    ApiC.patch(Model.Config, params);
  });

  on('clear-password', (el: HTMLElement) => {
    const key = `${el.dataset.target}_password`;
    const params = {};
    params[key] = null;
    ApiC.patch(Model.Config, params)
      .then(() => reloadElements([el.dataset.reload]));
  });

  on('patch-policy', (el: HTMLElement) => {
    let content = tinymce.get(el.dataset.textarea).getContent();
    if (el.dataset.operation === 'clear') {
      content = '';
    }
    const params = {};
    params[el.dataset.confname] = content;
    ApiC.patch(Model.Config, params);
  });

  on('destroy-idp', (el: HTMLElement) => {
    if (confirm(i18next.t('generic-delete-warning'))) {
      ApiC.delete(`idps/${el.dataset.id}`).then(() => reloadElements(['idpsDiv']));
    }
  });

  on('patch-onboarding-email', (el: HTMLElement) => {
    const key = el.dataset.target;
    ApiC.patch(Model.Config, {
      [key]: tinymce.get(key).getContent(),
    });
  });

  on('display-idp-modal', (el: HTMLElement) => {
    ApiC.getJson(`${Model.Idp}/${el.dataset.id}`).then(idp => {
      (document.getElementById('idpModal_name') as HTMLInputElement).value = idp.name;
      (document.getElementById('idpModal_entityid') as HTMLInputElement).value = idp.entityid;
      (document.getElementById('idpModal_sso_url') as HTMLInputElement).value = idp.sso_url;
      (document.getElementById('idpModal_sso_binding') as HTMLSelectElement).value = idp.sso_binding;
      (document.getElementById('idpModal_slo_url') as HTMLInputElement).value = idp.slo_url;
      (document.getElementById('idpModal_slo_binding') as HTMLSelectElement).value = idp.slo_binding;
      (document.getElementById('idpModal_x509_idp') as HTMLInputElement).value = idp.x509;
      (document.getElementById('idpModal_x509_new_idp') as HTMLInputElement).value = idp.x509_new;
      (document.getElementById('idpModal_email_attr') as HTMLInputElement).value = idp.email_attr;
      (document.getElementById('idpModal_fname_attr') as HTMLInputElement).value = idp.fname_attr;
      (document.getElementById('idpModal_lname_attr') as HTMLInputElement).value = idp.lname_attr;
      (document.getElementById('idpModal_team_attr') as HTMLInputElement).value = idp.team_attr;
      (document.getElementById('idpModal_orgid_attr') as HTMLInputElement).value = idp.orgid_attr;
      document.getElementById('idpModalSaveButton').dataset.id = idp.id;
      $('#idpModal').modal('show');
    });
  });

  on('save-idp', (el: HTMLElement, event: Event) => {
    // prevent form submission
    event.preventDefault();
    try {
      const form = document.getElementById('idpForm');
      const params = collectForm(form);
      clearForm(form);
      if (el.dataset.id) { // PATCH IDP
        // remove the id from the modal so clicking "Add new" won't edit the previously edited IDP
        ApiC.patch(`${Model.Idp}/${el.dataset.id}`, params).then(() => {
          document.getElementById('idpModalSaveButton').dataset.id = '';
          reloadElements(['idpsDiv']);
        });
      } else { // CREATE IDP
        ApiC.post(Model.Idp, params).then(() => {
          reloadElements(['idpsDiv']);
        });
      }
    } catch (e) {
      notify.error(e);
      return;
    }
  });

  on('save-idps-source', (el: HTMLElement) => {
    const url = el.parentElement.parentElement.querySelector('input').value.trim();
    ApiC.post(`${Model.IdpsSources}`, {url: url}).then(() => reloadElements(['idpsSourcesDiv']));
  });

  on('refresh-idps-source', (el: HTMLElement) => {
    const button = el as HTMLButtonElement;
    button.disabled = true;
    ApiC.patch(`${Model.IdpsSources}/${el.dataset.id}`, {action: Action.Replace})
      .then(() => reloadElements(['idpsSourcesDiv', 'idpsDiv']))
      .finally(() => button.disabled = false);
  });

  on('enable-idps-with-source', (el: HTMLElement) => {
    ApiC.patch(`${Model.IdpsSources}/${el.dataset.id}`, {action: Action.Validate}).then(() => reloadElements(['idpsSourcesDiv', 'idpsDiv']));
  });

  on('disable-idps-with-source', (el: HTMLElement) => {
    ApiC.patch(`${Model.IdpsSources}/${el.dataset.id}`, {action: Action.Finish}).then(() => reloadElements(['idpsSourcesDiv', 'idpsDiv']));
  });

  on('delete-idps-source', (el: HTMLElement) => {
    ApiC.delete(`${Model.IdpsSources}/${el.dataset.id}`).then(() => reloadElements(['idpsSourcesDiv', 'idpsDiv']));
  });
  document.querySelectorAll('[data-action="load-file-on-change"]').forEach(input => {
    input.addEventListener('change', (event) => {
      const el = (event.target as HTMLInputElement);
      const file = el.files[0];
      const reader = new FileReader();
      reader.readAsText(file);
      reader.onload = function() {
        try {
          const target = (document.getElementById(el.dataset.target) as HTMLInputElement);
          target.value = (reader.result as string);
          // trigger blur so it is saved if it is a save trigger
          target.dispatchEvent(new Event('blur'));
        } catch (error) {
          notify.error(error);
        }
      };
    });
  });

  getEditor().init('sysconfig');
}
