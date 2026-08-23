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
import { fileToAOA } from './spreadsheet-utils';
import { assignKey } from './keymaster';
import { notify } from './notify';

function SpreadsheetEditor() {
  // disable keyboard shortcuts completely
  assignKey.filter = () => false;

  const [data, setData] = useState([[]]);
  const [spreadsheetRevision, setSpreadsheetRevision] = useState(0);
  const isDirtyRef = useRef(false);

  // on changes in the spreadsheet, notify that there's unsaved changes
  const setUnsavedWarning = (visible) => {
    isDirtyRef.current = visible;
    window.parent.postMessage({ type: 'jss-dirty', dirty: visible }, '*');
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

  // load an attachment into the editor, capture filename & id
  useEffect(() => {
    const onMessage = (event) => {
      if (event.source !== window.parent) return;
      if (event.data?.type === 'jss-ping') {
        window.parent.postMessage({ type: 'jss-ready' }, '*');
      } else if (event.data?.type === 'jss-load-aoa') {
        const { aoa } = event.data.detail || {};
        setData(aoa);
        setSpreadsheetRevision(revision => revision + 1);
        setUnsavedWarning(false);
      } else if (event.data?.type === 'jss-saved') {
        setUnsavedWarning(false);
      }
    };
    window.addEventListener('message', onMessage);
    window.parent.postMessage({ type: 'jss-ready' }, '*');
    return () => window.removeEventListener('message', onMessage);
  }, []);

  /* local import/export actions included in the toolbar */
  // import a new file from computer
  const handleImportFile = async (e) => {
    const file = e.target.files?.[0];
    if (!file) return;
    const aoa = await fileToAOA(file);
    setData(aoa);
    setSpreadsheetRevision(revision => revision + 1);
    setUnsavedWarning(true);
    window.parent.postMessage({ type: 'jss-new-document' }, '*');
    // clear input too
    e.target.value = '';
  };

  const clearSpreadsheet = () => {
    if (!window.confirm(i18next.t('confirm-clear-spreadsheet'))) return;
    const empty = [[]];
    setData(empty);
    setSpreadsheetRevision(revision => revision + 1);
    setUnsavedWarning(true);
    window.parent.postMessage({ type: 'jss-new-document' }, '*');
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
    // we will also remove the ones that cannot be saved because of CE limitations, just target the indexes directly
    // 7,8,9,10,14 indexes are for: format_bold, format_color_text, format_color_fill, select, fullscreen
    const indices = new Set([7, 8, 9, 10, 14]);
    tb.items = tb.items.filter((_, i) => !indices.has(i));

    Object.assign(saveBtn, { tooltip: i18next.t('export') });
    // we render the spreadsheet in an iframe, so we'll also use a custom fullscreen button
    const fullscreenBtn = { type: 'icon', class: 'mx-2 fas fa-expand', tooltip: i18next.t('fullscreen'), onclick: () => toggleFullscreen()};
    const clearBtn = { type: 'icon', class: 'ml-2 fas fa-trash', tooltip: i18next.t('clear'), onclick: clearSpreadsheet };
    const importBtn = { type: 'icon', class: 'fas fa-upload', tooltip: i18next.t('import'), onclick: () => document.getElementById('importFileInput').click() };
    tb.items.push(fullscreenBtn, importBtn, clearBtn);
    return tb;
  };
  return (
    <>
      <input hidden type='file' accept='.xlsx,.csv,.ods' onChange={handleImportFile} id='importFileInput' name='file' />
      {/* move Spreadsheet into a child component to safely re-init on file uploads */}
      <SpreadsheetInner key={spreadsheetRevision} data={data} buildToolbar={buildToolbar} onSpreadsheetChange={markUnsaved}/>
    </>
  );
}
function SpreadsheetInner({ data, buildToolbar, onSpreadsheetChange }) {
  const spreadsheetRef = useRef(null);
  useEffect(() => {
    const onMessage = (event) => {
      if (event.source !== window.parent) return;
      if (event.data?.type !== 'jss-aoa-request' || typeof event.data.requestId !== 'string') return;
      window.parent.postMessage({
        type: 'jss-aoa-response',
        requestId: event.data.requestId,
        aoa: spreadsheetRef.current?.[0]?.getData?.() ?? data,
      }, '*');
    };
    window.addEventListener('message', onMessage);
    return () => window.removeEventListener('message', onMessage);
  }, [data]);
  return (
    <Spreadsheet ref={spreadsheetRef} tabs={true} toolbar={buildToolbar} onchange={onSpreadsheetChange}>
      <Worksheet data={data} minDimensions={[
          Math.max(12, data[0]?.length || 0),
          Math.max(12, data.length)
        ]}
      />
    </Spreadsheet>
  );
}

const el = document.getElementById('spreadsheetEditorRoot');
if (el) {
  const root = createRoot(el);
  root.render(<SpreadsheetEditor />);
}
