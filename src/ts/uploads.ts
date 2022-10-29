/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import $ from 'jquery';
import { Action as MalleAction, Malle } from '@deltablot/malle';
import '@fancyapps/fancybox/dist/jquery.fancybox.js';
import { Action, Model } from './interfaces';
import { displayMolFiles, display3DMolecules, getEntity, reloadElement } from './misc';
import { displayPlasmidViewer } from './ove';
import i18next from 'i18next';
import { Api } from './Apiv2.class';

document.addEventListener('DOMContentLoaded', () => {
  // holds info about the page through data attributes
  const about = document.getElementById('info')?.dataset;
  if (typeof about === 'undefined') {
    return;
  }

  const pages = ['edit', 'view'];
  if (!pages.includes(about.page)) {
    return;
  }

  displayMolFiles();
  display3DMolecules();
  displayPlasmidViewer(about);
  const entity = getEntity();
  const ApiC = new Api();

  // make file comments editable
  const malleableFilecomment = new Malle({
    formClasses: ['d-inline-flex'],
    fun: (value, original) => {
      const uploadid = parseInt(original.dataset.id, 10);
      ApiC.patch(`${entity.type}/${entity.id}/${Model.Upload}/${uploadid}`, {'comment': value});
      return value;
    },
    inputClasses: ['form-control'],
    listenOn: '.file-comment.editable',
    onBlur: MalleAction.Submit,
    onEdit: (original, event, input) => {
      // remove the default text
      // we use a data-isempty attribute so "Click to add comment" can be translated
      if (original.dataset.isempty === '1') {
        input.value = '';
        original.dataset.isempty = '0';
        return true;
      }
    },
    tooltip: i18next.t('upload-file-comment'),
  });
  malleableFilecomment.listen();

  function processNewFilename(event, original: HTMLElement, parent: HTMLElement): void {
    if (event.key === 'Enter' || event.type === 'blur') {
      const newFilename = (event.target as HTMLInputElement).value;
      ApiC.patch(`${entity.type}/${entity.id}/${Model.Upload}/${event.target.dataset.id}`, {'real_name': newFilename}).then(() => {
        event.target.remove();
        // change the link text with the new one
        original.textContent = newFilename;
        parent.prepend(original);
      });
    }
  }

  document.querySelector('.real-container').addEventListener('click', (event) => {
    const el = (event.target as HTMLElement);
    // RENAME UPLOAD
    if (el.matches('[data-action="rename-upload"]')) {
      // find the corresponding filename element
      // we replace the parent span to also remove the link for download
      const filenameLink = document.getElementById('upload-filename_' + el.dataset.id);
      const filenameInput = document.createElement('input');
      filenameInput.dataset.id = el.dataset.id;
      filenameInput.value = filenameLink.textContent;
      const parentSpan = filenameLink.parentElement;
      filenameInput.addEventListener('blur', event => {
        processNewFilename(event, filenameLink, parentSpan);
      });
      filenameInput.addEventListener('keypress', event => {
        processNewFilename(event, filenameLink, parentSpan);
      });
      filenameLink.replaceWith(filenameInput);

    // TOGGLE DISPLAY
    } else if (el.matches('[data-action="toggle-uploads-layout"]')) {
      ApiC.notifOnSaved = false;
      ApiC.patch(`${Model.User}/me`, {'uploads_layout': el.dataset.targetLayout}).then(() => {
        reloadElement('filesdiv');
      });

    // REPLACE UPLOAD
    } else if (el.matches('[data-action="replace-upload"]')) {
      document.getElementById('replaceUploadForm_' + el.dataset.uploadid).hidden = false;

    // MORE INFORMATION
    } else if (el.matches('[data-action="more-info-upload"]')) {
      document.getElementById('moreInfo_' + el.dataset.uploadid).classList.remove('d-none');

    // OPEN IN NMRIUM
    } else if (el.matches('[data-action="open-in-nmrium"]')) {
      ApiC.get(`${entity.type}/${entity.id}/${Model.Upload}/${el.dataset.uploadid}?format=binary`).then(response => {
        response.text().then(content => {
          window.open(`https://www.nmrium.org/nmrium#?rawJcamp=${encodeURIComponent(content)}`, '_blank');
        });
      });

    // SAVE MOL AS PNG
    } else if (el.matches('[data-action="save-mol-as-png"]')) {
      const params = {
        'action': Action.CreateFromString,
        'file_type': 'png',
        'real_name': el.dataset.name + '.png',
        'content': (document.getElementById(el.dataset.canvasid) as HTMLCanvasElement).toDataURL(),
      };
      ApiC.post(`${entity.type}/${entity.id}/${Model.Upload}`, params).then(() => reloadElement('filesdiv'));

    // DESTROY UPLOAD
    } else if (el.matches('[data-action="destroy-upload"]')) {
      const uploadid = parseInt(el.dataset.uploadid, 10);
      if (confirm(i18next.t('generic-delete-warning'))) {
        ApiC.delete(`${entity.type}/${entity.id}/${Model.Upload}/${uploadid}`).then(() => reloadElement('filesdiv'));
      }
    }
  });

  // ACTIVATE FANCYBOX
  $('[data-fancybox]').fancybox();

  // Create an observer instance linked to the callback function(mutationList, observer)
  // Start observing the target node for configured mutations
  new MutationObserver(() => {
    displayMolFiles();
    display3DMolecules(true);
    displayPlasmidViewer(about);
    malleableFilecomment.listen();
  }).observe(document.getElementById('filesdiv'), {childList: true});
});
