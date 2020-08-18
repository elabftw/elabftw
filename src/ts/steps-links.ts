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

$(document).ready(function() {
  const type = $('#info').data('type');

  ////////
  // STEPS
  const StepC = new Step(type);

  // CREATE
  $(document).on('keypress blur', '.stepinput', function(e) {
    // Enter is ascii code 13
    if (e.which === 13 || e.type === 'focusout') {
      StepC.create($(this));
    }
  });

  // STEP IS DONE
  $(document).on('click', 'input[type=checkbox].stepbox', function() {
    StepC.finish($(this));
  });


  // DESTROY
  $(document).on('click', '.stepDestroy', function() {
    StepC.destroy($(this));
  });

  // EDITABLE STEPS
  $(document).on('mouseenter', '.stepInput', function() {
    ($(this) as any).editable(function(value) {
      $.post('app/controllers/AjaxController.php', {
        type: $(this).data('type'),
        updateStep: true,
        body: value,
        id: $(this).data('id'),
        stepid: $(this).data('stepid'),
      });

      return(value);
    }, {
      tooltip : 'Click to edit',
      indicator : 'Saving...',
      onblur: 'submit',
      style : 'display:inline'
    });
  });

  // END STEPS
  ////////////

  ////////
  // LINKS
  const LinkC = new Link(type);

  // CREATE
  // listen keypress, add link when it's enter or on blur
  $(document).on('keypress blur', '.linkinput', function(e) {
    // Enter is ascii code 13
    if (e.which === 13 || e.type === 'focusout') {
      LinkC.create($(this));
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

  // END LINKS
  ////////////
});
