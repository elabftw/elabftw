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
        {/* DOWNLOAD AS FILE */}
        <button className='btn hl-hover-gray p-2 mr-2' onClick={handleDownload} title={i18next.t('save')} type='button'>
          <i className='fas fa-download fa-fw'></i>
        </button>
        <div className='vertical-separator'></div>
        {/* SAVE AS ATTACHMENT (uploads section) */}
        <button className='btn hl-hover-gray p-2 mr-2' onClick={() => {}} title={i18next.t('save-attachment')} type='button'>
          <i className='fas fa-paperclip fa-fw'></i>
        </button>
      </div>
      {columnDefs.length > 0 && rowData.length > 0 && (
        <>
          <div className='ag-theme-alpine' style={{ height: 400, marginTop: 10 }}>
            <AgGridReact
              rowData={rowData}
              columnDefs={columnDefs}
              defaultColDef={{ sortable: true, filter: true, editable: true }}
            />
          </div>
          <div className='btn-group mt-2'>
            {/* TODO: make a switch to select the export type*/}
            <button onClick={() => handleExport(FileType.Xlsx)} className='btn btn-primary'>{i18next.t('export')} XLSX</button>
            <button onClick={() => handleExport(FileType.Xlsb)} className='btn btn-secondary'>{i18next.t('export')} XLSB</button>
            <button onClick={() => handleExport(FileType.Csv)} className='btn btn-secondary'>{i18next.t('export')} CSV</button>
            <button onClick={() => handleExport(FileType.Html)} className='btn btn-secondary'>{i18next.t('export')} HTML</button>
            {/* TODO: styling button in header like json editor*/}
            <button onClick={addRow} className='btn btn-success'>Add Row</button>
            <button onClick={addColumn} className='btn btn-info'>Add Column</button>
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
});
