/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Mouss <Deltablot>
 * @copyright 2025 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

import { read, utils, WorkBook, write } from '@e965/xlsx';
import { FileType, Model } from './interfaces';
import { askFileName, getNewIdFromPostRequest } from './misc';
import { notify } from './notify';
import { getBookType, getMime, inferFileTypeFromName } from './spreadsheet-formats';

type Cell = string | number | boolean | null;
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
  const wb = read(buffer, { type: 'array', codepage: 65001 }); // UTF-8
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
    iframe.contentWindow.postMessage({ type: 'jss-load-aoa', detail: { aoa, name, uploadId } }, window.location.origin);
  } catch (e) {
    notify.error(e.message || 'Unexpected error while loading spreadsheet.');
  }
}

// helpers
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
