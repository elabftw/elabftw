/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import Comment from './Comment.class';
import { notif } from './misc';

$(document).ready(function() {
  const type = $('#info').data('type');
  const CommentC = new Comment(type);

  // CREATE COMMENTS
  $(document).on('click', '#commentsCreateButton', function() {
    CommentC.create();
  });

  // MAKE them editable on mousehover
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
    CommentC.destroy($(this).data('id'));
  });
});
