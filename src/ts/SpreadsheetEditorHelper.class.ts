/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Mouss <Deltablot>
 * @copyright 2025 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

import { FileType, GridColumn, GridRow, Model } from './interfaces';
import { getBookType, getMime } from './spreadsheet-formats';
import { askFileName, reloadElements } from './misc';
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

export class SpreadsheetEditorHelper {
  async loadInSpreadsheetEditor(link: string, name: string, uploadId: number): Promise<void> {
    try {
      const res = await fetch(`app/download.php?f=${link}`, {
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
      const wb = SpreadsheetEditorHelper.createWorkbookFromGrid(columnDefs, rowData);
      const bookType = getBookType(format);
      writeFile(wb, realName, { bookType });
      notify.success();
    } catch (e) {
      notify.error(e.message);
    }
  }

  // saves the current sheet as an upload for the entity. (.csv)
  async saveAsAttachment(format: FileType, columnDefs: GridColumn[], rowData: GridRow[], entityType: string, entityId: number): Promise<void> {
    if (!columnDefs.length || !rowData.length) {
      return;
    }
    const realName = askFileName(format);
    const wb = SpreadsheetEditorHelper.createWorkbookFromGrid(columnDefs, rowData);
    const file = SpreadsheetEditorHelper.workbookToFile(wb, realName, format);
    const url = SpreadsheetEditorHelper.uploadUrl(entityType, entityId);
    await SpreadsheetEditorHelper.uploadWorkbook(file, url);
  }

  async replaceExisting(columnDefs: GridColumn[], rowData: GridRow[], entityType: string, entityId: number, currentUploadName: string, currentUploadId: number):  Promise<void> {
    if (!columnDefs.length || !rowData.length || !currentUploadName || !currentUploadId) {
      return;
    }
    const wb = SpreadsheetEditorHelper.createWorkbookFromGrid(columnDefs, rowData);
    const wbBinary = write(wb, { bookType: FileType.Csv, type: 'array' });
    const file = new File([wbBinary], currentUploadName, {
      type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    });
    await SpreadsheetEditorHelper.uploadWorkbook(file, SpreadsheetEditorHelper.uploadUrl(entityType, entityId, currentUploadId));
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

  private static aoaToGrid(aoa: (string | number | boolean | null)[][]): { cols: GridColumn[]; rows: GridRow[] } {
    const headerRow = aoa[0].map((h, i) => (typeof h === 'string' ? h : `Column${i}`));
    const rows: GridRow[] = aoa.slice(1).map((r) => {
      const row: GridRow = {};
      headerRow.forEach((h, i) => {
        row[h] = String(r[i] ?? '');
      });
      return row;
    });
    const cols: GridColumn[] = headerRow.map((h) => ({ field: h, editable: true }));
    return { cols, rows };
  }

  private static parseFileToAOA(buffer: ArrayBuffer): (string | number | boolean | null)[][] {
    const wb = read(buffer, { type: 'array' });
    const ws = wb.Sheets[wb.SheetNames[0]];
    const aoa = utils.sheet_to_json(ws, { header: 1 }) as (string | number | boolean | null)[][];
    if (!aoa.length) throw new Error('Invalid file');
    return aoa;
  }

  private static createWorkbookFromGrid(columnDefs: GridColumn[], rowData: GridRow[]): WorkBook {
    const headers = columnDefs.map(col => col.field);
    const aoa = [headers, ...rowData.map(row => headers.map(h => row[h] ?? ''))];
    const ws = utils.aoa_to_sheet(aoa);
    const wb = utils.book_new();
    utils.book_append_sheet(wb, ws, 'Sheet1');
    return wb;
  }
}
