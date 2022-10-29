/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Marcel Bolten <github@marcelbolten.de>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

type getComparerReturnType = (a: HTMLElement, b: HTMLElement) => number;

export default class TableSorting {

  public init(): void {
    Array.from(this.getTables()).forEach(table => {
      this.makeSortable(table);
    });
  }

  /**
   * Add sort icon buttons to header cells, change them asc/desc
   * Sort rows in table based on selected column
   */
  public makeSortable(table: HTMLTableElement): void {
    // do not parse table twice, i.e. while loading entry bodies via toggle-body button
    if (table.dataset.sortingActivated === 'true') {
      return;
    }

    if (!this.isSortable(table)) {
      return;
    }

    const hasThead = table.tHead ? true : false;
    const headSelector = ':scope > ' + (hasThead ? 'thead' : 'tbody') + ' > tr:first-of-type > th';
    let prevSortIcon;
    table.querySelectorAll(headSelector).forEach((th: HTMLTableCellElement) => {
      // add sort button
      // need span because .fas has pointer-events:none
      th.innerHTML = '<span role="button"><i class="fas fa-sort"></i></span> ' + th.innerHTML;

      th.firstChild.addEventListener('click', (event => {
        const icon = (event.target as HTMLElement).firstChild as HTMLElement;

        // reset previous icon
        if (prevSortIcon && prevSortIcon != icon) {
          prevSortIcon.classList.remove('fa-sort-up', 'fa-sort-down');
          prevSortIcon.classList.add('fa-sort');
          prevSortIcon.closest('th').removeAttribute('data-order');
        }

        // update current icon
        if (!th.dataset.order || th.dataset.order === 'desc') {
          th.dataset.order = 'asc';
          icon.classList.remove('fa-sort', 'fa-sort-down');
          icon.classList.add('fa-sort-up');
        } else {
          th.dataset.order = 'desc';
          icon.classList.replace('fa-sort-up', 'fa-sort-down');
        }

        // sort data
        const rowSelector = ':scope > tbody > ' + (hasThead ? 'tr' : 'tr:nth-child(n+2)');
        Array.from(table.querySelectorAll(rowSelector))
          .sort(this.getComparer(Array.from(th.parentNode.children).indexOf(th), th.dataset.order === 'asc').bind(this))
          .forEach(tr => table.querySelector('tbody').appendChild(tr));
        prevSortIcon = icon;
      }));
    });
    table.dataset.sortingActivated = 'true';
  }

  /**
   * Find tables that shall get sorting
   */
  protected getTables(): HTMLTableElement[] {
    // ToDo: Find a way to add data-table-sort attribute to tables created by tinyMCE
    const viewMode: HTMLTableElement[] = Array.from(document.querySelectorAll('div[id="body_view"] table'));
    const showMode: HTMLTableElement[] = Array.from(document.querySelectorAll('[id="itemList"] table'));
    const dataAtt: HTMLTableElement[] = Array.from(document.querySelectorAll('table[data-table-sort="true"]'));
    return [...viewMode, ...showMode, ...dataAtt];
  }

  /**
   * Check if table qualifies for sorting
   * any rowspan will not work
   * colspan is not allowed in rows where the sort icons are added
   */
  protected isSortable(table: HTMLTableElement): boolean {
    // tables with rowspan cannot be sorted, cells might be rearranged across rows and columns
    const hasRowspan = table.querySelectorAll('th[rowspan], td[rowspan]').length !== 0;
    if (hasRowspan) {
      console.info('Table with merged cells along columns (rowspan) detected. Table sorting disabled.', table);
      return false;
    }

    // tables with colspan in top row (header) cannot be sorted, wrong column would be referenced
    const head = table.tHead ? 'thead' : 'tbody';
    const selector = `:scope > ${head} > tr:first-of-type > th[colspan], :scope > ${head} > tr:first-of-type > td[colspan]`;
    const hasHeaderColspan = table.querySelectorAll(selector).length !== 0;
    if (hasHeaderColspan) {
      console.info('Table with merged cells top row (colspan) detected. Table sorting disabled.', table);
      return false;
    }

    return true;
  }

  /**
   * Creates a compare function used by array.sort() to sort rows in a table based on
   * a specific column index in asc or desc order
   *
   * @param idx column index
   * @param asc ascending order
   *
   * @returns compare function
   */
  protected getComparer(idx: number, asc: boolean): getComparerReturnType {
    return function(a: HTMLElement, b: HTMLElement): number {
      return this.comparerCore(this.getCellValue(asc ? a : b, idx), this.getCellValue(asc ? b : a, idx));
    };
  }

  /**
   * Actual compare function will sort numerical and string data
   * natural sorting is used for strings, i.e. 'a2' < 'a10'
   */
  protected comparerCore(value1: string, value2: string): number {
    const diff = Number(value1) - Number(value2);

    return Number.isNaN(diff)
      ? value1.localeCompare(value2, undefined, {numeric: true})
      : diff;
  }

  /**
   * Get the table cell value based on the header cell id
   * merged cells along a row (colspan) is taken into account
   */
  protected getCellValue(tr: HTMLElement, idx: number): string {
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
    return (tr.children[idx] as HTMLTableCellElement).innerText || (tr.children[idx] as HTMLTableCellElement).textContent;
  }
}
