/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import $ from 'jquery';
import { Malle } from '@deltablot/malle';
import { DateTime, Duration } from 'luxon';
import Step from './Step.class';
import i18next from './i18n';
import {
  adjustHiddenState,
  collectForm,
  DATETIME_LOCAL_FORMAT,
  localInputToServer,
  localizeDatetimes,
  makeSortableGreatAgain,
  relativeMoment,
  reloadElements,
  serverTimezone,
  serverToLocal,
  SQL_DATETIME_FORMAT,
} from './misc';
import { Action, Target } from './interfaces';
import { ApiC } from './api';
import { entity } from './getEntity';
import { on } from './handlers';
import { createGroupedSortables } from './grouped-sortables';

// FINISH: outside if stepsDiv because can be from Todolist panel
$(document).on('click', 'input[type=checkbox].stepbox', function(e) {
  // ask for confirmation before un-finishing a step
  // this check happens after the browser changed the state, so it is inverted
  // what we are really checking here is if it was checked before the user clicks on it
  if (!$(this).is(':checked') && !confirm(i18next.t('step-unfinish-warning'))) {
    // re-check the box on cancel
    $(this).prop('checked', true);
    return;
  }

  // on the todolist we don't want to grab the type from the page
  // because it's only steps from experiments
  // so if the element has a data-type, take that instead
  // clone to avoid mutating shared state
  const newentity = { ...entity };
  if (e.currentTarget.dataset.type) {
    newentity.type = e.currentTarget.dataset.type;
    newentity.id = e.currentTarget.dataset.id;
  }
  const stepId = e.currentTarget.dataset.stepid;
  const StepNew = new Step(newentity);
  StepNew.finish(stepId).then(() => {
    reloadElements(['stepsDiv']).then(() => {
      // keep to do list in sync
      $('#todo_step_' + stepId).prop('checked', $('.stepbox[data-stepid="' + stepId + '"]').prop('checked'));
    });
  });
});

const StepC = new Step(entity);

// deadlines are stored without timezone and compared against the server clock to trigger notifications
// so they must be stored in the server timezone, while users see and type them in their own timezone
function serverNow(): DateTime {
  return DateTime.now().setZone(serverTimezone());
}

function formatStepDatetime(value: string|null): string {
  if (!value) {
    return '';
  }
  return serverToLocal(value).toLocaleString(DateTime.DATETIME_MED);
}

const stepGroupsEndpoint = `${entity.type}/${entity.id}/step_groups`;


on('create-step', (el: HTMLElement, event: Event) => {
  event.preventDefault();
  const form = el.closest('form');
  if (!(form instanceof HTMLFormElement)) return;
  const params = collectForm(form);
  const content = String(params['step'] ?? '').trim();
  if (!content) return;
  // An empty hidden group id means this step belongs to Default group
  const rawGroupId = String(params['group_id'] ?? '');
  const groupId = rawGroupId === '' ? null : parseInt(rawGroupId, 10);
  StepC.create(content, groupId).then(() => {
    reloadElements(['stepsDiv']).then(() => {
      // Keep the inline form open for quickly adding several steps to the same group.
      const reloadedForm = document.querySelector<HTMLFormElement>(`.add-step-form[data-groupid='${rawGroupId}']`);
      if (!reloadedForm) return;
      // Reuse toggle-next so the form, icon, aria-expanded and focus stay in sync
      document.querySelector<HTMLButtonElement>(`[data-toggle-target='${reloadedForm.id}']`)?.click();
    });
  });
});

on('create-step-group', (_, event: Event) => {
  event.preventDefault();
  const form = document.getElementById('addStepGroupForm') as HTMLFormElement;
  const params = collectForm(form);
  const title = String(params['title'] ?? '').trim();
  if (!title) return;
  ApiC.post(stepGroupsEndpoint, {title}).then(() => reloadElements(['stepsDiv']));
});

