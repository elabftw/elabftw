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
import { getCheckedBoxes, notif } from './misc';
import { Type, Entity } from './interfaces';

$(document).ready(function() {
  const type = $('#info').data('type');
  // holds info about the page through data attributes
  const about = document.getElementById('info').dataset;
  let entityType: Type;
  if (about.type === 'experiments') {
    entityType = Type.Experiment;
  }
  if (about.type === 'items') {
    entityType = Type.Item;
  }

  const entity: Entity = {
    type: entityType,
    id: parseInt(about.id),
  };

  // STEPS
  const StepC = new Step(entity);

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
      style : 'display:inline'
    });
  });

  // FINISH
  $(document).on('click', 'input[type=checkbox].stepbox', function(e) {
    // on the todolist we don't want to grab the type from the page
    // because it's only steps from experiments
    // so if the element has a data-type, take that instead
    if (e.currentTarget.dataset.type) {
      entity.type = e.currentTarget.dataset.type;
    }
    const stepId = e.currentTarget.dataset.stepid;
    StepC.finish(stepId).then(() => {
      // only reload children
      const loadUrl = window.location.href + ' #steps_div_' + stepId + ' > *';
      // reload the step list
      $('#steps_div_' + stepId).load(loadUrl, function() {
        relativeMoment();
        makeSortableGreatAgain();
      });
      $('#todo_step_' + stepId).prop('checked', true);
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
        });
      });
    }
  });

  // END STEPS

  // LINKS
  const LinkC = new Link(type);

  // CREATE
  // listen keypress, add link when it's enter or on blur
  $(document).on('keypress blur', '#linkinput', function(e) {
    // Enter is ascii code 13
    if (e.which === 13 || e.type === 'focusout') {
      LinkC.create(parseInt($(this).val() as string), $(this).data('id') as number);
    }
  });

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
          'res': false
        };
        notif(json);
        return;
      }
      $.each(checked, function(index) {
        LinkC.create(parseInt($('#linkInputMultiple').val() as string), checked[index]['id']);
      });
      $(this).val('');
    }
  });

  // AUTOCOMPLETE
  const cache: any = {};
  $('.linkinput').autocomplete({
    source: function(request: any, response: any) {
      const term = request.term;
      if (term in cache) {
        response(cache[term]);
        return;
      }
      $.getJSON('app/controllers/EntityAjaxController.php?source=items', request, function(data) {
        cache[term] = data;
        response(data);
      });
    }
  });

  // DESTROY
  $(document).on('click', '.linkDestroy', function() {
    LinkC.destroy($(this));
  });
});
