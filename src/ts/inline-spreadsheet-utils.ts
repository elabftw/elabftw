/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

/**
 * Helpers to turn a sheet's computed values (array of arrays) into the minimal,
 * sanitizer-friendly HTML block that gets embedded in an entity's body.
 *
 * The stored block is intentionally tiny so it survives Filter::body() (HTMLPurifier):
 *   <div class="elabftw-inline-sheet" data-upload-id="N" data-sheet-name="file.xlsx">
 *     <div class="elabftw-inline-sheet-title">file.xlsx</div>
 *     <table border="1"> … tr/td … </table>
 *   </div>
 * The filename is an independent title above the table (not a table caption), and every row is
 * rendered the same way (td) since the first row is not necessarily a header.
 * border="1" guarantees visible borders in the editor, view mode and PDF export
 * without depending on any stylesheet.
 *
 * contenteditable="false" is added so the block is reliably non-editable in the TinyMCE editor
 * (the noneditable_class is only applied on parse, but the refresh path replaces blocks via
 * outerHTML). Filter::body() strips contenteditable on save, and noneditable_class re-applies it
 * on reload, so the stored HTML stays clean.
 */

type Cell = string | number | boolean | null;

export interface SnapshotMeta {
  sheetName: string;
  uploadId: number;
}

// escape text for safe insertion into HTML content and double-quoted attribute values
export function escapeHtml(input: string): string {
  return input
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

function isEmptyCell(cell: Cell): boolean {
  return cell === null || cell === undefined || cell === '';
}

// trim trailing empty rows and columns so the snapshot stays tight
function trimEmpty(aoa: Cell[][]): Cell[][] {
  const rows = (aoa ?? []).map(row => (Array.isArray(row) ? row.slice() : []));
  let maxRow = 0;
  let maxCol = 0;
  rows.forEach((row, rowIdx) => {
    for (let col = row.length - 1; col >= 0; col--) {
      if (!isEmptyCell(row[col])) {
        maxRow = rowIdx + 1;
        maxCol = Math.max(maxCol, col + 1);
        break;
      }
    }
  });
  return rows.slice(0, maxRow).map(row => {
    const trimmed = row.slice(0, maxCol);
    while (trimmed.length < maxCol) {
      trimmed.push('');
    }
    return trimmed;
  });
}

function cellToText(cell: Cell): string {
  return isEmptyCell(cell) ? '' : String(cell);
}

// build the inline snapshot block; every row is rendered the same (td) since the first row is not necessarily a header
export function sheetDataToTableHtml(aoa: Cell[][], meta: SnapshotMeta): string {
  let rows = trimEmpty(aoa);
  // keep an always-visible, clickable block even when the sheet is empty (e.g. freshly created)
  if (rows.length === 0) {
    rows = [['']];
  }
  const body = rows
    .map(row => {
      const cells = row
        .map(cell => `<td>${escapeHtml(cellToText(cell))}</td>`)
        .join('');
      return `<tr>${cells}</tr>`;
    })
    .join('');
  // the filename is shown as an independent title above the table (not a table <caption>)
  const title = `<div class="elabftw-inline-sheet-title">${escapeHtml(meta.sheetName)}</div>`;
  return `<div class="elabftw-inline-sheet" contenteditable="false" data-upload-id="${meta.uploadId}" data-sheet-name="${escapeHtml(meta.sheetName)}">${title}<table border="1">${body}</table></div>`;
}
