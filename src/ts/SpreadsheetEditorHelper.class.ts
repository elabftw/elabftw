/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Mouss <Deltablot>
 * @copyright 2025 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

import $ from 'jquery';
import { FileType, GridColumn, GridRow, Model } from './interfaces';
import { getBookType, getMime } from './spreadsheet-formats';
import { askFileName, getNewIdFromPostRequest, reloadElements } from './misc';
import { notify } from './notify';
import { read, utils, write, writeFile, WorkBook } from '@e965/xlsx';

declare global {
  interface Window {
    _sheetImport?: {
      aoa: (string | number | boolean | null)[][];
      setColumnDefs: (cols: GridColumn[]) => void;
      setRowData: (rows: GridRow[]) => void;
      setCurrentUploadId: (uploadId: number) => void;
    };
  }
}

type Cell = string | number | boolean | null;

export class SpreadsheetEditorHelper {
  async loadInSpreadsheetEditor(link: string, name: string, uploadId: number): Promise<void> {
    try {
      const res = await fetch(`app/download.php?f=${encodeURIComponent(link)}`, {
        headers: new Headers({ 'cache-control': 'no-cache' }),
      });
      if (!res.ok) throw new Error('An unexpected error occurred fetching the file!');
      const buffer = await res.arrayBuffer();
      const aoa = SpreadsheetEditorHelper.parseFileToAOA(buffer);
      const { cols, rows } = SpreadsheetEditorHelper.aoaToGrid(aoa);
      const ev = new CustomEvent('sheet-load-data', { detail: { cols, rows, name, uploadId } });
      document.dispatchEvent(ev);
    } catch (e) {
      notify.error(e.message);
    }
  }

  loadWithHeaderChoice(file: File, setColumnDefs: (cols: GridColumn[]) => void, setRowData: (rows: GridRow[]) => void, setCurrentUploadId: (uploadId: number) => void): void {
    const reader = new FileReader();
    reader.onload = function(event) {
      try {
        const aoa = SpreadsheetEditorHelper.parseFileToAOA(event.target!.result as ArrayBuffer);
        // Attach the parsed AOA and the callbacks for the modal handler
        window._sheetImport = { aoa, setColumnDefs, setRowData, setCurrentUploadId };
        // 'use first line as header?' modal
        $('#spreadsheetModal').modal('show');
      } catch (e) {
        notify.error(e.message);
      }
    };
    reader.readAsArrayBuffer(file);
  }

  async handleExport(format: FileType, columnDefs: GridColumn[], rowData: GridRow[]): Promise<void> {
    try {
      if (!columnDefs.length || !rowData.length) {
        return;
      }
      const realName = askFileName(format);
      if (!realName) return;
      const wb = SpreadsheetEditorHelper.createWorkbookFromGrid(columnDefs, rowData);
      const bookType = getBookType(format);
      writeFile(wb, realName, { bookType });
      notify.success();
    } catch (e) {
      notify.error(e.message);
    }
  }

  static async uploadWorkbookAndReturnId(file: File, url: string): Promise<number> {
    const formData = new FormData();
    formData.append('file', file);
    const res = await fetch(url, { method: 'POST', body: formData });

    // keep UI behavior
    await reloadElements(['uploadsDiv']);
    notify.success();

    // reuse your parser
    return getNewIdFromPostRequest(res);
  }

  // saves the current sheet as an upload for the entity. (.csv)
  async saveAsAttachment(format: FileType, columnDefs: GridColumn[], rowData: GridRow[], entityType: string, entityId: number, fileName?: string): Promise<{ id: number; name: string } | void>  {
    if (!columnDefs.length || !rowData.length) {
      return;
    }
    const chosenName = fileName && fileName.trim()
      ? ensureExtension(fileName.trim(), format)
      : askFileName(format);

    if (!chosenName) return;

    const wb   = SpreadsheetEditorHelper.createWorkbookFromGrid(columnDefs, rowData);
    const file = SpreadsheetEditorHelper.workbookToFile(wb, chosenName, format);
    const url = SpreadsheetEditorHelper.uploadUrl(entityType, entityId);
    const id = await SpreadsheetEditorHelper.uploadWorkbookAndReturnId(file, url);
    return { id, name: chosenName };
  }

