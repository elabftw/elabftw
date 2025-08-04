// See https://www.ag-grid.com/react-data-grid/column-properties/#reference-header-headerComponent
// add more actions to the ag-grid column headers (new row, edit title...)
import React from 'react';
import i18next from './i18n';

export function ColumnHeader({ displayName, column, setColumnDefs, setRowData, columnDefs, rowData }) {
  const rename = () => {
    const newName = prompt(i18next.t('rename_column'), displayName);
    if (!newName || newName === displayName) return;
    const field = column.getColDef().field;

    // rename column def
    const newCols = columnDefs.map(c =>
      c.field === field ? { ...c, field: newName } : c
    );

    // rename row data keys
    const newRows = rowData.map(r => {
      const val = r[field];
      const nr = { ...r };
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

  return (
    <div className="ag-header-cell-label" style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
      <span>{displayName}</span>
      <div>
        <button onClick={rename} title={i18next.t('rename')} style={{ border: 'none', background: 'transparent' }}>
          <i className="fas fa-edit fa-sm" />
        </button>
        <button onClick={remove} title={i18next.t('delete')} style={{ border: 'none', background: 'transparent' }}>
          <i className="fas fa-trash-alt fa-sm ml-1" />
        </button>
      </div>
    </div>
  );
}
