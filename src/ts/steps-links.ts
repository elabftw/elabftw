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
import { getCheckedBoxes, notif } from './misc';

$(document).ready(function() {
  const type = $('#info').data('type');

  // STEPS
  const StepC = new Step(type);

  // CREATE
  $(document).on('keypress blur', '.stepinput', function(e) {
    // Enter is ascii code 13
    if (e.which === 13 || e.type === 'focusout') {
      StepC.create(e.currentTarget);
    }
  });

  // UPDATE
  $(document).on('mouseenter', '.stepInput', (e) => {
    ($(e.currentTarget) as any).editable((input) => {
      StepC.update(
        input,
        e.currentTarget.dataset.id,
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
    StepC.finish(e.currentTarget);
  });

  // DESTROY
  $(document).on('click', '.stepDestroy', function(e) {
    StepC.destroy(e.currentTarget);
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
