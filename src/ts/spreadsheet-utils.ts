/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Mouss <Deltablot>
 * @copyright 2025 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

import { read, utils, write } from '@e965/xlsx';
import type { WorkBook } from '@e965/xlsx';
import { FileType, Model } from './interfaces';
import { askFileName, getNewIdFromPostRequest } from './misc';
import { notify } from './notify';
import { getBookType, getMime, inferFileTypeFromName } from './spreadsheet-formats';

type Cell = string | number | boolean | null;
const MAX_SPREADSHEET_CELLS = 1_000_000;
const MAX_CELL_LENGTH = 1_000_000;
const RESPONSE_TIMEOUT_MS = 10_000;

// save current spreadsheet as a new attachment
export async function saveAsAttachment(aoa: Cell[][], entityType: string, entityId: number, fileName?: string): Promise<{ id:number; name:string } | void> {
  const raw = fileName?.trim() || askFileName(FileType.Xlsx);
  if (!raw) return;
  return uploadAOA(aoa, ensureExtensionExists(raw), entityType, entityId);
}

// replace an existing attachment with current spreadsheet
export async function replaceAttachment(aoa: Cell[][], entityType: string, entityId: number, uploadId: number, currentName: string): Promise<{id:number; name:string} | void> {
  if (!uploadId || !currentName) return;
  return uploadAOA(aoa, currentName, entityType, entityId, uploadId);
}

// import file from computer: convert to spreadsheet
export async function fileToAOA(file: File): Promise<Cell[][]> {
  const buffer = await file.arrayBuffer();
  return parseFileToAOA(buffer);
}

function parseFileToAOA(buffer: ArrayBuffer): Cell[][] {
  const wb = read(buffer, { type: 'array' });
  if (!wb.SheetNames || wb.SheetNames.length === 0) {
    throw new Error('No sheets found in uploaded file.');
  }
  const ws = wb.Sheets[wb.SheetNames[0]];
  if (!ws) {
    throw new Error('Failed to load the first sheet from the file.');
  }
  return utils.sheet_to_json(ws, { header: 1, defval: '', raw: true, blankrows: true }) as Cell[][];
}

export async function loadInSpreadsheetEditor(storage: string, path: string, name: string, uploadId: number): Promise<void> {
  try {
    const res = await fetch(`app/download.php?f=${encodeURIComponent(path)}&storage=${storage}`, {
      headers: new Headers({ 'cache-control': 'no-cache' }),
    });
    if (!res.ok) throw new Error('Failed to fetch uploaded file.');
    const buffer = await res.arrayBuffer();
    const aoa = parseFileToAOA(buffer);
    const iframe = document.getElementById('spreadsheetIframe') as HTMLIFrameElement;
    await waitForSpreadsheetEditor(iframe);
    iframe.contentWindow?.postMessage({ type: 'jss-load-aoa', detail: { aoa, name, uploadId } }, '*');
  } catch (e) {
    notify.error(e.message || 'Unexpected error while loading spreadsheet.');
  }
}

// helpers
const waitForSpreadsheetEditor = (iframe: HTMLIFrameElement): Promise<void> => {
  const iframeWindow = iframe?.contentWindow;
  if (!iframeWindow) return Promise.reject(new Error('Spreadsheet editor is not available.'));

  return new Promise<void>((resolve, reject) => {
    const cleanup = () => {
      window.clearTimeout(timeout);
      window.clearInterval(interval);
      iframe.removeEventListener('load', ping);
      window.removeEventListener('message', onMessage);
    };
    const onMessage = (event: MessageEvent) => {
      if (event.source !== iframeWindow || event.origin !== 'null') return;
      if (event.data?.type !== 'jss-ready') return;
      cleanup();
      resolve();
    };
    const ping = () => iframeWindow.postMessage({ type: 'jss-ping' }, '*');
    const timeout = window.setTimeout(() => {
      cleanup();
      reject(new Error('Spreadsheet editor did not become ready.'));
    }, RESPONSE_TIMEOUT_MS);
    const interval = window.setInterval(ping, 250);

    window.addEventListener('message', onMessage);
    iframe.addEventListener('load', ping, { once: true });
    ping();
  });
};

