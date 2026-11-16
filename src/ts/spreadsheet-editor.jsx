/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Moustapha <Deltablot>
 * @copyright 2025 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

/**
 * Code related to the excel tables present on the view/edit pages of an entity
 * Jspreadsheet-CE integration
 */

import React, { useEffect, useState, useRef } from 'react';
import { createRoot } from 'react-dom/client';
import { Spreadsheet, Worksheet } from "@jspreadsheet-ce/react";
import "jsuites/dist/jsuites.css";
import "jspreadsheet-ce/dist/jspreadsheet.css";
import i18next from './i18n';
import { fileToWorkbook } from './spreadsheet-utils';
import { assignKey } from './keymaster';
import { errorMessage } from './misc';
import { notify } from './notify';

function SpreadsheetEditor() {
  // disable keyboard shortcuts completely
  assignKey.filter = () => false;

  const [workbook, setWorkbook] = useState([{ name: 'Sheet1', data: [[]] }]);
  const [spreadsheetRevision, setSpreadsheetRevision] = useState(0);
  const isDirtyRef = useRef(false);
  const portRef = useRef(null);
  const workbookRef = useRef(workbook);
  const workbookReaderRef = useRef(null);

  // on changes in the spreadsheet, notify that there's unsaved changes
  const setUnsavedWarning = (visible) => {
    isDirtyRef.current = visible;
    portRef.current?.postMessage({ type: 'jss-dirty', dirty: visible });
  };

  const markUnsaved = () => setUnsavedWarning(true);

  // if Dirty state, ask user if he wants to save before leaving the page
  useEffect(() => {
    const handleBeforeUnload = (event) => {
      if (!isDirtyRef.current) return;
      event.preventDefault();
      event.returnValue = '';
    };
    window.addEventListener('beforeunload', handleBeforeUnload);
    return () => window.removeEventListener('beforeunload', handleBeforeUnload);
  }, []);

  // Establish a private channel before accepting spreadsheet data.
  useEffect(() => {
    const onPortMessage = (event) => {
      if (event.data?.type === 'jss-load-workbook') {
        const { workbook: nextWorkbook } = event.data.detail || {};
        if (!isWorkbook(nextWorkbook)) return;
        workbookRef.current = nextWorkbook;
        setWorkbook(nextWorkbook);
        setSpreadsheetRevision(revision => revision + 1);
        setUnsavedWarning(false);
      } else if (event.data?.type === 'jss-saved') {
        setUnsavedWarning(false);
      } else if (event.data?.type === 'jss-workbook-request' && typeof event.data.requestId === 'string') {
        portRef.current?.postMessage({
          type: 'jss-workbook-response',
          requestId: event.data.requestId,
          workbook: workbookReaderRef.current?.() ?? workbookRef.current,
        });
      }
    };
    const onMessage = (event) => {
      if (event.source !== window.parent) return;
      if (event.data?.type === 'jss-ping') {
        window.parent.postMessage({ type: 'jss-ready' }, '*');
      } else if (event.data?.type === 'jss-connect' && !portRef.current) {
        const port = event.ports?.[0];
        if (!port) return;
        portRef.current = port;
        port.addEventListener('message', onPortMessage);
        port.start();
        port.postMessage({ type: 'jss-connected' });
      }
    };
    window.addEventListener('message', onMessage);
    window.parent.postMessage({ type: 'jss-ready' }, '*');
    return () => {
      window.removeEventListener('message', onMessage);
      portRef.current?.removeEventListener('message', onPortMessage);
      portRef.current?.close();
      portRef.current = null;
    };
  }, []);

  /* local import/export actions included in the toolbar */
  // import a new file from computer
  const handleImportFile = async (e) => {
    const input = e.target;
    const file = input.files?.[0];
    if (!file) return;
    try {
      const nextWorkbook = await fileToWorkbook(file);
      workbookRef.current = nextWorkbook;
      setWorkbook(nextWorkbook);
      setSpreadsheetRevision(revision => revision + 1);
      setUnsavedWarning(true);
      portRef.current?.postMessage({ type: 'jss-new-document' });
    } catch (error) {
      notify.error(errorMessage(error, 'Unexpected error while importing spreadsheet.'));
    } finally {
      input.value = '';
    }
  };

  const clearSpreadsheet = () => {
    if (!window.confirm(i18next.t('confirm-clear-spreadsheet'))) return;
    const empty = [{ name: 'Sheet1', data: [[]] }];
    workbookRef.current = empty;
    setWorkbook(empty);
    setSpreadsheetRevision(revision => revision + 1);
    setUnsavedWarning(true);
    portRef.current?.postMessage({ type: 'jss-new-document' });
  };

  const toggleFullscreen = () => {
    if (document.fullscreenElement) {
      document.exitFullscreen().catch(err => notify.error(err));
    } else {
      const el = document.documentElement;
      if (el.requestFullscreen) {
        el.requestFullscreen().catch(err => notify.error(err));
      }
    }
  };
  // CUSTOM TOOLBAR ICONS (they are placed at the end)
  const buildToolbar = (tb) => {
    // Keep the built-in save button as a local export only.
    const saveBtn = tb.items.find(it => it.content === 'save');
    const unsupportedControls = new Set(['format_bold', 'format_color_text', 'format_color_fill', 'fullscreen']);
    const verticalAlignmentOptions = ['vertical_align_top', 'vertical_align_center', 'vertical_align_bottom'];
    tb.items = tb.items.filter(item => {
      const isVerticalAlignment = item.type === 'select'
        && Array.isArray(item.options)
        && verticalAlignmentOptions.every(option => item.options.includes(option));
      return !unsupportedControls.has(item.content) && !isVerticalAlignment;
    });

    if (saveBtn) {
        saveBtn.content = 'file_download';
        saveBtn.tooltip = i18next.t('export');
    }
    // we render the spreadsheet in an iframe, so we'll also use a custom fullscreen button
    const fullscreenBtn = { type: 'icon', class: 'mx-2 fas fa-expand', tooltip: i18next.t('fullscreen'), onclick: () => toggleFullscreen()};
    const clearBtn = { type: 'icon', class: 'fas fa-trash', tooltip: i18next.t('clear'), onclick: clearSpreadsheet };
    const importBtn = { type: 'icon', class: 'fas fa-upload', tooltip: i18next.t('import'), onclick: () => document.getElementById('importFileInput').click() };
    tb.items.splice(tb.items.indexOf(saveBtn), 0, importBtn);
    tb.items.push(fullscreenBtn, clearBtn);
    return tb;
  };
  return (
    <>
      <input hidden type='file' accept='.xlsx,.csv,.ods' onChange={handleImportFile} id='importFileInput' name='file' />
      {/* move Spreadsheet into a child component to safely re-init on file uploads */}
      <SpreadsheetInner
        key={spreadsheetRevision}
        workbook={workbook}
        workbookReaderRef={workbookReaderRef}
        buildToolbar={buildToolbar}
        onSpreadsheetChange={markUnsaved}
      />
    </>
  );
}
const isWorkbook = (value) => Array.isArray(value)
  && value.length > 0
  && value.every(worksheet => typeof worksheet?.name === 'string' && Array.isArray(worksheet.data));

