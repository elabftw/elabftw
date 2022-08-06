/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012, 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import { collectForm, reloadElement } from './misc';
import { Api } from './Apiv2.class';
import { Method } from './interfaces';

document.addEventListener('DOMContentLoaded', () => {
  if (window.location.pathname !== '/sysconfig.php'
    && window.location.pathname !== '/admin.php'
  ) {
    return;
  }

  const ApiC = new Api();

  document.querySelector('.real-container').addEventListener('click', (event) => {
    const el = (event.target as HTMLElement);
    // CREATE USER
    if (el.matches('[data-action="create-user"]')) {
      return ApiC.send('users', Method.POST, collectForm(el.closest('div.form-group')));

    // UPDATE USER
    } else if (el.matches('[data-action="update-user"]')) {
      return ApiC.send(`users/${el.dataset.userid}`, Method.PATCH, collectForm(el.closest('div.form-group'))).then(() => reloadElement('editUsersBox'));

    // ARCHIVE USER TOGGLE
    } else if (el.matches('[data-action="toggle-archive-user"]')) {
      // show alert
      if (!confirm('Are you sure you want to archive/unarchive this user?\nAll experiments will be locked and user will not be able to login anymore.')) {
        return;
      }
      return ApiC.send(`users/${el.dataset.userid}`, Method.PATCH, {'action': 'archive'}).then(() => reloadElement('editUsersBox'));

    // VALIDATE USER
    } else if (el.matches('[data-action="validate-user"]')) {
      return ApiC.send(`users/${el.dataset.userid}`, Method.PATCH, {'action': 'validate'}).then(() => reloadElement('editUsersBox'));

    // DESTROY USER
    } else if (el.matches('[data-action="destroy-user"]')) {
      if (!confirm('Are you sure you want to remove permanently this user and all associated data?')) {
        return;
      }
      return ApiC.send(`users/${el.dataset.userid}`, Method.DELETE).then(() => el.closest('li.list-group-item').remove());
    }
  });

  document.getElementById('editusersShowAll').addEventListener('click', () => {
    (document.getElementById('searchUsers') as HTMLInputElement).value = '';
    (document.getElementById('userSearchForm') as HTMLFormElement).submit();
  });
});
