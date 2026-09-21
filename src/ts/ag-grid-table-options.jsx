/**
 * @author Nicolas CARPi / Deltablot
 * @author Moustapha / Deltablot
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import React from 'react';
import i18next from './i18n';

const removeStoredColumnState = storageKey => {
  if (!storageKey) {
    return;
  }
  try {
    localStorage.removeItem(storageKey);
  } catch {
    // localStorage might be unavailable
  }
};

const AgGridTableOptions = ({ gridApi, storageKey }) => {
  const autoSizeColumns = () => {
    gridApi?.autoSizeAllColumns();
  };

  const fitColumns = () => {
    gridApi?.sizeColumnsToFit();
  };

  const restoreDefaultLayout = () => {
    gridApi?.resetColumnState();
    removeStoredColumnState(storageKey);
  };

  return (
    <div className='ag-grid-table-options dropup'>
      <button
        type='button'
        className='btn btn-transparent dropdown-toggle'
        data-toggle='dropdown'
        aria-haspopup='true'
        aria-expanded='false'
        aria-label={i18next.t('table-options')}
        title={i18next.t('table-options')}
      >
        <i className='fas fa-cog fa-fw' aria-hidden='true'></i>
      </button>

      <div className='dropdown-menu'>
        <h6 className='dropdown-header'>
          {i18next.t('table-options')}
        </h6>

        <button
          type='button'
          className='btn btn-dropdown-item dropdown-item'
          onClick={autoSizeColumns}
        >
          <i className='fas fa-arrows-left-right fa-fw mr-2'></i>
          {i18next.t('auto-size-columns')}
        </button>

        <button
          type='button'
          className='btn btn-dropdown-item dropdown-item'
          onClick={fitColumns}
        >
          <i className='fas fa-expand fa-fw mr-2'></i>
          {i18next.t('fit-columns-to-table')}
        </button>

        <div className='dropdown-divider'></div>

        <button
          type='button'
          className='btn btn-dropdown-item dropdown-item'
          onClick={restoreDefaultLayout}
        >
          <i className='fas fa-rotate-left fa-fw mr-2'></i>
          {i18next.t('restore-default-layout')}
        </button>
      </div>
    </div>
  );
};

export default AgGridTableOptions;
