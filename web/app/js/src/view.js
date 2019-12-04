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
    // add the title in the page name (see #324)
    document.title = $('.title_view').text() + ' - eLabFTW';

    var type = $('#info').data('type');
    var id = $('#info').data('id');
    var confirmText = $('#info').data('confirm');

    // EDIT
    key($('#shortcuts').data('edit'), function() {
      window.location.href = '?mode=edit&id=' + id;
    });

    // TOGGLE LOCK
    $('#lock').on('click', function() {
      $.post('app/controllers/EntityAjaxController.php', {
        lock: true,
        type: type,
        id: id
      }).done(function(json) {
        notif(json);
        if (json.res) {
          // change the lock icon
          $('#lock').toggleClass('fa-lock-open').toggleClass('fa-lock');
        }
      });
    });

    // CLICK TITLE TO GO IN EDIT MODE
    $(document).on('click', '.click2Edit', function() {
      window.location.href = '?mode=edit&id=' + id;
    });

    // DECODE ASN1
    $(document).on('click', '.decodeAsn1', function() {
      $.post('app/controllers/ExperimentsAjaxController.php', {
        asn1: $(this).data('token'),
        id: $(this).data('id')
      }).done(function(data) {
        $('#decodedDiv').html(data.msg);
      });
    });

    // DUPLICATE
    $('.duplicateItem').on('click', function() {
      $.post('app/controllers/EntityAjaxController.php', {
        duplicate: true,
        id: $(this).data('id'),
        type: type
      }).done(function(data) {
        window.location.replace('?mode=edit&id=' + data.msg);
      });
    });

    // SHARE
    $('.shareItem').on('click', function() {
      $.post('app/controllers/EntityAjaxController.php', {
        getShareLink: true,
        id: $(this).data('id'),
        type: type
      }).done(function(data) {
        $('#shareLinkInput').val(data.msg).toggle().focus().select();
      });
    });


    // COMMENTS
    var Comments = {
      controller: 'app/controllers/CommentsAjaxController.php',
      create: function() {
        document.getElementById('commentsCreateButton').disabled = true;
        const comment = $('#commentsCreateArea').val();

        $.post(this.controller, {
          create: true,
          comment: comment,
          type: type,
          id: id
        }).done(function(json) {
          notif(json);
          if (json.res) {
            $('#comment_container').load('?mode=view&id=' + id + ' #comment', function() {
              relativeMoment();
            });
          } else {
            document.getElementById('commentsCreateButton').disabled = false;
          }
        });
      },
      destroy: function(comment) {
        if (confirm(confirmText)) {
          $.post(this.controller, {
            destroy: true,
            type: $('#info').data('type'),
            id: comment
          }).done(function(json) {
            notif(json);
            if (json.res) {
              $('#comment_container').load('?mode=view&id=' + id + ' #comment', function() {
                relativeMoment();
              });
            }
          });
        } else {
          return false;
        }
      }
    };

    // CREATE COMMENTS
    $(document).on('focus', '#commentsCreateArea', function() {
      $('#commentsCreateButtonDiv').show();
    });

    $(document).on('click', '#commentsCreateButton', function() {
      Comments.create($(this).data('expid'));
    });

    $(document).on('mouseenter', '.comment', function() {
      $(this).editable('app/controllers/CommentsAjaxController.php', {
        name: 'update',
        type : 'textarea',
        submitdata: {
          type: $(this).data('type')
        },
        width: '80%',
        height: '200',
        tooltip : 'Click to edit',
        indicator : $(this).data('indicator'),
        submit : $(this).data('submit'),
        cancel : $(this).data('cancel'),
        style : 'display:inline',
        submitcssclass : 'button mt-2',
        cancelcssclass : 'button button-delete mt-2',
        callback : function(data) {
          let json = JSON.parse(data);
          notif(json);
          // show result in comment box
          if (json.res) {
            $(this).html(json.update);
          }
        }
      });
    });

    // DESTROY COMMENTS
    $(document).on('click', '.commentsDestroy', function() {
      Comments.destroy($(this).data('id'));
    });

    relativeMoment();

    // TIMESTAMP
    $(document).on('click', '#confirmTimestamp', function() {
      $('#timestampModal').modal('toggle');
      $('#goForTimestamp').on('click', function() {
        $(this).prop('disabled', true);
        $.post('app/controllers/ExperimentsAjaxController.php', {
          timestamp: true,
          id: id
        }).done(function(json) {
          if (json.res) {
            window.location.replace('experiments.php?mode=view&id=' + id);
          } else {
            $('.modal-body').css('color', 'red');
            $('.modal-body').html(json.msg);
          }
        });
      });
    });

    // ACTIVATE FANCYBOX
    // doesn't work if the src js is loaded
    // because only the .min.js has the fancybox lib bundled
    $('[data-fancybox]').fancybox();

  });
}());
