/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import $ from 'jquery';
import { Action as MalleAction, Malle } from '@deltablot/malle';
import * as $3Dmol from '3dmol';
import '@fancyapps/fancybox/dist/jquery.fancybox.js';
import { Action, Model } from './interfaces';
import { getEntity, relativeMoment, reloadElements } from './misc';
import { displayPlasmidViewer } from './ove';
import i18next from 'i18next';
import { Api } from './Apiv2.class';
import { marked } from 'marked';
import Prism from 'prismjs';
import { Uploader } from './uploader';

document.addEventListener('DOMContentLoaded', () => {
  // holds info about the page through data attributes
  const about = document.getElementById('info')?.dataset;
  if (typeof about === 'undefined') {
    return;
  }

  const pages = ['edit', 'view', 'template-edit'];
  if (!pages.includes(about.page)) {
    return;
  }

  displayPlasmidViewer(about);
  const entity = getEntity();
  const ApiC = new Api();

  // make file comments editable
  const malleableFilecomment = new Malle({
    formClasses: ['d-inline-flex'],
    fun: async (value, original) => {
      const uploadid = parseInt(original.dataset.id, 10);
      return ApiC.patch(`${entity.type}/${entity.id}/${Model.Upload}/${uploadid}`, {'comment': value})
        .then(resp => resp.json()).then(json => json.comment);
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
    returnedValueIsTrustedHtml: false,
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

  document.querySelector('.real-container').addEventListener('click', async (event) => {
    const el = (event.target as HTMLElement);
    // RENAME UPLOAD
    if (el.matches('[data-action="rename-upload"]')) {
      // find the corresponding filename element
      // we replace the parent span to also remove the link for download
      const filenameLink = document.getElementById('upload-filename_' + el.dataset.id);
      const filenameInput = document.createElement('input');
      filenameInput.dataset.id = el.dataset.id;
      filenameInput.classList.add('form-control');
      filenameInput.value = filenameLink.textContent;
      const parentSpan = filenameLink.parentElement;
      parentSpan.classList.add('form-inline');
      filenameInput.addEventListener('blur', event => {
        processNewFilename(event, filenameLink, parentSpan);
      });
      filenameInput.addEventListener('keypress', event => {
        processNewFilename(event, filenameLink, parentSpan);
      });
      filenameLink.replaceWith(filenameInput);
      filenameInput.focus();

    // TOGGLE DISPLAY
    } else if (el.matches('[data-action="toggle-uploads-layout"]')) {
      ApiC.notifOnSaved = false;
      ApiC.patch(`${Model.User}/me`, {'uploads_layout': el.dataset.targetLayout})
        .then(() => reloadElements(['uploadsDiv', 'uploadsViewToggler']));

    // SHOW CONTENT OF TEXT FILES, MARKDOWN OR JSON
    } else if (el.matches('[data-action="toggle-modal"][data-target="plainTextModal"]')) {
      // set the title for modal window
      document.getElementById('plainTextModalLabel').textContent = el.dataset.name;
      // get the file content
      const response = await fetch(`app/download.php?storage=${el.dataset.storage}&f=${el.dataset.path}`);
      const plainTextContentDiv = document.getElementById('plainTextContentDiv');
      if (el.dataset.ext === 'md') {
        plainTextContentDiv.innerHTML = await marked(await response.text());
      } else if (el.dataset.ext === 'json') {
        const preBlock = document.createElement('pre');
        preBlock.classList.add('language-json');
        const codeBlock = document.createElement('code');
        codeBlock.classList.add('language-json');
        preBlock.appendChild(codeBlock);
        response.json().then(content => {
          // use prismjs to display highlighted pretty-printed json content
          codeBlock.innerHTML = `${Prism.highlight(JSON.stringify(content, null, 2), Prism.languages.json, 'json')}`;
          // make sure to blank any previous content before appending
          plainTextContentDiv.innerHTML = '';
          plainTextContentDiv.appendChild(preBlock);
        });
      } else { // TXT
        response.text().then(content => plainTextContentDiv.innerText = content);
      }

    // TOGGLE SHOW ARCHIVED
    } else if (el.matches('[data-action="toggle-uploads-show-archived"]')) {
      const url = new URL(window.location.href);
      const queryParams = new URLSearchParams(url.search);

      // toggle "archived" query parameter
      if (queryParams.has('archived')) {
        queryParams.delete('archived');
      } else {
        queryParams.set('archived', 'on');
      }

      // Update the query parameters in the URL
      url.search = queryParams.toString();
      url.hash = 'filesdiv';
      const modifiedUrl = url.toString();
      window.location.replace(modifiedUrl);

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
      ApiC.post(`${entity.type}/${entity.id}/${Model.Upload}`, params)
        .then(() => reloadElements(['uploadsDiv']));

    // CHANGE 3DMOL FILES VISUALIZATION STYLE
    } else if (el.matches('[data-action="set-3dmol-style"]')) {
      const targetStyle = el.dataset.style;
      let options = {};
      const style = {};
      if (targetStyle === 'cartoon') {
        options = { color: 'spectrum' };
      }
      style[targetStyle] = options;

      $3Dmol.viewers[el.dataset.divid].setStyle(style).render();

    // ARCHIVE UPLOAD
    } else if (el.matches('[data-action="archive-upload"]')) {
      const uploadid = parseInt(el.dataset.uploadid, 10);
      ApiC.patch(`${entity.type}/${entity.id}/${Model.Upload}/${uploadid}`, {action: Action.Archive})
        .then(() => reloadElements(['uploadsDiv']));

    // DESTROY UPLOAD
    } else if (el.matches('[data-action="destroy-upload"]')) {
      const uploadid = parseInt(el.dataset.uploadid, 10);
      if (confirm(i18next.t('generic-delete-warning'))) {
        ApiC.delete(`${entity.type}/${entity.id}/${Model.Upload}/${uploadid}`)
          .then(() => document.getElementById(`uploadDiv_${uploadid}`).remove());
      }
    }
  });

  // ACTIVATE FANCYBOX
  $('[data-fancybox]').fancybox();

  const uploadsDiv = document.getElementById('uploadsDiv');
  if (uploadsDiv) {
    new MutationObserver(() => {
      $3Dmol.autoload();
      displayPlasmidViewer(about);
      malleableFilecomment.listen();
      if (['edit', 'template-edit'].includes(about.page)) {
        (new Uploader()).init();
      }
      relativeMoment();
      // don't use option {subtree: true} or there is an infinite loop that will destroy the world
    }).observe(uploadsDiv, {childList: true});
  }
});
