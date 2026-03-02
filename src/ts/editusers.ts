/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012, 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import i18next from './i18n';
import { clearForm, collectForm, populateUserModal, reloadElements } from './misc';
import { ApiC } from './api';
import { Action, Model } from './interfaces';
import $ from 'jquery';
import { notify } from './notify';
import { core } from './core';

if (document.getElementById('users-table')) {

  document.getElementById('container').addEventListener('click', async (event) => {
    const el = (event.target as HTMLElement);
    let userid = document.getElementById('editUserModal')?.dataset.userid;
    if (!userid) {
      userid = el.dataset.userid;
    }
    // CREATE USER
    if (el.matches('[data-action="create-user"]')) {
      event.preventDefault();
      el.setAttribute('disabled', 'disabled');

      try {
        const form = document.getElementById('createUserForm') as HTMLFormElement;
        const values = collectForm(form);
        if (el.dataset.checkArchived === '1') {
          // look for an archived user with the same email address
          const matchedArchivedUsers = [];
          const archivedUsers = await ApiC.getJson(`users?onlyArchived=1&q=${values['email']}`);
          archivedUsers.forEach(user => {
            if (user.email === values['email']) {
              matchedArchivedUsers.push(user);
            }
          });
          if (matchedArchivedUsers.length > 0) {
            const archivedUsersFoundList = (document.getElementById('archivedUsersFoundList') as HTMLUListElement);
            matchedArchivedUsers.forEach(user => {
              const archivedUser = document.createElement('li');
              archivedUser.textContent = `${user.fullname} (${user.email})`;
              archivedUser.classList.add('list-group-item');
              const btn = document.createElement('button');
              btn.classList.add('btn', 'btn-secondary', 'ml-3');
              btn.dataset.action = 'unarchive-and-add-to-team';
              btn.dataset.userid = user.userid;
              // on admin panel, team select is disabled, so collectForm doesn't pickup the value
              if (typeof(values['team']) !== 'undefined') {
                btn.dataset.team = values['team'];
              }
              const teamSelect = document.getElementById('create-user-team') as HTMLSelectElement;
              const team = teamSelect.options[teamSelect.selectedIndex].text;
              btn.textContent = i18next.t('unarchive-and-add-to-team', { team: team });
              archivedUser.append(btn);
              archivedUsersFoundList.append(archivedUser);
            });
            document.getElementById('archivedUsersFound').removeAttribute('hidden');
            return;
          }
        }

        ApiC.post('users', values).then(() => {
          // use form.reset() so user-invalid pseudo-class isn't present
          form.reset();
          document.getElementById('archivedUsersFound').setAttribute('hidden', 'hidden');
          document.getElementById('initialCreateUserBtn').removeAttribute('disabled');
          document.dispatchEvent(new CustomEvent('dataReload'));
        });
      } catch (error) {
        el.removeAttribute('disabled');
        notify.error(error);
      }

    // EDIT USER
    } else if (el.matches('[data-action="save-user"]')) {
      try {
        if (el.dataset.userid) { // edit
          const form = document.getElementById('editUserInputs');
          const params = collectForm(form);
          ApiC.patch(`users/${el.dataset.userid}`, params).then(() => {
            document.dispatchEvent(new CustomEvent('dataReload'));
            $('#editUserModal').modal('hide');
            clearForm(form);
          });
        }
      } catch (err) {
        notify.error('Something went wrong: ', {err});
        return;
      }
    // CREATE USER(s) FROM REMOTE DIRECTORY
    } else if (el.matches('[data-action="create-user-from-remote"]')) {
      // the users are in a table row, we need to collect all the rows that are selected
      const selected = document.getElementById('remoteDirectoryUsersTable').querySelectorAll('input[type="checkbox"]:checked');
      const users = [];
      const team = (document.getElementById('remoteUserTeam') as HTMLSelectElement).value;
      const usergroup = (document.getElementById('remoteUserIsAdmin') as HTMLInputElement).checked ? 2 : 4;
      selected.forEach(box => {
        const row = box.parentNode.parentNode as HTMLTableRowElement;
        users.push({
          'firstname': row.cells[1].innerText,
          'lastname': row.cells[2].innerText,
          'email': row.cells[3].innerText,
          'orgid': row.cells[4].innerText,
          'team': team,
          'usergroup': usergroup,
        });
        row.remove();
      });
      users.forEach(user => {
        ApiC.post('users', {...user}).then(() => document.dispatchEvent(new CustomEvent('dataReload')));
      });

    // UNARCHIVE AND ADD TO TEAM
    } else if (el.matches('[data-action="unarchive-and-add-to-team"]')) {
      ApiC.patch(`${Model.User}/${el.dataset.userid}`, {action: Action.Lock}).then(() => {
        const params = {action: Action.Add};
        if (el.dataset.team) {
          params['team'] = el.dataset.team;
        }
        ApiC.patch(`${Model.User}/${el.dataset.userid}`, params).then(() => {
          document.getElementById('archivedUsersFound').remove();
          document.dispatchEvent(new CustomEvent('dataReload'));
        });
      });

    // REMOVE 2FA
    } else if (el.matches('[data-action="remove-user-2fa"]')) {
      ApiC.patch(`users/${userid}`, {action: Action.Disable2fa}).then(() => {
        document.dispatchEvent(new CustomEvent('dataReload'));
        $('#editUserModal').modal('toggle');
      });

    // TOGGLE ADMIN STATUS
    } else if (el.matches('[data-action="toggle-admin-user"]')) {
      const group = el.dataset.promote === '1' ? 2 : 4;
      ApiC.patch(`${Model.User}/${userid}`, {action: Action.PatchUser2Team, team: el.dataset.team, target: 'group', content: group, userid: userid}).then(() => document.dispatchEvent(new CustomEvent('dataReload')));

    // VALIDATE USER
    } else if (el.matches('[data-action="validate-user"]')) {
      ApiC.patch(`users/${userid}`, {action: Action.Validate})
        .then(() => {
          document.dispatchEvent(new CustomEvent('dataReload'));
          reloadElements(['unvalidatedUsersBox']);
        });
    // SET PASSWORD (from sysadmin page)
    } else if (el.matches('[data-action="reset-user-password"]')) {
      const form = document.getElementById('resetUserPasswordForm');
      const params = collectForm(form);
      // because we're sysadmin, we don't need to provide the current_password parameter
      ApiC.patch(`users/${userid}`, {action: Action.UpdatePassword, password: params['resetPassword']}).then(() => {
        $('#editUserModal').modal('toggle');
        clearForm(form);
      });

    // DESTROY USER
    } else if (el.matches('[data-action="destroy-user"]')) {
      if (confirm('Are you sure you want to remove permanently this user and all associated data?')) {
        ApiC.delete(`users/${userid}`)
          .then(() => {
            reloadElements(['editUsersBox', 'unvalidatedUsersBox']);
            $('#editUserModal').modal('toggle');
            document.dispatchEvent(new CustomEvent('dataReload'));
          });
      }
    // ADD USER TO TEAM
    } else if (el.matches('[data-action="create-user2team"]')) {
      const selectEl = (el.previousElementSibling as HTMLSelectElement);
      const team = parseInt(selectEl.options[selectEl.selectedIndex].value, 10);
      ApiC.patch(`${Model.User}/${userid}`, {action: Action.Add, team: team})
        .then(response => response.json()).then(user => populateUserModal(user));
    } else if (el.matches('[data-action="import-users-in-team"]')) {
      const idList = el.dataset.target.split(',');
      if (!confirm(`Add ${idList.length} user(s) to your team?`)) {
        return;
      }
      const requests = idList.map(userid => ApiC.patch(`${Model.User}/${userid}`, {action: Action.Add, team: core.currentTeam}));
      await Promise.all(requests);
      document.dispatchEvent(new CustomEvent('dataReload'));
    // REMOVE USER FROM TEAM
    } else if (el.matches('[data-action="destroy-user2team"]')) {
      alert('It is currently not recommended to remove a user from a team. Use the "Is Archived" property instead to mark them as inactive.');
      /*
        const team = parseInt(el.dataset.teamid, 10);
        ApiC.patch(`${Model.User}/${userid}`, {action: Action.Unreference, team: team})
          .then(response => response.json()).then(user => populateUserModal(user));
      }
     */
    }
  });
}
