/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

/**
 * Code related to the audit logs table present on the Audit Logs tab of the Sysconfig panel
 */
import {
  ClientSideRowModelModule,
  ColumnAutoSizeModule,
  ColumnApiModule,
  ModuleRegistry,
  PaginationModule,
  QuickFilterModule,
  TextFilterModule,
  provideGlobalGridOptions,
} from 'ag-grid-community';
import { AgGridReact } from 'ag-grid-react';
import 'ag-grid-community/styles/ag-grid.css';
import React, { useEffect, useMemo, useState } from 'react';
import { createRoot } from 'react-dom/client';
import { ApiC } from './api';
import { DEFAULT_AG_GRID_PAGINATION } from './misc';
import { notify } from './notify';
import i18next from './i18n';
import { Model } from './interfaces';
import { getAgGridTheme } from './theme';
import AgGridTableOptions from './ag-grid-table-options';

const COLUMN_STATE_STORAGE_KEY = 'persistent_audit_logs_table_column_state_v1';

// the audit logs table can grow very large, so only the most recent events are loaded by default
const RECENT_EVENTS_LIMIT = 120;
// the server never returns more than this (AuditLogs::MAX_LIMIT), even when loading all events
const MAX_EVENTS_LIMIT = 10000;

provideGlobalGridOptions({ theme: 'legacy' });
ModuleRegistry.registerModules([
  ClientSideRowModelModule,
  ColumnAutoSizeModule,
  ColumnApiModule,
  PaginationModule,
  TextFilterModule,
  QuickFilterModule,
]);

const getStoredColumnState = () => {
  try {
    return JSON.parse(
      localStorage.getItem(COLUMN_STATE_STORAGE_KEY) ?? 'null'
    );
  } catch {
    return null;
  }
};

const storeColumnState = api => {
  try {
    localStorage.setItem(
      COLUMN_STATE_STORAGE_KEY,
      JSON.stringify(api.getColumnState())
    );
  } catch {
    // localStorage might be unavailable
  }
};

// link to the user in the Users tab of the Sysconfig panel
// userid 0 means no user was involved (e.g. a failed login with an unknown email)
const UseridRenderer = ({ value }) => {
  if (!value) {
    return <span className='p-1'>&ndash;</span>;
  }
  return <a href={`?tab=3&q=&userid=${value}`}>{value}</a>;
};

const AuditLogsTable = () => {
  const [rowData, setRowData] = useState([]);
  const [gridApi, setGridApi] = useState(null);
  const [allLoaded, setAllLoaded] = useState(false);
  const [hitMaxLimit, setHitMaxLimit] = useState(false);
  const [isLoading, setIsLoading] = useState(false);

  const onGridReady = event => {
    const columnState = getStoredColumnState();

    if (Array.isArray(columnState)) {
      event.api.applyColumnState({
        state: columnState,
        applyOrder: true,
      });
    }
    setGridApi(event.api);
  };

  const columnStateChanged = event => {
    if (event.finished === false) {
      return;
    }

    storeColumnState(event.api);
  };

  const onQuickFilterChange = e => {
    gridApi?.setGridOption('quickFilterText', e.target.value);
  };

  const [columnDefs] = useState([
    { field: 'id', headerName: i18next.t('id'), width: 100 },
    { field: 'created_at', headerName: i18next.t('created-at') },
    {
      field: 'category_name',
      headerName: i18next.t('category'),
      // allow filtering with the numerical value of the category too
      filterValueGetter: p => `${p.data.category_name} ${p.data.category}`,
    },
    { field: 'body', headerName: i18next.t('content'), flex: 1, minWidth: 300 },
    {
      field: 'requester_userid',
      headerName: `${i18next.t('actor')} (${i18next.t('userid')})`,
      cellRenderer: UseridRenderer,
      filterValueGetter: p => p.data.requester_userid || '',
    },
    {
      field: 'requester_fullname',
      headerName: i18next.t('actor'),
      valueGetter: p => p.data.requester_userid === 0 ? '' : p.data.requester_fullname,
    },
    {
      field: 'target_userid',
      headerName: `${i18next.t('target-user')} (${i18next.t('userid')})`,
      cellRenderer: UseridRenderer,
      filterValueGetter: p => p.data.target_userid || '',
    },
    {
      field: 'target_fullname',
      headerName: i18next.t('target-user'),
      valueGetter: p => p.data.target_userid === 0 ? '' : p.data.target_fullname,
    },
  ]);

  const fetchData = async (loadAll = false) => {
    setIsLoading(true);
    const query = `${Model.AuditLogs}?limit=${loadAll ? MAX_EVENTS_LIMIT : RECENT_EVENTS_LIMIT}`;
    try {
      const events = await ApiC.getJson(query);
      setRowData(events);
      // if we got less than what we asked for, everything is already there
      setAllLoaded(loadAll || events.length < RECENT_EVENTS_LIMIT);
      setHitMaxLimit(events.length >= MAX_EVENTS_LIMIT);
    } catch (error) {
      notify.error(error);
      console.error(`Could not load audit logs: ${error}`);
    } finally {
      setIsLoading(false);
    }
  };

  // Load data on component mount
  useEffect(() => {
    fetchData();
  }, []);

  const defaultColDef = useMemo(() => {
    return {
      filter: 'agTextColumnFilter',
      floatingFilter: true,
    };
  }, []);

  return (
    <>
      <div className='d-flex align-items-center mb-2'>
        <input
          type='text'
          placeholder={i18next.t('search')}
          onChange={onQuickFilterChange}
          className='form-control mr-2'
          aria-label={i18next.t('search')}
        />
        <button
          type='button'
          className='btn btn-secondary btn-sm text-nowrap'
          onClick={() => fetchData(allLoaded)}
          disabled={isLoading}
          aria-label={i18next.t('refresh')}
        >
          <i className={`fas fa-rotate mr-1 ${isLoading ? 'fa-spin' : ''}`}></i>{i18next.t('refresh')}
        </button>
      </div>
      <div
        className={`ag-grid-table-wrapper position-relative ${getAgGridTheme()}`}
        style={{ height: 650 }}
      >
        <AgGridReact
          rowData={rowData}
          columnDefs={columnDefs}
          defaultColDef={defaultColDef}
          onColumnResized={columnStateChanged}
          onColumnMoved={columnStateChanged}
          onColumnVisible={columnStateChanged}
          onColumnPinned={columnStateChanged}
          onSortChanged={columnStateChanged}
          onGridReady={onGridReady}
          {...DEFAULT_AG_GRID_PAGINATION}
        />
        <AgGridTableOptions
          gridApi={gridApi}
          storageKey={COLUMN_STATE_STORAGE_KEY}
        />
      </div>
      {hitMaxLimit && (
        <p className='text-center my-4'>{i18next.t('audit-logs-max', { num: MAX_EVENTS_LIMIT })}</p>
      )}
      {!allLoaded && (
        <div className='text-center my-4'>
          <p>{i18next.t('audit-logs-recent', { num: RECENT_EVENTS_LIMIT })}</p>
          <button
            type='button'
            className='btn btn-primary btn-lg'
            onClick={() => fetchData(true)}
            disabled={isLoading}
          >
            <i className={`fas ${isLoading ? 'fa-spinner fa-spin' : 'fa-download'} mr-2`}></i>
            {i18next.t('load-all-events')}
          </button>
        </div>
      )}
    </>
  );
};

const root = createRoot(document.getElementById('audit-logs-table'));
root.render(
  <AuditLogsTable/>
);
