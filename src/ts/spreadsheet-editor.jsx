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

import React, { useEffect, useState, useCallback, useMemo, useRef } from 'react';
import { createRoot } from 'react-dom/client';
import { AgGridReact } from '@ag-grid-community/react';
import { ModuleRegistry } from '@ag-grid-community/core';
import { ClientSideRowModelModule } from '@ag-grid-community/client-side-row-model';
import '@ag-grid-community/styles/ag-grid.css';
import '@ag-grid-community/styles/ag-theme-alpine.css';
import i18next from './i18n';
import { buildSafeColumnDefs, SpreadsheetEditorHelper } from './SpreadsheetEditorHelper.class';
import { ColumnHeader } from './spreadsheet-editor-column-header';
import { SaveAsAttachmentModal } from './spreadsheet-save-new-modal';
import { getEntity } from './misc';
import { FILE_EXPORT_OPTIONS } from './spreadsheet-formats';
import $ from 'jquery';

ModuleRegistry.registerModules([ClientSideRowModelModule]);

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
    }, [dirty]);

    const clear = () => {
      if (dirty && !confirm(i18next.t('confirm-clear-spreadsheet'))) {
        return;
      }
      setColumnDefs([]);
      setRowData([]);
      setCurrentUploadId(0);
      setCurrentUploadName('');
      setDirty(false);
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
      if (!confirm(i18next.t('delete-confirmation', { num: selected.length }))) {
        return;
      }
      api.applyTransaction({ remove: selected });
      setRowData(prev => {
        const next = prev.filter(r => !selected.includes(r));
        if (next !== prev) setDirty(true);
        return next;
      });
    };

    // helpers that always set dirty (listen to changes)
    const setColumnDefsDirty = useCallback((cols) => { setColumnDefs(cols); setDirty(true); }, []);
    const setRowDataDirty   = useCallback((rows) => { setRowData(rows); setDirty(true); }, []);

    // params passed to the header component
    const headerParams = useMemo(() => ({
      columnDefs,
      rowData,
      setColumnDefs: setColumnDefsDirty,
      setRowData: setRowDataDirty,
    }), [columnDefs, rowData, setColumnDefsDirty, setRowDataDirty]);

    // create virtual cols & rows for the empty spreadsheet feeling (spaces to fill)
    const PAD_ROWS = 10;
    const PAD_COLS = 10;
    // display rows: real rows + PAD_ROWS (virtual ones)
    const displayRowData = useMemo(() => {
      const pads = Array.from({ length: PAD_ROWS }, () => ({ __virtual: true }));
      return [...rowData, ...pads];
    }, [rowData]);
    // same for cols. real cols + virtual ones
    const displayColumnDefs = useMemo(() => {
      const cols = [...columnDefs];

      for (let i = 0; i < PAD_COLS; i++) {
        const field = `__padcol_${i}`;
        cols.push({
          field,
          headerName: '',
          editable: true,
          sortable: false,
          filter: false,
          floatingFilter: false,
          suppressMenu: true,
          cellClass: 'ag-cell--muted',
          headerClass: 'ag-header-cell--muted',
        });
      }
      return cols;
    }, [columnDefs]);

    // now when we edit the cell, we make that virtual col/row exist
    const onCellValueChanged = useCallback((params) => {
      const { colDef, rowIndex, newValue } = params;
      const isPadCol = colDef.field?.startsWith('__padcol_') === true;
      const isPadRow = displayRowData[rowIndex]?.__virtual === true;

      // case 1: User typed in a virtual column → create a real column and move the value there
      if (isPadCol) {
        const newField = `col${nextColIndex.current++}`;
        setColumnDefsDirty([...columnDefs, { field: newField, editable: true }]);

        if (rowIndex >= rowData.length) {
          setRowDataDirty([...rowData, { [newField]: newValue }]);
        } else {
          const copy = [...rowData];
          copy[rowIndex] = { ...copy[rowIndex], [newField]: newValue };
          setRowDataDirty(copy);
        }
        return;
      }

      // case 2: User typed in a virtual row on a real column → append a new real row
      if (isPadRow && rowIndex >= rowData.length) {
        setRowDataDirty([...rowData, { [colDef.field]: newValue }]);
        return;
      }
      // Normal edit on real row/col
      setDirty(true);
    }, [columnDefs, rowData, displayRowData, setColumnDefsDirty, setRowDataDirty]);

    // single source of truth for column defaults + header
    const defaultColDef = useMemo(() => ({
      sortable: true,
      filter: true,
      floatingFilter: true,
      editable: true,
      headerComponent: ColumnHeader,
      headerComponentParams: headerParams,
      cellStyle: { borderRight: '1px solid lightgray'}
    }), [headerParams]);

    function SaveButton() {
      return (
        <>
        {currentUploadId ? (
            // REPLACE EXISTING FILE WITH CURRENT EDITIONS
            <button disabled={!currentUploadId} className='btn hl-hover-gray p-2 lh-normal border-0 mr-2' id='replaceExisting' onClick={() => SpreadsheetHelperC.replaceExisting(columnDefs, rowData, entity.type, entity.id, currentUploadName, currentUploadId).then((res) => {
              if (res?.id) setCurrentUploadId(res.id); // track the latest subid and prevent duplicating
              setDirty(false);
            })} title={i18next.t('replace-existing')} aria-label={i18next.t('replace-existing')} type='button'>
              <i className='fas fa-save fa-fw'></i>
            </button>
          ) : (
            <>
              {/*SAVE AS ATTACHMENT (Opens modal to save the new Upload*/}
              <button id='saveAsAttachment' disabled={isDisabled} className='btn hl-hover-gray d-inline p-2 mr-2' title={i18next.t('save-attachment')} aria-label={i18next.t('save-attachment')} type='button' onClick={() => $('#saveNewSpreadsheetModal').modal?.('show')}>
                <i className='fas fa-save fa-fw' />
              </button>
              {/* The modal itself */}
              <SaveAsAttachmentModal
                id='saveNewSpreadsheetModal'
                isDisabled={isDisabled}
                helper={SpreadsheetHelperC}
                columnDefs={columnDefs}
                rowData={rowData}
                entity={entity}
                exportOptions={FILE_EXPORT_OPTIONS}
                onSaved={(result) => {
                  if (result?.id) {
                    setCurrentUploadId(result.id);
                    setCurrentUploadName(result.name || '');
                  }
                  setDirty(false);
                  $('#saveNewSpreadsheetModal').modal?.('hide');
                }}
              />
            </>
          )}
        </>
      )
    }

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
              {FILE_EXPORT_OPTIONS.map(({ type, icon, labelKey }) => (
                <button key={type} className='dropdown-item' onClick={() => handleExport(type)}>
                  <i className={`fas ${icon} fa-fw`}></i>{i18next.t(labelKey)}
                </button>
              ))}
            </div>
          </div>
          <div className='vertical-separator'></div>
          <SaveButton />

          <span hidden id='spreadsheetUnsavedChangesWarningDiv'>{i18next.t('You have unsaved changes')}</span>
          <div className='vertical-separator'></div>
          {/* ADD NEW ROW */}
          <button disabled={isDisabled} onClick={addRow} className='btn hl-hover-gray d-inline p-2' title={i18next.t('add-row')} type='button'>
            <i className='fas fa-plus-minus fa-fw'></i>
          </button>
          {/* CLEAR */}
          <button disabled={isDisabled} title={i18next.t('clear')} aria-label={i18next.t('clear')} type='button' onClick={clear} className='btn hl-hover-gray p-2 lh-normal border-0 mr-2 ml-auto'>
            <i className='fas fa-trash-alt fa-fw'></i>
          </button>
        </div>
        {isDisabled && <p>{i18next.t('import-spreadsheet')}</p>}
        {currentUploadName && <p>{i18next.t('current-edit')}: <span className='font-weight-bold my-2'>{ currentUploadName }</span></p>}
        {columnDefs.length > 0 && rowData.length > 0 && (
          <>
          {/* parent div to make it resizeable, as it's not built-in in ag-grid */}
          <div style={{ resize: "both", overflow: "auto", height: 600 }} className='mb-2' id='spreadsheetGrid'>
            <div className='ag-theme-alpine' style={{ width: "100%", height: "100%" }}>
              <AgGridReact
                ref={gridRef}
                // rowData={rowData}
                // columnDefs={columnDefs}
                // onCellValueChanged={() => setDirty(true)}
                rowData={displayRowData}
                columnDefs={displayColumnDefs}
                defaultColDef={defaultColDef}
                rowSelection="multiple"
                suppressFieldDotNotation={true}
                pagination={true}
                paginationPageSize={200}
                onCellValueChanged={onCellValueChanged}
              />
            </div>
          </div>
            <button type='button' onClick={removeSelectedRows} className='btn btn-danger btn-sm my-2'>
              {i18next.t('delete-selected')}
            </button>
          </>
        )}
      </div>
    );
  }

  const el = document.getElementById('spreadsheet-importer-root');
  if (el) {
    const root = createRoot(el);
    root.render(<SpreadsheetEditor />);
  }
}

