/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Mouss <Deltablot>
 * @copyright 2025 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

import { GridColumn, GridRow, FileType } from './interfaces';
import { Notification } from './Notifications.class';
import { read, utils, writeFile, writeFileXLSX } from 'xlsx';

const notify = new Notification();

export class SheetEditorHelper {
  currentUploadId = '';
  currentFilename = '';

  loadInSheetEditor(link: string, name: string, uploadId: string): void {
    const headers = new Headers();
    headers.append('cache-control', 'no-cache');
    fetch(`app/download.php?f=${link}`, { headers })
      .then(response => {
        if (!response.ok) throw new Error('An unexpected error occurred!');
        return response.arrayBuffer();
      })
      .then(buffer => {
        const wb = read(buffer, { type: 'array' });
        const ws = wb.Sheets[wb.SheetNames[0]];
        const aoa: (string | number | boolean | null)[][] = utils.sheet_to_json(ws, { header: 1 });

        if (!aoa.length) return notify.error('Invalid file');

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
          editable: true
        }));

        const ev = new CustomEvent('sheet-load-data', { detail: { cols, rows, name } });
        document.dispatchEvent(ev);

        this.currentUploadId = uploadId;
        this.currentFilename = name;
      })
      .catch(e => notify.error(e.message));
  }

  handleImport(file: File, setColumnDefs: (cols: GridColumn[]) => void, setRowData: (rows: GridRow[]) => void): void {
    const reader = new FileReader();
    reader.onload = function (event) {
      try {
        const wb = read(event.target?.result, { type: 'array' });
        const ws = wb.Sheets[wb.SheetNames[0]];
        // aoa is the most generic type for sheet data
        const aoa = utils.sheet_to_json(ws, { header: 1 }) as (string | number | boolean | null)[][];
        if (!aoa.length) return;

        const headerRow = aoa[0].map((h, i) => typeof h === 'string' ? h : `Column${i}`);
        const rows: GridRow[] = aoa.slice(1).map(r => {
          const row: GridRow = {};
          headerRow.forEach((h, i) => {
            row[h] = String(r[i] ?? '');
          });
          return row;
        });

        const cols: GridColumn[] = headerRow.map(h => ({ field: h, editable: true }));
        setColumnDefs(cols);
        setRowData(rows);
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
      case FileType.Xlsb:
        writeFile(wb, 'export.xlsb', { bookType: 'xlsb' });
        break;
      case FileType.Csv:
        writeFile(wb, 'export.csv', { bookType: 'csv' });
        break;
      case FileType.Html:
        writeFile(wb, 'export.html', { bookType: 'html' });
        break;
      default:
        writeFileXLSX(wb, 'export.xlsx');
    }
  }
}
