/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Mouss <Deltablot>
 * @copyright 2025 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

import { read, utils, write } from '@e965/xlsx';
import type { WorkBook, WorkSheet } from '@e965/xlsx';
import { Action, FileType, Model } from './interfaces';
import { askFileName, getNewIdFromPostRequest } from './misc';
import { notify } from './notify';
import { getBookType, getMime, inferFileTypeFromName } from './spreadsheet-formats';

type Cell = string | number | boolean | null;
export type SpreadsheetWorkbook = Array<{ name: string; data: Cell[][] }>;
const MAX_SPREADSHEET_CELLS = 1_000_000;
const MAX_SPREADSHEET_ROWS = 100_000;
const MAX_WORKSHEETS = 1_000;
const MAX_CELL_LENGTH = 1_000_000;
const RESPONSE_TIMEOUT_MS = 10_000;
let spreadsheetPortPromise: Promise<MessagePort> | null = null;

export const isSpreadsheetIframeMessage = (event: MessageEvent, iframeWindow: Window | null): boolean => {
  // The iframe sandbox intentionally omits allow-same-origin, so its origin is opaque.
  return event.source === iframeWindow && event.origin === 'null';
};

// save current spreadsheet as a new attachment
export async function saveAsAttachment(workbook: SpreadsheetWorkbook, entityType: string, entityId: number, fileName?: string): Promise<{ id:number; name:string } | void> {
  const raw = fileName?.trim() || askFileName(FileType.Xlsx);
  if (!raw) return;
  return uploadWorkbook(workbook, ensureExtensionExists(raw), entityType, entityId);
}

// replace an existing attachment with current spreadsheet
export async function replaceAttachment(workbook: SpreadsheetWorkbook, entityType: string, entityId: number, uploadId: number, currentName: string): Promise<{id:number; name:string} | void> {
  if (!uploadId || !currentName) return;
  return uploadWorkbook(workbook, currentName, entityType, entityId, uploadId);
}

// import file from computer: convert to spreadsheet
export async function fileToWorkbook(file: File): Promise<SpreadsheetWorkbook> {
  const buffer = await file.arrayBuffer();
  return parseFileToWorkbook(buffer, inferFileTypeFromName(file.name));
}

const sheetToSpreadsheetData = (worksheet: WorkSheet): Cell[][] => {
  const ref = worksheet['!ref'];
  if (!ref) return [];
  const range = utils.decode_range(ref);
  // Keep the original coordinates so formula references retain their meaning.
  const rowCount = range.e.r + 1;
  const columnCount = range.e.c + 1;
  if (rowCount > MAX_SPREADSHEET_ROWS || rowCount * columnCount > MAX_SPREADSHEET_CELLS) {
    throw new Error('Uploaded worksheet exceeds spreadsheet limits.');
  }
  const data = utils.sheet_to_json(worksheet, {
    header: 1,
    defval: '',
    raw: true,
    blankrows: true,
    range: { s: { r: 0, c: 0 }, e: range.e },
  }) as Cell[][];
  for (const address of Object.keys(worksheet)) {
    if (address.startsWith('!')) continue;
    const formula = worksheet[address]?.f;
    if (typeof formula !== 'string') continue;
    const { r, c } = utils.decode_cell(address);
    if (r < 0 || c < 0 || r >= rowCount || c >= columnCount) continue;
    while (data.length <= r) data.push([]);
    while (data[r].length <= c) data[r].push('');
    data[r][c] = `=${formula}`;
  }
  return data;
};

function parseFileToWorkbook(buffer: ArrayBuffer, fileType: FileType): SpreadsheetWorkbook {
  const wb = read(buffer, fileType === FileType.Csv
    ? { type: 'array', codepage: 65001 }
    : { type: 'array' });
  if (!wb.SheetNames || wb.SheetNames.length === 0) {
    throw new Error('No sheets found in uploaded file.');
  }
  const workbook = wb.SheetNames.map(name => {
    const ws = wb.Sheets[name];
    if (!ws) throw new Error(`Failed to load worksheet ${name}.`);
    return {
      name,
      data: sheetToSpreadsheetData(ws),
    };
  });
  if (!isValidWorkbook(workbook)) {
    throw new Error('Uploaded workbook exceeds spreadsheet limits.');
  }
  return workbook;
}

export async function loadInSpreadsheetEditor(storage: string, path: string, name: string, uploadId: number): Promise<boolean> {
  try {
    const res = await fetch(`app/download.php?f=${encodeURIComponent(path)}&storage=${storage}`, {
      headers: new Headers({ 'cache-control': 'no-cache' }),
    });
    if (!res.ok) throw new Error('Failed to fetch uploaded file.');
    const buffer = await res.arrayBuffer();
    const workbook = parseFileToWorkbook(buffer, inferFileTypeFromName(name));
    const port = await getSpreadsheetEditorPort();
    port.postMessage({ type: 'jss-load-workbook', detail: { workbook, name, uploadId } });
    return true;
  } catch (e) {
    notify.error(e.message || 'Unexpected error while loading spreadsheet.');
    return false;
  }
}

