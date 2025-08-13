/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Moustapha <Deltablot>
 * @copyright 2025 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

/**
 * add more actions to the ag-grid column headers (new row, edit title...)
 * See https://www.ag-grid.com/react-data-grid/column-properties/#reference-header-headerComponent
 * The sort behaviour had to be adjusted to match the custom component additions
 */

import React, { useEffect, useState } from 'react';
import i18next from './i18n';

export function ColumnHeader(props) {
  const { displayName, column, progressSort, api, columnDefs, rowData, setColumnDefs, setRowData } = props;
  const field = column.getColDef().field;

  // keep icon in sync with external sort changes
  const [dir, setDir] = useState(column.getSort());
  useEffect(() => {
    const update = () => setDir(column.getSort());
    column.addEventListener('sortChanged', update);
    api.addEventListener('sortChanged', update);
    return () => {
      column.removeEventListener('sortChanged', update);
      api.removeEventListener('sortChanged', update);
    };
  }, [api, column]);

  // icons that match the AG theme
  const sortIconClass =
    dir === 'asc' ? 'ag-icon-asc' :
      dir === 'desc' ? 'ag-icon-desc' : 'ag-icon-none';

  const insertColumn = () => {
    const idx = columnDefs.findIndex(column => column.field === field);
    const newField = `Column${columnDefs.length}`;
    const newCol = { field: newField, editable: true, sortable: true };

    const newCols = [
      ...columnDefs.slice(0, idx + 1),
      newCol,
      ...columnDefs.slice(idx + 1),
    ];
    const newRows = rowData.map(row => ({ ...row, [newField]: '' }));
    setColumnDefs(newCols);
    setRowData(newRows);
  };

  // rename column header
  const rename = () => {
    const newName = prompt(i18next.t('rename-column'), displayName);
    if (!newName || newName === displayName) return;
    const newCols = columnDefs.map(column => column.field === field ? { ...column, field: newName } : column);
    const newRows = rowData.map(row => {
      const value = row[field];
      const nr = { ...row };
      delete nr[field];
      nr[newName] = value;
      return nr;
    });
    setColumnDefs(newCols);
    setRowData(newRows);
  };

  // remove a column
  const remove = () => {
    setColumnDefs(columnDefs.filter(column => column.field !== field));
    setRowData(rowData.map(row => { const nr = { ...row }; delete nr[field]; return nr; }));
  };

  return (
    <div className='ag-header-cell-label d-flex justify-content-between'>
      {/* row 1: title + default sort behavior */}
      <span className='d-flex' onClick={(e) => progressSort?.(e.shiftKey)} title={displayName} aria-label={displayName}>
        <span>{displayName}</span>
        <span className={`ag-icon ${sortIconClass}`} aria-hidden="true" />
      </span>

      {/* Row 2: toolbar with actions */}
      <div>
        <button onClick={insertColumn} title={i18next.t('add-column')} className='border-0 bg-transparent mr-2'>
          <i className="fas fa-plus fa-sm" />
        </button>
        <button onClick={rename} title={i18next.t('rename')} className='border-0 bg-transparent mr-2'>
          <i className="fas fa-edit fa-sm" />
        </button>
        <button onClick={remove} title={i18next.t('delete')} className='border-0 bg-transparent'>
          <i className="fas fa-trash-alt fa-sm" />
        </button>
      </div>
    </div>
  );
}
