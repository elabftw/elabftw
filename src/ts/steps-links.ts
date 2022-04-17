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
import Link from './Link.class';
import Step from './Step.class';
import i18next from 'i18next';
import { relativeMoment, makeSortableGreatAgain, reloadElement } from './misc';
import { addAutocompleteToLinkInputs, getCheckedBoxes, notif, getEntity, adjustHiddenState } from './misc';
import { Entity, Target } from './interfaces';

document.addEventListener('DOMContentLoaded', () => {
  if (!document.getElementById('info')) {
    return;
  }
  const entity = getEntity();

  // STEPS
  const StepC = new Step(entity);

  // LINKS
  const LinkC = new Link(entity);

  relativeMoment();

  // MAIN LISTENER for actions
  document.querySelector('.real-container').addEventListener('click', event => {
    const el = (event.target as HTMLElement);
    // ADD DEADLINE ON STEP
    if (el.matches('[data-action="step-update-deadline"]')) {
      const value = (document.getElementById('stepSelectDeadline_' + el.dataset.stepid) as HTMLSelectElement).value;
      StepC.update(parseInt(el.dataset.stepid, 10), value, Target.Deadline).then(() => {
        reloadElement('stepsDiv');
      });
    // TOGGLE DEADLINE NOTIFICATIONS ON STEP
    } else if (el.matches('[data-action="step-toggle-deadline-notif"]')) {
      StepC.update(parseInt(el.dataset.stepid, 10), null, Target.DeadlineNotif).then(() => {
        reloadElement('stepsDiv');
      });

    // DESTROY DEADLINE ON STEP
    } else if (el.matches('[data-action="step-destroy-deadline"]')) {
      StepC.update(parseInt(el.dataset.stepid, 10), null, Target.Deadline).then(() => {
        reloadElement('stepsDiv');
      });
    // IMPORT LINK(S) OF LINK
    } else if (el.matches('[data-action="import-links"]')) {
      LinkC.importLinks(parseInt(el.dataset.target, 10)).then(() => reloadElement('linksDiv'));
    // DESTROY LINK
    } else if (el.matches('[data-action="destroy-link"]')) {
      if (confirm(i18next.t('link-delete-warning'))) {
        LinkC.destroy(parseInt(el.dataset.target, 10)).then(() => reloadElement('linksDiv'));
      }
    }
  });


  // CREATE
  $(document).on('keypress blur', '.stepinput', function(e) {
    // Enter is ascii code 13
    if (e.which === 13 || e.type === 'focusout') {
      const content = e.currentTarget.value;
      if (content.length > 0) {
        StepC.create(content).then(() => {
          reloadElement('stepsDiv');
          // clear input field
          e.currentTarget.value = '';
        });
      }
    }
  });

  // UPDATE MALLEABLE STEP BODY, FINISH TIME OR DEADLINE (data-target attribute)
  const malleableStep = new Malle({
    cancel : i18next.t('cancel'),
    cancelClasses: ['button', 'btn', 'btn-danger', 'mt-2'],
    inputClasses: ['form-control'],
    fun: (value, original) => {
      StepC.update(
        parseInt(original.dataset.stepid, 10),
        value,
        original.dataset.target as Target,
      ).then(() => {
        reloadElement('stepsDiv');
      });
      return value;
    },
    listenOn: '.step.editable',
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
    }).observe(document.getElementById('stepsDiv'), {childList: true, subtree: true});
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
      // only reload children
      reloadElement('steps_div_' + entity.id).then(() => {
        // keep to do list in sync
        $('#todo_step_' + stepId).prop('checked', $('.stepbox[data-stepid="' + stepId + '"]').prop('checked'));
      });
    });
  });

  // DESTROY
  $(document).on('click', '.stepDestroy', function(e) {
    if (confirm(i18next.t('step-delete-warning'))) {
      const stepId = e.currentTarget.dataset.stepid;
      StepC.destroy(stepId).then(() => {
        // only reload children
        reloadElement('steps_div_' + entity.id).then(() => {
          // keep to do list in sync
          $('#todo_step_' + stepId).parent().hide();
        });
      });
    }
  });

  // END STEPS

  // CREATE
  // listen keypress, add link when it's enter or on blur
  $(document).on('keypress blur', '#linkinput', function(e) {
    // Enter is ascii code 13
    if (e.which === 13 || e.type === 'focusout') {
      // grab the id from the target
      const target = parseInt($(this).val() as string);
      // only send request if there is a targetId
      if (Number.isNaN(target)) {
        return;
      }
      LinkC.create(target).then(() => {
        // only reload children of links_div_id
        reloadElement('links_div_' + entity.id).then(() => {
          // clear input field
          (document.getElementById('linkinput') as HTMLInputElement).value = '';
        });
      });
      $(this).val('');
    }
  });

  // CREATE FOR MULTIPLE ENTITIES
  $(document).on('keypress blur', '#linkInputMultiple', function(e) {
    if ($(this).val() === '') {
      return;
    }
    // Enter is ascii code 13
    if (e.which === 13 || e.type === 'focusout') {
      // get the ids of selected entities
      const checked = getCheckedBoxes();
      if (checked.length === 0) {
        const json = {
          'msg': 'Nothing selected!',
          'res': false,
        };
        notif(json);
        return;
      }
      $.each(checked, function(index) {
        const tmpEntity: Entity = {
          type: entity.type,
          id: checked[index]['id'],
        };
        const TmpLinkC = new Link(tmpEntity);
        TmpLinkC.create(parseInt($('#linkInputMultiple').val() as string));
      });
      $(this).val('');
    }
  });

  // AUTOCOMPLETE
  addAutocompleteToLinkInputs();
});