on('destroy-step-group', (el: HTMLElement) => {
  if (confirm(el.dataset.confirm)) {
    ApiC.delete(`${stepGroupsEndpoint}/${el.dataset.id}`).then(() => reloadElements(['stepsDiv']));
  }
});

// show the datetime input only when the custom deadline option is selected
document.addEventListener('change', event => {
  const el = event.target as HTMLElement;
  if (!(el instanceof HTMLSelectElement) || !el.classList.contains('step-deadline-select')) {
    return;
  }
  const input = document.getElementById(`stepCustomDeadline_${el.dataset.stepid}`) as HTMLInputElement;
  const isCustom = el.value === 'custom';
  input.hidden = !isCustom;
  if (isCustom) {
    if (!input.value) {
      input.value = DateTime.now().plus({ days: 1 }).toFormat(DATETIME_LOCAL_FORMAT);
    }
    input.focus();
  }
});

on('step-update-deadline', (el: HTMLElement) => {
  const stepid = parseInt(el.dataset.stepid, 10);
  const value = (document.getElementById(`stepSelectDeadline_${stepid}`) as HTMLSelectElement).value;
  let deadline: string;
  if (value === 'custom') {
    const input = document.getElementById(`stepCustomDeadline_${stepid}`) as HTMLInputElement;
    // the user types a wall-clock time in their own timezone
    const custom = localInputToServer(input.value);
    if (!input.value || !custom.isValid) {
      input.classList.add('is-invalid');
      input.focus();
      return;
    }
    input.classList.remove('is-invalid');
    deadline = custom.toFormat(SQL_DATETIME_FORMAT);
  } else {
    // computed at save time so a page left open does not produce a stale deadline
    deadline = serverNow().plus(Duration.fromISO(value)).toFormat(SQL_DATETIME_FORMAT);
  }
  StepC.update(stepid, deadline, Target.Deadline)
    .then(resp => resp.json())
    .then(json => {
      // Action.Notif is a toggle: only call it if there is no notification yet, otherwise we would remove it
      if (Number(json.deadline_notif) === 1) {
        return;
      }
      return StepC.notif(stepid);
    })
    .then(() => reloadElements(['stepsDiv']));
});

on('step-destroy-deadline', (el: HTMLElement) => {
  // the backend also removes the associated notification
  StepC.update(parseInt(el.dataset.stepid, 10), null, Target.Deadline)
    .then(() => reloadElements(['stepsDiv']));
});

on('destroy-step', (el: HTMLElement) => {
  if (confirm(i18next.t('step-delete-warning'))) {
    StepC.destroy(parseInt(el.dataset.id, 10)).then(() => {
      // keep to do list in sync
      const todoStep = document.getElementById(`todo_step_${el.dataset.id}`);
      if (todoStep) {
        todoStep.parentElement.remove();
      }
      reloadElements(['stepsDiv']);
    });
  }
});

on('toggle-all-immutable', (el: HTMLInputElement) => {
  ApiC.patch(`${entity.type}/${entity.id}/steps`, {action: el.checked ? Action.ForceLock : Action.ForceUnlock}).then(() => reloadElements(['stepsDiv']));
});

on('import-links', (el: HTMLElement) => {
  Promise.allSettled(['items_links', 'experiments_links'].map(endpoint => ApiC.post(
    `${entity.type}/${entity.id}/${endpoint}/${el.dataset.target}`,
    {action: Action.Duplicate},
  ))).then(() => reloadElements(['linksDiv', 'linksExpDiv']));
});

on('destroy-link', (el: HTMLElement) => {
  if (confirm(i18next.t('link-delete-warning'))) {
    ApiC.delete(`${entity.type}/${entity.id}/${el.dataset.endpoint}/${el.dataset.target}`)
      .then(() => el.parentElement.parentElement.remove());
  }
});

on('destroy-related-link', (el: HTMLElement) => {
  if (confirm(i18next.t('link-delete-warning'))) {
    ApiC.delete(`${el.dataset.endpoint.split('_')[0]}/${el.dataset.target}/${entity.type}_links/${entity.id}`)
      .then(() => el.parentElement.parentElement.remove());
  }
});

