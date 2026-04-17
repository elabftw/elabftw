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

// allow filtering by values for cells that render icons or badges (team, isSysadmin, etc.,)
const yesNo = v => v === 1 ? i18next.t('yes') : i18next.t('no');
const enabledDisabled = v => v === 1 ? i18next.t('enabled') : i18next.t('disabled');
const lastLoginText = v => v === null ? i18next.t('never') : v;
const validUntilText = v => v === null ? i18next.t('forever') : v;
// allow filtering for "admin" as well as team name
const teamsText = teams => teams?.map(t => `${t.name} ${t.is_admin ? i18next.t('admin') : i18next.t('user')}`).join(' ') ?? '';

async function toggleUserModal(user) {
  const textParams = ['userid', 'firstname', 'lastname', 'email', 'valid_until', 'orgid'];
  textParams.forEach(param => {
    (document.getElementById(`userInput-${param}`)).value = user[param];
  });
  const binaryParams = ['is_sysadmin', 'can_manage_users2teams', 'can_manage_compounds', 'can_manage_inventory_locations'];
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
    const isDark = document.documentElement.classList.contains('dark-mode');

    const onGridReady = (params) => {
      setGridApi(params.api);
    };
    const onQuickFilterChange = (e) => {
      gridApi.setGridOption('quickFilterText', e.target.value);
    };
    // renderer for teams column
    const TeamsRenderer = ({ value }) => {
      return (
        <span>{value.map(team => (
          <span key={team.id} data-id={team.id} className={`mr-2 ${team.is_admin ? 'admin' : 'user'}-badge ${team.is_archived ? 'bg-medium color-thirdlevel' : ''}`}>{team.name}</span>))}
        </span>
      );
    };

    const PastDateRenderer = ({ value }) => {
      return value === i18next.t('never')
        ? <span className='font-italic'>{value}</span>
        : <span>{value}</span>;
    };

    const ValidUntilRenderer = ({ value }) => {
      return value === i18next.t('forever')
        ? <span className='font-italic'>{value}</span>
        : <span>{value}</span>;
    };

    const HasMfaEnabledRenderer = ({ value }) => {
      return value === i18next.t('enabled')
        ? <span title={value}><i className='fas fa-user-shield mr-2'></i>{value}</span>
        : <span className='font-italic' title={value}>{value}</span>;
    };

    const BinaryRenderer = ({ value }) => {
      return value === i18next.t('yes')
        ? <span title={value}><i className='fas fa-circle-check mr-2 color-blue'></i>{value}</span>
        : <span title={value}><i className='fas fa-circle-xmark mr-2'></i>{value}</span>;
    };

    const [columnDefs] = useState([
      { field: 'userid', headerName: i18next.t('userid'), pinned: 'left' },
      { field: 'teams', headerName: i18next.t('teams'), cellRenderer: TeamsRenderer, filterValueGetter: p => teamsText(p.data.teams) },
      { field: 'firstname', headerName: i18next.t('firstname') },
      { field: 'lastname', headerName: i18next.t('lastname') },
      { field: 'email', headerName: i18next.t('email') },
      { field: 'last_login', headerName: i18next.t('last-login'), valueGetter: p => lastLoginText(p.data.last_login), filterValueGetter: p => lastLoginText(p.data.last_login), cellRenderer: PastDateRenderer},
      { field: 'is_sysadmin', headerName: i18next.t('is-sysadmin'), valueGetter: p => yesNo(p.data.is_sysadmin), filterValueGetter: p => yesNo(p.data.is_sysadmin), cellRenderer: BinaryRenderer },
      { field: 'has_mfa_enabled', headerName: i18next.t('2FA'), valueGetter: p => enabledDisabled(p.data.has_mfa_enabled), filterValueGetter: p => enabledDisabled(p.data.has_mfa_enabled), cellRenderer: HasMfaEnabledRenderer },
      { field: 'valid_until', headerName: i18next.t('Valid until'), valueGetter: p => validUntilText(p.data.valid_until), filterValueGetter: p => validUntilText(p.data.valid_until), cellRenderer: ValidUntilRenderer },
      { field: 'validated', headerName: i18next.t('Validated'), valueGetter: p => yesNo(p.data.validated), filterValueGetter: p => yesNo(p.data.validated), cellRenderer: BinaryRenderer },
      { field: 'orgid', headerName: i18next.t('Internal id') },
      { field: 'orcid', headerName: 'ORCID' },
      { field: 'can_manage_users2teams', headerName: i18next.t('can-manage-users2teams'), valueGetter: p => yesNo(p.data.can_manage_users2teams), filterValueGetter: p => yesNo(p.data.can_manage_users2teams), cellRenderer: BinaryRenderer },
      { field: 'can_manage_compounds', headerName: i18next.t('can-manage-compounds'), valueGetter: p => yesNo(p.data.can_manage_compounds), filterValueGetter: p => yesNo(p.data.can_manage_compounds), cellRenderer: BinaryRenderer },
      { field: 'can_manage_inventory_locations', headerName: i18next.t('can-manage-inventory-locations'), valueGetter: p => yesNo(p.data.can_manage_inventory_locations), filterValueGetter: p => yesNo(p.data.can_manage_inventory_locations), cellRenderer: BinaryRenderer },
      { field: 'created_at', headerName: i18next.t('created-at'), valueGetter: p => lastLoginText(p.data.created_at), filterValueGetter: p => lastLoginText(p.data.created_at), cellRenderer: PastDateRenderer},
      //TODO when field exists { field: 'modified_at', headerName: i18next.t('modified-at'), valueGetter: p => lastLoginText(p.data.modified_at), filterValueGetter: p => lastLoginText(p.data.last_login), cellRenderer: PastDateRenderer},
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
          type='text'
          placeholder={i18next.t('search')}
          onChange={onQuickFilterChange}
          className={'form-control mb-2'}
          aria-label={i18next.t('search')}
        />
        <div
          className={isDark ? 'ag-theme-alpine-dark' : 'ag-theme-alpine'} style={{ height: 650 }}>
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
    return <GridExample key={reloadKey}/>;
  };

  const root = createRoot(document.getElementById('users-table'));
  root.render(
    <App/>
  );
}
