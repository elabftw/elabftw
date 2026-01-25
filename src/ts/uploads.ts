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
import { loadInSpreadsheetEditor } from './spreadsheet-utils';
import { ensureTogglableSectionIsOpen, relativeMoment, reloadElements } from './misc';
import DOMPurify from 'dompurify';
import { displayPlasmidViewer } from './ove';
import { displayMoleculeViewer, get3dmol } from './3dmol';
import i18next from './i18n';
import { ApiC } from './api';
import { marked } from 'marked';
import Prism from 'prismjs';
import { Uploader } from './uploader';
import { entity } from './getEntity';
import { read as readXlsx, utils as xlsxUtils } from '@e965/xlsx';
type Cell = string | number | boolean | null;

function processNewFilename(event, original: HTMLElement, parent: HTMLElement): void {
  if (event.key === 'Enter' || event.type === 'blur') {
    const newFilename = (event.target as HTMLInputElement).value;
    ApiC.patch(`${entity.type}/${entity.id}/${Model.Upload}/${event.target.dataset.id}`, {real_name: newFilename}).then(() => {
      event.target.remove();
      // change the link text with the new one
      original.textContent = newFilename;
      parent.prepend(original);
    });
  }
}

async function blob2table(blob: Blob, container: HTMLDivElement, sheetName: string | null = null) {
  let wb;
  if (blob.type.includes('text/csv') || blob.type === '') {
    const csv = await blob.text();
    // type string will read it as UTF-8
    wb = readXlsx(csv, { type: 'string' });
  } else {
    const ab = await blob.arrayBuffer();
    wb = readXlsx(ab, { type: 'array' });
  }
  const ws = wb.Sheets[sheetName || wb.SheetNames[0]];

  // 2D array: rows of values (strings, numbers, booleans, null)
  const rows: Cell[][] = xlsxUtils.sheet_to_json(ws, { header: 1, raw: true, defval: '', blankrows: false });

  const table = document.createElement('table');
  table.classList.add('table');
  const tbody = document.createElement('tbody');

  for (const row of rows) {
    const tr = document.createElement('tr');
    for (const cell of row) {
      const td = document.createElement('td');
      td.textContent = cell == null ? '' : String(cell); // SAFE: no HTML parsing
      tr.appendChild(td);
    }
    tbody.appendChild(tr);
  }
  table.appendChild(tbody);
  container.replaceChildren(table);
}

const clickHandler = async (event: Event) => {
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
      plainTextContentDiv.innerHTML = DOMPurify.sanitize(await marked(await response.text()), { USE_PROFILES: { html: true }, FORBID_TAGS: ['style', 'script', 'iframe', 'form'] });
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
    } else if (el.dataset.ext === 'table') {
      const blob = await response.blob();
      blob2table(blob, plainTextContentDiv as HTMLDivElement);
    } else { // TXT
      response.text().then(content => plainTextContentDiv.innerText = content);
    }

  // TOGGLE SHOW ARCHIVED
  } else if (el.matches('[data-action="toggle-uploads-show-archived"]')) {
    const url = new URL(window.location.href);
    const queryParams = new URLSearchParams(url.search);

    // set the state query param to include normal and archived
    if (queryParams.has('state')) {
      queryParams.delete('state');
    } else {
      queryParams.set('state', '1,2');
    }

    // Update the query parameters in the URL
    url.search = queryParams.toString();
    url.hash = 'filesDiv';
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
    get3dmol().then(($3Dmol) => $3Dmol.viewers[el.dataset.divid].setStyle(style).render());

  // LOAD SPREADSHEET FILE
  } else if (el.matches('[data-action="xls-load-file"]')) {
    await loadInSpreadsheetEditor(el.dataset.storage, el.dataset.path, el.dataset.name, Number(el.dataset.uploadid));
    ensureTogglableSectionIsOpen('sheetEditorIcon', 'spreadsheetEditorDiv');

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
};

const uploadsDiv = document.getElementById('uploadsDiv');
if (uploadsDiv) {
  displayPlasmidViewer(entity);
  displayMoleculeViewer();

  // MAKE FILE COMMENTS EDITABLE
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

  document.querySelector('.real-container').addEventListener('click', async (event) => clickHandler(event));
  // reload uploads div when using spreadsheet editor (iframe sends message to parent window)
  window.addEventListener('message', (event) => {
    if (event.origin !== window.location.origin) return;
    if (event.data !== 'uploadsDiv') return;
    reloadElements(['uploadsDiv']);
  });

  // ACTIVATE FANCYBOX
  $('[data-fancybox]').fancybox();

  new MutationObserver(() => {
    displayMoleculeViewer();
    displayPlasmidViewer(entity);
    malleableFilecomment.listen();
    (new Uploader()).init();
    relativeMoment();
    // don't use option {subtree: true} or there is an infinite loop that will destroy the world
  }).observe(uploadsDiv, {childList: true});
}
