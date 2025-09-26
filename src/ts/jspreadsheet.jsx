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
import { ModuleRegistry } from '@ag-grid-community/core';
import { ClientSideRowModelModule } from '@ag-grid-community/client-side-row-model';
import { Spreadsheet, Worksheet } from "@jspreadsheet-ce/react";
import "jsuites/dist/jsuites.css";
import "jspreadsheet-ce/dist/jspreadsheet.css";
import i18next from './i18n';
import { fileToAOA, replaceAttachment, saveAsAttachment} from './jspreadsheet.utils';
import { getEntity } from './misc';

ModuleRegistry.registerModules([ClientSideRowModelModule]);

if (document.getElementById('jspreadsheet')) {
  function JSpreadsheet() {
    const spreadsheetRef = useRef(null);
    const [data, setData] = useState([[]]);
    const [currentUploadId, setCurrentUploadId] = useState(0);
    const entity = getEntity();

    // CUSTOM TOOLBAR ICONS (they are placed at the end)
    const toolbar = (toolbar) => {
      toolbar.items.push(
        {
          tooltip: i18next.t('import'),
          content: 'upload',
          onclick: () => document.getElementById('importFileInput').click()
        },
        {
          tooltip: i18next.t('save-attachment'),
          content: 'attachment',
          onclick: onSave
        },
        {
          tooltip: i18next.t('replace-existing'),
          content: 'upload_file',
          onclick: onReplace
        }
      );
      return toolbar;
    }

    const getAOA = () => spreadsheetRef.current?.[0]?.getData?.() ?? data;

    const onSave = async () => {
      const aoa = getAOA();
      await saveAsAttachment(aoa, entity.type, entity.id);
    };

    //TODO: implement replace existing
    const onReplace = async () => {
      const aoa = getAOA();
      await replaceAttachment(aoa, entity.type, entity.id, currentUploadId, replaceName);
    };

    const handleImportFile = async (e) => {
      const file = e.target.files?.[0];
      if (!file) return;
      const aoa = await fileToAOA(file);
      setData(aoa);
    };

    // Reload the grid when importing data
    useEffect(() => {
      if (spreadsheetRef.current?.[0]) {
        spreadsheetRef.current[0].setData(data);
      }
    }, [data]);
    // load an attachment into the editor
    useEffect(() => {
      document.addEventListener('jss-load-aoa', (e) => {
        const { aoa } = e.detail;
        setData(aoa);
      });
    })
    return (
      <>
        <input type='file' accept='.xlsx,.csv' onChange={handleImportFile} id='importFileInput' hidden name='file' />
        <Spreadsheet id='jspreadsheetDiv' ref={spreadsheetRef} tabs={true} toolbar={toolbar}>
          <Worksheet data={data} minDimensions={[10,10]} />
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
