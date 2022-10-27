/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Marcel Bolten <github@marcelbolten.de>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

export default class TableSorting {
  table: HTMLTableElement;
  prevSortIcon: HTMLElement;
  hasThead: boolean;

  constructor(table: HTMLTableElement) {
    this.hasThead = table.tHead ? true : false;
    this.makeSortable(table);
  }

  /**
   * Get the table cell value based of on the header cell id
   * Will take into account cells spanning along a row (colspan)
   */
  getCellValue(tr: HTMLElement, idx: number): string {
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

  /**
   * Returns a function responsible for sorting a specific column index
   * idx = columnIndex, asc = ascending order?
   */
  comparer(idx: number, asc: boolean) {

    // This is used by the array.sort() function...
    return function(a: HTMLElement, b: HTMLElement) {

      // This is a transient function, that is called straight away.
      // It allows passing in different order of args, based on
      // the ascending/descending order.
      return function(value1: string, value2: string) {
        const diff = Number(value1) - Number(value2);
        // sort based on a numeric or localeCompare, based on type...
        return Number.isNaN(diff)
          ? value1.toString().localeCompare(value2, undefined, {numeric: true})
          : diff;
      }(this.getCellValue(asc ? a : b, idx), this.getCellValue(asc ? b : a, idx));
    };
  }

  /**
   * Add sort icon buttons to header cells, change them asc/desc
   * Sort rows in table based on selected column
   */
  makeSortable(table: HTMLTableElement): void {
    if (!this.isSortable(table)) {
      return;
    }

    const headSelector = ':scope > ' + (this.hasThead ? 'thead' : 'tbody') + ' > tr:first-of-type > th';
    table.querySelectorAll(headSelector).forEach((th: HTMLTableCellElement) => {
      // add sort button
      // need span because .fas has pointer-events:none
      th.innerHTML = '<span role="button"><i class="fas fa-sort"></i></span> ' + th.innerHTML;

      th.firstChild.addEventListener('click', (event => {
        const icon = (event.target as HTMLElement).firstChild as HTMLElement;

        // reset previous icon
        if (this.prevSortIcon && this.prevSortIcon != icon) {
          this.prevSortIcon.classList.remove('fa-sort-up', 'fa-sort-down');
          this.prevSortIcon.classList.add('fa-sort');
          this.prevSortIcon.closest('th').removeAttribute('data-order');
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
        const rowSelector = ':scope > tbody > ' + (this.hasThead ? 'tr' : 'tr:nth-child(n+2)');
        Array.from(table.querySelectorAll(rowSelector))
          .sort(this.comparer(Array.from(th.parentNode.children).indexOf(th), th.dataset.order === 'asc').bind(this))
          .forEach(tr => table.querySelector('tbody').appendChild(tr));
        this.prevSortIcon = icon;
      }));
    });
  }

  isSortable(table: HTMLTableElement): boolean {
    // tables with rowspan cannot be sorted, cells might be rearranged across rows and columns
    const hasRowspan = table.querySelectorAll('th[rowspan], td[rowspan]').length !== 0;
    if (hasRowspan) {
      console.info('Table with merged cells along columns (rowspan) detected. Table sorting disabled.', table);
      return false;
    }

    // tables with colspan in top row (header) cannot be sorted, wrong column would be referenced
    const head = this.hasThead ? 'thead' : 'tbody';
    const selector = `:scope > ${head} > tr:first-of-type > th[colspan], :scope > ${head} > tr:first-of-type > td[colspan]`;
    const hasHeaderColspan = table.querySelectorAll(selector).length !== 0;
    if (hasHeaderColspan) {
      console.info('Table with merged cells top row (colspan) detected. Table sorting disabled.', table);
      return false;
    }

    return true;
  }
}
