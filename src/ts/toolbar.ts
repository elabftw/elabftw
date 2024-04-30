/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import { getEntity, reloadElement, reloadElements, relativeMoment, notifError } from './misc';
import { Api } from './Apiv2.class';
import EntityClass from './Entity.class';
import i18next from 'i18next';
import $ from 'jquery';
import { Action, Model } from './interfaces';

document.addEventListener('DOMContentLoaded', () => {

  if (!document.getElementById('info')) {
    return;
  }

  // holds info about the page through data attributes
  const about = document.getElementById('info').dataset;

  // only run in view/edit mode
  const allowedPages = ['view', 'edit', 'template-view', 'template-edit'];
  if (!allowedPages.includes(about.page)) {
    return;
  }

  const entity = getEntity();
  const EntityC = new EntityClass(entity.type);
  const ApiC = new Api();

  // Add click listener and do action based on which element is clicked
  document.querySelector('.real-container').addEventListener('click', (event) => {
    const el = (event.target as HTMLElement);
    // DUPLICATE
    if (el.matches('[data-action="duplicate-entity"]')) {
      let queryString = '';
      let page = '';
      if (about.page.startsWith('template-')) {
        queryString = 'tab=3&template';
        page = '/ucp.php';
      }

      EntityC.duplicate(entity.id).then(resp => window.location.href = `${page}?mode=edit&${queryString}id=${resp.headers.get('location').split('/').pop()}`);

    // SHARE
    } else if (el.matches('[data-action="share"]')) {
      EntityC.read(entity.id).then(json => {
        const link = (document.getElementById('shareLinkInput') as HTMLInputElement);
        link.value = json.sharelink;
        link.toggleAttribute('hidden');
        link.focus();
        link.select();
      });

    // TOGGLE PINNED
    } else if (el.matches('[data-action="toggle-pin"]')) {
      let id = entity.id;
      if (isNaN(id) || id === null) {
        id = parseInt(el.dataset.id, 10);
      }

      ApiC.patch(`${entity.type}/${id}`, {'action': Action.Pin}).then(() => {
        // toggle appearance of button and icon
        ['bgnd-gray', 'hl-hover-gray'].forEach(cl => el.classList.toggle(cl));
        el.querySelector('i').classList.toggle('color-weak');
      });

    // TIMESTAMP button in modal
    } else if (el.matches(`[data-action="${Action.Timestamp}"]`)) {
      EntityC.patchAction(entity.id, Action.Timestamp).then(() => {
        reloadElements(['requestActionsDiv', 'isTimestampedByInfoDiv']);
      });

    // BLOXBERG
    } else if (el.matches(`[data-action="${Action.Bloxberg}"]`)) {
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
      ApiC.patch(`${entity.type}/${entity.id}`, {'action': Action.Bloxberg})
        // reload uploaded files on success
        .then(() => reloadElement('uploadsDiv'))
        // remove overlay in all cases
        .finally(() => document.getElementById('container').removeChild(document.getElementById('loadingOverlay')));

    // SIGN ENTITY
    } else if (el.matches('[data-action="sign-entity"]')) {
      const passphraseInput = (document.getElementById('sigPassphraseInput') as HTMLInputElement);
      const meaningSelect = (document.getElementById('sigMeaningSelect') as HTMLSelectElement);
      ApiC.patch(`${entity.type}/${entity.id}`, {action: Action.Sign, passphrase: passphraseInput.value, meaning: meaningSelect.value}).then(() => {
        // using reloadElements here doesn't work for relativeMoment
        reloadElement('commentsDiv').then(() => {
          relativeMoment();
          reloadElement('requestActionsDiv');
        });
      });
    // REQUEST ACTION
    } else if (el.matches('[data-action="request-action"]')) {
      const actionSelect = (document.getElementById('requestActionActionSelect') as HTMLSelectElement);
      const userSelect = (document.getElementById('requestActionUserSelect') as HTMLSelectElement);
      ApiC.post(`${entity.type}/${entity.id}/request_actions`, {
        action: Action.Create,
        target_action: actionSelect.value,
        target_userid: userSelect.value,
      }).then(() => reloadElement('requestActionsDiv'));
    // SHOW ACTION
    } else if (el.matches('[data-action="show-action"]')) {
      const btn = document.getElementById(`actionButton-${el.dataset.target}`);
      btn.classList.add('border-danger');
    // CREATE PROCUREMENT REQUEST
    } else if (el.matches('[data-action="create-procurement-request"]')) {
      const input = (document.getElementById('procurementRequestQtyInput') as HTMLInputElement);
      const qty = parseInt(input.value, 10);
      // sanity check
      if (qty < 1) {
        notifError(new Error('Invalid quantity!'));
        return;
      }
      ApiC.post(`${Model.Team}/current/procurement_requests`, {entity_id: entity.id, qty_ordered: qty});

    // DO REQUEST ACTION
    } else if (el.matches('[data-action="do-requestable-action"]')) {
      switch (el.dataset.target.toLowerCase()) {
      case Action.Archive:
        ApiC.patch(`${entity.type}/${entity.id}`, {action: Action.Archive}).then(() => reloadElement('isArchivedDiv'));
        break;
      case Action.Lock:
        // reload the page to change the icon and make the edit button disappear (#1897)
        EntityC.patchAction(entity.id, Action.Lock).then(() => window.location.href = `?mode=view&id=${entity.id}`);
        break;
      case Action.Timestamp:
        $('#timestampModal').modal('toggle');
        break;
      case Action.Sign:
        $('#addSignatureModal').modal('toggle');
        break;
      }
      reloadElements(['commentsDiv', 'requestActionsDiv']).then(() => {
        relativeMoment();
      });
    // CANCEL REQUEST ACTION
    } else if (el.matches('[data-action="cancel-requestable-action"]')) {
      if (confirm(i18next.t('generic-delete-warning'))) {
        ApiC.delete(`${entity.type}/${entity.id}/request_actions/${el.dataset.id}`).then(() => el.parentElement.parentElement.parentElement.parentElement.remove());
      }

    // DESTROY ENTITY
    } else if (el.matches('[data-action="destroy"]')) {
      if (confirm(i18next.t('generic-delete-warning'))) {
        const path = window.location.pathname;
        EntityC.destroy(entity.id).then(() => window.location.replace(path.split('/').pop()));
      }
    }
  });
});
