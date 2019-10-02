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
    let confirmStep = $('#info').data('confirmstep');
    let confirmLink = $('#info').data('confirmlink');

    class Link {

      create(elem) {
        let id = elem.data('id');
        // get link
        let link = elem.val();
        // fix for user pressing enter with no input
        if (link.length > 0) {
          // parseint will get the id, and not the rest (in case there is number in title)
          let linkId = parseInt(link, 10);
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

      // add the body of the linked item at cursor position in editor
      importBody(elem) {
        const id = elem.data('linkid');
        const editor = $('#iHazEditor').data('editor');
        $.get('app/controllers/EntityAjaxController.php', {
          getBody : true,
          id : id,
          type : 'items',
          editor: editor
        }).done(function(json) {
          if (editor === 'tiny') {
            tinymce.activeEditor.insertContent(json.msg);

          } else if (editor === 'md') {
            const cursorPosition = $('#body_area').prop('selectionStart');
            const content = $('#body_area').val();
            const before = content.substring(0, cursorPosition);
            const after = content.substring(cursorPosition);
            $('#body_area').val(before + json.msg + after);

          } else {
            alert('Error: could not find current editor!');
          }
        });
      }

      destroy(elem) {
        let id = elem.data('id');
        let linkId = elem.data('linkid');
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

      create(elem) {
        let id = elem.data('id');
        // get body
        let body = elem.val();
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

      finish(elem) {
        // the id of the exp/item/tpl
        let id = elem.data('id');
        let stepId = elem.data('stepid');

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

      destroy(elem) {
        // the id of the exp/item/tpl
        let id = elem.data('id');
        let stepId = elem.data('stepid');
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
    let cache = {};
    $( '.linkinput' ).autocomplete({
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

    // IMPORT
    $(document).on('click', '.linkImport', function() {
      LinkC.importBody($(this));
    });

    // DESTROY
    $(document).on('click', '.linkDestroy', function() {
      LinkC.destroy($(this));
    });

    // END LINKS
    ////////////
  });
}());
