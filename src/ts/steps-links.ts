/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import $ from 'jquery';
import 'jquery-ui/ui/widgets/autocomplete';
import { Malle } from '@deltablot/malle';
import Step from './Step.class';
import i18next from './i18n';
import {
  addAutocompleteToCompoundsInputs,
  addAutocompleteToLinkInputs,
  adjustHiddenState,
  collectForm,
  makeSortableGreatAgain,
  relativeMoment,
  reloadElements,
} from './misc';
import { Action, Target } from './interfaces';
import { ApiC } from './api';
import { entity } from './getEntity';
import { on } from './handlers';

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

if (document.getElementById('stepsDiv')) {
  const StepC = new Step(entity);

  on('create-step', (_, event: Event) => {
    event.preventDefault();
    const form = document.getElementById('addStepForm') as HTMLFormElement;
    const params = collectForm(form);
    const content = String(params['step'] ?? '').trim();
    if (!content) return;
    StepC.create(content).then(() => {
      reloadElements(['stepsDiv']).then(() => {
        (document.getElementById('addStepInput') as HTMLInputElement).focus();
      });
    });
  });

  on('step-update-deadline', (el: HTMLElement) => {
    const value = (document.getElementById('stepSelectDeadline_' + el.dataset.stepid) as HTMLSelectElement).value;
    StepC.update(parseInt(el.dataset.stepid, 10), value, Target.Deadline).then(() => {
      reloadElements(['stepsDiv']);
    });
  });

  on('step-toggle-deadline-notif', (el: HTMLElement) => {
    StepC.notif(parseInt(el.dataset.stepid, 10)).then(() => reloadElements(['stepsDiv']));
  });

  on('step-destroy-deadline', (el: HTMLElement) => {
    StepC.update(parseInt(el.dataset.stepid, 10), null, Target.Deadline)
      .then(() => reloadElements(['stepsDiv']));
  });

  on('destroy-step', (el: HTMLElement) => {
    if (confirm(i18next.t('step-delete-warning'))) {
      StepC.destroy(parseInt(el.dataset.id, 10)).then(() => {
        el.parentElement.parentElement.remove();
        // keep to do list in sync
        const todoStep = document.getElementById(`todo_step_${el.dataset.id}`);
        if (todoStep) {
          todoStep.parentElement.remove();
        }
      });
    }
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
    fun: async (value, original) => {
      return StepC.update(parseInt(original.dataset.stepid, 10), value, original.dataset.target as Target)
        .then(resp => resp.json())
        .then(json => original.dataset.target === Target.Body
          ? json.body
          : json.deadline,
        );
    },
    listenOn: '.step.editable',
    returnedValueIsTrustedHtml: false,
    submit : i18next.t('save'),
    submitClasses: ['button', 'btn', 'btn-primary', 'mt-2'],
    tooltip: i18next.t('click-to-edit'),
  }).listen();

  // add an observer so new steps will get an event handler too
  new MutationObserver(() => {
    malleableStep.listen();
    adjustHiddenState();
    makeSortableGreatAgain();
    relativeMoment();
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
          addAutocompleteToLinkInputs();
          addAutocompleteToCompoundsInputs();
        });
      });
    }
  });
  // AUTOCOMPLETE
  addAutocompleteToLinkInputs();
  addAutocompleteToCompoundsInputs();
}
