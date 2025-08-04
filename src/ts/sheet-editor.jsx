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

ModuleRegistry.registerModules([ClientSideRowModelModule]);

function SheetEditor() {
  const sheetHelperC = useRef(new SheetEditorHelper()).current;
  const [columnDefs, setColumnDefs] = useState([]);
  const [rowData, setRowData] = useState([]);
  const fileInputRef = useRef();

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
    sheetHelperC.handleImport(file, setColumnDefs, setRowData);
  }, [sheetHelperC]);

  const handleExport = useCallback((format) => {
    sheetHelperC.handleExport(format, columnDefs, rowData);
  }, [sheetHelperC, columnDefs, rowData]);

  const addRow = () => {
    const newRow = {};
    columnDefs.forEach(col => {
      newRow[col.field] = '';
    });
    setRowData([...rowData, newRow]);
  };

  const addColumn = () => {
    const newField = `Column${columnDefs.length}`;
    const newCol = { field: newField, editable: true };
    const updatedColumns = [...columnDefs, newCol];
    const updatedRows = rowData.map(row => ({ ...row, [newField]: '' }));
    setColumnDefs(updatedColumns);
    setRowData(updatedRows);
  };

  const handleDownload = () => {
    if (!columnDefs.length || !rowData.length) return;
    const headers = columnDefs.map(col => col.field);
    const aoa = [headers, ...rowData.map(row => headers.map(h => row[h]))];
    const ws = utils.aoa_to_sheet(aoa);
    const wb = utils.book_new();
    utils.book_append_sheet(wb, ws, 'Sheet1');
    writeFileXLSX(wb, 'sheet-export.xlsx');
  };
  return (
    <div className='sheet-editor'>
      <div className='d-flex align-items-center'>
        {/* IMPORT FROM FILE */}
        <input type='file' accept='.csv,.xls,.xlsx,.ods,.ots,.fods,.xlsb' ref={fileInputRef} className='d-none' onChange={handleFile} />
        <button className='btn hl-hover-gray p-2 mr-2' onClick={() => fileInputRef.current?.click()} title={i18next.t('import')} type='button'>
          <i className='fas fa-upload fa-fw' />
        </button>
        <div className='vertical-separator'></div>
        {/* SAVE AS ATTACHMENT (uploads section) */}
        <button className='btn hl-hover-gray p-2 mr-2' onClick={() => {}} title={i18next.t('save-attachment')} type='button'>
          <i className='fas fa-paperclip fa-fw'></i>
        </button>
      </div>
      {columnDefs.length > 0 && rowData.length > 0 && (
        <>
          <div className='btn-group mt-2'>
            <div className='dropdown'>
              <button className='btn hl-hover-gray d-inline p-2 mr-2' title={i18next.t('export')} data-toggle='dropdown' aria-haspopup='true' aria-expanded='false' aria-label={i18next.t('export')} type='button'>
                <i className='fas fa-download fa-fw'></i>
              </button>
              <div className='dropdown-menu'>
                <button className='dropdown-item' onClick={() => handleExport(FileType.Csv)}>
                  <i className='fas fa-file-csv fa-fw'></i>{i18next.t('CSV File')}
                </button>
                <button className='dropdown-item' onClick={() => handleExport(FileType.Xls)}>
                  <i className='fas fa-file-excel fa-fw'></i>{i18next.t('XLS File')}
                </button>
                <button className='dropdown-item' onClick={() => handleExport(FileType.Xlsx)}>
                  <i className='fas fa-file-excel fa-fw'></i>{i18next.t('XLSX File')}
                </button>
                <button className='dropdown-item' onClick={() => handleExport(FileType.Ods)}>
                  <i className='fas fa-file-excel fa-fw'></i>{i18next.t('ODS File')}
                </button>
                <button className='dropdown-item' onClick={() => handleExport(FileType.Ots)}>
                  <i className='fas fa-file-excel fa-fw'></i>{i18next.t('OTS File')}
                </button>
                <button className='dropdown-item' onClick={() => handleExport(FileType.Fods)}>
                  <i className='fas fa-file-excel fa-fw'></i>{i18next.t('FODS File')}
                </button>
                <button className='dropdown-item' onClick={() => handleExport(FileType.Xlsb)}>
                  <i className='fas fa-file-excel fa-fw'></i>{i18next.t('XLSB File')}
                </button>
                <button className='dropdown-item' onClick={() => handleExport(FileType.Html)}>
                  <i className='fas fa-file-code fa-fw'></i>{i18next.t('HTML File')}
                </button>
              </div>
            </div>
            <button onClick={addRow} className='btn hl-hover-gray d-inline p-2 mr-2' title={i18next.t('add-row')}>
              <i className='fas fa-plus-minus fa-fw'></i>
            </button>
            <button onClick={addColumn} className='btn hl-hover-gray d-inline p-2 mr-2' title={i18next.t('add-column')}>
              <i className='fas fa-plus fa-fw'></i>
            </button>
          </div>
          <div className='ag-theme-alpine' style={{ height: 400, marginTop: 10 }}>
            <AgGridReact
              rowData={rowData}
              columnDefs={columnDefs}
              defaultColDef={{ sortable: true, filter: true, editable: true }}
            />
          </div>
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
