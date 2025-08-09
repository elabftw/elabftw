/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import i18next from './i18n';
import { InputType, Malle } from '@deltablot/malle';
import { Api } from './Apiv2.class';
import { getEntity, relativeMoment, reloadElements } from './misc';
import { Action, Model } from './interfaces';

const ApiC = new Api();
const entity = getEntity();

// UPDATE MALLEABLE COMMENT
const malleableComments = new Malle({
  cancel : i18next.t('cancel'),
  cancelClasses: ['button', 'btn', 'btn-danger', 'mt-2', 'ml-1'],
  inputClasses: ['form-control'],
  fun: async (value, original) => {
    const resp = await ApiC.patch(`${entity.type}/${entity.id}/${Model.Comment}/${original.dataset.id}`, {'comment': value});
    const json = await resp.json();
    // we reload all so the edition date is also reloaded
    reloadElements(['commentsDiv']).then(() => {
      malleableComments.listen();
      relativeMoment();
    });
    return json.comment;
  },
  inputType: InputType.Textarea,
  listenOn: '.comment.editable',
  returnedValueIsTrustedHtml: false,
  submit : i18next.t('save'),
  submitClasses: ['button', 'btn', 'btn-primary', 'mt-2'],
  tooltip: i18next.t('click-to-edit'),
});

const clickHandler = (event: Event) => {
  const el = (event.target as HTMLElement);
  // CREATE COMMENT
  if (el.matches('[data-action="create-comment"]')) {
    const content = (document.getElementById('commentsCreateArea') as HTMLTextAreaElement).value;
    ApiC.post(`${entity.type}/${entity.id}/${Model.Comment}`, {comment: content}).then(() => {
      reloadElements(['commentsDiv']).then(() => {
        malleableComments.listen();
        relativeMoment();
      });
    });

  // DESTROY COMMENT
  } else if (el.matches('[data-action="destroy-comment"]')) {
    if (confirm(i18next.t('generic-delete-warning'))) {
      ApiC.delete(`${entity.type}/${entity.id}/${Model.Comment}/${el.dataset.id}`).then(() => el.parentElement.parentElement.remove());
    }
  // RESTORE ENTITY
  } else if (el.matches('[data-action="restore-entity"]')) {
    ApiC.patch(`${entity.type}/${entity.id}`, { action: Action.Restore })
      .then(() => window.location.href = `?mode=view&id=${entity.id}`);
  }
};

const mode = new URLSearchParams(window.location.search).get('mode');
if (mode === 'view') {
  // add the title in the page name (see #324)
  document.title = document.getElementById('documentTitle').textContent + ' - eLabFTW';

  const core = document.getElementById('core');
  const isAnon = core ? JSON.parse(core.textContent!).isAnon === true : true;

  if (!isAnon) {
    document.querySelector('.real-container').addEventListener('click', event => clickHandler(event));
    // listen on existing comments
    malleableComments.listen();
  }
}
