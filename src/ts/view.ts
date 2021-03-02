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
import { BoundEvent, ResponseMsg } from './interfaces';
declare let key: any;
const moment = require('moment'); // eslint-disable-line @typescript-eslint/no-var-requires

document.addEventListener('DOMContentLoaded', () => {

  // holds info about the page through data attributes
  const about = document.getElementById('info').dataset;

  // only run in view mode
  if (about.page !== 'view') {
    return;
  }

  // add the title in the page name (see #324)
  document.title = document.querySelector('.title-view').textContent + ' - eLabFTW';

  const type = about.type;
  const id = about.id;

  const AjaxC = new Ajax(type, id);

  // add extra fields elements from metadata json
  const MetadataC = new Metadata(type, id);
  MetadataC.display('view');

  // EDIT SHORTCUT
  key(about.scedit, () => window.location.href = `?mode=edit&id=${id}`);

  // Add click listener and do action based on which element is clicked
  document.querySelector('.real-container').addEventListener('click', (event) => {
    const el = (event.target as HTMLElement);
    // DUPLICATE
    if (el.matches('[data-action="duplicate"]')) {
      AjaxC.post('duplicate').then(json => window.location.replace(`?mode=edit&id=${json.msg}`));

    // EDIT
    } else if (el.matches('[data-action="edit"]')) {
      window.location.href = `?mode=edit&id=${id}`;

    // TOGGLE LOCK
    } else if (el.matches('[data-action="lock"]')) {
      // reload the page to change the icon and make the edit button disappear (#1897)
      AjaxC.post('lock').then(() => window.location.href = `?mode=view&id=${id}`);

    // SEE EVENTS
    } else if (el.matches('[data-action="see-events"]')) {
      AjaxC.get('getBoundEvents').then(json => {
        const bookingsDiv = document.getElementById('boundBookings');
        for (const msg of (json.msg as Array<BoundEvent>)) {
          const el = document.createElement('a');
          el.href = `team.php?item=${msg.item}&start=${encodeURIComponent(msg.start)}`;
          const button = document.createElement('button');
          button.classList.add('mr-2', 'btn', 'btn-neutral', 'relative-moment');
          button.innerText = moment(msg.start).fromNow();
          el.appendChild(button);
          bookingsDiv.append(el);
        }
      });

    // SHARE
    } else if (el.matches('[data-action="share"]')) {
      AjaxC.get('getShareLink').then(json => {
        const link = (document.getElementById('shareLinkInput') as HTMLInputElement);
        link.value = (json.msg as string);
        link.style.display = 'inline';
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
      AjaxC.post('timestamp').then(() => window.location.replace(`experiments.php?mode=view&id=${id}`));
    }
  });
});
