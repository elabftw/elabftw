/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Marcel Bolten <github@marcelbolten.de>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

import i18next from './i18n';

type decoratedRow = {row: HTMLTableRowElement, value: string};
type getComparerReturnType = (a: decoratedRow, b: decoratedRow) => number;

/**
 * Check if table qualifies for sorting
 * any rowspan will not work
 * colspan is not allowed in rows where the sort icons are added
 * function is also used in ./tinymce.ts
 */
export function isSortable(table: HTMLTableElement, showAlert = false): boolean {
  // tables with rowspan cannot be sorted, cells might be rearranged across rows and columns
  const hasRowspan = table.querySelectorAll('th[rowspan], td[rowspan]').length !== 0;
  if (hasRowspan) {
    const msg = 'Table with merged cells along columns (rowspan) detected. Table sorting disabled.';
    console.info(msg, table);
    if (showAlert) {
      alert(msg);
    }
    return false;
  }

  // tables with colspan in top row (header) cannot be sorted, wrong column would be referenced
  const head = table.tHead ? 'thead' : 'tbody';
  const selector = `:scope > ${head} > tr:first-of-type > th[colspan], :scope > ${head} > tr:first-of-type > td[colspan]`;
  const hasHeaderColspan = table.querySelectorAll(selector).length !== 0;
  if (hasHeaderColspan) {
    const msg = 'Table with merged cells in top (header) row (colspan) detected. Table sorting disabled.';
    console.info(msg, table);
    if (showAlert) {
      alert(msg);
    }
    return false;
  }

  return true;
}

export default class TableSorting {

  public init(): void {
    (Array.from(document.querySelectorAll('table[data-table-sort="true"]')) as HTMLTableElement[]).forEach(table => {
      this.makeSortable(table);
    });
  }

  /**
   * Add sort icon buttons to header cells, change them asc/desc
   * Sort rows in table based on selected column
   */
  public makeSortable(table: HTMLTableElement): void {
    // do not parse table twice, e.g. while loading entry bodies via toggle-body button
    if (table.dataset.sortingActivated === 'true') {
      return;
    }

    if (!isSortable(table)) {
      return;
    }

    const hasThead = table.tHead ? true : false;
    const headSelector = ':scope > ' + (hasThead ? 'thead' : 'tbody') + ' > tr:first-of-type > th';
    let prevSortIcon: HTMLElement;
    table.querySelectorAll(headSelector).forEach((th: HTMLTableCellElement) => {
      // add sort button
      // need span because .fas has pointer-events:none
      th.innerHTML = `<span class='d-flex justify-content-between align-items-center'><span>${th.innerHTML}</span><button class='btn btn-link p-0 ml-2' type='button' title='${i18next.t('sort-by-column')} ${th.innerHTML}' aria-label='${i18next.t('sort-by-column')} ${th.innerHTML}'><i class='fas fa-sort'></i></button></span>`;

      th.firstChild.firstChild.nextSibling.addEventListener('click', (event => {
        const icon = (event.target as HTMLElement).firstChild as HTMLElement;

        // reset previous icon
        if (prevSortIcon && prevSortIcon != icon) {
          prevSortIcon.classList.remove('fa-sort-up', 'fa-sort-down');
          prevSortIcon.classList.add('fa-sort');
          prevSortIcon.closest('th').removeAttribute('aria-sort');
          prevSortIcon.closest('th').removeAttribute('data-order');
        }

        // update current icon
        if (!th.dataset.order || th.dataset.order === 'desc') {
          th.setAttribute('aria-sort', 'ascending');
          th.dataset.order = 'asc';
          icon.classList.remove('fa-sort', 'fa-sort-down');
          icon.classList.add('fa-sort-up');
        } else {
          th.setAttribute('aria-sort', 'descending');
          th.dataset.order = 'desc';
          icon.classList.replace('fa-sort-up', 'fa-sort-down');
        }
        prevSortIcon = icon;

        const columnId = Array.from(th.parentNode.children).indexOf(th);
        const rowSelector = ':scope > tbody > ' + (hasThead ? 'tr' : 'tr:nth-child(n+2)');
        const rows = Array.from(table.querySelectorAll(rowSelector)) as HTMLTableRowElement[];
        // Schwartzian transform (decorate)
        const decoratedRows: decoratedRow[] = rows.map(tr => {
          return {row:tr, value:this.getCellValue(tr, columnId)};
        });
        decoratedRows.sort(this.getComparer(th.dataset.order === 'asc').bind(this))
          // rebuild table, undecorate
          .forEach(row => table.querySelector('tbody').appendChild(row['row']));
      }));
    });
    table.dataset.sortingActivated = 'true';
  }

  /**
   * Creates a compare function used by array.sort() to sort rows in a table
   * in ascending or descending order
   */
  protected getComparer(asc: boolean): getComparerReturnType {
    return function(a: decoratedRow, b: decoratedRow): number {
      return this.comparerCore(asc ? a['value'] : b['value'], asc ? b['value'] : a['value']);
    };
  }

  /**
   * Actual compare function will sort numerical and string data
   * natural sorting is used for strings, i.e. 'a2' < 'a10'
   */
  protected comparerCore(a: string, b: string): number {
    const diff = Number(a) - Number(b);

    return Number.isNaN(diff)
      ? a.localeCompare(b, undefined, {numeric: true})
      : diff;
  }

  /**
   * Get the table cell value based on the header cell id
   * merged cells along a row (colspan) is taken into account
   */
  protected getCellValue(tr: HTMLTableRowElement, idx: number): string {
    // handle colspans
    const cells: HTMLTableCellElement[] = Array.from(tr.querySelectorAll(':scope > th, :scope > td'));
    let idxMax = 0;
    let idxMin = 0;
    for (const cell of cells) {
      idxMin = idxMax;
      idxMax += cell.colSpan;
      if (idxMin <= idx && idx <= idxMax-1) {
        idx = cell.cellIndex;
        break;
      }
    }

    const cell = tr.children[idx] as HTMLTableCellElement;
    return cell.innerText || cell.textContent;
  }
}
