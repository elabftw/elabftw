/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012, 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import { notif, insertParamAndReload } from './misc';
import { Ajax } from './Ajax.class';

document.addEventListener('DOMContentLoaded', () => {
  if (window.location.pathname !== '/sysconfig.php'
    && window.location.pathname !== '/admin.php'
  ) {
    return;
  }

  const AjaxC = new Ajax();
  const controller = 'app/controllers/UsersAjaxController.php';

  document.querySelector('.real-container').addEventListener('click', (event) => {
    const el = (event.target as HTMLElement);
    if (el.matches('[data-action="update-user"]')) {
      const formGroup = el.closest('div.form-group');
      let params = { 'usersUpdate': '1' };
      // text inputs
      ['userid', 'firstname', 'lastname', 'email', 'password'].forEach(input => {
        params = Object.assign(params, {[input]: (formGroup.querySelector(`input[name="${input}"]`) as HTMLInputElement).value});
        if (input === 'password') {
          (formGroup.querySelector(`input[name="${input}"]`) as HTMLInputElement).value = '';
        }
      });
      // clear the password field once collected
      // select inputs
      ['usergroup', 'use_mfa', 'validated'].forEach(input => {
        params = Object.assign(params, {[input]: (formGroup.querySelector(`select[name="${input}"]`) as HTMLSelectElement).value});
      });
      // now doing POST request
      AjaxC.postForm(controller, params)
        .then(res => res.json().then(json => notif(json)));

    // ARCHIVE USER TOGGLE
    } else if (el.matches('[data-action="toggle-archive-user"]')) {
      // show alert
      if (!confirm('Are you sure you want to archive/unarchive this user?\nAll experiments will be locked and user will not be able to login anymore.')) {
        return;
      }
      AjaxC.postForm(controller, {
        toggleArchiveUser: '1',
        userid: el.dataset.userid,
      }).then(res => res.json().then(json => {
        notif(json);
        if (json.res) {
          insertParamAndReload('tab', '3');
        }
      }));

    // DESTROY USER
    } else if (el.matches('[data-action="destroy-user"]')) {
      if (!confirm('Are you sure you want to remove permanently this user and all associated data?')) {
        return;
      }
      AjaxC.postForm(controller, {
        destroyUser: '1',
        userid: el.dataset.userid,
      }).then(res => res.json().then(json => {
        notif(json);
        if (json.res) {
          el.closest('li.list-group-item').remove();
        }
      }));
    }
  });

  document.getElementById('editusersShowAll').addEventListener('click', () => {
    (document.getElementById('searchUsers') as HTMLInputElement).value = '';
    (document.getElementById('userSearchForm') as HTMLFormElement).submit();
  });
});
