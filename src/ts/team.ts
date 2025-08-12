/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import i18next from './i18n';
import { Malle, InputType, SelectOptions } from '@deltablot/malle';
import 'jquery-ui/ui/widgets/autocomplete';
import 'bootstrap/js/src/modal.js';
import { ProcurementState } from './interfaces';
import { ApiC } from './api';
import { reloadElements } from './misc';
import {on} from './handlers';

if (window.location.pathname === '/team.php') {

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

  on('receive-procurement-request', (el: HTMLElement) => ApiC.patch(`teams/current/procurement_requests/${el.dataset.id}`));
  on('cancel-procurement-request', (el: HTMLElement) => {
    if (confirm(i18next.t('generic-delete-warning'))) {
      ApiC.delete(`teams/current/procurement_requests/${el.dataset.id}`).then(() => reloadElements(['procurementRequestsTable']));
    }
  });
}
