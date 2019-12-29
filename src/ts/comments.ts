/**
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import { notif } from './misc';

const Comments = {
  controller: 'app/controllers/CommentsAjaxController.php',
  create: function(): void {
    (document.getElementById('commentsCreateButton') as HTMLButtonElement).disabled = true;
    const comment = $('#commentsCreateArea').val();
    const type = $('#info').data('type');
    const id = $('#info').data('id');

    $.post(this.controller, {
      create: true,
      comment: comment,
      type: type,
      id: id,
    }).done(function(json) {
      notif(json);
      if (json.res) {
        $('#comment_container').load('?mode=view&id=' + id + ' #comment');
      } else {
        (document.getElementById('commentsCreateButton') as HTMLButtonElement).disabled = false;
      }
    });
  },
  destroy: function(commentId: string): void {
    const id = $('#info').data('id');
    const confirmText = $('#info').data('confirm');
    if (confirm(confirmText)) {
      $.post(this.controller, {
        destroy: true,
        type: $('#info').data('type'),
        id: commentId
      }).done(function(json) {
        notif(json);
        if (json.res) {
          $('#comment_container').load('?mode=view&id=' + id + ' #comment');
        }
      });
    }
  }
};

// CREATE COMMENTS
$(document).on('click', '#commentsCreateButton', function() {
  Comments.create();
});

$(document).on('mouseenter', '.comment', function() {
  ($(this) as any).editable('app/controllers/CommentsAjaxController.php', {
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
    submitcssclass : 'button btn btn-primary mt-2',
    cancelcssclass : 'button btn btn-danger mt-2',
    callback : function(data: string) {
      const json = JSON.parse(data);
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

