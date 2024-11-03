import { ClientSideRowModelModule } from '@ag-grid-community/client-side-row-model';
import { ModuleRegistry } from '@ag-grid-community/core';
import { AgGridReact } from '@ag-grid-community/react';
import '@ag-grid-community/styles/ag-grid.css';
import '@ag-grid-community/styles/ag-theme-quartz.css';
import React, { useMemo, useState } from 'react';
import { createRoot } from 'react-dom/client';
import { Api } from './Apiv2.class';

if (document.getElementById('compounds-table')) {
  ModuleRegistry.registerModules([ClientSideRowModelModule]);

  const rowSelection = {
      mode: 'multiRow',
      headerCheckbox: false,
  };

  const ApiC = new Api();
  // all the compounds are loaded in the table, which does client side pagination
  const compounds = await ApiC.getJson('compounds?limit=999999');

  const GridExample = () => {
      const [rowData, setRowData] = useState(compounds);

      const [columnDefs, setColumnDefs] = useState([
          { field: 'id', type: 'numericColumn' },
          {
            field: 'name',
            editable: true,
            cellEditor: 'agTextCellEditor',
            pinned: 'left',
          },
          { field: 'smiles' },
          { field: 'inchi' },
          { field: 'has_fingerprint', headerName: 'Has fingerprint' },
          { field: 'created_by', headerName: 'Created by' },
          { field: 'modified_at', headerName: 'Modified at' },
          { field: 'modified_by', headerName: 'Modified by' },
          { field: 'userid_human', headerName: 'Owner' },
          { field: 'team' },
      ]);

      const defaultColDef = useMemo(() => {
          return {
              filter: 'agTextColumnFilter',
              floatingFilter: true,
              onCellValueChanged: (event) => {
                console.log(event);
                const params = {};
                params[event.column.colId] = event.newValue;
                ApiC.patch(`compounds/${event.data.id}`, params);
              }
          };
      }, []);

      return (
          <div
              className={
                  "ag-theme-quartz-dark"
              }
              style={{ height: 650 }}
          >
              <AgGridReact
                  rowData={rowData}
                  columnDefs={columnDefs}
                  defaultColDef={defaultColDef}
                  rowSelection={rowSelection}
                  pagination={true}
                  paginationPageSize={15}
                  paginationPageSizeSelector={[15, 50, 100, 500]}
              />
          </div>
      );
  };

  const root = createRoot(document.getElementById('compounds-table'));
  root.render(
        <GridExample />
  );
}
