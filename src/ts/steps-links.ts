/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import $ from 'jquery';
import 'jquery-ui/ui/widgets/autocomplete';
import Link from './Link.class';
import Step from './Step.class';
import i18next from 'i18next';
import { relativeMoment, makeSortableGreatAgain } from './misc';
import { getCheckedBoxes, notif, getEntity } from './misc';
import { Entity } from './interfaces';

document.addEventListener('DOMContentLoaded', () => {
  if (!document.getElementById('info')) {
    return;
  }
  const entity = getEntity();

  // STEPS
  const StepC = new Step(entity);
  relativeMoment();

  // CREATE
  $(document).on('keypress blur', '.stepinput', function(e) {
    // Enter is ascii code 13
    if (e.which === 13 || e.type === 'focusout') {
      const content = e.currentTarget.value;
      if (content.length > 0) {
        StepC.create(content).then(() => {
          // only reload children
          const loadUrl = window.location.href + ' #steps_div_' + entity.id + ' > *';
          // reload the step list
          $('#steps_div_' + entity.id).load(loadUrl, function() {
            relativeMoment();
            makeSortableGreatAgain();
          });
          // clear input field
          e.currentTarget.value = '';
        });
      }
    }
  });

  // UPDATE
  $(document).on('mouseenter', '.stepInput', (e) => {
    ($(e.currentTarget) as any).editable((input: string) => {
      StepC.update(
        input,
        e.currentTarget.dataset.stepid,
      );
      // here the input is returned instead of the value returned by controller
      // in json response. That's because the call is asynchronous and jeditable expects
      // an asynchronous response
      return input;
    }, {
      tooltip : i18next.t('click-to-edit'),
      indicator : 'Saving...',
      onblur: 'submit',
      style : 'display:inline',
    });
  });

  // FINISH
  $(document).on('click', 'input[type=checkbox].stepbox', function(e) {
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
      const loadUrl = window.location.href + ' #steps_div_' + entity.id + ' > *';
      // reload the step list
      $('#steps_div_' + entity.id).load(loadUrl, function() {
        relativeMoment();
        makeSortableGreatAgain();
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
        const loadUrl = window.location + ' #steps_div_' + entity.id + ' > *';
        // reload the step list
        $('#steps_div_' + entity.id).load(loadUrl, function() {
          relativeMoment();
          makeSortableGreatAgain();
          $('#todo_step_' + stepId).parent().hide();
        });
      });
    }
  });

  // END STEPS

  // LINKS
  const LinkC = new Link(entity);

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
        $('#links_div_' + entity.id).load(window.location.href + ' #links_div_' + entity.id + ' > *');
        // clear input field
        (document.querySelector('.linkinput') as HTMLInputElement).value = '';
      });
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
  let cache = {};
  // this is the select category filter on add link input
  const catFilterEl = (document.getElementById('addLinkCatFilter') as HTMLInputElement);
  if (catFilterEl) {
    // when we change the category filter, reset the cache
    catFilterEl.addEventListener('change', () => {
      cache = {};
    });
    $('.linkinput').autocomplete({
      source: function(request: Record<string, string>, response: (data) => void): void {
        const term = request.term;
        if (term in cache) {
          response(cache[term]);
          return;
        }
        $.getJSON(`app/controllers/EntityAjaxController.php?source=items&filter=${catFilterEl.value}`, request, function(data) {
          cache[term] = data;
          response(data);
        });
      },
    });
  }

  // DESTROY
  $(document).on('click', '.linkDestroy', function() {
    if (confirm(i18next.t('link-delete-warning'))) {
      LinkC.destroy($(this).data('linkid')).then(() => {
        // only reload children of links_div_id
        $('#links_div_' + entity.id).load(window.location.href + ' #links_div_' + entity.id + ' > *');
      });
    }
  });
});
