/**
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import $ from 'jquery';
import 'jquery-ui/ui/widgets/autocomplete';
import { notif, relativeMoment } from './misc';

$(document).ready(function() {
  $.ajaxSetup({
    headers: {
      'X-CSRF-Token': $('meta[name="csrf-token"]').attr('content')
    }
  });
  const type = $('#info').data('type');
  const confirmStep = $('#info').data('confirmstep');
  const confirmLink = $('#info').data('confirmlink');

  class Link {

    create(elem): void {
      const id = elem.data('id');
      // get link
      const link = elem.val();
      // fix for user pressing enter with no input
      if (link.length > 0) {
        // parseint will get the id, and not the rest (in case there is number in title)
        const linkId = parseInt(link, 10);
        if (!isNaN(linkId)) {
          $.post('app/controllers/EntityAjaxController.php', {
            createLink: true,
            id: id,
            linkId: linkId,
            type: type
          }).done(function () {
            // reload the link list
            $('#links_div_' + id).load('?mode=edit&id=' + id + ' #links_div_' + id);
            // clear input field
            elem.val('');
          });
        } // end if input is bad
      } // end if input < 0
    }

    destroy(elem): void {
      const id = elem.data('id');
      const linkId = elem.data('linkid');
      if (confirm(confirmLink)) {
        $.post('app/controllers/EntityAjaxController.php', {
          destroyLink: true,
          id: id,
          linkId: linkId,
          type: type
        }).done(function(json) {
          notif(json);
          if (json.res) {
            $('#links_div_' + id).load('?mode=edit&id=' + id + ' #links_div_' + id);
          }
        });
      }
    }
  }

  class Step {

    create(elem): void {
      const id = elem.data('id');
      // get body
      const body = elem.val();
      // fix for user pressing enter with no input
      if (body.length > 0) {
        $.post('app/controllers/EntityAjaxController.php', {
          createStep: true,
          id: id,
          body: body,
          type: type
        }).done(function() {
          let loadUrl = '?mode=edit&id=' + id + ' #steps_div_' + id;
          if (type === 'experiments_templates') {
            loadUrl = '? #steps_div_' + id;
          }
          // reload the step list
          $('#steps_div_' + id).load(loadUrl, function() {
            relativeMoment();
          });
          // clear input field
          elem.val('');
        });
      } // end if input < 0
    }

    finish(elem): void {
      // the id of the exp/item/tpl
      const id = elem.data('id');
      const stepId = elem.data('stepid');

      $.post('app/controllers/EntityAjaxController.php', {
        finishStep: true,
        id: id,
        stepId: stepId,
        type: type
      }).done(function() {
        // reload the step list
        $('#steps_div_' + id).load('?mode=edit&id=' + id + ' #steps_div_' + id, function() {
          relativeMoment();
        });
        // clear input field
        elem.val('');
      });
    }

    destroy(elem): void {
      // the id of the exp/item/tpl
      const id = elem.data('id');
      const stepId = elem.data('stepid');
      if (confirm(confirmStep)) {
        $.post('app/controllers/EntityAjaxController.php', {
          destroyStep: true,
          id: id,
          stepId: stepId,
          type: type
        }).done(function(json) {
          notif(json);
          if (json.res) {
            let loadUrl = '?mode=edit&id=' + id + ' #steps_div_' + id;
            if (type === 'experiments_templates') {
              loadUrl = '? #steps_div_' + id;
            }
            // reload the step list
            $('#steps_div_' + id).load(loadUrl, function() {
              relativeMoment();
            });
          }
        });
      }
    }
  }

  ////////
  // STEPS
  const StepC = new Step();

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

  // END STEPS
  ////////////

  ////////
  // LINKS
  const LinkC = new Link();

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
  $('.list-group-item').on('click', '.linkDestroy', function() {
    LinkC.destroy($(this));
  });

  // END LINKS
  ////////////
});
