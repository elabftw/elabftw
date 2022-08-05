/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012, 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import { notif, insertParamAndReload } from './misc';
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
    if (el.matches('[data-action="create-user"]')) {
      const formGroup = (el.closest('div.row') as HTMLElement);
      let params = {};
      // text inputs
      ['firstname', 'lastname', 'email'].forEach(input => {
        params = Object.assign(params, {[input]: (formGroup.querySelector(`input[name="${input}"]`) as HTMLInputElement).value});
      });
      // select inputs
      ['team', 'usergroup'].forEach(input => {
        params = Object.assign(params, {[input]: (formGroup.querySelector(`select[name="${input}"]`) as HTMLSelectElement).value});
      });
      return ApiC.send('users', Method.POST, params).then(() => {
        notif({'res': true, 'msg': 'New user created'});
      });

    // UPDATE USER
    } else if (el.matches('[data-action="update-user"]')) {
      const formGroup = (el.closest('div.form-group') as HTMLElement);
      let params = {};
      // text inputs
      ['firstname', 'lastname', 'email', 'password'].forEach(input => {
        params = Object.assign(params, {[input]: (formGroup.querySelector(`input[name="${input}"]`) as HTMLInputElement).value});
        if (input === 'password') {
          // clear the password field once collected
          (formGroup.querySelector(`input[name="${input}"]`) as HTMLInputElement).value = '';
        }
      });
      if (params['password'] === '') {
        delete params['password'];
      }
      // select inputs
      ['usergroup', 'validated'].forEach(input => {
        params = Object.assign(params, {[input]: (formGroup.querySelector(`select[name="${input}"]`) as HTMLSelectElement).value});
      });
      return ApiC.send(`users/${el.dataset.userid}`, Method.PATCH, params).then(() => {
        notif({'res': true, 'msg': 'Saved'});
      });

    // ARCHIVE USER TOGGLE
    } else if (el.matches('[data-action="toggle-archive-user"]')) {
      // show alert
      if (!confirm('Are you sure you want to archive/unarchive this user?\nAll experiments will be locked and user will not be able to login anymore.')) {
        return;
      }
      return ApiC.send(`users/${el.dataset.userid}`, Method.PATCH, {'action': 'archive'}).then(() => {
        notif({'res': true, 'msg': 'Saved'});
        insertParamAndReload('tab', '3');
      });

    // VALIDATE USER
    } else if (el.matches('[data-action="validate-user"]')) {
      return ApiC.send(`users/${el.dataset.userid}`, Method.PATCH, {'action': 'validate'}).then(() => {
        notif({'res': true, 'msg': 'Saved'});
        insertParamAndReload('tab', '3');
      });

    // DESTROY USER
    } else if (el.matches('[data-action="destroy-user"]')) {
      if (!confirm('Are you sure you want to remove permanently this user and all associated data?')) {
        return;
      }
      return ApiC.send(`users/${el.dataset.userid}`, Method.DELETE).then(() => {
        notif({'res': true, 'msg': 'User deleted'});
        el.closest('li.list-group-item').remove();
      });
    }
  });

  document.getElementById('editusersShowAll').addEventListener('click', () => {
    (document.getElementById('searchUsers') as HTMLInputElement).value = '';
    (document.getElementById('userSearchForm') as HTMLFormElement).submit();
  });
});
