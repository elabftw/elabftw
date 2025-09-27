/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Mouss <Deltablot>
 * @copyright 2025 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

import { utils, write, WorkBook, read } from '@e965/xlsx';
import { getBookType, getMime } from './spreadsheet-formats';
import { reloadElements, askFileName, getNewIdFromPostRequest } from './misc';
import { notify } from './notify';
import { FileType, Model } from './interfaces';

type Cell = string | number | boolean | null;
// save current spreadsheet as a new attachment
export async function saveAsAttachment(aoa: (string|number|boolean|null)[][], entityType: string, entityId: number, format: FileType = FileType.Xlsx, fileName?: string): Promise<{id:number; name:string} | void> {
  const chosen = fileName?.trim()
    ? ensureExtension(fileName.trim(), format)
    : askFileName(format);
  if (!chosen) return;
  return uploadAOA(aoa, chosen, format, entityType, entityId);
}

// replace an existing attachment with current spreadsheet
export async function replaceAttachment(aoa: (string|number|boolean|null)[][], entityType: string, entityId: number, uploadId: number, currentName: string, format: FileType = FileType.Xlsx,
): Promise<{id:number; name:string} | void> {
  if (!uploadId || !currentName) return;
  return uploadAOA(aoa, currentName, format, entityType, entityId, uploadId);
}

export async function fileToAOA(file: File): Promise<Cell[][]> {
  const buffer = await file.arrayBuffer();
  return parseFileToAOA(buffer);
}

export async function loadInJSSpreadsheet(storage: string, path: string, name: string, uploadId: number): Promise<void> {
  try {
    const res = await fetch(`app/download.php?f=${encodeURIComponent(path)}&storage=${storage}`, {
      headers: new Headers({ 'cache-control': 'no-cache' }),
    });
    if (!res.ok) throw new Error('Failed to fetch uploaded file.');
    const buffer = await res.arrayBuffer();
    const aoa = parseFileToAOA(buffer);
    const ev = new CustomEvent('jss-load-aoa', { detail: { aoa, name, uploadId } });
    document.dispatchEvent(ev);
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
  await reloadElements(['uploadsDiv']);
  notify.success();
  return getNewIdFromPostRequest(res);
}

const ensureExtension = (name: string, format: FileType): string => {
  const ext = `.${String(format).toLowerCase()}`;
  return name.toLowerCase().endsWith(ext) ? name : name + ext;
};

const uploadUrl = (entityType: string, entityId: number, uploadId?: number): string => {
  const base = `api/v2/${entityType}/${entityId}/${Model.Upload}`;
  return uploadId ? `${base}/${uploadId}` : base;
};

const wbFromAOA = (aoa: (string|number|boolean|null)[][]): WorkBook => {
  const ws = utils.aoa_to_sheet(aoa);
  const wb = utils.book_new();
  utils.book_append_sheet(wb, ws, 'Sheet1');
  return wb;
};

const fileFromWB = (wb: WorkBook, name: string, format: FileType) => {
  const bookType = getBookType(format);
  const mime = getMime(format) ?? 'application/octet-stream';
  const bin = write(wb, { bookType, type: 'array' });
  return new File([bin], name, { type: mime });
};

async function uploadAOA(aoa: (string | number | boolean | null)[][], name: string, format: FileType, entityType: string, entityId: number, uploadId?: number,
): Promise<{ id: number; name: string } | void> {
  if (!aoa?.length) return;
  const wb = wbFromAOA(aoa);
  const file = fileFromWB(wb, name, format);
  const url = uploadUrl(entityType, entityId, uploadId);
  const id = await postAndReturnId(file, url);
  return { id, name };
}

function parseFileToAOA(buffer: ArrayBuffer): Cell[][] {
  const wb = read(buffer, { type: 'array', codepage: 65001 }); // UTF-8
  const ws = wb.Sheets[wb.SheetNames[0]];
  return utils.sheet_to_json(ws, {header: 1, defval: '', raw: true, blankrows: true}) as Cell[][];
}
