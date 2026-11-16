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
/** subset of a jspreadsheet worksheet's options worth preserving across a save/load round-trip */
export interface SpreadsheetWorksheetState {
  style?: Record<string, string>;
  comments?: Record<string, string>;
  classes?: Record<string, string>;
  meta?: Record<string, unknown>;
  mergeCells?: Record<string, [number, number]>;
  columns?: Array<{ width?: number }>;
  rows?: Array<{ height?: number }>;
  nestedHeaders?: unknown[];
  footers?: string[][];
  freezeColumns?: number;
}
export interface SpreadsheetWorksheet {
  name: string;
  data: Cell[][];
  /* displayed cell values (formula results), only used to store cached results in saved files */
  values?: Cell[][];
  /* worksheet configuration that lives outside the data grid: formatting, merges, dimensions */
  state?: SpreadsheetWorksheetState;
}
export type SpreadsheetWorkbook = SpreadsheetWorksheet[];
const MAX_SPREADSHEET_CELLS = 1_000_000;
const MAX_SPREADSHEET_ROWS = 100_000;
const MAX_WORKSHEETS = 1_000;
const MAX_CELL_LENGTH = 1_000_000;
const MAX_STATE_ENTRIES = 100_000;
const MAX_STATE_LENGTH = 4_000_000;
const MAX_SPAN = 10_000;
// custom workbook property carrying the worksheet state that xlsx cannot express (cell styles, ...)
const SPREADSHEET_STATE_PROP = 'elabftw_spreadsheet_state';
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

const isJsonValue = (value: unknown, maxLength: number): boolean => {
  try {
    return JSON.stringify(value).length <= maxLength;
  } catch {
    return false;
  }
};

const pickStringRecord = (value: unknown, maxValueLength: number): Record<string, string> | undefined => {
  if (!value || typeof value !== 'object' || Array.isArray(value)) return undefined;
  const record: Record<string, string> = {};
  for (const [key, val] of Object.entries(value)) {
    if (Object.keys(record).length >= MAX_STATE_ENTRIES) break;
    if (key.length <= 128 && typeof val === 'string' && val.length <= maxValueLength) record[key] = val;
  }
  return Object.keys(record).length ? record : undefined;
};

/* files come from user uploads: keep only the worksheet state entries we understand and can bound */
const sanitizeWorksheetState = (raw: unknown): SpreadsheetWorksheetState | undefined => {
  if (!raw || typeof raw !== 'object' || Array.isArray(raw)) return undefined;
  const src = raw as Record<string, unknown>;
  const state: SpreadsheetWorksheetState = {};
  const style = pickStringRecord(src.style, MAX_CELL_LENGTH);
  if (style) state.style = style;
  const comments = pickStringRecord(src.comments, MAX_CELL_LENGTH);
  if (comments) state.comments = comments;
  const classes = pickStringRecord(src.classes, 1024);
  if (classes) state.classes = classes;
  if (src.meta && typeof src.meta === 'object' && !Array.isArray(src.meta)) {
    const meta: Record<string, unknown> = {};
    for (const [key, val] of Object.entries(src.meta)) {
      if (Object.keys(meta).length >= MAX_STATE_ENTRIES) break;
      if (key.length <= 128 && isJsonValue(val, MAX_CELL_LENGTH)) meta[key] = val;
    }
    if (Object.keys(meta).length) state.meta = meta;
  }
  if (src.mergeCells && typeof src.mergeCells === 'object' && !Array.isArray(src.mergeCells)) {
    const mergeCells: Record<string, [number, number]> = {};
    for (const [cell, range] of Object.entries(src.mergeCells)) {
      if (Object.keys(mergeCells).length >= MAX_STATE_ENTRIES) break;
      if (/^[A-Z]+\d+$/.test(cell) && Array.isArray(range)
        && Number.isInteger(range[0]) && Number.isInteger(range[1])
        && range[0] > 0 && range[1] > 0 && range[0] <= MAX_SPAN && range[1] <= MAX_SPAN) {
        mergeCells[cell] = [range[0], range[1]];
      }
    }
    if (Object.keys(mergeCells).length) state.mergeCells = mergeCells;
  }
  const pickSizes = (value: unknown, key: 'width' | 'height') => {
    if (!Array.isArray(value) || value.length > MAX_STATE_ENTRIES) return undefined;
    let found = false;
    const sizes = value.map(item => {
      if (!item || typeof item !== 'object' || typeof item[key] !== 'number'
        || !Number.isFinite(item[key]) || item[key] < 0 || item[key] > MAX_SPAN) return {};
      found = true;
      return { [key]: item[key] };
    });
    return found ? sizes : undefined;
  };
  const columns = pickSizes(src.columns, 'width');
  if (columns) state.columns = columns;
  const rows = pickSizes(src.rows, 'height');
  if (rows) state.rows = rows;
  if (Array.isArray(src.nestedHeaders) && isJsonValue(src.nestedHeaders, MAX_STATE_LENGTH)) {
    state.nestedHeaders = src.nestedHeaders;
  }
  if (Array.isArray(src.footers)
    && src.footers.every(row => Array.isArray(row) && row.every(cell => typeof cell === 'string'))) {
    state.footers = src.footers;
  }
  if (typeof src.freezeColumns === 'number' && Number.isInteger(src.freezeColumns)
    && src.freezeColumns >= 0 && src.freezeColumns <= MAX_SPAN) {
    state.freezeColumns = src.freezeColumns;
  }
  return Object.keys(state).length ? state : undefined;
};

