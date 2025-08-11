/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Mouss <Deltablot>
 * @copyright 2025 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

import { FileType, GridColumn, GridRow, Model } from './interfaces';
import { askFileName, reloadElements } from './misc';
import { Notification } from './Notifications.class';
import { read, utils, write, writeFile, WorkBook } from '@e965/xlsx';
import { Api } from './Apiv2.class';

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

const notify = new Notification();

export class SpreadsheetEditorHelper {
  api: Api;

  constructor() {
    this.api = new Api();
  }

  loadInSpreadsheetEditor(link: string, name: string, uploadId: string): void {
    const headers = new Headers();
    headers.append('cache-control', 'no-cache');
    fetch(`app/download.php?f=${link}`, { headers })
      .then(response => {
        if (!response.ok) throw new Error('An unexpected error occurred!');
        return response.arrayBuffer();
      })
      .then(buffer => {
        const aoa = SpreadsheetEditorHelper.parseFileToAOA(buffer);
        const { cols, rows } = SpreadsheetEditorHelper.aoaToGrid(aoa);
        const ev = new CustomEvent('sheet-load-data', { detail: { cols, rows, name, uploadId } });
        document.dispatchEvent(ev);
      })
      .catch(e => notify.error(e.message));
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
      } catch (error) {
        notify.error(error.message);
      }
    };
    reader.readAsArrayBuffer(file);
  }

  handleExport(format: FileType, columnDefs: GridColumn[], rowData: GridRow[]): void {
    if (!columnDefs.length || !rowData.length) return;
    const wb = SpreadsheetEditorHelper.createWorkbookFromGrid(columnDefs, rowData);
    const realName = askFileName(FileType.Csv);
    if (!realName) return;
    switch (format) {
    case FileType.Fods:
      writeFile(wb, realName, { bookType: 'fods' });
      break;
    case FileType.Html:
      writeFile(wb, realName, { bookType: 'html' });
      break;
    case FileType.Ods:
      writeFile(wb, realName, { bookType: 'ods' });
      break;
    case FileType.Xls:
      writeFile(wb, realName, { bookType: 'xls' });
      break;
    case FileType.Xlsb:
      writeFile(wb, realName, { bookType: 'xlsb' });
      break;
    case FileType.Xlsx:
      writeFile(wb, realName, { bookType: 'xlsx' });
      break;
    default:
      writeFile(wb, realName, { bookType: 'csv' });
    }
    notify.success();
  }

  // saves the current sheet as an upload for the entity. (.csv)
  saveAsAttachment(columnDefs: GridColumn[], rowData: GridRow[], entityType: string, entityId: number): void {
    // should behave the same as Export. Make a modal with grouped input, [input-name, ., FileTypeEnum]
    if (!columnDefs.length || !rowData.length) return;
    const realName = askFileName(FileType.Csv);
    if (!realName) return;

    const wb = SpreadsheetEditorHelper.createWorkbookFromGrid(columnDefs, rowData);
    const wbBinary = write(wb, { bookType: FileType.Csv, type: 'array' });
    const file = new File([wbBinary], realName, {
      type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    });
    SpreadsheetEditorHelper.uploadWorkbook(file, `api/v2/${entityType}/${entityId}/${Model.Upload}`);
  }

  replaceExisting(columnDefs: GridColumn[], rowData: GridRow[], entityType: string, entityId: number, currentUploadName: string, currentUploadId: number): void {
    if (!columnDefs.length || !rowData.length || !currentUploadName || !currentUploadId) return;

    const wb = SpreadsheetEditorHelper.createWorkbookFromGrid(columnDefs, rowData);
    const wbBinary = write(wb, { bookType: FileType.Csv, type: 'array' });
    const file = new File([wbBinary], currentUploadName, {
      type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    });
    SpreadsheetEditorHelper.uploadWorkbook(file, `api/v2/${entityType}/${entityId}/${Model.Upload}/${currentUploadId}`);
  }

  // convert array of arrays to grid
  private static aoaToGrid(aoa: (string | number | boolean | null)[][]): { cols: GridColumn[], rows: GridRow[] } {
    const headerRow = aoa[0].map((h, i) => typeof h === 'string' ? h : `Column${i}`);
    const rows: GridRow[] = aoa.slice(1).map(r => {
      const row: GridRow = {};
      headerRow.forEach((h, i) => {
        row[h] = String(r[i] ?? '');
      });
      return row;
    });
    const cols: GridColumn[] = headerRow.map(h => ({
      field: h,
      editable: true,
    }));
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

  private static uploadWorkbook(file: File, url: string): void {
    const formData = new FormData();
    formData.append('file', file);
    fetch(url, { method: 'POST', body: formData })
      .then(() => reloadElements(['uploadsDiv']))
      .then(() => notify.success())
      .catch(e => notify.error(e.message));
  }
}
