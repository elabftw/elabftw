/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import {
  collectForm,
  reloadElements,
  saveStringAsFile,
} from './misc';
import i18next from './i18n';
import { Action, Model } from './interfaces';
import { Notification } from './Notifications.class';
import Tab from './Tab.class';
import { Api } from './Apiv2.class';

document.addEventListener('DOMContentLoaded', async () => {
  if (window.location.pathname !== '/ucp.php') {
    return;
  }

  const ApiC = new Api();
  (new Tab()).init(document.querySelector('.tabbed-menu'));

  // MAIN LISTENER
  document.getElementById('container').addEventListener('click', event => {
    const el = (event.target as HTMLElement);
    if (el.matches('[data-action="patch-account"]')) {
      const params = collectForm(document.getElementById('ucp-account-form'));
      // Allow clearing the field when sending empty orcid param
      if (!params['orcid']) {
        params['orcid'] = null;
      }
      ApiC.patch(`${Model.User}/me`, params);

    // GENERATE SIGKEY
    } else if (el.matches('[data-action="create-sigkeys"]')) {
      const passphraseInput = (document.getElementById('sigPassphraseInput') as HTMLInputElement);
      ApiC.post(`${Model.User}/me/${Model.Sigkeys}`, {action: Action.Create, passphrase: passphraseInput.value})
        .then(() => reloadElements(['ucp-sigkeys']));
    // REGENERATE SIGKEY
    } else if (el.matches('[data-action="regenerate-sigkeys"]')) {
      const passphraseInput = (document.getElementById('regen_sigPassphraseInput') as HTMLInputElement);
      ApiC.patch(`${Model.User}/me/${Model.Sigkeys}`, {action: Action.Update, passphrase: passphraseInput.value})
        .then(() => reloadElements(['ucp-sigkeys']));
    // DOWNLOAD SIG KEY (pub or priv)
    } else if (el.matches('[data-action="download-sigkey"]')) {
      ApiC.getJson(`${Model.User}/me`).then(user => {
        saveStringAsFile(`elabftw-signature-key.${el.dataset.target.split('_')[1]}`, user[el.dataset.target]);
      });

    // CREATE API KEY
    } else if (el.matches('[data-action="create-apikey"]')) {
      // clear any previous new key message
      const nameInput = (document.getElementById('apikeyName') as HTMLInputElement);
      const content = nameInput.value;
      if (!content) {
        (new Notification()).error('check-required');
        // set the border in red to bring attention
        nameInput.style.borderColor = 'red';
        return;
      }
      const canwrite = parseInt((document.getElementById('apikeyCanwrite') as HTMLInputElement).value, 10);
      ApiC.post(`${Model.Apikey}`, {'name': content, 'canwrite': canwrite}).then(resp => {
        const location = resp.headers.get('location').split('/');
        reloadElements(['apiTable']);
        const newkeyInputGroup = document.getElementById('newApiKeyInputGroup');
        const newkeyInput = document.getElementById('newApiKeyInput') as HTMLInputElement;
        newkeyInput.value = location[location.length -1];
        newkeyInputGroup.removeAttribute('hidden');
      });
    // DESTROY API KEY
    } else if (el.matches('[data-action="destroy-apikey"]')) {
      if (confirm(i18next.t('generic-delete-warning'))) {
        ApiC.delete(`${Model.Apikey}/${el.dataset.apikeyid}`)
          .then(() => el.parentElement.parentElement.remove());
      }
    }
  });
});
