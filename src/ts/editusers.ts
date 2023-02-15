/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012, 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import { collectForm, reloadElement, reloadEditUsersBox } from './misc';
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
      return ApiC.post('users', collectForm(el.closest('div.form-group')))
        .then(() => reloadEditUsersBox());

    // UPDATE USER
    } else if (el.matches('[data-action="update-user"]')) {
      return ApiC.patch(`users/${el.dataset.userid}`, collectForm(el.closest('div.form-group')))
        .then(() => reloadEditUsersBox());

    // ARCHIVE USER TOGGLE
    } else if (el.matches('[data-action="toggle-archive-user"]')) {
      // show alert
      if (confirm('Are you sure you want to archive/unarchive this user?\nAll experiments will be locked and user will not be able to login anymore.')) {
        return ApiC.patch(`users/${el.dataset.userid}`, {'action': 'archive'})
          .then(() => reloadEditUsersBox());
      }

    // VALIDATE USER
    } else if (el.matches('[data-action="validate-user"]')) {
      return ApiC.patch(`users/${el.dataset.userid}`, {'action': 'validate'})
        .then(() => reloadElement('unvalidatedUsersBox'))
        .then(() => reloadEditUsersBox());

    // DESTROY USER
    } else if (el.matches('[data-action="destroy-user"]')) {
      if (confirm('Are you sure you want to remove permanently this user and all associated data?')) {
        return ApiC.delete(`users/${el.dataset.userid}`)
          .then(() => reloadEditUsersBox());
      }
    }
  });

  document.getElementById('editusersShowAll').addEventListener('click', () => {
    (document.getElementById('searchUsers') as HTMLInputElement).value = '';
    (document.getElementById('userSearchForm') as HTMLFormElement).submit();
  });
});
