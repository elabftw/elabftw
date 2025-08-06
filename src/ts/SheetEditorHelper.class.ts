/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Mouss <Deltablot>
 * @copyright 2025 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

import { Action, FileType, GridColumn, GridRow, Model } from './interfaces';
import { askFileName, getNewIdFromPostRequest, reloadElements } from './misc';
import { Notification } from './Notifications.class';
import { read, utils, write, writeFile, writeFileXLSX } from 'xlsx';
import { Api } from './Apiv2.class';

declare global {
  interface Window {
    _sheetImport?: {
      aoa: (string | number | boolean | null)[][];
      setColumnDefs: (cols: GridColumn[]) => void;
      setRowData: (rows: GridRow[]) => void;
    };
  }
}

const notify = new Notification();

export class SheetEditorHelper {
  api: Api;
  currentUploadId = '';
  currentFilename = '';

  constructor() {
    this.api = new Api();
  }

  loadInSheetEditor(link: string, name: string, uploadId: string): void {
    const headers = new Headers();
    headers.append('cache-control', 'no-cache');
    fetch(`app/download.php?f=${link}`, { headers })
      .then(response => {
        if (!response.ok) throw new Error('An unexpected error occurred!');
        return response.arrayBuffer();
      })
      .then(buffer => {
        const aoa = SheetEditorHelper.parseFileToAOA(buffer);
        const { cols, rows } = SheetEditorHelper.aoaToGrid(aoa);
        const ev = new CustomEvent('sheet-load-data', { detail: { cols, rows, name } });
        document.dispatchEvent(ev);

        this.currentUploadId = uploadId;
        this.currentFilename = name;
      })
      .catch(e => notify.error(e.message));
  }

  loadWithHeaderChoice(file: File, setColumnDefs: (cols: GridColumn[]) => void, setRowData: (rows: GridRow[]) => void): void {
    const reader = new FileReader();
    reader.onload = function(event) {
      try {
        const aoa = SheetEditorHelper.parseFileToAOA(event.target!.result as ArrayBuffer);
        // Attach the parsed AOA and the callbacks for the modal handler
        window._sheetImport = { aoa, setColumnDefs, setRowData };
        // 'use first line as header?' modal
        $('#sheetModal').modal('show');
      } catch (error) {
        notify.error(error.message);
      }
    };
    reader.readAsArrayBuffer(file);
  }

  handleExport(format: FileType, columnDefs: GridColumn[], rowData: GridRow[]): void {
    if (!columnDefs.length || !rowData.length) return;
    const headers = columnDefs.map(col => col.field);
    const aoa = [headers, ...rowData.map(row => headers.map(h => row[h]))];
    const ws = utils.aoa_to_sheet(aoa);
    const wb = utils.book_new();
    utils.book_append_sheet(wb, ws, 'Sheet1');

    switch (format) {
    case FileType.Csv:
      writeFile(wb, 'export.csv', { bookType: 'csv' });
      break;
    case FileType.Fods:
      writeFile(wb, 'export.fods', { bookType: 'fods' });
      break;
    case FileType.Html:
      writeFile(wb, 'export.html', { bookType: 'html' });
      break;
    case FileType.Ods:
      writeFile(wb, 'export.ods', { bookType: 'ods' });
      break;
    case FileType.Xls:
      writeFile(wb, 'export.xls', { bookType: 'xls' });
      break;
    case FileType.Xlsb:
      writeFile(wb, 'export.xlsb', { bookType: 'xlsb' });
      break;
    case FileType.Xlsx:
      writeFile(wb, 'export.xlsx', { bookType: 'xlsx' });
      break;
    default:
      writeFileXLSX(wb, 'export.xlsx');
    }
  }


  saveAsAttachment(columnDefs: GridColumn[], rowData: GridRow[], entityType: string, entityId: number): void {
    if (!columnDefs.length || !rowData.length) return;
    const realName = askFileName(FileType.Xlsx);
    if (!realName) return;

    const headers = columnDefs.map(col => col.field);
    const aoa = [headers, ...rowData.map(row => headers.map(h => row[h]))];
    const ws = utils.aoa_to_sheet(aoa);
    const wb = utils.book_new();
    utils.book_append_sheet(wb, ws, 'Sheet1');

    const content = write(wb, { bookType: 'xlsx', type: 'array' });
    const params = {
      action: Action.CreateFromString,
      file_type: FileType.Xlsx,
      real_name: realName,
      content: `data:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;base64,${content}`,
    };

    this.api.post(`${entityType}/${entityId}/${Model.Upload}`, params)
      .then(resp => {
        this.currentUploadId = String(getNewIdFromPostRequest(resp));
        reloadElements(['uploadsDiv']);
      })
      .catch(e => notify.error(e.message));
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
}
