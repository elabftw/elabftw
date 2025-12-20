/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2025 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

/**
 * Code related to the users table present on the Users tab of Admin and Sysconfig panels
 */
import { ClientSideRowModelModule } from '@ag-grid-community/client-side-row-model';
import { ModuleRegistry } from '@ag-grid-community/core';
import { AgGridReact } from '@ag-grid-community/react';
import '@ag-grid-community/styles/ag-grid.css';
import '@ag-grid-community/styles/ag-theme-alpine.css';
import React, { useEffect, useMemo, useState } from 'react';
import { createRoot } from 'react-dom/client';
import { ApiC } from './api';
import { populateUserModal } from './misc';
import { notify } from './notify';
import i18next from './i18n';
import $ from 'jquery';

async function toggleUserModal(user) {
  const textParams = [
    'userid',
    'firstname',
    'lastname',
    'email',
    'valid_until',
    'orgid',
  ];
  textParams.forEach(param => {
    (document.getElementById(`userInput-${param}`)).value = user[param];
  });
  const binaryParams = [
    'is_sysadmin',
    'can_manage_users2teams',
    'can_manage_compounds',
    'can_manage_inventory_locations',
  ];
  binaryParams.forEach(param => {
    const input = (document.getElementById(`userInput-${param}`));
    input.checked = user[param] === 1;
  });
  document.getElementById('editUserModalSaveBtn').dataset.userid = user.userid;
  populateUserModal(user);
  $('#editUserModal').modal('toggle');
}

