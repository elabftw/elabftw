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
    const [replaceName, setReplaceName] = useState(null);

    // refs that always have the latest values (for toolbar onclick)
    const replaceIdRef = useRef(null);
    const replaceNameRef = useRef(null);
    useEffect(() => { replaceIdRef.current = currentUploadId; }, [currentUploadId]);
    useEffect(() => { replaceNameRef.current = replaceName; }, [replaceName]);

    const getAOA = () => spreadsheetRef.current?.[0]?.getData?.() ?? data;
    const entity = getEntity();

    const onSaveOrReplace = async () => {
      const aoa = getAOA();
      const replaceId = replaceIdRef.current;
      const replaceName = replaceNameRef.current;
      if (replaceId && replaceName) {
        // REPLACE MODE
        const res = await replaceAttachment(aoa, entity.type, entity.id, replaceId, replaceName);
        // keep tracking the latest subid
        if (res?.id) setCurrentUploadId(res.id);
      } else {
        // SAVE MODE
        const res = await saveAsAttachment(aoa, entity.type, entity.id);
        if (res?.id) { setCurrentUploadId(res.id); setReplaceName(res.name); }
      }
    }

    // reload spreadsheet data after state changes
    useEffect(() => {
      const instance = spreadsheetRef.current?.[0];
      if (instance) instance.setData(data);
    }, [data]);

    // load an attachment into the editor, capture filename & id
    useEffect(() => {
      const onLoad = (e) => {
        const { aoa, name, uploadId } = e.detail || {};
        setData(aoa);
        setReplaceName(name ?? null);
        setCurrentUploadId(typeof uploadId === 'number' ? uploadId : null);
      };
      document.addEventListener('jss-load-aoa', onLoad);
      return () => document.addEventListener('jss-load-aoa', onLoad);
    }, []);

    /* actions (import, save, replace) included in the toolbar */
    // import a new file from computer
    const handleImportFile = async (e) => {
      const file = e.target.files?.[0];
      if (!file) return;
      const aoa = await fileToAOA(file);
      setData(aoa);
      // clear any current spreadsheet id tracking
      setCurrentUploadId(null);
      setReplaceName(null);
    };

    // CUSTOM TOOLBAR ICONS (they are placed at the end)
    const toolbar = (toolbar) => {
      toolbar.items.push(
        { content: 'upload', tooltip: i18next.t('import'), onclick: () => document.getElementById('importFileInput').click() },
        { content: 'attachment', tooltip: i18next.t('save-attachment'), onclick: onSaveOrReplace }
      );
      return toolbar;
    }

    return (
      <>
        <input hidden type='file' accept='.xlsx,.csv' onChange={handleImportFile} id='importFileInput' name='file' />
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
