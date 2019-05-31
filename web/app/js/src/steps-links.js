/**
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
(function() {
  'use strict';
  $(document).ready(function() {
    let type = $('#info').data('type');
    // TODO deprecated, use data-id
    let id = $('#info').data('id');
    let confirmText = $('#info').data('confirm');

    class Link {

      create() {
        // get link
        let link = $('#linkinput').val();
        // fix for user pressing enter with no input
        if (link.length > 0) {
          // parseint will get the id, and not the rest (in case there is number in title)
          link = parseInt(link, 10);
          if (!isNaN(link)) {
            $.post('app/controllers/EntityAjaxController.php', {
              createLink: true,
              id: $('#linkinput').data('id'),
              linkId: link,
              type: type
            }).done(function () {
              // reload the link list
              $('#links_div').load('?mode=edit&id=' + id + ' #links_div');
              // clear input field
              $('#linkinput').val('');
            });
          } // end if input is bad
        } // end if input < 0
      }

      destroy(linkId) {
        if (confirm(confirmText)) {
          $.post('app/controllers/EntityAjaxController.php', {
            destroyLink: true,
            id: $('#linkinput').data('id'),
            linkId: linkId,
            type: type
          }).done(function(json) {
            notif(json);
            if (json.res) {
              $('#links_div').load('?mode=edit&id=' + id + ' #links_div');
            }
          });
        }
      }
    }

    class Step {

      create() {
        // get body
        let body = $('#stepinput').val();
        // fix for user pressing enter with no input
        if (body.length > 0) {
          $.post('app/controllers/EntityAjaxController.php', {
            createStep: true,
            id: $('#stepinput').data('id'),
            body: body,
            type: type
          }).done(function() {
            // reload the step list
            $('#steps_div').load('?mode=edit&id=' + id + ' #steps_div', function() {
              relativeMoment();
            });
            // clear input field
            $('#stepinput').val('');
          });
        } // end if input < 0
      }

      finish(stepId) {
        $.post('app/controllers/EntityAjaxController.php', {
          finishStep: true,
          id: $('#stepinput').data('id'),
          stepId: stepId,
          type: type
        }).done(function() {
          // reload the step list
          $('#steps_div').load('?mode=edit&id=' + id + ' #steps_div', function() {
            relativeMoment();
          });
          // clear input field
          $('#stepinput').val('');
        });
      }

      destroy(stepId) {
        if (confirm(confirmText)) {
          $.post('app/controllers/EntityAjaxController.php', {
            destroyStep: true,
            id: $('#stepinput').data('id'),
            stepId: stepId,
            type: type
          }).done(function(json) {
            notif(json);
            if (json.res) {
              $('#steps_div').load('?mode=edit&id=' + id + ' #steps_div', function() {
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
    $(document).on('keypress blur', '#stepinput', function (e) {
      // Enter is ascii code 13
      if (e.which === 13 || e.type === 'focusout') {
        StepC.create();
      }
    });

    // STEP IS DONE
    $(document).on('click', 'input[type=checkbox]', function() {
      StepC.finish($(this).data('stepid'));
    });


    // DESTROY
    $(document).on('click', '.stepDestroy', function() {
      StepC.destroy($(this).data('stepid'));
    });

    // END STEPS
    ////////////

    ////////
    // LINKS
    const LinkC = new Link();

    // CREATE
    // listen keypress, add link when it's enter or on blur
    $(document).on('keypress blur', '#linkinput', function (e) {
      // Enter is ascii code 13
      if (e.which === 13 || e.type === 'focusout') {
        LinkC.create();
      }
    });

    // AUTOCOMPLETE
    let cache = {};
    $( '#linkinput' ).autocomplete({
      source: function(request, response) {
        let term = request.term;
        if (term in cache) {
          response(cache[term]);
          return;
        }
        $.getJSON('app/controllers/EntityAjaxController.php', request, function(data) {
          cache[term] = data;
          response(data);
        });
      }
    });

    // DESTROY
    $(document).on('click', '.linkDestroy', function() {
      LinkC.destroy($(this).data('linkid'));
    });

    // END LINKS
    ////////////
  });
}());
