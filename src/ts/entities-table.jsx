/**
 * @author Nicolas CARPi / Deltablot
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

/**
 * Code related to the entities table present on the index page
 */
import { ClientSideRowModelModule } from '@ag-grid-community/client-side-row-model';
import { ModuleRegistry } from '@ag-grid-community/core';
import { AgGridReact } from '@ag-grid-community/react';
import '@ag-grid-community/styles/ag-grid.css';
import '@ag-grid-community/styles/ag-theme-alpine.css';
import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { get } from 'svelte/store';
import { createRoot } from 'react-dom/client';
import { ApiC } from './api';
import { notify } from './notify';
import i18next from './i18n';
import { getEntityTypeFromPage } from './misc';

// allow filtering by displayed values for cells that render their raw value differently
const yesNo = v => v === 1 ? i18next.t('yes') : i18next.t('no');
const lastLoginText = v => v === null ? i18next.t('never') : v;
let entitiesTableRoot = null;

const rowSelection = {
  mode: 'multiRow',
  headerCheckbox: true,
  selectAll: 'currentPage',
};

const EntitiesTable = ({ searchQuery, selectedEntities }) => {
  const [rowData, setRowData] = useState([]);
  const gridApiRef = useRef(null);
  const isDark = document.documentElement.classList.contains('dark-mode');

  const onGridReady = (params) => {
    gridApiRef.current = params.api;

    if (searchQuery) {
      params.api.setGridOption('quickFilterText', get(searchQuery));
    }

    fetchData();
  };

  const PastDateRenderer = ({ value }) => {
    return value === i18next.t('never')
      ? <span className='font-italic'>{value}</span>
      : <span>{value}</span>;
  };

  const BinaryRenderer = ({ value }) => {
    return value === i18next.t('yes')
      ? <span title={value}><i className='fas fa-circle-check mr-2'></i>{value}</span>
      : <span title={value}><i className='fas fa-circle-xmark mr-2'></i>{value}</span>;
  };

  const TagsRenderer = ({ value }) => {
    const tags = Array.isArray(value) ? value : [];

    if (tags.length === 0) {
      return null;
    }

    return (
      <span className='d-flex flex-wrap'>
        {tags.map(tagData => {
          const params = new URLSearchParams();
          params.set('mode', 'show');
          params.append('tags[]', tagData.tag);

          return (
            <a
              key={tagData.id ?? tagData.tag}
              className={`tag margin-1px${tagData.is_favorite ? ' favorite' : ''}`}
              href={`${window.location.pathname}?${params.toString()}`}
              onClick={event => event.stopPropagation()}
            >
              {tagData.tag}
            </a>
          );
        })}
      </span>
    );
  };

  const [columnDefs] = useState([
    { field: 'title', headerName: i18next.t('title') },
    { field: 'team_name', headerName: i18next.t('team') },
    { field: 'date', headerName: i18next.t('started-on'), valueGetter: p => lastLoginText(p.data.date), filterValueGetter: p => lastLoginText(p.data.date), cellRenderer: PastDateRenderer},
    { field: 'category', headerName: i18next.t('category'), valueGetter: p => p.data.category_title },
    { field: 'status', headerName: i18next.t('status'), valueGetter: p => p.data.status_title  },
    { field: 'tags_decoded', headerName: i18next.t('tags'), valueGetter: p => p.data.tags_decoded, cellRenderer: TagsRenderer },
    { field: 'id', headerName: i18next.t('id') },
    { field: 'custom_id', headerName: i18next.t('custom-id') },
    { field: 'fullname', headerName: i18next.t('owner') },
    { field: 'timestamped', headerName: i18next.t('Is timestamped'), valueGetter: p => yesNo(p.data.timestamped), filterValueGetter: p => yesNo(p.data.timestamped), cellRenderer: BinaryRenderer },
    { field: 'locked', headerName: i18next.t('Is locked'), valueGetter: p => yesNo(p.data.locked), filterValueGetter: p => yesNo(p.data.locked), cellRenderer: BinaryRenderer },
  ]);

   const getEntityFilterParams = event => {
    const detail = event?.detail;

    if (detail instanceof URLSearchParams) {
      return detail;
    }

    if (typeof detail === 'string') {
      return new URLSearchParams(detail);
    }

    if (detail?.params) {
      return new URLSearchParams(detail.params);
    }

    if (detail?.search) {
      return new URLSearchParams(detail.search);
    }

    return new URLSearchParams(document.location.search);
  };

  // all the entries are loaded in the table, which does client side pagination
  const fetchData = useCallback(async event => {
    const params = getEntityFilterParams(event);
    const queryString = params.toString();

    try {
      const endpoint = getEntityTypeFromPage(window.location);
      const entities = await ApiC.getJson(`${endpoint}?${queryString ? `&${queryString}` : ''}`);
      setRowData(entities);
    } catch (error) {
      notify.error(error);
      console.error(`Could not load entities: ${error}`);
    }
  }, []);

  const getRowId = useCallback(params => String(params.data.id), []);

  // Load data on component mount and reload when entity filters change
  useEffect(() => {
    const handleEntityFiltersChanged = event => {
      fetchData(event);
    };

    window.addEventListener('entity-filters-changed', handleEntityFiltersChanged);

    return () => {
      window.removeEventListener('entity-filters-changed', handleEntityFiltersChanged);
    };
  }, [fetchData]);

  useEffect(() => {
    if (!searchQuery) {
      return undefined;
    }

    const unsubscribe = searchQuery.subscribe(value => {
      gridApiRef.current?.setGridOption('quickFilterText', value);
    });

    return unsubscribe;
  }, [searchQuery]);

  // when a row is selected with the checkbox
  const selectionChanged = (event) => {
    const selectedRows = event.api.getSelectedRows();
    const selectedIds = selectedRows.map(row => String(row.id));

    selectedEntities?.set(selectedIds);

    const withSelected = document.getElementById('withSelected');
    if (!withSelected) {
      return;
    }

    if (selectedIds.length > 0) {
      withSelected.classList.remove('d-none');
    } else {
      withSelected.classList.add('d-none');
    }
  };

  const defaultColDef = useMemo(() => {
    return {
      filter: 'agTextColumnFilter',
      floatingFilter: true,
      onCellValueChanged: (event) => {
        const params = {};
        params[event.column.colId] = event.newValue;
        ApiC.patch(`users/${event.data.id}`, params);
      }
    };
  }, []);

  const cellDoubleClicked = (event) => {
    window.location = `?mode=view&id=${event.data.id}`;
  };

  return (
    <>
      <div
        className={isDark ? 'ag-theme-alpine-dark' : 'ag-theme-alpine'} style={{ height: 650 }}>
        <AgGridReact
          rowData={rowData}
          columnDefs={columnDefs}
          defaultColDef={defaultColDef}
          getRowId={getRowId}
          onGridReady={onGridReady}
          rowSelection={rowSelection}
          onCellDoubleClicked={cellDoubleClicked}
          onSelectionChanged={selectionChanged}
          pagination={true}
          paginationPageSize={15}
          paginationPageSizeSelector={[15, 50, 100, 500]}
        />
      </div>
    </>
  );
};

const App = ({ searchQuery, selectedEntities }) => (
  <EntitiesTable
    searchQuery={searchQuery}
    selectedEntities={selectedEntities}
  />
);

export const mountEntitiesTable = (rootElement, searchQuery, selectedEntities) => {
  if (!rootElement) {
    return null;
  }

  ModuleRegistry.registerModules([ClientSideRowModelModule]);

  if (!entitiesTableRoot) {
    entitiesTableRoot = createRoot(rootElement);
  }

  entitiesTableRoot.render(
  <App
    searchQuery={searchQuery}
    selectedEntities={selectedEntities}
  />
);

  return entitiesTableRoot;
};

export const unmountEntitiesTable = () => {
  if (!entitiesTableRoot) {
    return;
  }

  entitiesTableRoot.unmount();
  entitiesTableRoot = null;
};
