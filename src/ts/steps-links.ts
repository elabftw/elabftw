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
import i18next from 'i18next';
import { relativeMoment, makeSortableGreatAgain, reloadElements, addAutocompleteToLinkInputs, addAutocompleteToCompoundsInputs, getEntity, adjustHiddenState } from './misc';
import { Action, Target } from './interfaces';
import { Api } from './Apiv2.class';

document.addEventListener('DOMContentLoaded', () => {
  if (!document.getElementById('info')) {
    return;
  }
  const entity = getEntity();
  const ApiC = new Api();

  // STEPS
  const StepC = new Step(entity);

  relativeMoment();

  // MAIN LISTENER for actions
  document.querySelector('.real-container').addEventListener('click', event => {
    const el = (event.target as HTMLElement);

    // ADD DEADLINE ON STEP
    if (el.matches('[data-action="step-update-deadline"]')) {
      const value = (document.getElementById('stepSelectDeadline_' + el.dataset.stepid) as HTMLSelectElement).value;
      StepC.update(parseInt(el.dataset.stepid, 10), value, Target.Deadline).then(() => {
        reloadElements(['stepsDiv']);
      });
    // ADD STEP
    } else if (el.matches('[data-action="create-step"]')) {
      createStep(el.parentElement.parentElement.querySelector('input'));
    // TOGGLE DEADLINE NOTIFICATIONS ON STEP
    } else if (el.matches('[data-action="step-toggle-deadline-notif"]')) {
      StepC.notif(parseInt(el.dataset.stepid, 10)).then(() => reloadElements(['stepsDiv']));
    // DESTROY DEADLINE ON STEP
    } else if (el.matches('[data-action="step-destroy-deadline"]')) {
      StepC.update(parseInt(el.dataset.stepid, 10), null, Target.Deadline)
        .then(() => reloadElements(['stepsDiv']));
    // IMPORT LINK(S) OF LINK
    } else if (el.matches('[data-action="import-links"]')) {
      Promise.allSettled(['items_links', 'experiments_links'].map(endpoint => ApiC.post(`${entity.type}/${entity.id}/${endpoint}/${el.dataset.target}`, {'action': Action.Duplicate}))).then(() => reloadElements(['linksDiv', 'linksExpDiv']));
    // DESTROY LINK
    } else if (el.matches('[data-action="destroy-link"]')) {
      if (confirm(i18next.t('link-delete-warning'))) {
        ApiC.delete(`${entity.type}/${entity.id}/${el.dataset.endpoint}/${el.dataset.target}`)
          .then(() => el.parentElement.parentElement.remove());
      }
    } else if (el.matches('[data-action="destroy-related-link"]')) {
      if (confirm(i18next.t('link-delete-warning'))) {
        ApiC.delete(`${el.dataset.endpoint.split('_')[0]}/${el.dataset.target}/${entity.type}_links/${entity.id}`)
          .then(() => el.parentElement.parentElement.remove());
      }
    } else if (el.matches('[data-action="destroy-step"]')) {
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
    }
  });


  // CREATE
  $(document).on('keypress', '.stepinput', function(e) {
    // Enter is ascii code 13
    if (e.which === 13) {
      createStep(e.currentTarget);
    }
  });

  function createStep(input: HTMLInputElement): void
  {
    const content = input.value;
    if (content.length > 0) {
      StepC.create(content).then(() => {
        reloadElements(['stepsDiv']).then(() => {
          // clear input field
          input.value = '';
          input.focus();
        });
      });
    }
  }

  // UPDATE MALLEABLE STEP BODY, FINISH TIME OR DEADLINE (data-target attribute)
  const malleableStep = new Malle({
    cancel : i18next.t('cancel'),
    cancelClasses: ['button', 'btn', 'btn-danger', 'mt-2'],
    inputClasses: ['form-control'],
    fun: async (value, original) => {
      return StepC.update(parseInt(original.dataset.stepid, 10), value, original.dataset.target as Target)
        .then(resp => resp.json()).then(json => original.dataset.target === Target.Body ? json.body : json.deadline);
    },
    listenOn: '.step.editable',
    returnedValueIsTrustedHtml: false,
    submit : i18next.t('save'),
    submitClasses: ['button', 'btn', 'btn-primary', 'mt-2'],
    tooltip: i18next.t('click-to-edit'),
  }).listen();

  // add an observer so new steps will get an event handler too
  if (document.getElementById('stepsDiv')) {
    new MutationObserver(() => {
      malleableStep.listen();
      adjustHiddenState();
      makeSortableGreatAgain();
      relativeMoment();
    }).observe(document.getElementById('stepsDiv'), {childList: true});
  }

  // FINISH
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
    const newentity = entity;
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
        });
      });
    }
  });
  // AUTOCOMPLETE
  addAutocompleteToLinkInputs();
  addAutocompleteToCompoundsInputs();
});
