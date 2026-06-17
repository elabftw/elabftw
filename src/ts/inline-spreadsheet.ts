/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

/**
 * Drives the "inline spreadsheet" feature on the entity edit page.
 *
 * This module is the bridge between three actors that live in different bundles:
 *  - the TinyMCE toolbar/context buttons (main.bundle.js) dispatch `inline-sheet-insert` / `inline-sheet-edit`
 *  - the standalone spreadsheet editor iframe (spreadsheet.bundle.js) posts `inline-sheet-refresh` with computed values
 *  - TinyMCE listens for the `inline-sheet-render` event to actually insert/update the body block
 *
 * All spreadsheet computation happens in the standalone editor; this module never instantiates jspreadsheet.
 */
import { entity } from './getEntity';
import { Model } from './interfaces';
import { ApiC } from './api';
import { saveAsAttachment, loadInSpreadsheetEditor } from './spreadsheet-utils';
import { ensureTogglableSectionIsOpen } from './misc';
import { sheetDataToTableHtml } from './inline-spreadsheet-utils';
import { notify } from './notify';
import i18next from './i18n';

type Cell = string | number | boolean | null;

interface UploadLite {
  id: number;
  real_name: string;
  long_name: string;
  storage: number;
}

// keep the same extensions the standalone editor supports (Elabftw\Elabftw\Extensions::SPREADSHEET)
const SHEET_EXTENSIONS = ['csv', 'ods', 'xls', 'xlsx', 'xlsb'];

// list of spreadsheet uploads currently shown in the "use existing" select, to map id -> upload on click
let sheetUploads: UploadLite[] = [];

function isSheetUpload(upload: UploadLite): boolean {
  const ext = (upload.real_name?.split('.').pop() || '').toLowerCase();
  return SHEET_EXTENSIONS.includes(ext);
}

async function fetchUpload(uploadId: number): Promise<UploadLite | null> {
  try {
    return await ApiC.getJson<UploadLite>(`${entity.type}/${entity.id}/${Model.Upload}/${uploadId}`);
  } catch {
    return null;
  }
}

// ask TinyMCE to insert a new block (insertIfMissing) or update the existing one keyed by filename
function dispatchRender(html: string, sheetName: string, insertIfMissing: boolean): void {
  document.dispatchEvent(new CustomEvent('inline-sheet-render', { detail: { html, sheetName, insertIfMissing } }));
}

function hideModal(): void {
  // cross-bundle: Bootstrap's .modal() is registered on the global jQuery by common.ts, not the imported $
  window.jQuery('#inlineSpreadsheetModal').modal('hide');
}

// CREATE NEW: make an empty xlsx attachment, embed an (empty) snapshot, then open it in the editor to enter data
async function onCreateNew(): Promise<void> {
  const input = document.getElementById('inlineSheetNewName') as HTMLInputElement | null;
  const raw = input?.value?.trim();
  if (!raw) {
    notify.error(i18next.t('error-no-filename'));
    return;
  }
  hideModal();
  const empty: Cell[][] = [['']];
  const res = await saveAsAttachment(empty, entity.type, entity.id, raw);
  if (!res) {
    return;
  }
  if (input) {
    input.value = '';
  }
  // embed an empty, clickable snapshot block immediately, keyed by filename
  dispatchRender(sheetDataToTableHtml(empty, { sheetName: res.name, uploadId: res.id }), res.name, true);
  // open the freshly created attachment in the standalone editor
  const upload = await fetchUpload(res.id);
  if (upload) {
    await loadInSpreadsheetEditor(String(upload.storage), upload.long_name, upload.real_name, upload.id);
    ensureTogglableSectionIsOpen('sheetEditorIcon', 'spreadsheetEditorDiv');
  }
}

// USE EXISTING: load the chosen attachment into the editor with embedInline so it posts back a computed snapshot
async function onUseExisting(): Promise<void> {
  const select = document.getElementById('inlineSheetExistingSelect') as HTMLSelectElement | null;
  const uploadId = Number(select?.value);
  if (!uploadId) {
    return;
  }
  const upload = sheetUploads.find(item => item.id === uploadId);
  if (!upload) {
    return;
  }
  hideModal();
  await loadInSpreadsheetEditor(String(upload.storage), upload.long_name, upload.real_name, upload.id, true);
  ensureTogglableSectionIsOpen('sheetEditorIcon', 'spreadsheetEditorDiv');
}

// EDIT (double-click / context toolbar): open the block's attachment in the standalone editor
async function onEdit(uploadId: number): Promise<void> {
  if (!uploadId) {
    return;
  }
  const upload = await fetchUpload(uploadId);
  if (!upload) {
    return;
  }
  await loadInSpreadsheetEditor(String(upload.storage), upload.long_name, upload.real_name, upload.id);
  ensureTogglableSectionIsOpen('sheetEditorIcon', 'spreadsheetEditorDiv');
}

// fill the "use existing" select with the entity's spreadsheet attachments
async function populateExisting(): Promise<void> {
  const select = document.getElementById('inlineSheetExistingSelect') as HTMLSelectElement | null;
  const useBtn = document.getElementById('inlineSheetUseExistingBtn') as HTMLButtonElement | null;
  const emptyHint = document.getElementById('inlineSheetNoSheets');
  if (!select) {
    return;
  }
  select.innerHTML = '';
  let uploads: UploadLite[];
  try {
    uploads = await ApiC.getJson<UploadLite[]>(`${entity.type}/${entity.id}/${Model.Upload}`);
  } catch {
    uploads = [];
  }
  sheetUploads = uploads.filter(isSheetUpload);
  sheetUploads.forEach(upload => {
    const option = document.createElement('option');
    option.value = String(upload.id);
    option.textContent = upload.real_name;
    select.appendChild(option);
  });
  const hasSheets = sheetUploads.length > 0;
  select.disabled = !hasSheets;
  if (useBtn) {
    useBtn.disabled = !hasSheets;
  }
  if (emptyHint) {
    emptyHint.hidden = hasSheets;
  }
}

async function openModal(): Promise<void> {
  await populateExisting();
  window.jQuery('#inlineSpreadsheetModal').modal('show');
}

// the standalone editor posted computed values after a save or an embedInline load: (re)build the snapshot
function onRefreshMessage(event: MessageEvent): void {
  if (event.origin !== window.location.origin) {
    return;
  }
  const data = event.data;
  if (!data || data.type !== 'inline-sheet-refresh') {
    return;
  }
  const { name, uploadId, computed, embed } = data;
  if (!name || !uploadId || !Array.isArray(computed)) {
    return;
  }
  dispatchRender(sheetDataToTableHtml(computed, { sheetName: name, uploadId }), name, Boolean(embed));
}

function init(): void {
  // only meaningful on an entity edit page
  if (!entity.id) {
    return;
  }
  document.addEventListener('inline-sheet-insert', () => {
    void openModal();
  });
  document.addEventListener('inline-sheet-edit', (event: Event) => {
    const detail = (event as CustomEvent).detail || {};
    void onEdit(Number(detail.uploadId));
  });
  window.addEventListener('message', onRefreshMessage);
  document.getElementById('inlineSheetCreateBtn')?.addEventListener('click', () => {
    void onCreateNew();
  });
  document.getElementById('inlineSheetUseExistingBtn')?.addEventListener('click', () => {
    void onUseExisting();
  });
}

init();
