/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012, 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import i18next from 'i18next';
import { collectForm, reloadElement } from './misc';
import { InputType, Malle } from '@deltablot/malle';
import { Api } from './Apiv2.class';

document.addEventListener('DOMContentLoaded', () => {
  if (!['/sysconfig.php', '/admin.php'].includes(window.location.pathname)) {
    return;
  }

  const ApiC = new Api();

  document.querySelector('.real-container').addEventListener('click', (event) => {
    const el = (event.target as HTMLElement);
    // CREATE USER
    if (el.matches('[data-action="create-user"]')) {
      return ApiC.post('users', collectForm(el.closest('div.form-group'))).then(() => reloadElement('editUsersBox'));

    // UPDATE USER
    } else if (el.matches('[data-action="update-user"]')) {
      return ApiC.patch(`users/${el.dataset.userid}`, collectForm(el.closest('div.form-group'))).then(() => reloadElement('editUsersBox'));

    // ARCHIVE USER TOGGLE
    } else if (el.matches('[data-action="toggle-archive-user"]')) {
      // show alert
      if (confirm('Are you sure you want to archive/unarchive this user?\nAll experiments will be locked and user will not be able to login anymore.')) {
        return ApiC.patch(`users/${el.dataset.userid}`, {'action': 'archive'}).then(() => reloadElement('editUsersBox'));
      }

    // VALIDATE USER
    } else if (el.matches('[data-action="validate-user"]')) {
      return ApiC.patch(`users/${el.dataset.userid}`, {'action': 'validate'}).then(() => reloadElement('unvalidatedUsersBox')).then(() => reloadElement('editUsersBox'));
    // SET PASSWORD (from sysadmin page)
    } else if (el.matches('[data-action="reset-user-password"]')) {
      const password = (document.getElementById(`resetUserPasswordInput_${el.dataset.userid}`) as HTMLInputElement).value;
      return ApiC.patch(`users/${el.dataset.userid}`, {'password': password});

    // DESTROY USER
    } else if (el.matches('[data-action="destroy-user"]')) {
      if (confirm('Are you sure you want to remove permanently this user and all associated data?')) {
        return ApiC.delete(`users/${el.dataset.userid}`).then(() => reloadElement('editUsersBox'));
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

  new MutationObserver(() => {
    malleableUsergroup.listen();
  }).observe(document.getElementById('usersTable'), {childList: true});
});