/* worksheet state embedded by eLabFTW on save, in a custom workbook property */
const embeddedWorksheetStates = (wb: WorkBook): Array<SpreadsheetWorksheetState | undefined> | null => {
  const raw = (wb.Custprops as Record<string, unknown> | undefined)?.[SPREADSHEET_STATE_PROP];
  if (typeof raw !== 'string') return null;
  try {
    const parsed = JSON.parse(raw);
    if (!parsed || parsed.v !== 1 || !Array.isArray(parsed.sheets)
      || parsed.sheets.length > MAX_WORKSHEETS) return null;
    return parsed.sheets.map((sheet: unknown) => sanitizeWorksheetState(sheet));
  } catch {
    return null;
  }
};

/* recover worksheet state that foreign files express with native xlsx features */
const stateFromNativeSheet = (ws: WorkSheet): SpreadsheetWorksheetState | undefined => {
  const state: SpreadsheetWorksheetState = {};
  if (Array.isArray(ws['!merges']) && ws['!merges'].length) {
    const mergeCells: Record<string, [number, number]> = {};
    for (const range of ws['!merges']) {
      const colspan = range.e.c - range.s.c + 1;
      const rowspan = range.e.r - range.s.r + 1;
      if (colspan > 1 || rowspan > 1) mergeCells[utils.encode_cell(range.s)] = [colspan, rowspan];
    }
    if (Object.keys(mergeCells).length) state.mergeCells = mergeCells;
  }
  if (Array.isArray(ws['!cols']) && ws['!cols'].length) {
    const columns = ws['!cols'].map(col => {
      const width = col?.wpx ?? (typeof col?.width === 'number' ? Math.round(col.width * 7 + 5) : undefined);
      return typeof width === 'number' ? { width } : {};
    });
    if (columns.some(col => col.width)) state.columns = columns;
  }
  if (Array.isArray(ws['!rows']) && ws['!rows'].length) {
    const rows = ws['!rows'].map(row => {
      const height = row?.hpx ?? (typeof row?.hpt === 'number' ? Math.round(row.hpt * 4 / 3) : undefined);
      return typeof height === 'number' ? { height } : {};
    });
    if (rows.some(row => row.height)) state.rows = rows;
  }
  const comments: Record<string, string> = {};
  for (const address of Object.keys(ws)) {
    if (address.startsWith('!')) continue;
    const cellComments = ws[address]?.c;
    if (Array.isArray(cellComments) && cellComments.length) {
      comments[address] = cellComments.map(comment => comment.t ?? '').join('\n');
    }
  }
  if (Object.keys(comments).length) state.comments = comments;
  return Object.keys(state).length ? state : undefined;
};

function parseFileToWorkbook(buffer: ArrayBuffer, fileType: FileType): SpreadsheetWorkbook {
  const wb = read(buffer, fileType === FileType.Csv
    ? { type: 'array', codepage: 65001, cellStyles: true }
    : { type: 'array', cellStyles: true });
  if (!wb.SheetNames || wb.SheetNames.length === 0) {
    throw new Error('No sheets found in uploaded file.');
  }
  const states = embeddedWorksheetStates(wb);
  const workbook = wb.SheetNames.map((name, index) => {
    const ws = wb.Sheets[name];
    if (!ws) throw new Error(`Failed to load worksheet ${name}.`);
    const worksheet: SpreadsheetWorksheet = {
      name,
      data: sheetToSpreadsheetData(ws),
    };
    const state = states?.[index] ?? stateFromNativeSheet(ws);
    if (state) worksheet.state = state;
    return worksheet;
  });
  if (!isValidWorkbook(workbook)) {
    throw new Error('Uploaded workbook exceeds spreadsheet limits.');
  }
  return workbook;
}

