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
 */

import React from 'react';
import i18next from './i18n';

export function ColumnHeader({ displayName, column, setColumnDefs, setRowData, columnDefs, rowData }) {
  const rename = () => {
    const newName = prompt(i18next.t('rename_column'), displayName);
    if (!newName || newName === displayName) return;
    const field = column.getColDef().field;

    // rename column def
    const newCols = columnDefs.map(col =>
      col.field === field ? { ...col, field: newName } : col
    );

    // rename row data keys
    const newRows = rowData.map(row => {
      const val = row[field];
      const nr = { ...row };
      delete nr[field];
      nr[newName] = val;
      return nr;
    });
    setColumnDefs(newCols);
    setRowData(newRows);
  };

  const remove = () => {
    const field = column.getColDef().field;
    setColumnDefs(columnDefs.filter(c => c.field !== field));
    setRowData(rowData.map(r => {
      const nr = { ...r };
      delete nr[field];
      return nr;
    }));
  };

  const insertColumn = () => {
    const field = column.getColDef().field;
    const idx = columnDefs.findIndex(c => c.field === field);
    const newField = `Column${columnDefs.length}`;
    const newCol = { field: newField, editable: true };

    const newCols = [
      ...columnDefs.slice(0, idx + 1),
      newCol,
      ...columnDefs.slice(idx + 1)
    ];

    const newRows = rowData.map(row => ({
      ...row,
      [newField]: '',
    }));

    setColumnDefs(newCols);
    setRowData(newRows);
  };

  return (
    <div className='ag-header-cell-label d-flex justify-content-between'>
      <span>{displayName}</span>
      <div>
        {/* TODO: check all added i18n translations and harmonize */}
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
