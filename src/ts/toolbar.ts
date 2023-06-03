/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import { getEntity, reloadElement } from './misc';
import { Api } from './Apiv2.class';
import EntityClass from './Entity.class';
import i18next from 'i18next';
import { DateTime } from 'luxon';
import { Action } from './interfaces';

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
      if (about.page.startsWith('template-')) {
        queryString = 'tab=3&template';
      }
      EntityC.duplicate(entity.id).then(resp => window.location.href = `?mode=edit&${queryString}id=${resp.headers.get('location').split('/').pop()}`);

    // TOGGLE LOCK
    } else if (el.matches('[data-action="lock-entity"]')) {
      // reload the page to change the icon and make the edit button disappear (#1897)
      EntityC.lock(entity.id).then(() => window.location.href = `?mode=view&id=${entity.id}`);

    // SHARE
    } else if (el.matches('[data-action="share"]')) {
      EntityC.read(entity.id).then(json => {
        const link = (document.getElementById('shareLinkInput') as HTMLInputElement);
        link.value = json.sharelink;
        link.toggleAttribute('hidden');
        link.focus();
        link.select();
      });
    // SEE EVENTS
    } else if (el.matches('[data-action="see-events"]')) {
      EntityC.read(entity.id).then(json => {
        const eventId = json.events_id;
        // now read the event info
        ApiC.getJson(`event/${eventId}`).then(json => {
          const bookingsDiv = document.getElementById('boundBookings');
          const el = document.createElement('a');
          el.href = `team.php?item=${json.item}&start=${encodeURIComponent(json.start)}`;
          const button = document.createElement('button');
          button.classList.add('mr-2', 'btn', 'btn-neutral', 'relative-moment');
          const locale = document.getElementById('user-prefs').dataset.jslang;
          button.innerText = DateTime.fromISO(json.start, {'locale': locale}).toRelative();
          el.appendChild(button);
          bookingsDiv.replaceChildren(el);
        });
      });

    // TOGGLE PINNED
    } else if (el.matches('[data-action="toggle-pin"]')) {
      ApiC.patch(`${entity.type}/${entity.id}`, {'action': Action.Pin}).then(() => {
        document.getElementById('toggle-pin-icon').classList.toggle('color-weak');
        ['bgnd-gray', 'hl-hover-gray'].forEach(cl => document.getElementById('toggle-pin-icon-div').classList.toggle(cl));
      });

    // TIMESTAMP button in modal
    } else if (el.matches('[data-action="timestamp"]')) {
      // prevent double click
      (event.target as HTMLButtonElement).disabled = true;
      EntityC.timestamp(entity.id).then(() => {
        $('#timestampModal').modal('toggle');
        reloadElement('uploadsDiv');
      });

    // BLOXBERG
    } else if (el.matches('[data-action="bloxberg"]')) {
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
        .then(() => reloadElement('filesdiv'))
        // remove overlay in all cases
        .finally(() => document.getElementById('container').removeChild(document.getElementById('loadingOverlay')));

    // DESTROY ENTITY
    } else if (el.matches('[data-action="destroy"]')) {
      if (confirm(i18next.t('generic-delete-warning'))) {
        const path = window.location.pathname;
        EntityC.destroy(entity.id).then(() => window.location.replace(path.split('/').pop()));
      }
    }
  });
});
