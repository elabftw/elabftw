/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Moustapha <Deltablot>
 * @copyright 2025 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

/**
 * Code related to the excel tables present on the Edit page of an entity
 * SheetJs integration (xlsx) with AG-Grid
 */

import React, { useEffect, useState, useCallback, useRef } from 'react';
import { createRoot } from 'react-dom/client';
import { AgGridReact } from '@ag-grid-community/react';
import { ModuleRegistry } from '@ag-grid-community/core';
import { ClientSideRowModelModule } from '@ag-grid-community/client-side-row-model';
import '@ag-grid-community/styles/ag-grid.css';
import '@ag-grid-community/styles/ag-theme-alpine.css';
import { FileType } from './interfaces';
import i18next from './i18n';
import { SpreadsheetEditorHelper } from './SpreadsheetEditorHelper.class';
import { ColumnHeader } from './spreadsheet-editor-column-header';
import { getEntity } from './misc';

ModuleRegistry.registerModules([ClientSideRowModelModule]);

const fileExportOptions = [
  { type: FileType.Csv, icon: 'fa-file-csv', labelKey: 'CSV' },
  { type: FileType.Xls, icon: 'fa-file-excel', labelKey: 'XLS' },
  { type: FileType.Xlsx, icon: 'fa-file-excel', labelKey: 'XLSX' },
  { type: FileType.Ods, icon: 'fa-file-excel', labelKey: 'ODS' },
  { type: FileType.Fods, icon: 'fa-file-excel', labelKey: 'FODS' },
  { type: FileType.Xlsb, icon: 'fa-file-excel', labelKey: 'XLSB' },
  { type: FileType.Html, icon: 'fa-file-code', labelKey: 'HTML' },
];
const entity = getEntity();

