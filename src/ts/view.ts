/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import i18next from 'i18next';
import { InputType, Malle } from '@deltablot/malle';
import { Metadata } from './Metadata.class';
import { Api } from './Apiv2.class';
import { getEntity, updateCategory, relativeMoment, reloadElement, showContentPlainText } from './misc';
import { EntityType, Action, Model } from './interfaces';
import { DateTime } from 'luxon';
import EntityClass from './Entity.class';
declare let key: any; // eslint-disable-line @typescript-eslint/no-explicit-any

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
  document.title = document.getElementById('documentTitle').textContent + ' - eLabFTW';

  const entity = getEntity();
  const EntityC = new EntityClass(entity.type);
  const ApiC = new Api();

  // add extra fields elements from metadata json
  const MetadataC = new Metadata(entity);
  MetadataC.display('view').then(() => {
    // go over all the type: url elements and create a link dynamically
    document.querySelectorAll('[data-gen-link="true"]').forEach(el => {
      const link = document.createElement('a');
      const url = (el as HTMLSpanElement).innerText;
      link.href = url;
      link.text = url;
      el.replaceWith(link);
    });
  });

  // EDIT SHORTCUT
  key(about.scedit, () => window.location.href = `?mode=edit&id=${entity.id}`);

  // Add click listener and do action based on which element is clicked
  document.querySelector('.real-container').addEventListener('click', (event) => {
    const el = (event.target as HTMLElement);
    // DUPLICATE
    if (el.matches('[data-action="duplicate-entity"]')) {
      EntityC.duplicate(entity.id).then(resp => window.location.href = `?mode=edit&id=${resp.headers.get('location').split('/').pop()}`);

    // EDIT
    } else if (el.matches('[data-action="edit"]')) {
      window.location.href = `?mode=edit&id=${entity.id}`;

    // TOGGLE LOCK
    } else if (el.matches('[data-action="lock-entity"]')) {
      // reload the page to change the icon and make the edit button disappear (#1897)
      EntityC.lock(entity.id).then(() => window.location.href = `?mode=view&id=${entity.id}`);

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

    // SHARE
    } else if (el.matches('[data-action="share"]')) {
      EntityC.read(entity.id).then(json => {
        const link = (document.getElementById('shareLinkInput') as HTMLInputElement);
        link.value = json.sharelink;
        link.hidden = false;
        link.focus();
        link.select();
      });

    // TOGGLE PINNED
    } else if (el.matches('[data-action="toggle-pin"]')) {
      EntityC.pin(entity.id).then(() => document.getElementById('toggle-pin-icon').classList.toggle('grayed-out'));

    // TIMESTAMP button in modal
    } else if (el.matches('[data-action="timestamp"]')) {
      // prevent double click
      (event.target as HTMLButtonElement).disabled = true;
      EntityC.timestamp(entity.id).then(() => window.location.replace(`experiments.php?mode=view&id=${entity.id}`));

    // BLOXBERG
    } else if (el.matches('[data-action="bloxberg"]')) {
      const overlay = document.createElement('div');
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
      ApiC.patch(`${EntityType.Experiment}/${entity.id}`, {'action': Action.Bloxberg}).then(() => window.location.replace(`?mode=view&id=${entity.id}`));

    // SHOW CONTENT OF PLAIN TEXT FILES
    } else if (el.matches('[data-action="show-plain-text"]')) {
      showContentPlainText(el);
    // CREATE COMMENT
    } else if (el.matches('[data-action="create-comment"]')) {
      const content = (document.getElementById('commentsCreateArea') as HTMLTextAreaElement).value;
      ApiC.post(`${entity.type}/${entity.id}/${Model.Comment}`, {'comment': content}).then(() => reloadElement('commentsDiv'));

    // DESTROY COMMENT
    } else if (el.matches('[data-action="destroy-comment"]')) {
      if (confirm(i18next.t('generic-delete-warning'))) {
        ApiC.delete(`${entity.type}/${entity.id}/${Model.Comment}/${el.dataset.id}`).then(() => reloadElement('commentsDiv'));
      }
    }
  });

  // UPDATE MALLEABLE COMMENT
  const malleableComments = new Malle({
    cancel : i18next.t('cancel'),
    cancelClasses: ['button', 'btn', 'btn-danger', 'mt-2', 'ml-1'],
    inputClasses: ['form-control'],
    fun: (value, original) => {
      ApiC.patch(`${entity.type}/${entity.id}/${Model.Comment}/${original.dataset.id}`, {'comment': value}).then(() => reloadElement('commentsDiv'));
      return value;
    },
    inputType: InputType.Textarea,
    listenOn: '.comment.editable',
    submit : i18next.t('save'),
    submitClasses: ['button', 'btn', 'btn-primary', 'mt-2'],
    tooltip: i18next.t('click-to-edit'),
  });

  // UPDATE MALLEABLE CATEGORY
  let category;
  // TODO make it so it calls only on trigger!
  if (entity.type === EntityType.Experiment) {
    category = ApiC.getJson(`${Model.Team}/${about.team}/status`).then(json => Array.from(json));
  } else {
    category = ApiC.getJson(`${EntityType.ItemType}`).then(json => Array.from(json));
  }
  const malleableCategory = new Malle({
    cancel : i18next.t('cancel'),
    cancelClasses: ['button', 'btn', 'btn-danger', 'mt-2', 'ml-1'],
    inputClasses: ['form-control'],
    fun: value => updateCategory(entity, value),
    inputType: InputType.Select,
    selectOptionsValueKey: 'category_id',
    selectOptionsTextKey: 'category',
    selectOptions: category.then(categoryArr => categoryArr),
    listenOn: '.malleableCategory',
    submit : i18next.t('save'),
    submitClasses: ['button', 'btn', 'btn-primary', 'mt-2'],
    tooltip: i18next.t('click-to-edit'),
  });

  // listen on existing comments
  malleableComments.listen();
  malleableCategory.listen();

  new MutationObserver(() => {
    malleableCategory.listen();
  }).observe(document.getElementById('main_section'), {childList: true});

  // add an observer so new comments will get an event handler too
  new MutationObserver(() => {
    malleableComments.listen();
    relativeMoment();
  }).observe(document.getElementById('commentsDiv'), {childList: true});
  // END COMMENTS
});
