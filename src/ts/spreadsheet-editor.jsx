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
import { fileToAOA, replaceAttachment, saveAsAttachment} from './spreadsheet-utils';
import { getEntity } from './misc';
import { assignKey } from './keymaster';

if (document.getElementById('spreadsheetEditorRoot')) {
  function SpreadsheetEditor() {
    const spreadsheetRef = useRef(null);
    // disable keyboard shortcuts completely
    assignKey.filter = () => false;

    const [data, setData] = useState([[]]);
    const [currentUploadId, setCurrentUploadId] = useState(0);
    const [replaceName, setReplaceName] = useState(null);
    // loading state to prevent spamming save btn
    const [isSaving, setIsSaving] = useState(false);

    // refs that always have the latest values (for toolbar onclick)
    const replaceIdRef = useRef(null);
    const replaceNameRef = useRef(null);
    useEffect(() => { replaceIdRef.current = currentUploadId; }, [currentUploadId]);
    useEffect(() => { replaceNameRef.current = replaceName; }, [replaceName]);

    const getAOA = () => spreadsheetRef.current?.[0]?.getData?.() ?? data;
    const entity = getEntity(true);

    // keep tracking the latest upload info
    const keepResult = (res) => {
      if (!res) return;
      if (res.id) setCurrentUploadId(res.id);
      if (res.name) setReplaceName(res.name);
    };

    const onSaveOrReplace = async () => {
      if (isSaving) return;
      setIsSaving(true);
      try {
        const aoa = getAOA();
        const replaceId = replaceIdRef.current;
        const replaceName = replaceNameRef.current;
        let res;
        if (replaceId && replaceName) {
          // REPLACE MODE
          res = await replaceAttachment(aoa, entity.type, entity.id, replaceId, replaceName);
        } else {
          // SAVE MODE
          res = await saveAsAttachment(aoa, entity.type, entity.id);
        }
        keepResult(res);
      } finally {
        window.parent.postMessage('uploadsDiv', window.location.origin);
        setIsSaving(false);
      }
    }

    // reload spreadsheet data after state changes
    useEffect(() => {
      const instance = spreadsheetRef.current?.[0];
      if (instance) instance.setData(data);
    }, [data]);

    // load an attachment into the editor, capture filename & id
    useEffect(() => {
      const onMessage = (event) => {
        if (event.origin !== window.location.origin) return;
        if (event.data?.type === 'jss-load-aoa') {
          const { aoa, name, uploadId } = event.data.detail || {};
          setData(aoa);
          setReplaceName(name ?? null);
          setCurrentUploadId(typeof uploadId === 'number' ? uploadId : null);
        }
      }
      window.addEventListener('message', onMessage);
      return () => window.removeEventListener('message', onMessage);
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

    const clearSpreadsheet = () => {
      if (!window.confirm(i18next.t('confirm-clear-spreadsheet'))) return;
      const inst = spreadsheetRef.current?.[0];
      const empty = [[]];
      inst?.setData?.(empty);
      setData(empty);
      setCurrentUploadId(null);
      setReplaceName(null);
    };

    // CUSTOM TOOLBAR ICONS (they are placed at the end)
    const buildToolbar = (tb) => {
      // we will replace the save button with ours, and add an export button that has the same behavior as default save button
      const saveBtn = tb.items.find(it => it.content === 'save');
      const originalSave = saveBtn && typeof saveBtn.onclick === 'function' ? saveBtn.onclick : null;
      // we will also remove the ones that cannot be saved because of CE limitations, just target the indexes directly
      const indices = new Set([7, 8, 9, 10]);
      tb.items = tb.items.filter((_, i) => !indices.has(i));

      const exportBtn = {
        type: 'icon',
        class: 'ml-2 fas fa-download',
        tooltip: i18next.t('export'),
        // reuse the same handler signature (itemEl, event, spreadsheetInstance)
        onclick: (el, ev, inst) => originalSave(el, ev, inst),
      };
      // replace original save with our custom save function
      Object.assign(saveBtn, {
        // need to blank this property
        content: '',
        type: 'icon',
        class: 'ml-2 fas fa-floppy-disk',
        tooltip: i18next.t('save-attachment'),
        onclick: isSaving ? undefined : onSaveOrReplace,
      });

      const clearBtn = {
        type: 'icon',
        class: 'ml-2 fas fa-trash',
        tooltip: i18next.t('clear'),
        onclick: () => clearSpreadsheet(),
      }
      tb.items.push(
        { type: 'icon', class: 'fas fa-upload', tooltip: i18next.t('import'), onclick: () => document.getElementById('importFileInput').click() },
        exportBtn,
        clearBtn
      );
      return tb;
    }

    return (
      <>
        <input hidden type='file' accept='.xlsx,.csv' onChange={handleImportFile} id='importFileInput' name='file' />
        <Spreadsheet ref={spreadsheetRef} tabs={true} toolbar={buildToolbar}>
          <Worksheet data={data} minDimensions={[12,12]} />
        </Spreadsheet>
      </>
    );
  }

  const el = document.getElementById('spreadsheetEditorRoot');
  if (el) {
    const root = createRoot(el);
    root.render(<SpreadsheetEditor />);
  }
}
