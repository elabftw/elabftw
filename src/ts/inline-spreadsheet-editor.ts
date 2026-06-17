/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

/**
 * Helpers used by the standalone spreadsheet editor (spreadsheet-editor.jsx) to feed the inline
 * spreadsheet feature: read the live worksheet's values off the DOM and post them to the parent
 * page so an embedded snapshot can be (re)built. Kept out of the .jsx so they are type-checked.
 */

type Cell = string | number | boolean | null;

interface JssWorksheet {
  getData(highlighted: boolean, processed: boolean): Cell[][];
}

// jspreadsheet attaches the live worksheet instance to the .jss_container DOM element.
// Reading off the DOM (instead of a React ref) sidesteps the @jspreadsheet-ce/react remount unreliability.
function getLiveWorksheet(): JssWorksheet | null {
  const container = document.querySelector('#spreadsheetEditorRoot .jss_container') as (Element & { jssWorksheet?: JssWorksheet }) | null;
  return container?.jssWorksheet ?? null;
}

// jspreadsheet-ce v5 getData(highlighted, processed): processed=true returns computed/displayed values, false returns source formulas
export function getComputedFromDom(): Cell[][] | null {
  const ws = getLiveWorksheet();
  return ws?.getData ? ws.getData(false, true) : null;
}

export function getSourceFromDom(): Cell[][] | null {
  const ws = getLiveWorksheet();
  return ws?.getData ? ws.getData(false, false) : null;
}

// true once the live worksheet actually holds every cell of the data we just loaded
// (guards against reading an old instance mid-remount before the new data is in place)
export function worksheetHasLoaded(live: Cell[][] | null, loaded: Cell[][]): boolean {
  if (!Array.isArray(live)) {
    return false;
  }
  for (let r = 0; r < loaded.length; r++) {
    const loadedRow = loaded[r] || [];
    const liveRow = live[r] || [];
    for (let c = 0; c < loadedRow.length; c++) {
      const a = loadedRow[c] == null ? '' : String(loadedRow[c]);
      const b = liveRow[c] == null ? '' : String(liveRow[c]);
      if (a !== b) {
        return false;
      }
    }
  }
  return true;
}

// notify the parent page so any inline snapshot for this filename regenerates with computed values
export function postInlineRefresh(name: string, uploadId: number, computed: Cell[][] | null, embed: boolean): void {
  if (!name || !uploadId || !computed) {
    return;
  }
  window.parent.postMessage({ type: 'inline-sheet-refresh', name, uploadId, computed, embed }, window.location.origin);
}