// helpers
const getSpreadsheetEditorPort = (): Promise<MessagePort> => {
  if (spreadsheetPortPromise) return spreadsheetPortPromise;
  const iframe = document.getElementById('spreadsheetIframe') as HTMLIFrameElement;
  const iframeWindow = iframe?.contentWindow;
  if (!iframeWindow) return Promise.reject(new Error('Spreadsheet editor is not available.'));

  const connection = new Promise<MessagePort>((resolve, reject) => {
    let channel: MessageChannel | null = null;
    let portMessageHandler: ((event: MessageEvent) => void) | null = null;
    const cleanup = () => {
      window.clearTimeout(timeout);
      window.clearInterval(interval);
      iframe.removeEventListener('load', ping);
      window.removeEventListener('message', onMessage);
      if (channel && portMessageHandler) {
        channel.port1.removeEventListener('message', portMessageHandler);
      }
    };
    const onMessage = (event: MessageEvent) => {
      if (!isSpreadsheetIframeMessage(event, iframeWindow)) return;
      if (event.data?.type !== 'jss-ready') return;
      window.clearInterval(interval);
      iframe.removeEventListener('load', ping);
      window.removeEventListener('message', onMessage);
      channel = new MessageChannel();
      portMessageHandler = (portEvent: MessageEvent) => {
        if (portEvent.data?.type !== 'jss-connected') return;
        cleanup();
        channel.port1.start();
        resolve(channel.port1);
      };
      channel.port1.addEventListener('message', portMessageHandler);
      channel.port1.start();
      iframeWindow.postMessage({ type: 'jss-connect' }, '*', [channel.port2]);
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
  spreadsheetPortPromise = connection.catch(error => {
    spreadsheetPortPromise = null;
    throw error;
  });
  return spreadsheetPortPromise;
};

export async function listenForSpreadsheetMessages(listener: (event: MessageEvent) => void): Promise<void> {
  const port = await getSpreadsheetEditorPort();
  port.addEventListener('message', listener);
  port.start();
}

export async function sendSpreadsheetMessage(message: object): Promise<void> {
  const port = await getSpreadsheetEditorPort();
  port.postMessage(message);
}

const isCell = (value: unknown): value is Cell => {
  return value === null || ['string', 'number', 'boolean'].includes(typeof value);
};

const isValidWorkbook = (value: unknown): value is SpreadsheetWorkbook => {
  if (!Array.isArray(value) || value.length === 0 || value.length > MAX_WORKSHEETS) return false;
  let cellCount = 0;
  const worksheetNames = new Set<string>();
  for (const worksheet of value) {
    if (!worksheet || typeof worksheet !== 'object') return false;
    if (typeof worksheet.name !== 'string' || !worksheet.name || worksheet.name.length > 31) return false;
    /* eslint-disable quotes */
    const hasInvalidName = /[:\\/?*[\]]/.test(worksheet.name)
      || worksheet.name.startsWith("'")
      || worksheet.name.endsWith("'");
    /* eslint-enable quotes */
    if (hasInvalidName) return false;
    const normalizedName = worksheet.name.toLowerCase();
    if (normalizedName === 'history' || worksheetNames.has(normalizedName)) return false;
    worksheetNames.add(normalizedName);
    if (!Array.isArray(worksheet.data) || worksheet.data.length > MAX_SPREADSHEET_ROWS) return false;
    for (const row of worksheet.data) {
      if (!Array.isArray(row)) return false;
      cellCount += row.length;
      if (cellCount > MAX_SPREADSHEET_CELLS) return false;
      for (const cell of row) {
        if (!isCell(cell)) return false;
        if (typeof cell === 'string' && cell.length > MAX_CELL_LENGTH) return false;
        if (typeof cell === 'number' && !Number.isFinite(cell)) return false;
      }
    }
  }
  return true;
};

export async function requestSpreadsheetWorkbook(): Promise<SpreadsheetWorkbook> {
  const port = await getSpreadsheetEditorPort();
  const requestId = crypto.randomUUID();
  return new Promise<SpreadsheetWorkbook>((resolve, reject) => {
    const cleanup = () => {
      window.clearTimeout(timeout);
      port.removeEventListener('message', onMessage);
    };
    const onMessage = (event: MessageEvent) => {
      if (event.data?.type !== 'jss-workbook-response' || event.data.requestId !== requestId) return;
      cleanup();
      if (!isValidWorkbook(event.data.workbook)) {
        reject(new Error('Spreadsheet editor returned invalid data.'));
        return;
      }
      resolve(event.data.workbook);
    };
    const timeout = window.setTimeout(() => {
      cleanup();
      reject(new Error('Spreadsheet editor did not respond.'));
    }, RESPONSE_TIMEOUT_MS);

    port.addEventListener('message', onMessage);
    port.postMessage({ type: 'jss-workbook-request', requestId });
  });
}

async function postAndReturnId(file: File, url: string, action?: Action): Promise<number> {
  const fd = new FormData();
  fd.append('file', file);
  if (action) fd.append('action', action);
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

const wbFromSpreadsheet = (workbook: SpreadsheetWorkbook): WorkBook => {
  const wb = utils.book_new();
  workbook.forEach(worksheet => {
    const data = worksheet.data.map(row => row.map(cell => {
      return typeof cell === 'string' && cell.length > 1 && cell.startsWith('=')
        ? { t: 'n' as const, f: cell.slice(1) }
        : cell;
    }));
    utils.book_append_sheet(wb, utils.aoa_to_sheet(data), worksheet.name);
  });
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
async function uploadWorkbook(workbook: SpreadsheetWorkbook, name: string, entityType: string, entityId: number, uploadId?: number): Promise<{ id: number; name: string } | void> {
  if (!workbook?.length) return;
  if (!isValidWorkbook(workbook)) {
    throw new Error('Spreadsheet contains invalid data or worksheet names.');
  }
  if (inferFileTypeFromName(name) === FileType.Csv && workbook.length > 1) {
    throw new Error('CSV files can contain only one worksheet. Save the spreadsheet as XLSX to preserve all worksheets.');
  }
  const wb = wbFromSpreadsheet(workbook);
  const file = fileFromWB(wb, name);
  const url = uploadUrl(entityType, entityId, uploadId);
  const id = await postAndReturnId(file, url, uploadId ? Action.Replace : undefined);
  return { id, name };
}
