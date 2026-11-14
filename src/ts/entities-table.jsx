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
  TooltipModule,
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
import { DEFAULT_AG_GRID_PAGINATION, errorMessage, getEntityTypeFromPage } from './misc';
import { EntityType } from './interfaces';
import { readStorage, writeStorage } from './localStorage';
import { getAgGridTheme } from './theme';
import AgGridTableOptions from './ag-grid-table-options';
import {
  ExtraFieldsColumnsPicker,
  getStoredExtraFieldsColumns,
  makeExtraFieldColumnDef,
  storeExtraFieldsColumns,
} from './entities-table-extra-fields';

// the entity type is fixed by the page: experiments, resources or their templates
const ENTITY_TYPE = getEntityTypeFromPage(window.location);

// shared by all entity types before custom field columns existed, migrated on first read
const LEGACY_COLUMN_STATE_STORAGE_KEY = 'persistent_entities_table_column_state_v1';
// the columns differ between entity types (custom fields), so the layout is stored per entity type
const getColumnStateStorageKey = entityType => `${LEGACY_COLUMN_STATE_STORAGE_KEY}_${entityType}`;
const COLUMN_STATE_STORAGE_KEY = getColumnStateStorageKey(ENTITY_TYPE);

// copy the layout stored with the shared key to each entity type that shows this table, then drop it
const migrateLegacyColumnState = () => {
  const legacy = readStorage(LEGACY_COLUMN_STATE_STORAGE_KEY);
  if (legacy === null) {
    return;
  }
  [EntityType.Experiment, EntityType.Item, EntityType.Template, EntityType.ItemType].forEach(entityType => {
    const key = getColumnStateStorageKey(entityType);
    if (readStorage(key) === null) {
      writeStorage(key, legacy);
    }
  });
  try {
    localStorage.removeItem(LEGACY_COLUMN_STATE_STORAGE_KEY);
  } catch {
    // localStorage might be unavailable
  }
};

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
    return JSON.parse(readStorage(COLUMN_STATE_STORAGE_KEY) ?? 'null');
  } catch {
    return null;
  }
};

const storeColumnState = api => writeStorage(COLUMN_STATE_STORAGE_KEY, JSON.stringify(api.getColumnState()));

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

