/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import Crud from './Crud.class';
import i18next from 'i18next';

export default class Comment extends Crud {
  type: string;

  constructor(type: string) {
    super('app/controllers/CommentsAjaxController.php');
    this.type = type;
  }

  create(): void {
    this.send({
      action: 'create',
      id: $('#info').data('id') as number,
      content: $('#commentsCreateArea').val() as string,
      type: this.type,
    });
    $('#comment_container').load(window.location.href + ' #comment');
  }

  destroy(commentId: number): void {
    if (confirm(i18next.t('generic-delete-warning'))) {
      this.send({
        action: 'destroy',
        id: commentId,
        type: this.type,
      });
      $('#comment_container').load(window.location.href + ' #comment');
    }
  }
}
