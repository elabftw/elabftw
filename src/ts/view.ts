/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import i18next from './i18n';
import { InputType, Malle } from '@deltablot/malle';
import { ApiC } from './api';
import { relativeMoment, reloadElements } from './misc';
import { Action, Model } from './interfaces';
import { entity } from './getEntity';
import { on } from './handlers';
import { core } from './core';

const mode = new URLSearchParams(window.location.search).get('mode');
if (mode === 'view') {
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

  on('create-comment', () => {
    const content = (document.getElementById('commentsCreateArea') as HTMLTextAreaElement).value;
    ApiC.post(`${entity.type}/${entity.id}/${Model.Comment}`, {comment: content}).then(() => {
      reloadElements(['commentsDiv']).then(() => {
        malleableComments.listen();
        relativeMoment();
      });
    });
  });

  on('destroy-comment', (el: HTMLElement) => {
    if (confirm(i18next.t('generic-delete-warning'))) {
      ApiC.delete(`${entity.type}/${entity.id}/${Model.Comment}/${el.dataset.id}`).then(() => el.parentElement.parentElement.remove());
    }
  });

  on('restore-entity', () => {
    ApiC.patch(`${entity.type}/${entity.id}`, { action: Action.Restore })
      .then(() => window.location.href = `?mode=view&id=${entity.id}`);
  });

  on('override-exclusive-edit-lock', () => {
    ApiC.patch(`${entity.type}/${entity.id}`, { action: Action.RemoveExclusiveEditMode })
      .then(() => window.location.href = `?mode=view&id=${entity.id}`);
  });

  // add the title in the page name (see #324)
  document.title = document.getElementById('documentTitle').textContent + ' - eLabFTW';

  if (!core.isAnon) {
    // listen on existing comments
    malleableComments.listen();
  }
}