export async function loadInSpreadsheetEditor(storage: string, path: string, name: string, uploadId: number): Promise<boolean> {
  try {
    const params = new URLSearchParams({ f: path, storage });
    const res = await fetch(`app/download.php?${params.toString()}`, {
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

const isValidDataGrid = (data: unknown, cellCount: { count: number }): boolean => {
  if (!Array.isArray(data) || data.length > MAX_SPREADSHEET_ROWS) return false;
  for (const row of data) {
    if (!Array.isArray(row)) return false;
    cellCount.count += row.length;
    if (cellCount.count > MAX_SPREADSHEET_CELLS) return false;
    for (const cell of row) {
      if (!isCell(cell)) return false;
      if (typeof cell === 'string' && cell.length > MAX_CELL_LENGTH) return false;
      if (typeof cell === 'number' && !Number.isFinite(cell)) return false;
    }
  }
  return true;
};

const isValidWorkbook = (value: unknown): value is SpreadsheetWorkbook => {
  if (!Array.isArray(value) || value.length === 0 || value.length > MAX_WORKSHEETS) return false;
  const cellCount = { count: 0 };
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
    if (!isValidDataGrid(worksheet.data, cellCount)) return false;
    if (worksheet.values !== undefined && !isValidDataGrid(worksheet.values, cellCount)) return false;
    if (worksheet.state !== undefined
      && (typeof worksheet.state !== 'object' || worksheet.state === null
        || Array.isArray(worksheet.state) || !isJsonValue(worksheet.state, MAX_STATE_LENGTH))) {
      return false;
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

// a formula cell carries its last computed value so viewers (and Excel) can display it without recalculating
const formulaCell = (formula: string, cached: Cell | undefined) => {
  const cell: { t: 'n' | 'b' | 'e' | 'str'; f: string; v?: number | boolean | string } = { t: 'e', f: formula };
  if (typeof cached === 'number' && Number.isFinite(cached)) {
    cell.t = 'n';
    cell.v = cached;
  } else if (typeof cached === 'boolean') {
    cell.t = 'b';
    cell.v = cached;
  } else if (typeof cached === 'string' && cached.trim() !== '') {
    const numeric = Number(cached);
    if (Number.isFinite(numeric)) {
      cell.t = 'n';
      cell.v = numeric;
    } else if (cached.startsWith('#')) {
      cell.v = cached;
    } else {
      cell.t = 'str';
      cell.v = cached;
    }
  }
  return cell;
};

const wbFromSpreadsheet = (workbook: SpreadsheetWorkbook): WorkBook => {
  const wb = utils.book_new();
  workbook.forEach(worksheet => {
    const data = worksheet.data.map((row, rowIndex) => row.map((cell, colIndex) => {
      return typeof cell === 'string' && cell.length > 1 && cell.startsWith('=')
        ? formulaCell(cell.slice(1), worksheet.values?.[rowIndex]?.[colIndex])
        : cell;
    }));
    const ws = utils.aoa_to_sheet(data);
    const state = worksheet.state;
    if (state?.mergeCells) {
      ws['!merges'] = Object.entries(state.mergeCells).map(([cell, [colspan, rowspan]]) => {
        const { r, c } = utils.decode_cell(cell);
        return { s: { r, c }, e: { r: r + rowspan - 1, c: c + colspan - 1 } };
      });
    }
    if (state?.columns?.length) {
      ws['!cols'] = [];
      state.columns.forEach((col, index) => {
        if (col?.width) ws['!cols']![index] = { wpx: col.width };
      });
    }
    if (state?.rows?.length) {
      ws['!rows'] = [];
      state.rows.forEach((row, index) => {
        if (row?.height) ws['!rows']![index] = { hpx: row.height };
      });
    }
    utils.book_append_sheet(wb, ws, worksheet.name);
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
  const fileType = inferFileTypeFromName(name);
  if (fileType === FileType.Csv && workbook.length > 1) {
    throw new Error('CSV files can contain only one worksheet. Save the spreadsheet as XLSX to preserve all worksheets.');
  }
  const wb = wbFromSpreadsheet(workbook);
  // xlsx cannot express jspreadsheet cell styles: embed the worksheet state in a custom
  // document property so formatting, merges and sizes survive the save → load round-trip
  if (fileType === FileType.Xlsx) {
    const sheets = workbook.map(worksheet => worksheet.state ?? null);
    if (sheets.some(state => state !== null)) {
      wb.Custprops = {
        ...(wb.Custprops as Record<string, unknown> | undefined),
        [SPREADSHEET_STATE_PROP]: JSON.stringify({ v: 1, sheets }),
      };
    }
  }
  const file = fileFromWB(wb, name);
  const url = uploadUrl(entityType, entityId, uploadId);
  const id = await postAndReturnId(file, url, uploadId ? Action.Replace : undefined);
  return { id, name };
}