  async replaceExisting(columnDefs: GridColumn[], rowData: GridRow[], entityType: string, entityId: number, currentUploadName: string, currentUploadId: number): Promise<{ id: number; name: string } | void> {
    if (!columnDefs.length || !rowData.length || !currentUploadName || !currentUploadId) {
      return;
    }
    const wb = SpreadsheetEditorHelper.createWorkbookFromGrid(columnDefs, rowData);
    const wbBinary = write(wb, { bookType: FileType.Csv, type: 'array' });
    const file = new File([wbBinary], currentUploadName, {
      type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    });
    const url  = SpreadsheetEditorHelper.uploadUrl(entityType, entityId, currentUploadId);
    const newId = await SpreadsheetEditorHelper.uploadWorkbookAndReturnId(file, url);
    return { id: newId, name: currentUploadName };
  }

  private static workbookToFile(wb: WorkBook, name: string, format: FileType): File {
    const bookType = getBookType(format);
    const mime = getMime(format) ?? 'application/octet-stream';
    const bin = write(wb, { bookType, type: 'array' });
    return new File([bin], name, { type: mime });
  }

  private static uploadUrl(entityType: string, entityId: number, uploadId?: number): string {
    const base = `api/v2/${entityType}/${entityId}/${Model.Upload}`;
    return uploadId ? `${base}/${uploadId}` : base;
  }

  private static async uploadWorkbook(file: File, url: string): Promise<void> {
    const formData = new FormData();
    formData.append('file', file);
    await fetch(url, { method: 'POST', body: formData });
    await reloadElements(['uploadsDiv']);
    notify.success();
  }

  private static aoaToGrid(aoa: Cell[][]): { cols: GridColumn[]; rows: GridRow[] } {
    const headerRaw = Array.isArray(aoa[0]) ? aoa[0] : [];
    const cols = buildSafeColumnDefs(headerRaw);
    const fields = cols.map(c => c.field);
    const width = fields.length;
    const rows: GridRow[] = aoa.slice(1).map(r => {
      const arr = SpreadsheetEditorHelper.normalizeRow(r, width);
      const obj: GridRow = {};
      for (let i = 0; i < width; i++) obj[fields[i]] = arr[i];
      return obj;
    });
    return { cols, rows };
  }

  private static parseFileToAOA(buffer: ArrayBuffer): Cell[][] {
    const wb = read(buffer, { type: 'array' });
    const ws = wb.Sheets[wb.SheetNames[0]];
    // keep empty cells, drop truly empty rows
    const aoa = utils.sheet_to_json(ws, {
      header: 1,
      blankrows: false,
      // important: we preserve empties so columns stay aligned. We don't want the columns to be under wrong title
      // https://stackoverflow.com/a/66859139
      defval: '',
      raw: true,
    }) as Cell[][];
    if (!aoa.length) throw new Error('Invalid file');
    return aoa;
  }

  // normalize function
  public static normalizeRow(cells: ReadonlyArray<Cell>, width: number): string[] {
    const out = new Array<string>(width);
    for (let i = 0; i < width; i++) {
      out[i] = String(cells?.[i] ?? '');
    }
    return out;
  }

  private static createWorkbookFromGrid(columnDefs: GridColumn[], rowData: GridRow[]): WorkBook {
    const headerLabels = columnDefs.map(c => (c.headerName && String(c.headerName).trim()) || c.field);
    const fields = columnDefs.map(c => c.field);
    const aoa = [headerLabels, ...rowData.map(row => fields.map(f => row[f] ?? ''))];
    const ws = utils.aoa_to_sheet(aoa);
    const wb = utils.book_new();
    utils.book_append_sheet(wb, ws, 'Sheet1');
    return wb;
  }
}

// Reuse this everywhere you need to normalize a name
export function ensureExtension(name: string, format: FileType): string {
  const ext = `.${String(format).toLowerCase()}`;
  return name.toLowerCase().endsWith(ext) ? name : name + ext;
}

// not part of the helper directly because spreadsheet-editor.jsx clickHandler is using it, and React can't instantiate the helper twice in the same component
export function buildSafeColumnDefs(rawHeaders: unknown[]): GridColumn[] {
  const used = new Set<string>();
  return rawHeaders.map((val, i) => {
    // start from a string
    let base = (typeof val === 'string' ? val : '').trim();
    if (!base) base = `Column${i}`;
    // ag grid dislikes some characters in field ids (e.g., control chars)
    let id = base.replace(/\s+/g, '_').replace(/[^\w.-]/g, '');
    if (!id) id = `col_${i}`;
    // ensure uniqueness (Column1, Column1_2, Column1_3, …) for when we have large databases
    let unique = id, k = 2;
    while (used.has(unique)) unique = `${id}_${k++}`;
    used.add(unique);
    return { field: unique, colId: unique, headerName: base, editable: true };
  });
}
