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
import {
  ClientSideRowModelModule,
  ColumnAutoSizeModule,
  ColumnApiModule,
  ModuleRegistry,
  PaginationModule,
  RowSelectionModule,
  TextFilterModule,
  provideGlobalGridOptions,
} from 'ag-grid-community';
import { AgGridReact } from 'ag-grid-react';
import 'ag-grid-community/styles/ag-grid.css';
import 'ag-grid-community/styles/ag-theme-alpine.css';
import 'ag-grid-community/styles/ag-theme-quartz.css';
import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { createRoot } from 'react-dom/client';
import { ApiC } from './api';
import i18next from './i18n';
import { DEFAULT_AG_GRID_PAGINATION, getEntityTypeFromPage } from './misc';
import { getAgGridTheme } from './theme';
import AgGridTableOptions from './ag-grid-table-options';
import { renderEntityContent } from './common';

const COLUMN_STATE_STORAGE_KEY = 'persistent_entities_table_column_state_v1';

// allow filtering by displayed values for cells that render their raw value differently
const yesNo = v => v === 1 ? i18next.t('yes') : i18next.t('no');
const lastLoginText = v => v === null ? i18next.t('never') : v;
let entitiesTableRoot = null;

const normalizeStringParam = value => {
  if (value === null || value === undefined) {
    return '';
  }

  return String(value).trim();
};

const normalizeNumberParam = value => {
  const stringValue = normalizeStringParam(value);

  if (stringValue.length === 0) {
    return null;
  }

  const numberValue = Number(stringValue);

  return Number.isFinite(numberValue) ? numberValue : null;
};

const applyStringParamFallback = (params, param, fallback) => {
  if ((params.get(param) ?? '').length > 0) {
    return;
  }

  const value = normalizeStringParam(fallback);

  if (value.length > 0) {
    params.set(param, value);
  }
};

const applyNumberParamFallback = (params, param, fallback) => {
  const urlValue = params.get(param);

  if (urlValue !== null && urlValue.length > 0) {
    const numberValue = normalizeNumberParam(urlValue);

    if (numberValue === null) {
      params.delete(param);
      return;
    }

    params.set(param, String(numberValue));
    return;
  }

  const value = normalizeNumberParam(fallback);

  if (value !== null) {
    params.set(param, String(value));
  }
};

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

