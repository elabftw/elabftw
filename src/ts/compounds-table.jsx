/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

/**
 * Code related to the compounds table present on the Compounds page from ag-grid
 */
import {
  ClientSideRowModelModule,
  CellStyleModule,
  ColumnApiModule,
  ColumnAutoSizeModule,
  ExternalFilterModule,
  ModuleRegistry,
  PaginationModule,
  QuickFilterModule,
  RowSelectionModule,
  TextFilterModule,
  provideGlobalGridOptions,
} from 'ag-grid-community';
import { AgGridReact } from 'ag-grid-react';
import 'ag-grid-community/styles/ag-grid.css';
import React, { useEffect, useMemo, useRef, useState } from 'react';
import { createRoot } from 'react-dom/client';
import { ApiC } from './api';
import { DEFAULT_AG_GRID_PAGINATION, toggleEditCompound } from './misc';
import i18next from './i18n';
import { notify } from './notify';
import { getAgGridTheme } from './theme';
import AgGridTableOptions from './ag-grid-table-options';

const COLUMN_STATE_STORAGE_KEY = 'persistent_compounds_table_column_state_v1';

provideGlobalGridOptions({ theme: 'legacy' });
ModuleRegistry.registerModules([
  ClientSideRowModelModule,
  CellStyleModule,
  ColumnApiModule,
  ColumnAutoSizeModule,
  ExternalFilterModule,
  RowSelectionModule,
  PaginationModule,
  TextFilterModule,
  QuickFilterModule,
]);

// SINGLE SEARCH BAR
// The text is a quick filter of the table, in the browser: every column, as it is typed.
// A term with one of these prefixes is a structure search, done by the server:
// smiles:CCO for the compounds containing this substructure, exact:CCO for this structure, smarts:[OX2H]c for a SMARTS pattern
const STRUCTURE_TERM = /^(smiles|exact|smarts):(\S+)$/i;
// the first block of an InChIKey encodes the connectivity: searching it also finds stereoisomers and other protonation states
const INCHIKEY = /^[A-Z]{14}-[A-Z]{10}-[A-Z]$/i;
// a structure search runs Open Babel: wait for a pause in the typing
const STRUCTURE_SEARCH_DELAY_MS = 400;

const parseSearch = query => {
  let structure = null;
  const text = [];
  query.trim().split(/\s+/).filter(Boolean).forEach(term => {
    // a prefix alone is a structure being typed: it does not filter the text
    if (/^(smiles|exact|smarts):$/i.test(term)) {
      return;
    }
    const match = term.match(STRUCTURE_TERM);
    if (match) {
      // only one structure is searched: the last one typed
      structure = { type: match[1].toLowerCase(), value: match[2] };
      return;
    }
    text.push(INCHIKEY.test(term) ? `${term.slice(0, 14)}-` : term);
  });
  return { text: text.join(' '), structure };
};

const structureSearchParams = structure => {
  if (structure.type === 'smarts') {
    return { search_smarts: structure.value };
  }
  return { search_fp_smi: structure.value, exact: structure.type === 'exact' ? '1' : '0' };
};

// the table is also mounted by the chemical editor, which searches with the drawn structure: see search-from-editor in chem-editor.ts
// on the compounds page, the search is in the url, so it can be shared and survives a reload of the table
const getInitialQuery = syncUrl => {
  const editorInput = document.getElementById('substructureSearchInput');
  if (editorInput?.value) {
    const exact = document.getElementById('search-fp-exact')?.checked;
    return `${exact ? 'exact' : 'smiles'}:${editorInput.value}`;
  }
  if (!syncUrl) {
    return '';
  }
  const urlParams = new URLSearchParams(window.location.search);
  // search_fp_smi: older links to a substructure search
  if (urlParams.get('search_fp_smi')) {
    const exact = ['1', 'on', 'true'].includes(urlParams.get('exact') ?? '');
    return `${exact ? 'exact' : 'smiles'}:${urlParams.get('search_fp_smi')}`;
  }
  return urlParams.get('q') ?? '';
};

const storeQueryInUrl = query => {
  const url = new URL(window.location.href);
  url.searchParams.delete('search_fp_smi');
  url.searchParams.delete('exact');
  if (query.trim()) {
    url.searchParams.set('q', query);
  } else {
    url.searchParams.delete('q');
  }
  window.history.replaceState(window.history.state, '', url);
};