// handle 'use first line as header' modal
const clickHandler = async (event) => {
  const action = event.target.dataset.action;
  if (!action || !['use-header-row', 'use-data-as-header'].includes(action)) return;
  const state = window._sheetImport;
  if (!state) return;

  const { aoa, setColumnDefs, setRowData, setCurrentUploadId } = state;
  delete window._sheetImport;

  const useHeader = action === 'use-header-row';

  // build header row (with names or Column{i})
  const headerRow = useHeader
    ? aoa[0].map((h, i) => (typeof h === 'string' && h.trim() ? h : `Column${i}`))
    : aoa[0].map((_, i) => `Column${i}`);
  // sanitize and build cols & rows
  const cols = buildSafeColumnDefs(headerRow);
  const fields = cols.map(c => c.field);
  const dataRows = useHeader ? aoa.slice(1) : aoa;
  const rows = dataRows.map(cells => {
    const arr = SpreadsheetEditorHelper.normalizeRow(cells, fields.length);
    const obj = {};
    for (let i = 0; i < fields.length; i++) {
      obj[fields[i]] = arr[i]
    }
    return obj;
  });

  setColumnDefs(cols);
  setRowData(rows);
  // disable Save button
  setCurrentUploadId(0);
};

document.getElementById('container').addEventListener('click', event => clickHandler(event));
