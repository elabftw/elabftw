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
import { notify } from './notify';
import { ApiC } from './api';
import { on } from './handlers';
import $ from 'jquery';

if (window.location.pathname === '/ucp.php') {
  on('patch-account', () => {
    const params = collectForm(document.getElementById('ucp-account-form'));
    // Allow clearing the field when sending empty orcid param
    if (!params['orcid']) {
      params['orcid'] = null;
    }
    ApiC.patch(`${Model.User}/me`, params);
  });

  on('disable-mfa', () => {
    ApiC.patch(`${Model.User}/me`, {action: Action.Disable2fa})
      .then(() => reloadElements(['ucp-mfa']));
  });

  on('create-sigkeys', (_, event: Event) => {
    event.preventDefault();
    const form = document.getElementById('sigPassphraseForm') as HTMLFormElement;
    const params = collectForm(form);
    params['action'] = Action.Create;
    ApiC.post(`${Model.User}/me/${Model.Sigkeys}`, params)
      .then(() => {
        reloadElements(['ucp-sigkeys']);
        form.reset();
      });
  });

  on('download-sigkey', (el: HTMLElement) => {
    ApiC.getJson(`${Model.User}/me`).then(user => {
      saveStringAsFile(`elabftw-signature-key.${el.dataset.target.split('_')[1]}`, user[el.dataset.target]);
    });
  });

  on('regenerate-sigkeys', (_, event: Event) => {
    event.preventDefault();
    const form = document.getElementById('regenerateSigPassphraseForm') as HTMLFormElement;
    const params = collectForm(form);
    params['action'] = Action.Update;
    ApiC.patch(`${Model.User}/me/${Model.Sigkeys}`, params)
      .then(() => {
        $('#regenerateSigkeysModal').modal('hide');
        reloadElements(['ucp-sigkeys']);
        form.reset();
      });
  });

  on('create-apikey', () => {
    // clear any previous new key message
    const nameInput = (document.getElementById('apikeyName') as HTMLInputElement);
    const content = nameInput.value;
    if (!content) {
      notify.error('check-required');
      // set the border in red to bring attention
      nameInput.style.borderColor = 'red';
      return;
    }
    const canwrite = parseInt((document.getElementById('apikeyCanwrite') as HTMLInputElement).value, 10);
    ApiC.post(`${Model.Apikey}`, {name: content, canwrite: canwrite}).then(resp => {
      const location = resp.headers.get('location').split('/');
      reloadElements(['apiTable']);
      const newkeyInputGroup = document.getElementById('newApiKeyInputGroup');
      const newkeyInput = document.getElementById('newApiKeyInput') as HTMLInputElement;
      newkeyInput.value = location[location.length -1];
      newkeyInputGroup.removeAttribute('hidden');
    });
  });

  on('destroy-apikey', (el: HTMLElement) => {
    if (confirm(i18next.t('generic-delete-warning'))) {
      ApiC.delete(`${Model.Apikey}/${el.dataset.apikeyid}`)
        .then(() => el.parentElement.parentElement.remove());
    }
  });
}
