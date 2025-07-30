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

import React, { useState, useCallback, useRef } from 'react';
import { createRoot } from 'react-dom/client';
import { read, utils, writeFile, writeFileXLSX } from 'xlsx';
import { AgGridReact } from '@ag-grid-community/react';
import { ModuleRegistry } from '@ag-grid-community/core';
import { ClientSideRowModelModule } from '@ag-grid-community/client-side-row-model';
import '@ag-grid-community/styles/ag-grid.css';
import '@ag-grid-community/styles/ag-theme-alpine.css';
import { Notification } from './Notifications.class';
import { FileType } from './interfaces';

ModuleRegistry.registerModules([ClientSideRowModelModule]);

function SheetEditor() {
  const [columnDefs, setColumnDefs] = useState([]);
  const [rowData, setRowData] = useState([]);
  const fileInputRef = useRef();

  const handleFile = useCallback((e) => {
    const file = e.target.files?.[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = function(event) {
      try {
        const wb = read(event.target.result, { type: 'array' });
        const ws = wb.Sheets[wb.SheetNames[0]];
        const aoa = utils.sheet_to_json(ws, { header: 1 });
        if (!aoa.length) return;
        const headers = aoa[0];
        const rows = aoa.slice(1).map((r, index) => {
          const row = {};
          headers.forEach((h, j) => row[h || `Column${j}`] = r[j] ?? '');
          return row;
        });
        const columns = headers.map((header, index) => ({ field: header || `Column${index}`, editable: true }));
        setColumnDefs(columns);
        setRowData(rows);
      } catch (error) {
        (new Notification()).error(error);
      }
    };
    reader.readAsArrayBuffer(file);
  }, []);

  const handleExport = useCallback((format) => {
    if (!columnDefs.length || !rowData.length) return;
    const headers = columnDefs.map(col => col.field);
    // TODO: typescript: when migrating, see https://docs.sheetjs.com/docs/demos/grid/rdg/#integration-details
    // Array of arraysis the most generic data representation
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
  }, [columnDefs, rowData]);

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

  /*
              // when using f()addColumn, the column is named ColumnX but we might want to rename it.
              // since ag-grid doesn't allow editing headers, let's use a modal for user input
              onColumnHeaderClicked={(params) => {
                if (!params.column) return;
                const oldField = params.column.getColDef().field;
                const newField = prompt('Rename column:', oldField);
                if (!newField || newField === oldField) return;

                const updatedColumns = columnDefs.map(col =>
                  col.field === oldField ? { ...col, field: newField } : col
                );
                const updatedRows = rowData.map(row => {
                  const newRow = { ...row, [newField]: row[oldField] };
                  delete newRow[oldField];
                  return newRow;
                });

                setColumnDefs(updatedColumns);
                setRowData(updatedRows);
              }}
   */
   return (
    <div className='sheet-editor'>
      <input
        type='file'
        accept='.xlsx,.xls'
        ref={fileInputRef}
        className='d-none'
        onChange={handleFile}
      />
      <button
        className='btn hl-hover-gray p-2 mr-2'
        onClick={() => fileInputRef.current?.click()}
        title='Import'
        type='button'
      >
        <i className='fas fa-upload fa-fw' />
      </button>
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
            <button onClick={() => handleExport(FileType.Xlsx)} className='btn btn-primary'>Export XLSX</button>
            <button onClick={() => handleExport(FileType.Xlsb)} className='btn btn-secondary'>Export XLSB</button>
            <button onClick={() => handleExport(FileType.Csv)} className='btn btn-secondary'>Export CSV</button>
            <button onClick={() => handleExport(FileType.Html)} className='btn btn-secondary'>Export HTML</button>
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
