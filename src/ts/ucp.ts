/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import {
  collectForm,
  getEntity,
  getNewIdFromPostRequest,
  notif,
  reloadElements,
  saveStringAsFile,
  updateCatStat,
} from './misc';
import tinymce from 'tinymce/tinymce';
import { getTinymceBaseConfig } from './tinymce';
import i18next from 'i18next';
import { Action, Model, Target, EntityType } from './interfaces';
import Templates from './Templates.class';
import { getEditor } from './Editor.class';
import Tab from './Tab.class';
import EntityClass from './Entity.class';
import { Api } from './Apiv2.class';
import $ from 'jquery';
import { Uploader } from './uploader';

const ApiC = new Api();

document.addEventListener('DOMContentLoaded', () => {
  if (window.location.pathname !== '/ucp.php') {
    return;
  }

  // show the handles to reorder when the menu entry is clicked
  $('#toggleReorder').on('click', function() {
    $('.sortableHandle').toggle();
  });

  const EntityC = new Templates();
  // initialize the file uploader
  (new Uploader()).init();

  const entity = getEntity();
  const TabMenu = new Tab();
  TabMenu.init(document.querySelector('.tabbed-menu'));

  // Which editor are we using? md or tiny
  const editor = getEditor();
  editor.init();

  // CATEGORY SELECT
  $(document).on('change', '.catstatSelect', function() {
    updateCatStat($(this).data('target'), entity, String($(this).val()));
  });

  // MAIN LISTENER
  document.querySelector('.real-container').addEventListener('click', (event) => {
    const el = (event.target as HTMLElement);
    const TemplateC = new EntityClass(EntityType.Template);
    // CREATE TEMPLATE
    if (el.matches('[data-action="create-template"]')) {
      const title = prompt(i18next.t('template-title'));
      if (title) {
        // no body on template creation
        EntityC.create(title).then(async resp => {
          const newId = getNewIdFromPostRequest(resp);
          window.location.href = `ucp.php?tab=3&mode=edit&templateid=${newId}`;
        });
      }
    // LOCK TEMPLATE
    } else if (el.matches('[data-action="toggle-lock"]')) {
      EntityC.patchAction(parseInt(el.dataset.id), Action.Lock).then(() => reloadElements(['lockTemplateButton']));
    // UPDATE TEMPLATE
    } else if (el.matches('[data-action="update-template"]')) {
      EntityC.update(entity.id, Target.Body, editor.getContent());
    // INSERT IMAGE AT CURSOR POSITION IN TEXT FIXME TODO duplicated code from edit.ts
    } else if (el.matches('[data-action="insert-image-in-body"]')) {
      // link to the image
      const url = `app/download.php?name=${el.dataset.name}&f=${el.dataset.link}&storage=${el.dataset.storage}`;
      // switch for markdown or tinymce editor
      let content: string;
      if (editor.type === 'md') {
        content = '\n![image](' + url + ')\n';
      } else if (editor.type === 'tiny') {
        content = '<img src="' + url + '" />';
      }
      editor.setContent(content);

    // DESTROY TEMPLATE
    } else if (el.matches('[data-action="destroy-template"]')) {
      if (confirm(i18next.t('generic-delete-warning'))) {
        EntityC.destroy(parseInt(el.dataset.id))
          .then(() => window.location.replace('ucp.php?tab=3'))
          .catch((e) => notif({'res': false, 'msg': e.message}));
      }

    } else if (el.matches('[data-action="patch-account"]')) {
      const params = collectForm(document.getElementById('ucp-account-form'), false);
      if (params['orcid'] === '') {
        delete params['orcid'];
      }
      ApiC.patch(`${Model.User}/me`, params);

    // IMPORT TPL
    } else if (el.matches('[data-action="import-template"]')) {
      TemplateC.duplicate(parseInt(el.dataset.id), false);

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
        notif({'res': false, 'msg': 'A name is required!'});
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

  // TinyMCE
  tinymce.init(getTinymceBaseConfig('ucp'));
});