const rowSelection = {
    mode: 'multiRow',
    headerCheckbox: false,
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

const GridExample = ({ canManageCompounds, deleteLabel, restoreLabel, searchHelpId, searchPlaceholder, syncUrl }) => {
    const [rowData, setRowData] = useState([]);
    const [gridApi, setGridApi] = useState(null);
    const [query, setQuery] = useState(() => getInitialQuery(syncUrl));
    const search = useMemo(() => parseSearch(query), [query]);
    // ids of the compounds found by the structure search, null without one
    const structureMatches = useRef(null);
    const [structurePending, setStructurePending] = useState(false);
    const [structureError, setStructureError] = useState('');
    const onGridReady = (event) => {
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

    const [columnDefs] = useState([
        { field: 'name', pinned: 'left' },
        { field: 'cas_number', headerName: 'CAS Number' },
        { field: 'iupac_name', headerName: 'IUPAC Name' },
        { field: 'molecular_weight', headerName: 'Molecular weight (g/mol)' },
        { field: 'molecular_formula', headerName: 'Molecular formula' },
        { field: 'ec_number', headerName: 'EC Number' },
        { field: 'pubchem_cid', headerName: 'PubChem CID' },
        { field: 'smiles', headerName: 'SMILES' },
        { field: 'inchi', headerName: 'InChI' },
        { field: 'inchi_key', headerName: 'InChI Key' },
        { field: 'userid_human', headerName: 'Owner' },
        { field: 'team_name', headerName: 'Team' },
        { field: 'modified_at', headerName: 'Modified at' },
        { field: 'has_fingerprint', headerName: 'Has fingerprint' },
        { field: 'is_corrosive', headerName: 'Corrosive' },
        { field: 'is_explosive', headerName: 'Explosive' },
        { field: 'is_flammable', headerName: 'Flammable' },
        { field: 'is_gas_under_pressure', headerName: 'Gas under pressure' },
        { field: 'is_hazardous2env', headerName: 'Hazardous to environment' },
        { field: 'is_hazardous2health', headerName: 'Hazardous to health' },
        { field: 'is_serious_health_hazard', headerName: 'Serious health hazard' },
        { field: 'is_oxidising', headerName: 'Oxidising' },
        { field: 'is_toxic', headerName: 'Toxic' },
        { field: 'ghs_signal_word', headerName: 'GHS signal word' },
        { field: 'ghs_codes', headerName: 'GHS statements' },
        { field: 'is_radioactive', headerName: 'Radioactive' },
        { field: 'is_antibiotic', headerName: 'Antibiotic' },
        { field: 'is_antibiotic_precursor', headerName: 'Antibiotic precursor' },
        { field: 'is_drug', headerName: 'Drug' },
        { field: 'is_drug_precursor', headerName: 'Drug precursor' },
        { field: 'is_explosive_precursor', headerName: 'Explosive precursor' },
        { field: 'is_cmr', headerName: 'CMR' },
        { field: 'is_nano', headerName: 'Nanomaterial' },
        { field: 'is_controlled', headerName: 'Controlled substance' },
        { field: 'is_ed2health', headerName: 'Endocrine disruptor for human health' },
        { field: 'is_ed2env', headerName: 'Endocrine disruptor for the environment' },
        { field: 'is_pbt', headerName: 'Persistent, bioaccumulative and toxic' },
        { field: 'is_vpvb', headerName: 'Very persistent and very bioaccumulative' },
        { field: 'is_pmt', headerName: 'Persistent, mobile and toxic' },
        { field: 'is_vpvm', headerName: 'Very persistent and very mobile' },
        { field: 'id', type: 'numericColumn' },
    ]);

  // filter by deleted compounds
  const [showDeleted, setShowDeleted] = useState(false);
  const [selectedIds, setSelectedIds] = useState([]);

  // Load data on component mount and refresh on showDeleted change
  useEffect(() => {
      fetchData();
  }, [showDeleted]);

  // all the compounds are loaded in the table, which does client side pagination and filtering
  const fetchData = async () => {
    // adding deleted flag to query
    const deletedParam = showDeleted ? '&state=3' : '';
    try {
      const compounds = await ApiC.getJson(`compounds?limit=999999${deletedParam}`);
      setRowData(compounds);
    } catch (error) {
      notify.error(error);
    }
  };

  // the text filters the table at once
  useEffect(() => {
    gridApi?.setGridOption('quickFilterText', search.text);
  }, [gridApi, search.text]);

  useEffect(() => {
    if (syncUrl) {
      storeQueryInUrl(query);
    }
  }, [query]);

  // the structure is searched on the server once the typing pauses, the latest search wins
  const structureKey = search.structure ? `${search.structure.type}:${search.structure.value}` : '';
  useEffect(() => {
    setStructureError('');
    if (!search.structure) {
      structureMatches.current = null;
      setStructurePending(false);
      gridApi?.onFilterChanged();
      return;
    }
    let cancelled = false;
    setStructurePending(true);
    const timer = setTimeout(() => {
      // an incomplete structure is expected while typing: show why it is not searched next to the bar, not as a notification
      ApiC.getJson('compounds', { ...structureSearchParams(search.structure), notifOnError: 0 })
        .then(compounds => {
          if (cancelled) return;
          structureMatches.current = new Set(compounds.map(compound => compound.id));
        })
        .catch(error => {
          if (cancelled) return;
          structureMatches.current = new Set();
          setStructureError(error.message);
        })
        .finally(() => {
          if (cancelled) return;
          setStructurePending(false);
          gridApi?.onFilterChanged();
        });
    }, STRUCTURE_SEARCH_DELAY_MS);
    return () => {
      cancelled = true;
      clearTimeout(timer);
    };
  }, [gridApi, structureKey]);

  const isExternalFilterPresent = () => structureMatches.current !== null;
  const doesExternalFilterPass = node => structureMatches.current?.has(node.data.id) ?? true;

    const defaultColDef = useMemo(() => {
        return {
            filter: 'agTextColumnFilter',
            floatingFilter: true,
            onCellValueChanged: (event) => {
              const params = {};
              params[event.column.colId] = event.newValue;
              ApiC.patch(`compounds/${event.data.id}`, params);
            }
        };
    }, []);

  // when a row is selected with the checkbox
  const selectionChanged = (event) => {
    setSelectedIds(event.api.getSelectedRows().map(c => c.id));
  };

  const toggleShowDeleted = () => {
    gridApi?.deselectAll();
    setSelectedIds([]);
    setShowDeleted(prev => !prev);
  };

  const cellDoubleClicked = (event) => {
    ApiC.getJson(`compounds/${event.data.id}`).then(json => {
      toggleEditCompound(json);
    });
  };

  return (
    <>
      <div className='input-group mb-2'>
        <div className='input-group-prepend'>
          <span className='input-group-text' aria-hidden='true'>
            <i className={`fas fa-fw ${structurePending ? 'fa-spinner fa-spin' : 'fa-magnifying-glass'}`}></i>
          </span>
        </div>
        <input
          type='search'
          aria-label={i18next.t('search')}
          aria-describedby={searchHelpId || undefined}
          aria-busy={structurePending}
          placeholder={searchPlaceholder || i18next.t('search')}
          value={query}
          onChange={e => setQuery(e.target.value)}
          className='form-control'
          spellCheck='false'
          autoComplete='off'
          autoCapitalize='off'
          data-cy='compounds-search'
        />
      </div>
      {structureError && (
        <p className='text-danger small mb-2' role='alert'>{structureError}</p>
      )}
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
        rowSelection={rowSelection}
        includeHiddenColumnsInQuickFilter={true}
        isExternalFilterPresent={isExternalFilterPresent}
        doesExternalFilterPass={doesExternalFilterPass}
        onCellDoubleClicked={cellDoubleClicked}
        onGridReady={onGridReady}
        onSelectionChanged={selectionChanged}
        {...DEFAULT_AG_GRID_PAGINATION}
      />
      <AgGridTableOptions
        gridApi={gridApi}
        storageKey={COLUMN_STATE_STORAGE_KEY}
      />
      </div>
      <div className='d-flex align-items-center my-2'>
        {canManageCompounds && (
          <>
            <button
              type='button'
              id='deleteCompoundsBtn'
              hidden={showDeleted}
              disabled={selectedIds.length === 0}
              data-action='delete-compounds'
              data-target={selectedIds.join(',')}
              className='btn btn-danger btn-sm'
            >
              {deleteLabel}
            </button>
            <button
              type='button'
              id='restoreCompoundsBtn'
              hidden={!showDeleted}
              disabled={selectedIds.length === 0}
              data-action='restore-compounds'
              data-target={selectedIds.join(',')}
              className='btn btn-primary btn-sm'
            >
              {restoreLabel}
            </button>
          </>
        )}
        <button
          type='button'
          className={'btn btn-sm btn-ghost ml-auto'}
          onClick={toggleShowDeleted}
        >
          {showDeleted ? i18next.t('hide-deleted') : i18next.t('show-deleted')}
        </button>
      </div>
  </>
  );
};

// In order to reload the table, we wrap it in another element with a key that is incremented when a dataReload event happens
// This change will trigger a full remount of the element, and the table will be updated
const App = (props) => {
  const [reloadKey, setReloadKey] = useState(0);
  // trigger this with document.dispatchEvent(new CustomEvent('dataReload'))
  useEffect(() => {
    const reloadTable = () => setReloadKey(prevKey => prevKey + 1);
    document.addEventListener('dataReload', reloadTable);
    return () => document.removeEventListener('dataReload', reloadTable);
  }, []);
  return <GridExample key={reloadKey} {...props} />;
};

const tableElement = document.getElementById('compounds-table');
const root = createRoot(tableElement);
root.render(
  <App
    canManageCompounds={tableElement.dataset.canManageCompounds === '1'}
    deleteLabel={tableElement.dataset.deleteLabel}
    restoreLabel={tableElement.dataset.restoreLabel}
    searchHelpId={tableElement.dataset.searchHelpId}
    searchPlaceholder={tableElement.dataset.searchPlaceholder}
    syncUrl={tableElement.dataset.syncUrl === '1'}
  />
);
