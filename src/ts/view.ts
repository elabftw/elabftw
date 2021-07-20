/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import 'jquery-jeditable/src/jquery.jeditable.js';
import { Metadata } from './Metadata.class';
import { Ajax } from './Ajax.class';
import { getEntity } from './misc';
import { BoundEvent, Payload, Method, Action, Target } from './interfaces';
import { DateTime } from 'luxon';
import EntityClass from './Entity.class';
declare let key: any;

document.addEventListener('DOMContentLoaded', () => {

  if (!document.getElementById('info')) {
    return;
  }
  // holds info about the page through data attributes
  const about = document.getElementById('info').dataset;

  // only run in view mode
  if (about.page !== 'view') {
    return;
  }

  // add the title in the page name (see #324)
  document.title = document.querySelector('.title-view').textContent + ' - eLabFTW';

  const entity = getEntity();
  const EntityC = new EntityClass(entity.type);

  // DEPRECATED, use EntityC
  const AjaxC = new Ajax(entity.type, String(entity.id));

  // add extra fields elements from metadata json
  const MetadataC = new Metadata(entity);
  MetadataC.display('view');

  // EDIT SHORTCUT
  key(about.scedit, () => window.location.href = `?mode=edit&id=${entity.id}`);

  // Add click listener and do action based on which element is clicked
  document.querySelector('.real-container').addEventListener('click', (event) => {
    const el = (event.target as HTMLElement);
    // DUPLICATE
    if (el.matches('[data-action="duplicate-entity"]')) {
      EntityC.duplicate(entity.id).then((json) => {
        if (json.res) {
          window.location.href = `?mode=edit&id=${json.value}`;
        }
      });

    // EDIT
    } else if (el.matches('[data-action="edit"]')) {
      window.location.href = `?mode=edit&id=${entity.id}`;

    // TOGGLE LOCK
    } else if (el.matches('[data-action="lock-entity"]')) {
      // reload the page to change the icon and make the edit button disappear (#1897)
      EntityC.lock(entity.id).then((json) => {
        if (json.res) {
          window.location.href = `?mode=view&id=${entity.id}`;
        }
      });

    // SEE EVENTS
    } else if (el.matches('[data-action="see-events"]')) {
      const payload: Payload = {
        method: Method.GET,
        action: Action.Read,
        entity: entity,
        model: entity.type,
        target: Target.BoundEvent
      };
      AjaxC.send(payload).then(json => {
        const bookingsDiv = document.getElementById('boundBookings');
        for (const msg of (json.value as Array<BoundEvent>)) {
          const el = document.createElement('a');
          el.href = `team.php?item=${msg.item}&start=${encodeURIComponent(msg.start)}`;
          const button = document.createElement('button');
          button.classList.add('mr-2', 'btn', 'btn-neutral', 'relative-moment');
          const locale = document.getElementById('user-prefs').dataset.jslang;
          button.innerText = DateTime.fromISO(msg.start, {'locale': locale}).toRelative();
          el.appendChild(button);
          bookingsDiv.append(el);
        }
      });

    // SHARE
    } else if (el.matches('[data-action="share"]')) {
      AjaxC.get('getShareLink').then(json => {
        // TODO action read, target sharelink
        const link = (document.getElementById('shareLinkInput') as HTMLInputElement);
        link.value = (json.msg as string);
        link.hidden = false;
        link.focus();
        link.select();
      });

    // TOGGLE PINNED
    } else if (el.matches('[data-action="pin"]')) {
      AjaxC.post('togglePin').then(() => el.querySelector('svg').classList.toggle('grayed-out'));

    // TIMESTAMP button in modal
    } else if (el.matches('[data-action="timestamp"]')) {
      // prevent double click
      (event.target as HTMLButtonElement).disabled = true;
      AjaxC.post('timestamp').then(() => window.location.replace(`experiments.php?mode=view&id=${entity.id}`));

    // BLOXBERG
    } else if (el.matches('[data-action="bloxberg"]')) {
      const overlay = document.createElement('div');
      const loading = document.createElement('p');
      const ring = document.createElement('div');
      ring.classList.add('lds-ring');
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
      AjaxC.post('bloxberg').then(() => window.location.replace(`?mode=view&id=${entity.id}`));
    }
  });
});
