/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import { collectForm, notif, notifError, reloadElement, reloadElements, removeEmpty } from './misc';
import { Action, Model } from './interfaces';
import i18next from 'i18next';
import tinymce from 'tinymce/tinymce';
import { getTinymceBaseConfig } from './tinymce';
import Tab from './Tab.class';
import { Ajax } from './Ajax.class';
import { Api } from './Apiv2.class';
import $ from 'jquery';
import { SemverCompare } from './SemverCompare.class';

document.addEventListener('DOMContentLoaded', () => {
  if (window.location.pathname !== '/sysconfig.php') {
    return;
  }

  const TabMenu = new Tab();
  const AjaxC = new Ajax();
  const ApiC = new Api();
  TabMenu.init(document.querySelector('.tabbed-menu'));

  // GET the latest version information
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
          notifError(error);
        }
      };
    });
  });

  // Add click listener and do action based on which element is clicked
  document.querySelector('.real-container').addEventListener('click', (event) => {
    const el = (event.target as HTMLElement);
    // CLEAR-LOCKEDUSERS and CLEAR-LOCKOUTDEVICES
    if (el.matches('[data-action="clear-nologinusers"]') || el.matches('[data-action="clear-lockoutdevices"]')) {
      AjaxC.postForm('app/controllers/SysconfigAjaxController.php', { [el.dataset.action]: '1' })
        .then(res => res.json().then(json => {
          if (json.res) {
            reloadElement('bruteforceDiv');
          }
          notif(json);
        }));

    // CREATE TEAM
    } else if (el.matches('[data-action="create-team"]')) {
      const input = document.getElementById('newTeamName') as HTMLInputElement;
      ApiC.post(Model.Team, {name: input.value}).then(() => {
        input.value = '';
        reloadElements(['teamsDiv', 'userSearchForm', 'create-user-team']);
      });
    // UPDATE TEAM
    } else if (el.matches('[data-action="patch-team-sysadmin"]')) {
      const id = el.dataset.id;
      const params = {
        'name': (document.getElementById('teamName_' + id) as HTMLInputElement).value,
        'orgid': (document.getElementById('teamOrgid_' + id) as HTMLInputElement).value,
        'visible': (document.getElementById('teamVisible_' + id) as HTMLSelectElement).value,
      };
      ApiC.patch(`${Model.Team}/${id}`, removeEmpty(params));
    // ARCHIVE TEAM
    } else if (el.matches('[data-action="archive-team"]')) {
      ApiC.patch(`${Model.Team}/${el.dataset.id}`, {'action': Action.Archive});
    // DESTROY TEAM
    } else if (el.matches('[data-action="destroy-team"]')) {
      ApiC.delete(`${Model.Team}/${el.dataset.id}`).then(() => reloadElement('teamsDiv'));
    // ADD USER TO TEAM
    } else if (el.matches('[data-action="create-user2team"]')) {
      const selectEl = (el.previousElementSibling as HTMLSelectElement);
      const team = parseInt(selectEl.options[selectEl.selectedIndex].value, 10);
      const userid = parseInt(el.dataset.userid, 10);
      ApiC.patch(`${Model.User}/${userid}`, {'action': Action.Add, 'team': team}).then(() => reloadElement(`manageUsers2teamsModal_${userid}`));
    // REMOVE USER FROM TEAM
    } else if (el.matches('[data-action="destroy-user2team"]')) {
      if (confirm(i18next.t('generic-delete-warning'))) {
        const userid = parseInt(el.dataset.userid, 10);
        const team = parseInt(el.dataset.teamid, 10);
        ApiC.patch(`${Model.User}/${userid}`, {'action': Action.Unreference, 'team': team}).then(() => reloadElement(`manageUsers2teamsModal_${userid}`));
      }
    // MODIFY USER GROUP IN TEAM
    } else if (el.matches('[data-action="patch-user2team-group"]')) {
      // will be 1 for Admin, 0 for user
      const selectEl = (el.previousElementSibling as HTMLSelectElement);
      const group = parseInt(selectEl.options[selectEl.selectedIndex].value, 10);
      const team = parseInt(el.dataset.team, 10);
      const userid = parseInt(el.dataset.userid, 10);
      // add the userid in params too for Users2Teams
      ApiC.patch(`${Model.User}/${userid}`, {action: Action.PatchUser2Team, team: team, target: 'group', content: group, userid: userid});
    // DESTROY ts_password
    } else if (el.matches('[data-action="destroy-ts-password"]')) {
      ApiC.patch('config', {'ts_password': ''}).then(() => reloadElement('ts_loginpass'));
    // PATCH ANNOUNCEMENT - save or clear
    } else if (el.matches('[data-action="patch-announcement"]')) {
      const input = (document.getElementById(el.dataset.inputid) as HTMLInputElement);
      if (el.dataset.operation === 'clear') {
        input.value = '';
      }
      const params = {};
      params[input.name] = input.value;
      ApiC.patch('config', params);
    } else if (el.matches('[data-action="clear-password"]')) {
      const key = `${el.dataset.target}_password`;
      const params = {};
      params[key] = null;
      ApiC.patch('config', params).then(() => {
        reloadElement(el.dataset.reload);
      });
    // PATCH POLICY - save or clear
    } else if (el.matches('[data-action="patch-policy"]')) {
      let content = tinymce.get(el.dataset.textarea).getContent();
      if (el.dataset.operation === 'clear') {
        content = '';
      }
      const params = {};
      params[el.dataset.confname] = content;
      ApiC.patch('config', params);
    // TEST MAIL
    } else if (el.matches('[data-action="send-test-email"]')) {
      const button = (el as HTMLButtonElement);
      button.disabled = true;
      button.innerText = 'Sending…';
      const email = (document.getElementById('testemailEmail') as HTMLInputElement).value;
      AjaxC.postForm(
        'app/controllers/SysconfigAjaxController.php',
        { 'testemailSend': '1', 'email': email }).then(resp => handleEmailResponse(resp, button));
    // MASS MAIL
    } else if (el.matches('[data-action="send-mass-email"]')) {
      const massEmailDiv = document.getElementById('massEmailDiv');
      const targetRadio = (massEmailDiv.querySelector('input[name="target"]:checked') as HTMLInputElement);
      const button = (el as HTMLButtonElement);
      button.disabled = true;
      button.innerText = 'Sending…';
      const subject = (document.getElementById('massSubject') as HTMLInputElement).value;
      const body = (document.getElementById('massBody') as HTMLInputElement).value;
      AjaxC.postForm(
        'app/controllers/SysconfigAjaxController.php',
        { massEmail: '1', subject: subject, body: body, target: targetRadio.value }).then(resp => handleEmailResponse(resp, button));
    } else if (el.matches('[data-action="create-idp"]')) {
      const params = collectForm(document.getElementById('createIdpForm'));
      ApiC.post(Model.Idp, params).then(() => {
        $('#createIdpModal').modal('hide');
        reloadElement('idpsDiv');
      });
    } else if (el.matches('[data-action="destroy-idp"]')) {
      event.preventDefault();
      if (confirm(i18next.t('generic-delete-warning'))) {
        AjaxC.postForm('app/controllers/SysconfigAjaxController.php', {
          idpsDestroy: '1',
          id: el.dataset.id,
        }).then(() => reloadElement('idpsDiv'));
      }
    }
  });

  function handleEmailResponse(resp: Response, button: HTMLButtonElement): void {
    resp.json().then(json => {
      notif(json);
      if (json.res) {
        button.innerText = 'Sent!';
        button.disabled = false;
      } else {
        button.innerText ='Error';
        button.style.backgroundColor = '#e6614c';
      }
    });
  }

  /**
   * Timestamp provider select
   */
  const noAccountTsa = ['dfn', 'digicert', 'sectigo', 'globalsign'];
  if (document.getElementById('ts_authority')) {
    const select = (document.getElementById('ts_authority') as HTMLSelectElement);
    // trigger the function when the value is changed
    select.addEventListener('change', () => {
      updateTsFieldsVisibility(select);
    });
    // and also on page load
    updateTsFieldsVisibility(select);
  }

  function updateTsFieldsVisibility(select: HTMLSelectElement) {
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
  tinymce.init(getTinymceBaseConfig('sysconfig'));
});
