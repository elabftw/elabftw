/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

/**
 * Build a CSV file in the browser, like AbstractMakeCsv does on the server: for rows already loaded and filtered on the page
 */

// spreadsheet applications evaluate a cell starting with one of these characters as a formula
// keep in sync with AbstractMakeCsv::FORMULA_TRIGGERS
const FORMULA_TRIGGERS = ['=', '+', '-', '@', '\t', '\r', '\n'];
// like is_numeric() in PHP: a number such as -20 cannot be a formula, so it stays a number in the spreadsheet
const NUMERIC = /^\s*[+-]?(\d+(\.\d*)?|\.\d+)([eE][+-]?\d+)?\s*$/;
// like fputcsv(), the cells containing one of these characters are enclosed
const NEEDS_ENCLOSURE = /[",\r\n\t ]/;

type Cell = string | number | boolean | null | undefined | object;

function cellToString(cell: Cell): string {
  if (cell === null || cell === undefined || cell === false) {
    return '';
  }
  if (cell === true) {
    return '1';
  }
  if (typeof cell === 'object') {
    return JSON.stringify(cell);
  }
  return String(cell);
}

// prevent CSV/formula injection: user content must not be evaluated by the spreadsheet application opening the file
export function escapeFormula(value: string): string {
  if (value === '' || NUMERIC.test(value)) {
    return value;
  }
  return FORMULA_TRIGGERS.includes(value[0]) ? `'${value}` : value;
}

function toField(cell: Cell): string {
  const value = escapeFormula(cellToString(cell));
  // RFC 4180: a double quote is escaped by doubling it, never with a backslash
  return NEEDS_ENCLOSURE.test(value) ? `"${value.replaceAll('"', '""')}"` : value;
}

/**
 * The header is the keys of the first row, like on the server. Starts with a UTF-8 BOM for spreadsheet applications.
 */
export function toCsv(rows: Array<Record<string, Cell>>): string {
  if (rows.length === 0) {
    return '';
  }
  const header = Object.keys(rows[0]);
  const lines = [header, ...rows.map(row => header.map(key => row[key]))]
    .map(record => record.map(toField).join(','));
  return `\uFEFF${lines.join('\n')}\n`;
}

// the date of the file name, like date('Y-m-d') on the server, in the local time of the user
export function getCsvDate(date: Date = new Date()): string {
  const pad = (n: number): string => String(n).padStart(2, '0');
  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
}
