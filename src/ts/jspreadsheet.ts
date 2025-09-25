/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Mouss <Deltablot>
 * @copyright 2025 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

import { utils, write, WorkBook } from '@e965/xlsx';
import { getBookType, getMime } from './spreadsheet-formats';
import { reloadElements, askFileName, getNewIdFromPostRequest } from './misc';
import { notify } from './notify';
import { FileType, Model } from './interfaces';

const ensureExtension = (name: string, fmt: FileType) => {
  const ext = '.' + String(fmt).toLowerCase();
  return name.toLowerCase().endsWith(ext) ? name : name + ext;
};

const uploadUrl = (entityType: string, entityId: number, uploadId?: number) => {
  const base = `api/v2/${entityType}/${entityId}/${Model.Upload}`;
  return uploadId ? `${base}/${uploadId}` : base;
};

const wbFromAOA = (aoa: (string|number|boolean|null)[][]): WorkBook => {
  const ws = utils.aoa_to_sheet(aoa);
  const wb = utils.book_new();
  utils.book_append_sheet(wb, ws, 'Sheet1');
  return wb;
};

const fileFromWB = (wb: WorkBook, name: string, fmt: FileType) => {
  const bookType = getBookType(fmt);
  const mime = getMime(fmt) ?? 'application/octet-stream';
  const bin = write(wb, { bookType, type: 'array' });
  return new File([bin], name, { type: mime });
};

async function postAndReturnId(file: File, url: string): Promise<number> {
  const fd = new FormData();
  fd.append('file', file);
  const res = await fetch(url, { method: 'POST', body: fd });
  await reloadElements(['uploadsDiv']);
  notify.success();
  return getNewIdFromPostRequest(res);
}

// save current spreadsheet as a new attachment
export async function jssSaveAsAttachment(aoa: (string|number|boolean|null)[][], entityType: string, entityId: number, fmt: FileType = FileType.Xlsx, fileName?: string): Promise<{id:number; name:string} | void> {
  if (!aoa?.length) return;
  const chosen = fileName?.trim()
    ? ensureExtension(fileName.trim(), fmt)
    : askFileName(fmt);
  if (!chosen) return;

  const wb   = wbFromAOA(aoa);
  const file = fileFromWB(wb, chosen, fmt);
  const url  = uploadUrl(entityType, entityId);
  const id   = await postAndReturnId(file, url);
  return { id, name: chosen };
}

// TODO: wip Replace an existing attachment with current sheet
export async function jssReplaceAttachment(aoa: (string|number|boolean|null)[][], entityType: string, entityId: number, uploadId: number, currentName: string, fmt: FileType = FileType.Xlsx
): Promise<{id:number; name:string} | void> {
  if (!aoa?.length || !uploadId || !currentName) return;
  const wb   = wbFromAOA(aoa);
  const file = fileFromWB(wb, currentName, fmt);
  const url  = uploadUrl(entityType, entityId, uploadId);
  const id   = await postAndReturnId(file, url);
  return { id, name: currentName };
}