const cloneJsonValue = (value) => {
  if (value === undefined) return undefined;
  try {
    return JSON.parse(JSON.stringify(value));
  } catch {
    return undefined;
  }
};

// jspreadsheet keeps formatting, merges and dimension config outside the data grid:
// pick the option keys that describe the worksheet state so they can be saved with it
const pickWorksheetState = (options) => {
  if (!options || typeof options !== 'object') return undefined;
  const state = {};
  for (const key of ['comments', 'classes', 'meta', 'nestedHeaders', 'footers', 'freezeColumns']) {
    const value = cloneJsonValue(options[key]);
    if (value !== undefined) state[key] = value;
  }
  if (options.style && typeof options.style === 'object') {
    // every rendered cell gets an entry with just the default alignment: keep only real styles
    const defaultAlign = `text-align: ${options.defaultColAlign || 'center'};`;
    const style = {};
    for (const [cell, css] of Object.entries(options.style)) {
      if (typeof css === 'string' && css !== defaultAlign) style[cell] = css;
    }
    if (Object.keys(style).length) state.style = style;
  }
  if (options.mergeCells && typeof options.mergeCells === 'object') {
    const mergeCells = {};
    for (const [cell, range] of Object.entries(options.mergeCells)) {
      if (Array.isArray(range) && Number.isFinite(range[0]) && Number.isFinite(range[1])) {
        mergeCells[cell] = [range[0], range[1]];
      }
    }
    if (Object.keys(mergeCells).length) state.mergeCells = mergeCells;
  }
  if (Array.isArray(options.columns)) {
    const columns = options.columns.map(col => (
      col && typeof col === 'object' && typeof col.width === 'number' ? { width: col.width } : {}
    ));
    if (columns.some(col => col.width)) state.columns = columns;
  }
  if (Array.isArray(options.rows)) {
    const rows = options.rows.map(row => (
      row && typeof row === 'object' && typeof row.height === 'number' ? { height: row.height } : {}
    ));
    if (rows.some(row => row.height)) state.rows = rows;
  }
  return Object.keys(state).length ? state : undefined;
};

function SpreadsheetInner({ workbook, workbookReaderRef, buildToolbar, onSpreadsheetChange }) {
  const spreadsheetRef = useRef(null);
  useEffect(() => {
    workbookReaderRef.current = () => {
      const worksheets = spreadsheetRef.current;
      if (!Array.isArray(worksheets) || worksheets.length === 0) return workbook;
      return worksheets.map((worksheet, index) => {
        // displayed values let the saved file cache formula results
        let values;
        try {
          values = worksheet.getData?.(false, true) ?? workbook[index]?.values;
        } catch {
          values = workbook[index]?.values;
        }
        // ragged rows can yield undefined cells that would fail workbook validation
        const data = (worksheet.getData?.() ?? workbook[index]?.data ?? [[]]).map(
          row => (Array.isArray(row) ? row.map(cell => cell ?? '') : row),
        );
        return {
          name: worksheet.options?.worksheetName || workbook[index]?.name || `Sheet${index + 1}`,
          data,
          values,
          state: pickWorksheetState(worksheet.options),
        };
      });
    };
    return () => {
      workbookReaderRef.current = null;
    };
  }, [workbook, workbookReaderRef]);
  return (
    <Spreadsheet
      ref={spreadsheetRef}
      tabs={true}
      toolbar={buildToolbar}
      onchange={onSpreadsheetChange}
      oncreateworksheet={onSpreadsheetChange}
      ondeleteworksheet={onSpreadsheetChange}
    >
      {workbook.map((worksheet, index) => (
        <Worksheet
          key={index}
          {...(worksheet.state ?? {})}
          worksheetName={worksheet.name}
          data={worksheet.data}
          minDimensions={[
            Math.max(12, worksheet.data[0]?.length || 0),
            Math.max(12, worksheet.data.length)
          ]}
        />
      ))}
    </Spreadsheet>
  );
}

const el = document.getElementById('spreadsheetEditorRoot');
if (el) {
  const root = createRoot(el);
  root.render(<SpreadsheetEditor />);
}
