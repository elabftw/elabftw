/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import { notif } from './misc';
import i18next from 'i18next';

export default class Comment {
  controller: string;
  type: string;

  constructor(type: string) {
    this.type = type;
    this.controller = 'app/controllers/CommentsAjaxController.php';
  }

  create(): void {
    (document.getElementById('commentsCreateButton') as HTMLButtonElement).disabled = true;
    const comment = $('#commentsCreateArea').val();
    const id = $('#info').data('id');

    $.post(this.controller, {
      create: true,
      comment: comment,
      type: this.type,
      id: id,
    }).done(function(json) {
      notif(json);
      $('#comment_container').load(window.location.href + ' #comment');
      (document.getElementById('commentsCreateButton') as HTMLButtonElement).disabled = false;
    });
  }

  destroy(commentId: string): void {
    const id = $('#info').data('id');
    if (confirm(i18next.t('generic-delete-warning'))) {
      $.post(this.controller, {
        destroy: true,
        type: this.type,
        id: commentId
      }).done(function(json) {
        notif(json);
        if (json.res) {
          $('#comment_container').load(window.location.href + ' #comment');
        }
      });
    }
  }
}
