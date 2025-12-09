/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import $ from 'jquery';
import { ApiC } from './api';
import { entity } from './getEntity';
import { on } from './handlers';
import i18next from './i18n';
import { Action, Model } from './interfaces';
import { collectForm, relativeMoment, reloadElements } from './misc';
import { notify } from './notify';

if (document.getElementById('topToolbar')) {
  on(Action.Duplicate, () => {
    const copyFiles = (document.getElementById('duplicateKeepFilesSelect') as HTMLInputElement);
    const linkToOriginalExperiment = (document.getElementById('duplicateLinkToOriginal') as HTMLInputElement);
    // Ensure the link to original exists because this feature is not available for Template entities
    ApiC.post2location(`${entity.type}/${entity.id}`, {
      action: Action.Duplicate,
      copyFiles: Boolean(copyFiles.checked),
      linkToOriginal: Boolean(linkToOriginalExperiment?.checked ?? false)},
    ).then(id => {
      window.location.href = `?mode=edit&id=${id}`;
    });
  });

  on(Action.Timestamp, () => {
    ApiC.patch(`${entity.type}/${entity.id}`, {action: Action.Timestamp}).then(() => {
      reloadElements(['requestActionsDiv', 'isTimestampedByInfoDiv']);
    }).catch(error => {
      notify.error(error);
    });
  });

  on(Action.Bloxberg, () => {
    const overlay = document.createElement('div');
    overlay.id = 'loadingOverlay';
    const loading = document.createElement('p');
    const ring = document.createElement('div');
    ring.classList.add('lds-dual-ring');
    // see https://loading.io/css/
    const emptyDiv = document.createElement('div');
    ring.appendChild(emptyDiv);
    ring.appendChild(emptyDiv);
    ring.appendChild(emptyDiv);
    ring.appendChild(emptyDiv);
    overlay.classList.add('full-screen-overlay');
    loading.appendChild(ring);
    overlay.appendChild(loading);
    document.getElementById('container').append(overlay);
    ApiC.patch(`${entity.type}/${entity.id}`, {action: Action.Bloxberg})
      // reload uploaded files on success
      .then(() => reloadElements(['uploadsDiv']))
      // remove overlay in all cases
      .finally(() => document.getElementById('container').removeChild(document.getElementById('loadingOverlay')));
  });

  on(Action.Sign, (_, event: Event) => {
    event.preventDefault();
    const form = document.getElementById('sigPassphraseForm') as HTMLFormElement;
    const params = collectForm(form);
    params['action'] = Action.Sign;
    ApiC.patch(`${entity.type}/${entity.id}`, params).then(() => {
      reloadElements(['commentsDiv', 'requestActionsDiv']);
      form.reset();
      $('#addSignatureModal').modal('hide');
    });
  });

  on(Action.RequestAction, () => {
    const actionSelect = (document.getElementById('requestActionActionSelect') as HTMLSelectElement);
    const userSelect = (document.getElementById('requestActionUserSelect') as HTMLSelectElement);
    ApiC.post(`${entity.type}/${entity.id}/request_actions`, {
      action: Action.Create,
      target_action: actionSelect.value,
      target_userid: parseInt(userSelect.value.split(' ')[0], 10),
    }).then(() => reloadElements(['requestActionsDiv']))
      .then(() => relativeMoment())
      // the request gets rejected if repeated
      .catch(error => console.error(error.message));
  });

  on('do-requestable-action', (el: HTMLElement) => {
    switch (el.dataset.target) {
    case Action.Archive:
      // reload the page to avoid further actions on the entity (in edit mode), also refreshing gets to "You cannot edit it!" page. (See #5552)
      ApiC.patch(`${entity.type}/${entity.id}`, {action: Action.Archive})
        .then(() => window.location.href = `?mode=view&id=${entity.id}`);
      break;
    case Action.Unarchive:
      ApiC.patch(`${entity.type}/${entity.id}`, {action: Action.Unarchive})
        .then(() => window.location.href = `?mode=view&id=${entity.id}`);
      break;
    case Action.Lock:
      // reload the page to change the icon and make the edit button disappear (#1897)
      ApiC.patch(`${entity.type}/${entity.id}`, {action: Action.Lock})
        .then(() => window.location.href = `?mode=view&id=${entity.id}`);
      break;
    case Action.Review:
      ApiC.patch(`${entity.type}/${entity.id}`, {action: Action.Review})
        .then(() => window.location.href = `?mode=view&id=${entity.id}`);
      break;
    case Action.Timestamp:
      $('#timestampModal').modal('toggle');
      break;
    case Action.Sign:
      $('#addSignatureModal').modal('toggle');
      break;
    }
  });

  on(Action.CancelRequestableAction, (el: HTMLElement) => {
    if (confirm(i18next.t('generic-delete-warning'))) {
      ApiC.delete(`${entity.type}/${entity.id}/request_actions/${el.dataset.id}`)
        .then(() => el.parentElement.parentElement.parentElement.parentElement.remove());
    }
  });

  on('export-to', (el: HTMLElement) => {
    const format = el.dataset.format;
    const changelog = (document.getElementById(`${format}_exportWithChangelog`) as HTMLInputElement).checked ? 1 : 0;
    const classification = (document.getElementById(`${format}_exportClassification`) as HTMLSelectElement).value;
    let json = 0;
    if (format === 'zip') {
      json = (document.getElementById(`${format}_exportJson`) as HTMLInputElement).checked ? 1 : 0;
    }
    const finalFormat = (document.getElementById(`${format}_exportPdfa`) as HTMLInputElement).checked ? format + 'a' : format;
    window.open(`/api/v2/${el.dataset.type}/${el.dataset.id}?format=${finalFormat}&changelog=${changelog}&json=${json}&classification=${classification}`, '_blank');
  });

  on('export-to-qrpng', (el: HTMLElement) => {
    const size = (document.getElementById('qrpng_exportSize') as HTMLInputElement).value;
    const title = (document.getElementById('qrpng_exportTitle') as HTMLInputElement).checked ? 1: 0;
    const titleLines = (document.getElementById('qrpng_exportTitleLines') as HTMLInputElement).value;
    const titleChars = (document.getElementById('qrpng_exportTitleChars') as HTMLInputElement).value;
    window.open(`/api/v2/${el.dataset.type}/${el.dataset.id}?format=qrpng&size=${size}&withTitle=${title}&titleLines=${titleLines}&titleChars=${titleChars}`, '_blank');
  });

  on(Action.Destroy, () => {
    if (confirm(i18next.t('generic-delete-warning'))) {
      const path = window.location.pathname;
      ApiC.delete(`${entity.type}/${entity.id}`).then(
        () => window.location.replace(path.split('/').pop()));
    }
  });

  on(Action.CreateProcurementRequest, () => {
    const input = (document.getElementById('procurementRequestQtyInput') as HTMLInputElement);
    const qty = parseInt(input.value, 10);
    // sanity check
    if (qty < 1) {
      notify.error('invalid-info');
      return;
    }
    ApiC.post(`${Model.Team}/current/procurement_requests`, {entity_id: entity.id, qty_ordered: qty});
  });
}