const isCell = (value: unknown): value is Cell => {
  return value === null || ['string', 'number', 'boolean'].includes(typeof value);
};

const isValidAOA = (value: unknown): value is Cell[][] => {
  if (!Array.isArray(value)) return false;
  let cellCount = 0;
  for (const row of value) {
    if (!Array.isArray(row)) return false;
    cellCount += row.length;
    if (cellCount > MAX_SPREADSHEET_CELLS) return false;
    for (const cell of row) {
      if (!isCell(cell)) return false;
      if (typeof cell === 'string' && cell.length > MAX_CELL_LENGTH) return false;
      if (typeof cell === 'number' && !Number.isFinite(cell)) return false;
    }
  }
  return true;
};

export function requestSpreadsheetAOA(): Promise<Cell[][]> {
  const iframe = document.getElementById('spreadsheetIframe') as HTMLIFrameElement;
  const iframeWindow = iframe?.contentWindow;
  if (!iframeWindow) return Promise.reject(new Error('Spreadsheet editor is not available.'));

  const requestId = crypto.randomUUID();
  return new Promise<Cell[][]>((resolve, reject) => {
    const cleanup = () => {
      window.clearTimeout(timeout);
      window.removeEventListener('message', onMessage);
    };
    const onMessage = (event: MessageEvent) => {
      if (event.source !== iframeWindow || event.origin !== 'null') return;
      if (event.data?.type !== 'jss-aoa-response' || event.data.requestId !== requestId) return;
      cleanup();
      if (!isValidAOA(event.data.aoa)) {
        reject(new Error('Spreadsheet editor returned invalid data.'));
        return;
      }
      resolve(event.data.aoa);
    };
    const timeout = window.setTimeout(() => {
      cleanup();
      reject(new Error('Spreadsheet editor did not respond.'));
    }, RESPONSE_TIMEOUT_MS);

    window.addEventListener('message', onMessage);
    iframeWindow.postMessage({ type: 'jss-aoa-request', requestId }, '*');
  });
}

async function postAndReturnId(file: File, url: string): Promise<number> {
  const fd = new FormData();
  fd.append('file', file);
  const res = await fetch(url, { method: 'POST', body: fd });
  if (!res.ok) {
    const msg = `Upload failed (${res.status})`;
    notify.error(msg);
    throw new Error(msg);
  }
  notify.success();
  return getNewIdFromPostRequest(res);
}

// default to xlsx if extension missing
const ensureExtensionExists = (name: string): string => {
  return /\.[^./\\]+$/.test(name) ? name : `${name}.xlsx`;
};

const uploadUrl = (entityType: string, entityId: number, uploadId?: number): string => {
  const base = `api/v2/${entityType}/${entityId}/${Model.Upload}`;
  return uploadId ? `${base}/${uploadId}` : base;
};

// TODO: later - handle multiple sheets
const wbFromAOA = (aoa: Cell[][]): WorkBook => {
  const ws = utils.aoa_to_sheet(aoa);
  const wb = utils.book_new();
  utils.book_append_sheet(wb, ws, 'Sheet1');
  return wb;
};

const fileFromWB = (wb: WorkBook, name: string) => {
  const fileType = inferFileTypeFromName(name);
  const bookType = getBookType(fileType);
  const mime = getMime(fileType);
  const bin = write(wb, { bookType, type: 'array' });
  return new File([bin], name, { type: mime });
};

// upload to eLab as attachment (save/replace)
async function uploadAOA(aoa: Cell[][], name: string, entityType: string, entityId: number, uploadId?: number): Promise<{ id: number; name: string } | void> {
  if (!aoa?.length) return;
  const wb = wbFromAOA(aoa);
  const file = fileFromWB(wb, name);
  const url = uploadUrl(entityType, entityId, uploadId);
  const id = await postAndReturnId(file, url);
  return { id, name };
}