const getEntityFilterParams = event => {
  const detail = event?.detail;

  if (detail instanceof URLSearchParams) {
    return new URLSearchParams(detail);
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

const rowSelection = {
  mode: 'multiRow',
  headerCheckbox: true,
  selectAll: 'currentPage',
};

const entityFilterByColumn = {
  category: { param: 'category', valueField: 'category', labelField: 'category_title' },
  status: { param: 'status', valueField: 'status', labelField: 'status_title' },
  fullname: { param: 'owner', valueField: 'userid', labelField: 'fullname' },
};

const ToggleBodyRenderer = ({ data, context }) => {
  if (!data?.id) {
    return null;
  }

  const isExpanded = context?.expandedEntityId === data.id;

  return (
    <button
      type='button'
      className='btn btn-ghost btn-sm p-1 lh-normal border-0'
      title={i18next.t('Toggle content')}
      aria-label={i18next.t('Toggle content')}
      aria-expanded={isExpanded}
      onClick={event => {
        event.stopPropagation();
        context?.setExpandedEntityId(prev => (prev === data.id ? null : data.id));
      }}
    >
      <i
        className={`fas ${isExpanded ? 'fa-caret-down' : 'fa-caret-right'} fa-fw`}
        aria-hidden='true'
      ></i>
    </button>
  );
};

const EntitiesTable = ({
  selectedEntities,
  order = 'date',
  sort = 'desc',
  related = null,
  relatedOrigin = '',
}) => {
  const [rowData, setRowData] = useState([]);
  const [gridApi, setGridApi] = useState(null);
  const [expandedEntityId, setExpandedEntityId] = useState(null);

  const onGridReady = event => {
    const columnState = getStoredColumnState();
    setGridApi(event.api);

    if (Array.isArray(columnState)) {
      event.api.applyColumnState({
        state: columnState,
        applyOrder: true,
      });
    }
    fetchData();
  };

  const columnStateChanged = event => {
    if (event.finished === false) {
      return;
    }

    storeColumnState(event.api);
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

  const ColorDotRenderer = ({ value, data, colDef }) => {
    if (!value) {
      return null;
    }

    const color = data[`${colDef.field}_color`];
    if (!color) {
      return <span>{value}</span>;
    }

    return <span><i className='fas fa-circle fa-fw' style={{ '--bg': `#${color}`, color: 'var(--bg)' }}></i> {value}</span>;
  };

  const RatingsRenderer = ({ value }) => {
    return Number(value) > 0
      ? <span className='rating-show rounded p-1 font-weight-bold'><i className='fas fa-star mr-1' aria-hidden='true'></i>{value}</span>
      : null;
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

  const columnDefs = useMemo(() => [
    {
      colId: 'toggle_body',
      headerName: '',
      width: 44,
      minWidth: 44,
      maxWidth: 44,
      pinned: 'left',
      sortable: false,
      filter: false,
      resizable: false,
      suppressMovable: true,
      cellRenderer: ToggleBodyRenderer,
    },
    { field: 'title', headerName: i18next.t('title') },
    { field: 'team_name', headerName: i18next.t('team') },
    { field: 'date', headerName: i18next.t('started-on'), valueGetter: p => lastLoginText(p.data.date), filterValueGetter: p => lastLoginText(p.data.date), cellRenderer: PastDateRenderer},
    { field: 'category', headerName: i18next.t('category'), valueGetter: p => p.data.category_title, cellRenderer: ColorDotRenderer },
    { field: 'status', headerName: i18next.t('status'), valueGetter: p => p.data.status_title, cellRenderer: ColorDotRenderer },
    { field: 'tags_decoded', headerName: i18next.t('tags'), valueGetter: p => p.data.tags_decoded, filterValueGetter: p => p.data.tags_decoded?.map(tagData => tagData.tag).join(' ') ?? '', cellRenderer: TagsRenderer },
    { field: 'id', headerName: i18next.t('id') },
    { field: 'custom_id', headerName: i18next.t('custom-id') },
    { field: 'fullname', headerName: i18next.t('owner') },
    { field: 'timestamped', headerName: i18next.t('is-timestamped'), valueGetter: p => yesNo(p.data.timestamped), filterValueGetter: p => yesNo(p.data.timestamped), cellRenderer: BinaryRenderer },
    { field: 'modified_at', headerName: i18next.t('last-modified-at'), valueGetter: p => p.data.modified_at },
    { field: 'locked', headerName: i18next.t('is-locked'), valueGetter: p => yesNo(p.data.locked), filterValueGetter: p => yesNo(p.data.locked), cellRenderer: BinaryRenderer },
    { field: 'rating', headerName: i18next.t('rating'), cellRenderer: RatingsRenderer },
    { field: 'next_step', headerName: i18next.t('next-step'),  cellRenderer: ({ value }) => value ? value.split('|')[0] : null }
  ], []);

  const gridContext = useMemo(() => ({
    expandedEntityId,
    setExpandedEntityId,
  }), [expandedEntityId]);

  useEffect(() => {
    gridApi?.refreshCells({ columns: ['toggle_body'], force: true });
  }, [gridApi, expandedEntityId]);

  const getResolvedEntityFilterParams = useCallback(event => {
    const params = getEntityFilterParams(event);

    applyStringParamFallback(params, 'order', order);
    applyStringParamFallback(params, 'sort', sort);
    applyNumberParamFallback(params, 'related', related);
    applyStringParamFallback(params, 'related_origin', relatedOrigin);

    return params;
  }, [order, sort, related, relatedOrigin]);

  // all the entries are loaded in the table, which does client side pagination
  const fetchData = useCallback(async event => {
    const params = getResolvedEntityFilterParams(event);
    const queryString = params.toString();

    try {
      const endpoint = getEntityTypeFromPage(window.location);
      const url = queryString ? `${endpoint}?${queryString}` : endpoint;
      const entities = await ApiC.getJson(url, {notifOnError: 0});
      setRowData(entities);
    } catch (error) {
      console.error(`Could not load entities: ${error}`);
    }
  }, [getResolvedEntityFilterParams]);

  const getRowId = useCallback(params => String(params.data.id), []);

  const processRowPostCreate = useCallback(params => {
    if (params.node.data?.id) {
      params.eRow.dataset.entityId = String(params.node.data.id);
    }
  }, []);

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
      withSelected.removeAttribute('hidden');
    } else {
      withSelected.setAttribute('hidden', 'hidden');
    }
  };

  const defaultColDef = useMemo(() => {
    return {
      filter: 'agTextColumnFilter',
      floatingFilter: true,
    };
  }, []);

  const cellClicked = event => {
    const target = event.event?.target;
    const url = `?mode=view&id=${encodeURIComponent(event.data.id)}`;

    const entityFilter = entityFilterByColumn[event.colDef.field];
    if (entityFilter) {
      const value = event.data[entityFilter.valueField];

      if (value !== null && value !== undefined && String(value).length > 0) {
        window.dispatchEvent(new CustomEvent('entity-filter-requested', {
          detail: {
            param: entityFilter.param,
            value: String(value),
            label: event.data[entityFilter.labelField] ?? String(value),
          },
        }));
        return;
      }
    }

    if (
      target instanceof HTMLElement
      && target.closest('input, button, a, .ag-selection-checkbox')
    ) {
      return;
    }
    if (event.event?.ctrlKey || event.event?.metaKey) {
      window.open(url, '_blank');
      return;
    }
    window.location = url;
  };

  useEffect(() => {
    if (expandedEntityId === null) {
      return;
    }
    const exists = rowData.some(row => row.id === expandedEntityId);
    if (!exists) {
      setExpandedEntityId(null);
    }
  }, [rowData, expandedEntityId]);

  const expandedEntity = useMemo(
    () => rowData.find(row => row.id === expandedEntityId) ?? null,
    [rowData, expandedEntityId]
  );

  return (
    <>
      <div className={`ag-grid-table-wrapper position-relative ${getAgGridTheme()}`} style={{ height: 650 }}>
        <AgGridReact
          rowData={rowData}
          columnDefs={columnDefs}
          defaultColDef={defaultColDef}
          getRowId={getRowId}
          processRowPostCreate={processRowPostCreate}
          context={gridContext}
          onColumnResized={columnStateChanged}
          onColumnMoved={columnStateChanged}
          onColumnVisible={columnStateChanged}
          onColumnPinned={columnStateChanged}
          onSortChanged={columnStateChanged}
          onGridReady={onGridReady}
          rowSelection={rowSelection}
          onCellClicked={cellClicked}
          onSelectionChanged={selectionChanged}
          {...DEFAULT_AG_GRID_PAGINATION}
        />
        <AgGridTableOptions gridApi={gridApi} storageKey={COLUMN_STATE_STORAGE_KEY}/>
      </div>
      {expandedEntityId !== null && (
        <EntityDetailPanel
          entityId={expandedEntityId}
          entity={expandedEntity}
          onClose={() => setExpandedEntityId(null)}
        />
      )}
    </>
  );
};

const EntityDetailPanel = ({ entityId, entity, onClose }) => {
  const contentRef = useRef(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    let isCancelled = false;

    if (contentRef.current) {
      contentRef.current.innerHTML = '';
    }

    setLoading(true);
    setError(null);

    const endpoint = getEntityTypeFromPage(window.location);

    ApiC.getJson(`${endpoint}/${entityId}`)
      .then(async json => {
        if (isCancelled) {
          return;
        }

        if (contentRef.current) {
          await renderEntityContent(
            contentRef.current,
            endpoint,
            entityId,
            json.body_html ?? ''
          );
        }

        if (isCancelled) {
          return;
        }

        setLoading(false);
      })
      .catch(err => {
        if (!isCancelled) {
          console.error(`Could not load entity ${entityId}:`, err);
          setError(err?.message || i18next.t('Error loading content'));
          setLoading(false);
        }
      });

    return () => {
      isCancelled = true;
    };
  }, [entityId]);

  const viewUrl = `?mode=view&id=${encodeURIComponent(entityId)}`;

  return (
    <div className='card mt-3 shadow-sm entity-table-detail-panel'>
      <div className='card-header d-flex align-items-center justify-content-between py-2'>
        <div className='d-flex align-items-center text-truncate mr-2'>
          <i className='fas fa-file-lines mr-2 text-muted' aria-hidden='true'></i>
          <span className='font-weight-bold text-truncate'>
            {entity?.custom_id ? `${entity.custom_id} - ` : ''}
            {entity?.title || `${i18next.t('Entry')} #${entityId}`}
          </span>
          <a
            href={viewUrl}
            className='btn btn-ghost btn-sm ml-2 text-nowrap'
            title={i18next.t('View')}
            aria-label={i18next.t('View')}
          >
            <i className='fas fa-arrow-up-right-from-square' aria-hidden='true'></i>
          </a>
        </div>
        <button
          type='button'
          className='btn btn-ghost btn-sm p-1'
          onClick={onClose}
          aria-label={i18next.t('Close')}
          title={i18next.t('Close')}
        >
          <i className='fas fa-times fa-fw' aria-hidden='true'></i>
        </button>
      </div>
      <div className='card-body p-3' style={{ maxHeight: '600px', overflowY: 'auto' }}>
        {loading && (
          <div className='d-flex align-items-center justify-content-center p-4 text-muted'>
            <i className='fas fa-spinner fa-spin mr-2' aria-hidden='true'></i>
            <span>{i18next.t('loading')}</span>
          </div>
        )}
        {error && (
          <div className='alert alert-danger mb-0' role='alert'>
            {error}
          </div>
        )}
        <div ref={contentRef} style={{ display: loading || error ? 'none' : 'block' }}></div>
      </div>
    </div>
  );
};

const App = ({ selectedEntities, order, sort, related, relatedOrigin }) => (
  <EntitiesTable
    selectedEntities={selectedEntities}
    order={order}
    sort={sort}
    related={related}
    relatedOrigin={relatedOrigin}
  />
);

export const mountEntitiesTable = (
  rootElement,
  selectedEntities,
  order = 'date',
  sort = 'desc',
  related = null,
  relatedOrigin = '',
) => {
  if (!rootElement) {
    return null;
  }

  provideGlobalGridOptions({ theme: 'legacy' });
  ModuleRegistry.registerModules([
    ClientSideRowModelModule,
    ColumnAutoSizeModule,
    ColumnApiModule,
    RowSelectionModule,
    PaginationModule,
    TextFilterModule,
  ]);

  if (!entitiesTableRoot) {
    entitiesTableRoot = createRoot(rootElement);
  }

  entitiesTableRoot.render(
    <App
      selectedEntities={selectedEntities}
      order={order}
      sort={sort}
      related={related}
      relatedOrigin={relatedOrigin}
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