// UPDATE MALLEABLE STEP BODY, FINISH TIME OR DEADLINE (data-target attribute)
const malleableStep = new Malle({
  cancel : i18next.t('cancel'),
  cancelClasses: ['button', 'btn', 'btn-danger', 'mt-2'],
  inputClasses: ['form-control'],
  formClasses: ['w-100'],
  fun: async (value, original) => {
    const target = original.dataset.target as Target;
    if (target === Target.Body) {
      return StepC.update(parseInt(original.dataset.stepid, 10), value, target)
        .then(resp => resp.json())
        .then(json => json.body);
    }
    // datetime targets: the input holds a browser local time, the server stores its own timezone
    // an empty value clears the datetime, an unparsable one is sent as is for the backend to reject
    const dt = localInputToServer(value);
    const content = value && dt.isValid ? dt.toFormat(SQL_DATETIME_FORMAT) : value;
    const json = await StepC.update(parseInt(original.dataset.stepid, 10), content, target).then(resp => resp.json());
    // reload so the displayed value, relative time and input value are all refreshed
    // await it so a failed reload surfaces here instead of as an unhandled rejection
    await reloadElements(['stepsDiv']);
    return formatStepDatetime(json[target]);
  },
  listenOn: '.step.editable',
  returnedValueIsTrustedHtml: false,
  submit : i18next.t('save'),
  submitClasses: ['button', 'btn', 'btn-primary', 'mt-2'],
  tooltip: i18next.t('click-to-edit'),
}).listen();

const malleableStepGroupTitle = new Malle({
  cancel : i18next.t('cancel'),
  cancelClasses: ['button', 'btn', 'btn-danger', 'mt-2'],
  inputClasses: ['form-control'],
  fun: async (value, original) => ApiC.patch(`${stepGroupsEndpoint}/${original.dataset.groupid}`, {title: value})
    .then(resp => resp.json())
    .then(json => json.title),
  listenOn: '.step-group-title.editable',
  returnedValueIsTrustedHtml: false,
  submit : i18next.t('save'),
  submitClasses: ['button', 'btn', 'btn-primary', 'mt-2'],
  tooltip: i18next.t('click-to-edit'),
}).listen();

const initStepGroupSortables = createGroupedSortables({
  itemContainerSelector: '.steps-sortable',
  itemHandleSelector: '.sortableHandle',
  itemIdPrefix: 'step_',
  itemIdsKey: 'step_ids',
  itemEndpoint: `${entity.type}/${entity.id}/steps`,
  groupContainerSelector: '.step-groups-sortable',
  groupItemSelector: '.step-group',
  groupHandleSelector: '.step-group-sortable-handle',
  groupEndpoint: stepGroupsEndpoint,
  reload: () => reloadElements(['stepsDiv']),
});

initStepGroupSortables();

// add an observer so new steps will get an event handler too
new MutationObserver(() => {
  malleableStep.listen();
  malleableStepGroupTitle.listen();
  adjustHiddenState();
  makeSortableGreatAgain();
  initStepGroupSortables();
  relativeMoment();
  localizeDatetimes();
}).observe(document.getElementById('stepsDiv'), {childList: true});

// END STEPS

// CREATE LINK
// listen keypress, add link when it's enter or on blur
$(document).on('keypress blur', '.linkinput', function(e) {
  // Enter is ascii code 13
  if (e.which === 13 || e.type === 'focusout') {
    // grab the id from the value of the input, but only before the first space, which is the ID
    const target = parseInt(($(this).val() as string).split(' ')[0], 10);
    // only send request if target is a number
    if (Number.isNaN(target)) {
      return;
    }
    ApiC.post(`${entity.type}/${entity.id}/${$(this).data('endpoint')}/${target}`).then(() => {
      reloadElements(['linksDiv', 'linksExpDiv', 'compoundDiv']).then(() => {
        // clear input field
        $(this).val('');
      });
    });
  }
});