const EntitiesTable = ({
  selectedEntities,
  order = 'date',
  sort = 'desc',
  related = null,
  relatedOrigin = '',
}) => {
  const [rowData, setRowData] = useState([]);
  const [gridApi, setGridApi] = useState(null);
  // columns built from custom fields, they are part of the column defs from the start
  // so the stored column state (width, order, sort) applies to them too
  const [extraFieldsColumns, setExtraFieldsColumns] = useState(() => getStoredExtraFieldsColumns(ENTITY_TYPE));
  // the filters come with the event, they are kept so adding a column refetches the same entries
  const lastFilterEventRef = useRef(undefined);

  useEffect(() => {
    storeExtraFieldsColumns(ENTITY_TYPE, extraFieldsColumns);
  }, [extraFieldsColumns]);

  const onGridReady = event => {
    migrateLegacyColumnState();
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

  const [baseColumnDefs] = useState([
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
  ]);

  const columnDefs = useMemo(
    () => [...baseColumnDefs, ...extraFieldsColumns.map(makeExtraFieldColumnDef)],
    [baseColumnDefs, extraFieldsColumns],
  );

  const getResolvedEntityFilterParams = useCallback((event, columns) => {
    const params = getEntityFilterParams(event);

    applyStringParamFallback(params, 'order', order);
    applyStringParamFallback(params, 'sort', sort);
    applyNumberParamFallback(params, 'related', related);
    applyStringParamFallback(params, 'related_origin', relatedOrigin);

    params.delete('extra_fields[]');
    columns.forEach(name => params.append('extra_fields[]', name));

    return params;
  }, [order, sort, related, relatedOrigin]);

  // only the last request shows its results: an older one can finish after it
  const requestSeq = useRef(0);

  // all the entries are loaded in the table, which does client side pagination
  // like the list (EntityList.svelte), tell the forms that changed the filters if they were rejected, see initReactionSearch() in show.ts
  // columns is given when it changes, as the state is only updated on the next render
  const fetchData = useCallback(async (event, columns = extraFieldsColumns) => {
    if (event !== undefined) {
      lastFilterEventRef.current = event;
    }
    const params = getResolvedEntityFilterParams(lastFilterEventRef.current, columns);
    const queryString = params.toString();
    const seq = ++requestSeq.current;

    try {
      const url = queryString ? `${ENTITY_TYPE}?${queryString}` : ENTITY_TYPE;
      const entities = await ApiC.getJson(url, {notifOnError: 0});
      if (seq !== requestSeq.current) {
        return;
      }
      setRowData(entities);
      window.dispatchEvent(new CustomEvent('entity-list-loaded'));
    } catch (error) {
      if (seq !== requestSeq.current) {
        return;
      }
      // the rows of the previous query are not the results of this one
      setRowData([]);
      window.dispatchEvent(new CustomEvent('entity-list-rejected', { detail: errorMessage(error) }));
      console.error(`Could not load entities: ${error}`);
    }
  }, [getResolvedEntityFilterParams, extraFieldsColumns]);

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

  // the selection can also be dropped from outside the grid, as a batch action does once it
  // is finished: the ticks have to follow, or the next tick would bring the old rows back
  useEffect(() => {
    const handleSelectionCleared = () => gridApi?.deselectAll();

    window.addEventListener('entities-selection-cleared', handleSelectionCleared);

    return () => {
      window.removeEventListener('entities-selection-cleared', handleSelectionCleared);
    };
  }, [gridApi]);

  // when a row is selected with the checkbox
  const selectionChanged = (event) => {
    const selectedRows = event.api.getSelectedRows();
    const selectedIds = selectedRows.map(row => String(row.id));

    selectedEntities?.set(selectedIds);
  };

  const addExtraFieldColumn = name => {
    if (extraFieldsColumns.includes(name)) {
      return;
    }
    const columns = [...extraFieldsColumns, name];
    setExtraFieldsColumns(columns);
    // the values of a custom field are only sent when requested
    fetchData(undefined, columns);
  };

  // no need to refetch here: the values of the removed column are simply not displayed anymore
  const removeExtraFieldColumn = name => {
    setExtraFieldsColumns(columns => columns.filter(column => column !== name));
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

  return (
    <>
      <div className={`ag-grid-table-wrapper entities-table-wrapper position-relative ${getAgGridTheme()}`} style={{ height: 650 }}>
        <AgGridReact
          rowData={rowData}
          columnDefs={columnDefs}
          defaultColDef={defaultColDef}
          getRowId={getRowId}
          processRowPostCreate={processRowPostCreate}
          onColumnResized={columnStateChanged}
          onColumnMoved={columnStateChanged}
          onColumnVisible={columnStateChanged}
          onColumnPinned={columnStateChanged}
          onSortChanged={columnStateChanged}
          onGridReady={onGridReady}
          rowSelection={rowSelection}
          onCellClicked={cellClicked}
          onSelectionChanged={selectionChanged}
          maintainColumnOrder={true}
          {...DEFAULT_AG_GRID_PAGINATION}
        />
        <div className='entities-table-actions'>
          <AgGridTableOptions gridApi={gridApi} storageKey={COLUMN_STATE_STORAGE_KEY}/>
          <ExtraFieldsColumnsPicker
            entityType={ENTITY_TYPE}
            columns={extraFieldsColumns}
            onAdd={addExtraFieldColumn}
            onRemove={removeExtraFieldColumn}
          />
        </div>
      </div>
    </>
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
    // headerTooltip of the custom field columns
    TooltipModule,
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
