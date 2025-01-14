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
import { ClientSideRowModelModule } from '@ag-grid-community/client-side-row-model';
import { ModuleRegistry } from '@ag-grid-community/core';
import { AgGridReact } from '@ag-grid-community/react';
import '@ag-grid-community/styles/ag-grid.css';
import '@ag-grid-community/styles/ag-theme-quartz.css';
import React, { useEffect, useMemo, useState } from 'react';
import { createRoot } from 'react-dom/client';
import { Api } from './Apiv2.class';
import { notif } from './misc';

const ApiC = new Api();

if (document.getElementById('compounds-table')) {
  ModuleRegistry.registerModules([ClientSideRowModelModule]);

  const rowSelection = {
      mode: 'multiRow',
      headerCheckbox: false,
  };


  const GridExample = () => {
      const [rowData, setRowData] = useState([]);

      const [columnDefs, setColumnDefs] = useState([
          { field: 'id', type: 'numericColumn' },
          {
            field: 'name',
            editable: true,
            cellEditor: 'agTextCellEditor',
            pinned: 'left',
          },
          { field: 'iupac_name' },
          { field: 'smiles' },
          { field: 'inchi' },
          { field: 'inchi_key' },
          { field: 'molecular_formula' },
          { field: 'cas_number' },
          { field: 'pubchem_cid' },
          { field: 'userid_human', headerName: 'Owner' },
          { field: 'team_name', headerName: 'Team' },
          { field: 'modified_at', headerName: 'Modified at' },
          { field: 'has_fingerprint', headerName: 'Has fingerprint' },
      ]);

    // Load data on component mount
    useEffect(() => {
        fetchData();
    }, []);

    // all the compounds are loaded in the table, which does client side pagination
    const fetchData = async () => {
      let searchString = '';
      if (document.getElementById('substructureSearchInput')) {
        const subInput = document.getElementById('substructureSearchInput');
        if (subInput.value) {
          searchString = `&search_fp_smi=${encodeURIComponent(subInput.value)}`;
        }
      }
      const compounds = await ApiC.getJson(`compounds?limit=999999${searchString}`);
      setRowData(compounds);
    };

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

    const rowClicked = (event) => {
      console.log(event.data);
    };

    // when a row is selected with the checkbox
    const selectionChanged = (event) => {
      // we store the selected rows as data-target string on the delete button
      const btn = document.getElementById('deleteCompoundsBtn');
      btn.removeAttribute('disabled');
      const selectedRows = event.api.getSelectedRows();
      btn.dataset.target = selectedRows.map(c => c.id).join(',');
    };

    const cellDoubleClicked = (event) => {
      if (event.value && event.column.colId !== 'name') {
        navigator.clipboard.writeText(event.value);
        notif({res:true, msg:'Copied to clipboard'});
      }
    };

    return (
      <div
        className={'ag-theme-quartz-dark'}
        style={{ height: 650 }}
      >
      <AgGridReact
        rowData={rowData}
        columnDefs={columnDefs}
        defaultColDef={defaultColDef}
        rowSelection={rowSelection}
        onCellDoubleClicked={cellDoubleClicked}
        onRowClicked={rowClicked}
        onSelectionChanged={selectionChanged}
        pagination={true}
        paginationPageSize={15}
        paginationPageSizeSelector={[15, 50, 100, 500]}
      />
      </div>
    );
  };

  // In order to reload the table, we wrap it in another element with a key that is incremented when a dataReload event happens
  // This change will trigger a full remount of the element, and the table will be updated
  const App = () => {
    const [reloadKey, setReloadKey] = useState(0);
    // trigger this with document.dispatchEvent(new CustomEvent('dataReload'))
    document.addEventListener('dataReload', () => setReloadKey(prevKey => prevKey + 1));
    return <GridExample key={reloadKey} />;
  };

  const root = createRoot(document.getElementById('compounds-table'));
  root.render(
    <App />
  );
}
