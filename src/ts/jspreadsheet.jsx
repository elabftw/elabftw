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

import React, { useEffect, useState, useCallback, useMemo, useRef } from 'react';
import { createRoot } from 'react-dom/client';
import { ModuleRegistry } from '@ag-grid-community/core';
import { ClientSideRowModelModule } from '@ag-grid-community/client-side-row-model';
import { Spreadsheet, Worksheet } from "@jspreadsheet-ce/react";
import "jsuites/dist/jsuites.css";
import "jspreadsheet-ce/dist/jspreadsheet.css";
import * as XLSX from "@e965/xlsx";

ModuleRegistry.registerModules([ClientSideRowModelModule]);

if (document.getElementById('jspreadsheet')) {
  function JSpreadsheet() {
    const spreadsheetRef = useRef(null);
    const [data, setData] = useState([[]]);

    const handleImportFile = (e) => {
      const file = e.target.files[0];
      const reader = new FileReader();

      reader.onload = (evt) => {
        const arr = new Uint8Array(evt.target.result);
        const workbook = XLSX.read(arr, { type: "array", cellStyles: true });
        const firstSheet = workbook.SheetNames[0];
        const worksheet = workbook.Sheets[firstSheet];
        const jsonData = XLSX.utils.sheet_to_json(worksheet, { header: 1 });
        setData(jsonData);
      };
      reader.readAsArrayBuffer(file);
    };

    // Reload the grid when importing data
    useEffect(() => {
      if (spreadsheetRef.current?.[0]) {
        spreadsheetRef.current[0].setData(data);
      }
    }, [data]);

    return (
      <>
        <input type="file" accept=".xlsx" onChange={handleImportFile} />
        <Spreadsheet ref={spreadsheetRef} tabs={true} toolbar={true}>
          <Worksheet data={data} minDimensions={[20, 30]} />
        </Spreadsheet>
      </>
    );
  }

  const el = document.getElementById('jspreadsheet-importer-root');
  if (el) {
    const root = createRoot(el);
    root.render(<JSpreadsheet />);
  }
}