if (document.getElementById('spreadsheetEditor')) {
  function SpreadsheetEditor() {
    const SpreadsheetHelperC = useRef(new SpreadsheetEditorHelper()).current;
    const [columnDefs, setColumnDefs] = useState([]);
    const [rowData, setRowData] = useState([]);
    const fileInputRef = useRef();
    const gridRef = useRef();
    const isDisabled = columnDefs.length === 0;
    const [currentUploadId, setCurrentUploadId] = useState(0);
    const [currentUploadName, setCurrentUploadName] = useState('');
    const nextColIndex = useRef(1);
    // track unsaved changes
    const [dirty, setDirty] = useState(false);

    useEffect(() => {
      const handleData = (e) => {
        const { cols, rows, name, uploadId } = e.detail;
        setColumnDefs(cols);
        setRowData(rows);
        setCurrentUploadId(uploadId);
        setCurrentUploadName(name);
        setDirty(false);
      };
      document.addEventListener('sheet-load-data', handleData);
      return () => {
        document.removeEventListener('sheet-load-data', handleData);
      };
    }, []);

    // handle dirty state (unsaved changes)
    useEffect(() => {
      const saveBtn = document.getElementById('replaceExisting');
      const exportBtn = document.getElementById('exportBtn');
      const attachBtn = document.getElementById('saveAsAttachment');
      const warn = document.getElementById('spreadsheetUnsavedChangesWarningDiv');

      if (dirty) {
        saveBtn?.classList.add('border-danger');
        attachBtn?.classList.add('border-danger');
        exportBtn?.classList.add('border-danger');
        warn?.removeAttribute('hidden');
      } else {
        saveBtn?.classList.remove('border-danger');
        attachBtn?.classList.remove('border-danger');
        exportBtn?.classList.remove('border-danger');
        if (warn) {
          warn.setAttribute('hidden', 'hidden');
        }
      }
    }, [dirty])

    const clear = () => {
      setColumnDefs([]);
      setRowData([]);
      setCurrentUploadId(0);
      setCurrentUploadName('');
    };

    const createNewSpreadsheet = () => {
      // reset the counter
      nextColIndex.current = 1;
      // generate a unique field for the first column
      const firstField = `col${nextColIndex.current++}`;
      const initialColumn = [{
        field: firstField,
        headerName: 'Column0',
        editable: true,
        colId: firstField,
      }];
      const initialRow = [{ [firstField]: '' }];
      setColumnDefs(initialColumn);
      setRowData(initialRow);
      setCurrentUploadId(0);
      setCurrentUploadName('');
      setDirty(true);
    };

    const handleImport = useCallback((e) => {
      const file = e.target.files?.[0];
      if (!file) return;
      SpreadsheetHelperC.loadWithHeaderChoice(file, setColumnDefs, setRowData, setCurrentUploadId);
    }, [SpreadsheetHelperC]);

    const handleExport = useCallback((format) => {
      SpreadsheetHelperC.handleExport(format, columnDefs, rowData).then(() => setDirty(false));
    }, [SpreadsheetHelperC, columnDefs, rowData]);

    // add a row next to the selected line. When no row is selected, it's added at the bottom line.
    const addRow = useCallback(() => {
      const api = gridRef.current.api;
      // https://www.ag-grid.com/react-data-grid/data-update-transactions/#transaction-update-api
      const selectedNodes = api.getSelectedNodes();
      // figure out the insertion index
      const insertIndex = selectedNodes.length > 0
        ? selectedNodes[0].rowIndex + 1
        : rowData.length;
      // build your new empty row
      const newRow = {};
      columnDefs.forEach(col => { newRow[col.field] = '' });
      // update React state, adding a new column takes into account existing rows.
      const updated = [
        ...rowData.slice(0, insertIndex),
        newRow,
        ...rowData.slice(insertIndex),
      ];
      setRowData(updated);
      setDirty(true);
    }, [columnDefs, rowData]);

    const removeSelectedRows = () => {
      const api = gridRef.current.api;
      const selected = api.getSelectedRows();
      if (!confirm(`Delete ${selected.length} line(s)?`)) {
        return;
      }
      api.applyTransaction({ remove: selected });
      setRowData(prev => {
        const next = prev.filter(r => !selected.includes(r));
        if (next !== prev) setDirty(true);
        return next;
      });
    };

    return (
      <div className='spreadsheet-editor'>
        <input type='file' accept='.csv,.xls,.xlsx,.ods,.fods,.xlsb' ref={fileInputRef} className='d-none' onChange={handleImport} />
        <div className='d-flex align-items-center'>
          {/* NEW SPREADSHEET BUTTON */}
          <button className='btn hl-hover-gray p-2 main-action-button lh-normal border-0' onClick={createNewSpreadsheet} title={i18next.t('new-spreadsheet')} aria-label={i18next.t('new-spreadsheet')} type='button'>
            <i className='fas fa-plus fa-fw'></i>
          </button>
          <div className='vertical-separator'></div>
          {/* IMPORT BUTTON */}
          <button className='btn hl-hover-gray p-2 mr-2' onClick={() => fileInputRef.current?.click()} title={i18next.t('import')} type='button'>
            <i className='fas fa-upload fa-fw'></i>
          </button>
          {/* EXPORT BUTTON: Select with different types */}
          <div className='dropdown'>
            <button id='exportBtn' disabled={isDisabled} className='btn hl-hover-gray d-inline p-2 mr-2' title={i18next.t('export')} data-toggle='dropdown' aria-haspopup='true' aria-expanded='false' aria-label={i18next.t('export')} type='button'>
              <i className='fas fa-download fa-fw'></i>
            </button>
            <div className='dropdown-menu'>
              {fileExportOptions.map(({ type, icon, labelKey }) => (
                <button key={type} className="dropdown-item" onClick={() => handleExport(type)}>
                  <i className={`fas ${icon} fa-fw`}></i>{i18next.t(labelKey)}
                </button>
              ))}
            </div>
          </div>
          <div className='vertical-separator'></div>
          {/* SAVE AS ATTACHMENT (uploads section) */}
          <button disabled={isDisabled} className='btn hl-hover-gray p-2 mr-2' id='saveAsAttachment' onClick={() => SpreadsheetHelperC.saveAsAttachment(columnDefs, rowData, entity.type, entity.id).then(() => setDirty(false))} title={i18next.t('save-attachment')} type='button'>
            <i className='fas fa-paperclip fa-fw'></i>
          </button>
          {/* REPLACE EXISTING FILE WITH CURRENT EDITIONS */}
          <button disabled={!currentUploadId} className='btn hl-hover-gray p-2 lh-normal border-0 mr-2' id='replaceExisting' onClick={() => SpreadsheetHelperC.replaceExisting(columnDefs, rowData, entity.type, entity.id, currentUploadName, currentUploadId).then(() => setDirty(false))} title={i18next.t('replace-existing')} aria-label={i18next.t('replace-existing')} type='button'>
            <i className='fas fa-save fa-fw'></i>
          </button>
          <span hidden id='spreadsheetUnsavedChangesWarningDiv'>{i18next.t('You have unsaved changes')}</span>
          <div className='vertical-separator'></div>
          {/* ADD NEW ROW */}
          <button disabled={isDisabled} onClick={addRow} className='btn hl-hover-gray d-inline p-2' title={i18next.t('add-row')} type='button'>
            <i className='fas fa-plus-minus fa-fw'></i>
          </button>
          {/* CLEAR */}
          <button disabled={isDisabled} title={i18next.t('clear')} aria-label={i18next.t('add-row')} type='button' onClick={clear} className='btn hl-hover-gray p-2 lh-normal border-0 mr-2 ml-auto'>
            <i className='fas fa-trash-alt fa-fw'></i>
          </button>
        </div>
        {isDisabled && <p>{i18next.t('import-spreadsheet')}</p>}
        {columnDefs.length > 0 && rowData.length > 0 && (
          <>
            <div className='ag-theme-alpine' style={{ height: 400, marginTop: 10 }}>
              <AgGridReact
                ref={gridRef}
                rowData={rowData}
                columnDefs={columnDefs.map(col => ({
                  ...col,
                  headerComponent: ColumnHeader,
                  headerComponentParams: {
                    columnDefs,
                    rowData,
                    setColumnDefs: (cols) => { setColumnDefs(cols); setDirty(true); },
                    setRowData: (rows) => { setRowData(rows); setDirty(true); },
                  }
                }))}
                defaultColDef={{ sortable: true, filter: true, editable: true }}
                rowSelection='multiple'
                onCellValueChanged={() => setDirty(true)}
              />
            </div>
            <button type='button' onClick={removeSelectedRows} className='btn btn-danger btn-sm my-2'>
              Delete Selected Rows
            </button>
          </>
        )}
      </div>
    );
  }

  document.addEventListener('DOMContentLoaded', () => {
    const el = document.getElementById('spreadsheet-importer-root');
    if (el) {
      const root = createRoot(el);
      root.render(<SpreadsheetEditor />);
    }
    // handle 'use first line as header' modal
    document.body.addEventListener('click', event => {
      const target = event.target;
      if (!(target instanceof HTMLElement)) return;

      const action = target.getAttribute('data-action');
      if (!action || !['use-header-row', 'use-data-as-header'].includes(action)) return;

      const state = window._sheetImport;
      if (!state) return;

      const { aoa, setColumnDefs, setRowData, setCurrentUploadId } = state;
      delete window._sheetImport;

      const useHeader = action === 'use-header-row';
      const headerRow = useHeader
        ? aoa[0].map((h, i) => typeof h === 'string' ? h : `Column${i}`)
        : aoa[0].map((_, i) => `Column${i}`);

      const dataRows = useHeader ? aoa.slice(1) : aoa;
      const rows = dataRows.map(r => {
        const row = {};
        headerRow.forEach((h, i) => {
          row[h] = String(r[i] ?? '');
        });
        return row;
      });

      const cols = headerRow.map(h => ({ field: h, editable: true }));
      setColumnDefs(cols);
      setRowData(rows);
      // need to reset the current Upload ID to disable the "Replace existing file" button and prevent rewriting existing file with currently loaded sheet
      setCurrentUploadId(0);
    });
  });
}
