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
import { SheetEditorHelper } from './SheetEditorHelper.class';
import {ColumnHeader} from './sheet-editor-column-header';
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

function SheetEditor() {
  const sheetHelperC = useRef(new SheetEditorHelper()).current;
  const [columnDefs, setColumnDefs] = useState([]);
  const [rowData, setRowData] = useState([]);
  const fileInputRef = useRef();
  const gridRef = useRef();

  useEffect(() => {
    const handleData = (e) => {
      const { cols, rows } = e.detail;
      setColumnDefs(cols);
      setRowData(rows);
    };
    document.addEventListener('sheet-load-data', handleData);
    return () => {
      document.removeEventListener('sheet-load-data', handleData);
    };
  }, []);

  const handleFile = useCallback((e) => {
    const file = e.target.files?.[0];
    if (!file) return;
    sheetHelperC.loadWithHeaderChoice(file, setColumnDefs, setRowData);
  }, [sheetHelperC]);

  const handleExport = useCallback((format) => {
    sheetHelperC.handleExport(format, columnDefs, rowData);
  }, [sheetHelperC, columnDefs, rowData]);


  // add a row next to the selected line.
  // If no row is selected, it just adds it at the end
  const addRow = () => {
    const api = gridRef.current.api;
    // https://www.ag-grid.com/react-data-grid/data-update-transactions/#transaction-update-api
    const selectedNodes = api.getSelectedNodes();
    const newRow = {};
    columnDefs.forEach(col => {
      newRow[col.field] = '';
    });
    const index = selectedNodes.length > 0
      ? selectedNodes[0].rowIndex + 1
      : rowData.length;
    api.applyTransaction({
      add: [newRow],
      addIndex: index,
    });
  };

  const removeSelectedRows = () => {
    const api = gridRef.current.api;
    const selected = api.getSelectedRows();
    if (!confirm(`Delete ${selected.length} line(s)?`)) {
      return;
    }
    api.applyTransaction({ remove: selected });
    setRowData(prev => prev.filter(r => !selected.includes(r)));
  };

  return (
    <div className='sheet-editor'>
      <input type='file' accept='.csv,.xls,.xlsx,.ods,.fods,.xlsb' ref={fileInputRef} className='d-none' onChange={handleFile} />
      <div className='d-flex align-items-center'>
        {/* IMPORT BUTTON */}
        <button className='btn hl-hover-gray p-2 mr-2' onClick={() => fileInputRef.current?.click()} title={i18next.t('import')} type='button'>
          <i className='fas fa-upload fa-fw'></i>
        </button>
        {/* EXPORT BUTTON: Select with different types */}
        <div className='dropdown' disabled={!columnDefs.length}>
          <button className='btn hl-hover-gray d-inline p-2 mr-2' title={i18next.t('export')} data-toggle='dropdown' aria-haspopup='true' aria-expanded='false' aria-label={i18next.t('export')} type='button'>
            <i className='fas fa-download fa-fw'></i>
          </button>
          <div className='dropdown-menu'>
            {fileExportOptions.map(({ type, icon, labelKey }) => (
              <button disabled={!columnDefs.length} key={type} className="dropdown-item" onClick={() => handleExport(type)}>
                <i className={`fas ${icon} fa-fw`}></i>{i18next.t(labelKey)}
              </button>
            ))}
          </div>
        </div>
        <div className='vertical-separator'></div>
        {/* SAVE AS ATTACHMENT (uploads section) */}
        <button disabled={!columnDefs.length} className='btn hl-hover-gray p-2 mr-2' onClick={() => sheetHelperC.saveAsAttachment(columnDefs, rowData, entity.type, entity.id)} title={i18next.t('save-attachment')} type='button'>
          <i className='fas fa-paperclip fa-fw'></i>
        </button>
        <button disabled={!columnDefs.length}  className='btn hl-hover-gray p-2 lh-normal border-0 mr-2' onClick={() => sheetHelperC.replaceExisting(columnDefs, rowData, entity.type, entity.id)} title={i18next.t('save')} aria-label={i18next.t('save')} type='button'>
          <i className='fas fa-save fa-fw'></i>
        </button>
        <div className='vertical-separator'></div>
        <button disabled={!columnDefs.length} onClick={addRow} className='btn hl-hover-gray d-inline p-2' title={i18next.t('add-row')} type='button'>
          <i className='fas fa-plus-minus fa-fw'></i>
        </button>
      </div>
      {!columnDefs.length && <p>{i18next.t('import-sheet')}</p>}
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
                  columnDefs, rowData, setColumnDefs, setRowData
                }
              }))}
              defaultColDef={{ sortable: true, filter: true, editable: true }}
              rowSelection='multiple'
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
  const el = document.getElementById('sheet-importer-root');
  if (el) {
    const root = createRoot(el);
    root.render(<SheetEditor />);
  }
  // handle 'use first line as header' modal
  document.body.addEventListener('click', event => {
    const target = event.target;
    if (!(target instanceof HTMLElement)) return;

    const action = target.getAttribute('data-action');
    if (!action || !['use-header-row', 'use-data-as-header'].includes(action)) return;

    const state = window._sheetImport;
    if (!state) return;

    const { aoa, setColumnDefs, setRowData } = state;
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
  });
});