if (document.getElementById('users-table')) {
  ModuleRegistry.registerModules([ClientSideRowModelModule]);

  const rowSelection = {
      mode: 'multiRow',
      headerCheckbox: false,
  };

  const GridExample = () => {
    const [rowData, setRowData] = useState([]);
    const [gridApi, setGridApi] = useState(null);

    const onGridReady = (params) => {
      setGridApi(params.api);
    };
    const onQuickFilterChange = (e) => {
      gridApi.setGridOption('quickFilterText', e.target.value);
    };
    // renderer for teams column
    const TeamsRenderer = ({ value }) => {
      const items = value
        .map(team => (
          <span className={`mr-2 ${team.is_admin ? 'admin' : 'user'}-badge ${team.is_archived ? 'bg-medium' : ''}`} key={team.id} data-id={team.id}>
            {team.name}
          </span>
        ));
      return <span>{items}</span>;
    };

    const LastLoginRenderer = ({ value }) => {
      return value === null
        ? <span className='font-italic'>{i18next.t('never')}</span>
        : <span>{value}</span>;
    };

    const ValidUntilRenderer = ({ value }) => {
      return value === null
        ? <span className='font-italic'>{i18next.t('forever')}</span>
        : <span>{value}</span>;
    };

    const HasMfaEnabledRenderer = ({ value }) => {
      return value === 1
        ? <span title={i18next.t('enabled')}><i title={i18next.t('enabled')} className='fas fa-user-shield mr-2'></i>{i18next.t('enabled')}</span>
        : <span className='font-italic' title='disabled'>{i18next.t('disabled')}</span>;
    };

    const BinaryRenderer = ({ value }) => {
      return value === 1
        ? <span title={i18next.t('yes')}><i title={i18next.t('yes')} className='fas fa-circle-check mr-2 color-blue'></i>{i18next.t('yes')}</span>
        : <span title={i18next.t('no')}><i title={i18next.t('no')} className='fas fa-circle-xmark mr-2'></i>{i18next.t('no')}</span>
    };

    const [columnDefs] = useState([
        { field: 'userid', headerName: i18next.t('userid'), pinned: 'left' },
        { field: 'teams', headerName: i18next.t('teams'), cellRenderer: TeamsRenderer },
        { field: 'firstname', headerName: i18next.t('firstname') },
        { field: 'lastname', headerName: i18next.t('lastname') },
        { field: 'email', headerName: i18next.t('email') },
        { field: 'last_login', headerName: i18next.t('last-login'), cellRenderer: LastLoginRenderer },
        { field: 'is_sysadmin', headerName: i18next.t('is-sysadmin'), cellRenderer: BinaryRenderer},
        { field: 'has_mfa_enabled', headerName: i18next.t('2FA'), cellRenderer: HasMfaEnabledRenderer },
        { field: 'valid_until', headerName: i18next.t('Valid until'), cellRenderer: ValidUntilRenderer },
        { field: 'validated', headerName: i18next.t('Validated'), cellRenderer: BinaryRenderer},
        { field: 'orgid', headerName: i18next.t('Internal id') },
        { field: 'orcid', headerName: 'ORCID' },
        { field: 'can_manage_users2teams', headerName: i18next.t('can-manage-users2teams'), cellRenderer: BinaryRenderer},
        { field: 'can_manage_compounds', headerName: i18next.t('can-manage-compounds'), cellRenderer: BinaryRenderer},
        { field: 'can_manage_inventory_locations', headerName: i18next.t('can-manage-inventory-locations'), cellRenderer: BinaryRenderer},
    ]);

    // Load data on component mount
    useEffect(() => {
        fetchData();
    }, []);

    // all the users are loaded in the table, which does client side pagination
    const fetchData = async () => {
      const params = new URLSearchParams(document.location.search);
      let teamParam = params.get('team') ?? '';
      let currentTeam = 0;
      const showAllUsersInput = document.getElementById('showAllUsers');
      if (!teamParam && document.location.pathname.endsWith('/admin.php')) {
        currentTeam = 1;
      }
      if (showAllUsersInput?.checked) {
        teamParam = 0;
        currentTeam = 0;
      }
      const queryParams = `&onlyArchived=${params.get('onlyArchived')}&team=${teamParam}&currentTeam=${currentTeam}`;
      try {
        const users = await ApiC.getJson(`users?limit=999999${queryParams}`);
        setRowData(users);
      } catch (error) {
        notify.error(error);
        console.error(`Could not load users: ${error}`);
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

    // when a row is selected with the checkbox
    const selectionChanged = (event) => {
      // we store the selected rows as data-target string on the delete and restore buttons
      const selectedRows = event.api.getSelectedRows();
      const selectedIds = selectedRows.map(c => c.userid).join(',');
      const importBtn = document.getElementById('importUsersBtn');

      // buttons are disabled if no rows are selected.
      if (importBtn) {
        importBtn.disabled = selectedRows.length === 0;
        importBtn.dataset.target = selectedIds;
      }
    };

    const cellDoubleClicked = (event) => {
      ApiC.getJson(`users/${event.data.userid}`).then(json => {
        toggleUserModal(json);
      });
    };

    return (
      <>
        <input
          type="text"
          placeholder={i18next.t('search')}
          onChange={onQuickFilterChange}
          className={'form-control mb-2'}
        />
      <div
        className={'ag-theme-alpine'}
        style={{ height: 650 }}
      >
        <AgGridReact
          rowData={rowData}
          columnDefs={columnDefs}
          defaultColDef={defaultColDef}
          onGridReady={onGridReady}
          rowSelection={rowSelection}
          onCellDoubleClicked={cellDoubleClicked}
          onSelectionChanged={selectionChanged}
          pagination={true}
          paginationPageSize={15}
          paginationPageSizeSelector={[15, 50, 100, 500]}
        />
      </div>
      <div className='d-flex justify-content-start my-2'>
        <button
          data-action='import-users-in-team'
          id='importUsersBtn'
          type='button'
          disabled='disabled'
          className={'btn btn-sm btn-secondary'}
        >
        {i18next.t('add-to-team')}
        </button>
      </div>
    </>
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

  const root = createRoot(document.getElementById('users-table'));
  root.render(
    <App />
  );
}
