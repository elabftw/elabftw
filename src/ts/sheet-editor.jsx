/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Moustapha <Deltablot>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

import React, { useRef, useEffect, useCallback } from 'react';
import { createRoot } from 'react-dom/client';
import Spreadsheet from 'x-data-spreadsheet';
import { read, utils, writeFileXLSX } from 'xlsx';
// CSS loaded via <link> in head.html template

// helper: convert AoA (array of arrays) object to x-data-spreadsheet format
function aoaToGrid(dataObj) {
  const result = {};
  Object.entries(dataObj).forEach(([name, aoa]) => {
    const sheet = { name, rows: {} };
    aoa.forEach((row, ri) => {
      const rowObj = { cells: {} };
      row.forEach((cell, ci) => {
        rowObj.cells[ci] = { text: cell != null ? String(cell) : '' };
      });
      sheet.rows[ri] = rowObj;
    });
    result[name] = sheet;
  });
  return result;
}

function XSpreadsheetEditor() {
  const containerRef = useRef(null);
  const gridRef = useRef(null);
  const fileInputRef = useRef(null);

  // Initialize grid once
  useEffect(() => {
    if (containerRef.current) {
      gridRef.current = new Spreadsheet(containerRef.current, {
        mode: 'edit',
        showToolbar: true,
        showGrid: true,
        showContextmenu: true,
        view: {
          height: () => containerRef.current.clientHeight,
          width: () => containerRef.current.clientWidth
        }
      });
    }
  }, []);

  const handleImport = useCallback((e) => {
    const file = e.target.files && e.target.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = (ev) => {
      const data = ev.target.result;
      const wb = read(data, { type: 'array' });
      // build AoA object
      const aoaObj = {};
      wb.SheetNames.forEach((name) => {
        const ws = wb.Sheets[name];
        aoaObj[name] = utils.sheet_to_json(ws, { header: 1, raw: false });
      });
      // convert and load
      if (containerRef.current) {
        containerRef.current.innerHTML = '';
        gridRef.current = new Spreadsheet(containerRef.current, {
          mode: 'edit',
          showToolbar: true,
          showGrid: true,
          showContextmenu: true,
          view: {
            height: () => containerRef.current.clientHeight,
            width: () => containerRef.current.clientWidth
          }
        });
        gridRef.current.loadData(aoaToGrid(aoaObj));
        // console.log(aoaObj);
      }
    };
    reader.readAsArrayBuffer(file);
  }, []);

  // Export handler
  const handleExport = useCallback(() => {
    if (!gridRef.current) return;
    const gridData = gridRef.current.getData();
    const aoaObj = {};
    Object.entries(gridData).forEach(([name, sheet]) => {
      const rowsIndex = Object.keys(sheet.rows).map(r => parseInt(r, 10));
      const maxRow = rowsIndex.length ? Math.max(...rowsIndex) : 0;
      const colsIndex = sheet.rows[0]
        ? Object.keys(sheet.rows[0].cells).map(c => parseInt(c, 10))
        : [];
      const maxCol = colsIndex.length ? Math.max(...colsIndex) : 0;
      const aoa = Array.from({ length: maxRow + 1 }, (_, ri) =>
        Array.from({ length: maxCol + 1 }, (_, ci) =>
          sheet.rows[ri] && sheet.rows[ri].cells[ci]
            ? sheet.rows[ri].cells[ci].text
            : ''
        )
      );
      aoaObj[name] = aoa;
    });
    const wb = utils.book_new();
    Object.entries(aoaObj).forEach(([name, aoa]) => {
      const ws = utils.aoa_to_sheet(aoa);
      utils.book_append_sheet(wb, ws, name);
    });
    writeFileXLSX(wb, 'export.xlsx');
  }, []);

  return (
    <div style={{ height: '100%', width: '100%' }}>
      <input
        type="file"
        accept=".xlsx,.xls"
        ref={fileInputRef}
        style={{ display: 'none' }}
        onChange={handleImport}
      />
      <button
        className="btn hl-hover-gray p-2 mr-2"
        onClick={() => fileInputRef.current && fileInputRef.current.click()}
        title="Import"
        aria-label="Import"
        type="button"
      >
        <i className="fas fa-upload fa-fw" />
      </button>
      <button
        className="btn hl-hover-gray p-2"
        onClick={handleExport}
        title="Export"
        aria-label="Export"
        type="button"
      >
        <i className="fas fa-download fa-fw" />
      </button>
      <div
        id="xspreadsheet"
        ref={containerRef}
        style={{ height: '400px', width: '100%', marginTop: '10px' }}
      />
    </div>
  );
}

document.addEventListener('DOMContentLoaded', () => {
  const el = document.getElementById('sheet-importer-root');
  if (el) {
    const root = createRoot(el);
    root.render(<XSpreadsheetEditor />);
  }
});
