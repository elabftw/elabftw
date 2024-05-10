/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012, 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import i18next from 'i18next';
import { collectForm, reloadElements } from './misc';
import { InputType, Malle } from '@deltablot/malle';
import { Api } from './Apiv2.class';
import { Action, Model } from './interfaces';

document.addEventListener('DOMContentLoaded', () => {
  if (!['/sysconfig.php', '/admin.php'].includes(window.location.pathname)) {
    return;
  }

  const ApiC = new Api();

  document.querySelector('.real-container').addEventListener('click', (event) => {
    const el = (event.target as HTMLElement);
    // CREATE USER
    if (el.matches('[data-action="create-user"]')) {
      ApiC.post('users', collectForm(el.closest('div.form-group'))).then(() => {
        el.closest('div.form-group').querySelectorAll('input').forEach(input => {
          input.value = '';
        });
        reloadElements(['editUsersBox']);
      });

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
        ApiC.post('users', {...user});
      });

    // UPDATE USER
    } else if (el.matches('[data-action="update-user"]')) {
      ApiC.patch(`users/${el.dataset.userid}`, collectForm(el.closest('div.form-group'))).then(() => reloadElements(['editUsersBox']));

    // REMOVE 2FA
    } else if (el.matches('[data-action="remove-user-2fa"]')) {
      ApiC.patch(`users/${el.dataset.userid}`, {action: Action.Disable2fa}).then(() => reloadElements(['editUsersBox']));

    // TOGGLE ADMIN STATUS
    } else if (el.matches('[data-action="toggle-admin-user"]')) {
      const group = el.dataset.promote === '1' ? 2 : 4;
      ApiC.patch(`${Model.User}/${el.dataset.userid}`, {action: Action.PatchUser2Team, team: el.dataset.team, target: 'group', content: group, userid: el.dataset.userid}).then(() => reloadElements(['editUsersBox']));

    // ADD TO TEAM
    } else if (el.matches('[data-action="add-user-to-team"]')) {
      ApiC.patch(`${Model.User}/${el.dataset.userid}`, {'action': Action.Add, 'team': el.dataset.team}).then(() => reloadElements(['editUsersBox']));

    // ARCHIVE USER TOGGLE
    } else if (el.matches('[data-action="toggle-archive-user"]')) {
      let lockExp = false;
      if (document.getElementById(`lockSwitch_${el.dataset.userid}`)) {
        lockExp = (document.getElementById(`lockSwitch_${el.dataset.userid}`) as HTMLInputElement).checked;
      }
      ApiC.patch(`users/${el.dataset.userid}`, {action: Action.Archive, with_exp: lockExp}).then(() => reloadElements(['editUsersBox']));

    // VALIDATE USER
    } else if (el.matches('[data-action="validate-user"]')) {
      ApiC.patch(`users/${el.dataset.userid}`, {action: Action.Validate}).then(() => reloadElements(['unvalidatedUsersBox', 'editUsersBox']));
    // SET PASSWORD (from sysadmin page)
    } else if (el.matches('[data-action="reset-user-password"]')) {
      const password = (document.getElementById(`resetUserPasswordInput_${el.dataset.userid}`) as HTMLInputElement).value;
      // because we're sysadmin, we don't need to provide the current_password parameter
      ApiC.patch(`users/${el.dataset.userid}`, {action: Action.UpdatePassword, password: password});

    // DESTROY USER
    } else if (el.matches('[data-action="destroy-user"]')) {
      if (confirm('Are you sure you want to remove permanently this user and all associated data?')) {
        ApiC.delete(`users/${el.dataset.userid}`)
          .then(() => reloadElements(['editUsersBox', 'unvalidatedUsersBox']));
      }
    }
  });

  document.getElementById('editusersShowAll').addEventListener('click', () => {
    (document.getElementById('searchUsers') as HTMLInputElement).value = '';
    (document.getElementById('userSearchForm') as HTMLFormElement).submit();
  });

  // UPDATE MALLEABLE USERGROUP
  const malleableUsergroup = new Malle({
    cancel : i18next.t('cancel'),
    cancelClasses: ['btn', 'btn-danger', 'mt-2', 'ml-1'],
    inputClasses: ['form-control'],
    fun: (value, original) => ApiC.patch(`users/${original.dataset.userid}`, {'usergroup': value})
      .then(res => res.json())
      .then(json => json.usergroup),
    inputType: InputType.Select,
    selectOptions: [{value: '1', text: 'Sysadmin'}, {value: '2', text: 'Admin'}, {value: '4', text: 'User'}],
    listenOn: '.malleableUsergroup',
    submit : i18next.t('save'),
    submitClasses: ['btn', 'btn-primary', 'mt-2'],
    tooltip: i18next.t('click-to-edit'),
  }).listen();


  if (document.getElementById('usersTable')) {
    new MutationObserver(() => {
      malleableUsergroup.listen();
    }).observe(document.getElementById('usersTable'), {childList: true});
  }
});
