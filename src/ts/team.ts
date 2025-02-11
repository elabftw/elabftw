/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import i18next from 'i18next';
import { Malle, InputType, SelectOptions } from '@deltablot/malle';
import 'jquery-ui/ui/widgets/autocomplete';
import 'bootstrap/js/src/modal.js';
import { ProcurementState } from './interfaces';
import { Api } from './Apiv2.class';
import { reloadElements } from './misc';
import Tab from './Tab.class';

document.addEventListener('DOMContentLoaded', () => {
  if (window.location.pathname !== '/team.php') {
    return;
  }

  const TabMenu = new Tab();
  TabMenu.init(document.querySelector('.tabbed-menu'));

  const ApiC = new Api();

  // transform the enum into the kind of object we want
  const procurementStateArr: SelectOptions[] = Object.keys(ProcurementState)
    .filter(key => !isNaN(Number(key)))
    .map(key => ({
      selected: false,
      text: ProcurementState[key],
      value: key,
    }));

  new Malle({
    cancel : i18next.t('cancel'),
    cancelClasses: ['btn', 'btn-danger', 'mt-2', 'ml-1'],
    inputClasses: ['form-control'],
    fun: (value: string, original: HTMLElement) => {
      return ApiC.patch(`teams/current/procurement_requests/${original.dataset.id}`, {state: value}).then(res => res.json()).then(json => json.state);
    },
    inputType: InputType.Select,
    selectOptions: procurementStateArr,
    listenOn: '.malleableState',
    returnedValueIsTrustedHtml: false,
    submit : i18next.t('save'),
    submitClasses: ['btn', 'btn-primary', 'mt-2'],
    tooltip: i18next.t('click-to-edit'),
  }).listen();


  // Add click listener and do action based on which element is clicked
  document.querySelector('.real-container').addEventListener('click', (event) => {
    const el = (event.target as HTMLElement);
    // RECEIVE PROCUREMENT REQUEST
    if (el.matches('[data-action="receive-procurement-request"]')) {
      ApiC.patch(`teams/current/procurement_requests/${el.dataset.id}`);

    // CANCEL PROCUREMENT REQUEST
    } else if (el.matches('[data-action="cancel-procurement-request"]')) {
      if (confirm(i18next.t('generic-delete-warning'))) {
        ApiC.delete(`teams/current/procurement_requests/${el.dataset.id}`).then(() => reloadElements(['procurementRequestsTable']));
      }
    }
  });
});
